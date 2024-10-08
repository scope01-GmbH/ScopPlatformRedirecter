<?php /** @noinspection JsonEncodingApiUsageInspection */

/** @noinspection UnknownInspectionInspection */
declare(strict_types=1);
/**
 * Implemented by scope01 GmbH team https://scope01.com
 *
 * @copyright scope01 GmbH https://scope01.com
 * @license MIT
 * @link https://scope01.com
 */

namespace Scop\PlatformRedirecter\Administration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Scop\PlatformRedirecter\Redirect\Redirect;
use Scop\PlatformRedirecter\ScopPlatformRedirecter;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;

use function is_resource;

class ImportExportRedirectsController extends AbstractController
{
    private EntityRepository $redirectRepository;

    private string $separator;

    /** @var ?resource  */
    private $failedCsv;

    private Assert\Collection $constraints;

    private Connection $connection;

    private array $salesChannelsIds = [];


    public function __construct(
        EntityRepository $redirectRepository,
        SystemConfigService $configService,
        Connection $connection
    ) {
        $this->redirectRepository = $redirectRepository;
        $this->separator = $configService->getString('ScopPlatformRedirecter.config.csvSeparator');
        if (empty($this->separator)) {
            $this->separator = ';';
        }
        $this->constraints = new Assert\Collection([
            'fields'             => [
                'id'         => [
                    new Assert\NotBlank(),
                    new Assert\Callback([$this, 'validateId']),
                ],
                'source_url' => [
                    new Assert\Type('string'),
                    new Assert\NotBlank(),
                ],
                'target_url' => [
                    new Assert\Type('string'),
                    new Assert\NotBlank(),
                ],
                'http_code' => [
                    new Assert\NotBlank(),
                    new Assert\Choice(['301', '302']),
                ],
                'enabled' => [
                    new Assert\NotBlank(),
                    new Assert\Choice(['0', '1']),
                ],
                'query_params_handling' => [
                    new Assert\Choice(['0', '1']),
                ],
                'sales_channel_id' => [
                    new Assert\Callback([$this, 'validateId']),
                    new Assert\Callback([$this, 'validateSaleChannelId']),
                ]
            ],
            'allowMissingFields' => true,
            'allowExtraFields'   => true,
        ]);
        $this->connection = $connection;
    }

    /**
     * Removes old export files, that are older than 60 Seconds.
     * Then exports all redirects into a new file.
     *
     * @throws \Exception
     */
    #[Route(
        path: '/api/_action/scop/platform/redirecter/prepare-export',
        name: 'api.action.scop.platform.redirecter.prepare-export',
        defaults: ['_routeScope' => ['api']],
        methods: ['POST']
    )]
    public function prepareExport(Context $context): Response
    {
        $filename = 'redirects_' . time() . '.csv';
        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $path = $this->container->getParameter('kernel.project_dir') . '/files/redirects/';
        $file = $path . $filename;

        if (!file_exists($path)) { //Folder didn't exist, trying to create a new one
            if (!mkdir($path, 0777, true) && !is_dir($path)) {
                /** @noinspection ThrowRawExceptionInspection */
                throw new \Exception('Could not create folder: ' . $path);
            }
        } else { //Folder exist, checking all files in it and deleting those older than 60 Seconds
            foreach (scandir($path) as $folderFile) {
                if (str_starts_with($folderFile, 'redirects_') && str_ends_with($folderFile, '.csv')) {
                    $time = str_replace(['redirects_', '.csv'], '', $folderFile);
                    if (($time + 60 < time()) && !unlink($path . $folderFile)) {
                        /** @noinspection ThrowRawExceptionInspection */
                        throw new \Exception('Could not delete old file: ' . $folderFile);
                    }
                }
            }
        }

        //Preparing answer, filling it with the filename
        $answer = array();
        $answer['file'] = $filename;

        //Trying to open the file
        $fileStream = fopen($file, 'wb');
        if (!is_resource($fileStream)) {
            $answer['detail'] = 'File could not be created!';
            return new Response(json_encode($answer), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        //Loading all Redirects from the Database
        $redirects = $this->redirectRepository->search(new Criteria(), $context);

        /**
         * @var Redirect $redirect
         */
        fputcsv($fileStream, array('id', 'source_url', "target_url", "http_code", "enabled", "query_params_handling", "sales_channel_id"), $this->separator); // Writing the CSV Headline
        foreach ($redirects as $redirect) { // Writing each Redirect into the file
            fputcsv($fileStream, array($redirect->getId(), $redirect->getSourceURL(), $redirect->getTargetURL(), $redirect->getHttpCode(), $redirect->isEnabled() ? 1 : 0, $redirect->getQueryParamsHandling(), $redirect->getSalesChannelId() ?? ''), $this->separator);
        }

        //Closing the File
        fclose($fileStream);
        //Sending back a success Message inclusive filename
        $answer['detail'] = 'File created!';
        return new Response(json_encode($answer), Response::HTTP_OK);
    }

    /**
     * Downloads an exported file. The Filename must be in the $request.
     */
    #[Route(
        path: '/api/_action/scop/platform/redirecter/download-export',
        name: 'api.action.scop.platform.redirecter.download-export',
        defaults: ['_routeScope' => ['api'], 'auth_required' => false],
        methods: ['GET']
    )]
    public function download(Request $request): Response
    {
        $params = $request->query->all();

        $filename = $params['filename'];
        $path = '../files/redirects/';
        if (!file_exists($path . $filename)) { //Checking if File exists, otherwise return an Error
            $response = array();
            $response['error'] = "File not found";
            return new Response(json_encode($response), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        //Preparing the Headers for the Response
        $headers = [
            'Content-Disposition' => HeaderUtils::makeDisposition(
                'attachment',
                $filename,
                // only printable ascii
                preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $filename)
            ),
            'Content-Type' => 'text/csv',
            'Content-Transfer-Encoding' => 'binary'
        ];

        //Trying to open the file
        $stream = fopen($path . $filename, 'rb');
        if (!\is_resource($stream)) {
            $response = array();
            $response['detail'] = "File not found";
            return new Response(json_encode($response), Response::HTTP_BAD_REQUEST);
        }

        //Returning the file as StreamedResponse
        return new StreamedResponse(function () use ($stream): void {
            fpassthru($stream);
        }, Response::HTTP_OK, $headers);
    }

    #[Route(path:
        '/api/_action/scop/platform/redirecter/failed',
        name: 'api.action.scop.platform.redirecter.failed',
        defaults: ['_routeScope' => ['api']],
        methods: ['POST']
    )]
    public function hasFailed(): Response
    {
        $filename = 'failed_import.csv';
        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $path = $this->container->getParameter('kernel.project_dir') . '/files/redirects/';
        $file = $path . $filename;
        return new Response(json_encode(['failedCsv' => file_exists($file) ? $filename : null]), Response::HTTP_OK);
    }


    #[Route(path:
        '/api/_action/scop/platform/redirecter/clearfailed',
        name: 'api.action.scop.platform.redirecter.clearfailed',
        defaults: ['_routeScope' => ['api']],
        methods: ['POST']
    )]
    public function clearFailed(): Response
    {
        $filename = 'failed_import.csv';
        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $path = $this->container->getParameter('kernel.project_dir') . '/files/redirects/';
        $file = $path . $filename;
        unlink($file);
        return new Response(json_encode(['failedCsv' => file_exists($file) ? $filename : null]), Response::HTTP_OK);
    }

    /**
     * Imports an uploaded File.
     *
     * @param Request $request
     * @param Context $context
     * @return Response
     * @noinspection NotOptimalIfConditionsInspection
     * @throws DBALException
     */
    #[Route(path:
        '/api/_action/scop/platform/redirecter/import',
        name: 'api.action.scop.platform.redirecter.import',
        defaults: ['_routeScope' => ['api']],
        methods: ['POST']
    )]
    public function import(Request $request, Context $context): Response
    {
        $this->salesChannelsIds = $this->connection->fetchFirstColumn('SELECT LOWER(HEX(id)) FROM sales_channel');
        $answer = [];

        /**
         * @var UploadedFile $file
         */
        $file = $request->files->get("file");
        $overrideID = ($request->get("overrideID") === "true");
        $override = ($request->get("override") === "true");

        //Checking if it is a csv File
        $guessedExtension = $file->guessClientExtension();
        if ($guessedExtension === 'csv' || $file->getClientOriginalExtension() === 'csv') {
            $type = 'text/csv';
        } else {
            $type = $file->getClientMimeType();
        }

        if($type !== 'text/csv'){
            return new Response(json_encode(['detail' => ScopPlatformRedirecter::ERROR_INVALID_FILE_TYPE]), Response::HTTP_OK);
        }

        $isUtf8 = mb_check_encoding(file_get_contents($file->getPathname()), 'UTF-8');
        if ($isUtf8 !== true) {
            return new Response(json_encode(['detail' => ScopPlatformRedirecter::ERROR_WRONG_ENCODING]), Response::HTTP_OK);
        }

        //Opening the uploaded File
        $fileStream = fopen($file->getPathname(), 'rb');
        if (!$fileStream) {
            return new Response(json_encode(['detail' => ScopPlatformRedirecter::ERROR_FILE_OPEN]), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        //Get CSV headline, then checking if it matches with the original headline
        /** @var array|false|null $headline */
        $headline = fgetcsv($fileStream, 0, $this->separator);
        /** @noinspection PhpConditionAlreadyCheckedInspection */
        if ($headline === null) {
            return new Response(json_encode(['detail' => ScopPlatformRedirecter::ERROR_FILE_READ]), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        if ($headline === false) {
            return new Response(json_encode(['detail' => ScopPlatformRedirecter::ERROR_FILE_READ]), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if (empty($headline)) {
            return new Response(json_encode(['detail' => ScopPlatformRedirecter::ERROR_EMPTY_FILE]), Response::HTTP_OK);
        }

        $validHeadline = false;
        switch (count($headline)) {
            case 5: // Backward compatibility for exports before v1.2.0
                if ($headline[0] === "id" && $headline[1] === "source_url" && $headline[2] === "target_url" && $headline[3] === "http_code" && $headline[4] === "enabled") {
                    $validHeadline = true;
                }
                break;
            case 6: // Backward compatibility for exports before v2.3.0
                if ($headline[0] === "id" && $headline[1] === "source_url" && $headline[2] === "target_url" && $headline[3] === "http_code" && $headline[4] === "enabled" && ($headline[5] === "query_params_handling" || $headline[5] === "ignore_query_params")) { // ignore_query_params: Backward compatibility for exports before v2.1.0
                    $validHeadline = true;
                }
                break;
            case 7:
                if ($headline[0] === "id" && $headline[1] === "source_url" && $headline[2] === "target_url" && $headline[3] === "http_code" && $headline[4] === "enabled" && ($headline[5] === "query_params_handling" || $headline[5] === "ignore_query_params") && $headline[6] === "sales_channel_id") { // ignore_query_params: Backward compatibility for exports before v2.1.0
                    $validHeadline = true;
                }
                break;
            default:
                break;
        }
        if (!$validHeadline) {
            return new Response(json_encode(['detail' => ScopPlatformRedirecter::ERROR_INCORRECT_COLUMNS]), Response::HTTP_OK);
        }

        $count = 0; //Amount of Redirects that where imported successfully
        $skip = 0; //Amount of Redirects that where skipped
        $error = 0; //Amount of Redirects that are invalid
        while ($line = fgetcsv($fileStream, 0, ";")) { //Trying to import each line as a Redirect
            $row = array_combine($headline, $line);
            $errors = $this->validateLine($row);
            if (!empty($errors)) {
                $this->addFailed($headline, $line, $errors);
                $error++;
                continue;
            }
            $id = $row['id'];
            $sourceURL = $row['source_url'];
            $targetURL = $row['target_url'];
            $httpCode = (int)$row['http_code'];

            $enabled = (bool)$row['enabled'];
            $queryParamsHandling = (int)($row['query_params_handling'] ?? 0);
            $salesChannelId = $row['sales_channel_id'] ?? null;
            if($salesChannelId === '') {
                $salesChannelId = null;
            }

            //Search either for the id and the sourceURL in the Database, or if the id is empty, only for the sourceURL
            $criteria = new Criteria();
            if ($id !== "") {
                $criteria->addFilter(new MultiFilter(MultiFilter::CONNECTION_OR, [
                    new EqualsFilter('id', $id),
                    new EqualsFilter('sourceURL', $sourceURL)
                ]));
            } else {
                $criteria->addFilter(
                    new EqualsFilter('sourceURL', $sourceURL)
                );
            }
            $contained = $this->redirectRepository->search($criteria, $context);

            if (count($contained) > 0) { //A Redirect matches at least one requirement, updating or skipping it

                /** @var Redirect $redirect */
                $redirect = $contained->first();

                if ($redirect->getId() === $id && $overrideID) { //The IDs match and should be updated, updating it
                    $writeEvent = $this->redirectRepository->update([[
                        'id' => $id,
                        'sourceURL' => $sourceURL,
                        'targetURL' => $targetURL,
                        'httpCode' => $httpCode,
                        'enabled' => $enabled,
                        'queryParamsHandling' => $queryParamsHandling,
                        'salesChannelId' => $salesChannelId
                    ]], $context);
                    if (count($writeEvent->getErrors()) > 0) {
                        $this->addFailed($headline, $line, $writeEvent->getErrors());
                        $error++;
                        continue;
                    }
                    $count++;
                } elseif (strcasecmp($redirect->getSourceURL(), $sourceURL) === 0 && $override) { //The SourceURLs match and should be updated, updating it
                    $writeEvent = $this->redirectRepository->update([[
                        'id' => $redirect->getId(),
                        'sourceURL' => $sourceURL,
                        'targetURL' => $targetURL,
                        'httpCode' => $httpCode,
                        'enabled' => $enabled,
                        'queryParamsHandling' => $queryParamsHandling,
                        'salesChannelId' => $salesChannelId
                    ]], $context);
                    if (count($writeEvent->getErrors()) > 0) {
                        $this->addFailed($headline, $line, $writeEvent->getErrors());
                        $error++;
                        continue;
                    }
                    $count++;
                } else { //The Redirect should not be overridden, skipping it
                    $skip++;
                }

            } else { //No Redirect matches a requirement, creating a new Redirect...
                if ($id !== "") { //... with the given ID
                    $writeEvent = $this->redirectRepository->create([
                        [
                            'id'                  => $id,
                            'sourceURL'           => $sourceURL,
                            'targetURL'           => $targetURL,
                            'httpCode'            => $httpCode,
                            'enabled'             => $enabled,
                            'queryParamsHandling' => $queryParamsHandling,
                            'salesChannelId'      => $salesChannelId
                        ]
                    ], $context);
                } else { //... with a new ID
                    $writeEvent = $this->redirectRepository->create([
                        [
                            'sourceURL'           => $sourceURL,
                            'targetURL'           => $targetURL,
                            'httpCode'            => $httpCode,
                            'enabled'             => $enabled,
                            'queryParamsHandling' => $queryParamsHandling,
                            'salesChannelId'      => $salesChannelId
                        ]
                    ], $context);
                }
                if (count($writeEvent->getErrors()) > 0) {
                    $this->addFailed($headline, $line, $writeEvent->getErrors());
                    $error++;
                    continue;
                }
                $count++;
            }
        }

        //Returning the amount of imported, skipped and invalid Redirects
        $answer['detail'] = 'File Imported!';
        $answer['amount'] = $count;
        $answer['skipped'] = $skip;
        $answer['error'] = $error;
        if ($error > 0 && is_resource($this->failedCsv)) {
            $meta_data = stream_get_meta_data($this->failedCsv);
            $answer['failedCsv'] = $meta_data["uri"] ?? null;
            fclose($this->failedCsv);
        }
        return new Response(json_encode($answer), Response::HTTP_OK);
    }


    /**
     * @param array $headline
     * @return void
     */
    private function prepareFailed(array $headline): void
    {
        $filename = 'failed_import.csv';
        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $path = $this->container->getParameter('kernel.project_dir') . '/files/redirects/';
        $file = $path . $filename;

        if (!file_exists($path)) { //Folder didn't exist, trying to create a new one
            if (!mkdir($path, 0777, true) && !is_dir($path)) {
                return;
            }
        } else { //Folder exist, checking all files in it and deleting those older than 60 Seconds
            foreach (scandir($path) as $folderFile) {
                if (str_starts_with($folderFile, 'redirects_') && str_ends_with($folderFile, '.csv')) {
                    $time = str_replace(['redirects_', '.csv'], '', $folderFile);
                    if (($time + 60 < time()) && !unlink($path . $folderFile)) {
                        return;
                    }
                }
            }
        }


        $this->failedCsv = fopen($file, 'wb');
        if (!is_resource($this->failedCsv)) {
            return;
        }

        fputcsv($this->failedCsv, $headline, $this->separator); // Writing the CSV Headline
    }

    /**
     * @param array $headline
     * @param array $data
     * @param array $errors
     * @return void
     */
    private function addFailed(array $headline, array $data, array $errors): void
    {
        if (!is_resource($this->failedCsv)) {
            $headline[] = 'error';
            $this->prepareFailed($headline);
        }
        $data[] = implode(" \n", $errors);
        fputcsv($this->failedCsv, $data, $this->separator);
    }

    /**
     * @param array $data
     * @return array
     */
    private function validateLine(array $data): array
    {
        $validator =  Validation::createValidator();
        $violationList = $validator->validate($data, $this->constraints);
        $errors = [];
        foreach ($violationList as $violation) {
            $errors[] = $violation->getMessage() . ' ' . $violation->getPropertyPath() . ' ' . $violation->getInvalidValue();
        }
        return $errors;
    }

    /**
     * @param string $id
     * @param ExecutionContextInterface $context
     * @return void
     */
    public function validateId(string $id, ExecutionContextInterface $context): void
    {
        if (!Uuid::isValid($id)) {
            $context->buildViolation('This is not a valid UUID')
                ->addViolation();
        }
    }

    /**
     * @param string $id
     * @param ExecutionContextInterface $context
     * @return void
     */
    public function validateSaleChannelId(string $id, ExecutionContextInterface $context): void
    {
        if (!in_array(strtolower($id), $this->salesChannelsIds, true)) {
            $context->buildViolation('Sales channel is not exist')
                ->addViolation();
        }
    }
}

