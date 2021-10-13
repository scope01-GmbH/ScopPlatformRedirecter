<?php
/**
 * Implemented by scope01 GmbH team https://scope01.com
 *
 * @copyright scope01 GmbH https://scope01.com
 * @license MIT License
 * @link https://scope01.com
 */
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
use Doctrine\DBAL\Exception;
use Psr\Container\ContainerInterface;
use Scop\PlatformRedirecter\Redirect\Redirect;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use function is_resource;
use function OpenApi\scan;

/**
 * @RouteScope(scopes={"api"})
 */
class ImportExportRedirectsController extends AbstractController
{
    /**
     * @var EntityRepositoryInterface
     */
    private $redirectRepository;


    public function __construct(EntityRepositoryInterface $redirectRepository)
    {
        $this->redirectRepository = $redirectRepository;
    }

    /**
     * Route for older Shopware Versions
     *
     * @Route("/api/v{version}/_action/scop/platform/redirecter/prepare-export", name="api.action.scop.platform.redirecter.prepare-export-old", methods={"POST"})
     * @throws \Exception
     */
    public function prepareExportOLD(Context $context): Response
    {
        return $this->prepareExport($context);
    }

    /**
     * Removes old export files, that are older than 60 Seconds.
     * Then exports all redirects into a new file.
     *
     * @Route("/api/_action/scop/platform/redirecter/prepare-export", name="api.action.scop.platform.redirecter.prepare-export", methods={"POST"})
     * @throws \Exception
     */
    public function prepareExport(Context $context): Response
    {
        $filename = 'redirects_' . time() . '.csv';
        $path = $this->container->getParameter('kernel.project_dir') . '/files/redirects/';
        $file = $path . $filename;

        if (!file_exists($path)) { //Folder didn't exist, trying to create a new one
            if (!mkdir($path, 0777, true)) {
                throw new \Exception('Could not create folder: ' . $path);
            }
        } else { //Folder exist, checking all files in it and deleting those older than 60 Seconds
            foreach (scandir($path) as $folderFile) {
                if (str_starts_with($folderFile, 'redirects_') && str_ends_with($folderFile, '.csv')) {
                    $time = str_replace('.csv', '', str_replace('redirects_', '', $folderFile));
                    if ($time + 60 < time()) {
                        if (!unlink($path . $folderFile)) {
                            throw new \Exception('Could not delete old file: ' . $folderFile);
                        }
                    }
                }
            }
        }

        //Preparing answer, filling it with the filename
        $answer = array();
        $answer['file'] = $filename;

        //Trying to open the file
        $fileStream = fopen($file, 'w');
        if (!is_resource($fileStream)) {
            $answer['detail'] = 'File could not be created!';
            return new Response(json_encode($answer), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        //Loading all Redirects from the Database
        $redirects = $this->redirectRepository->search(new Criteria(), $context);

        /**
         * @var Redirect $redirect
         */
        fputcsv($fileStream, array('id', 'source_url', "target_url", "http_code", "enabled"), ';'); // Writing the CSV Headline
        foreach ($redirects as $redirect) { // Writing each Redirect into the file
            fputcsv($fileStream, array($redirect->getId(), $redirect->getSourceURL(), $redirect->getTargetURL(), $redirect->getHttpCode(), $redirect->isEnabled() ? 1 : 0), ';');
        }

        //Closing the File
        fclose($fileStream);

        //Sending back a success Message inclusive filename
        $answer['detail'] = 'File created!';
        return new Response(json_encode($answer), Response::HTTP_OK);
    }

    /**
     * Route for older Shopware Versions
     *
     * @Route("/api/v{version}/_action/scop/platform/redirecter/download-export", name="api.action.scop.platform.redirecter.download-export-old", defaults={"auth_required"=false}, methods={"GET"})
     */
    public function downloadOLD(Request $request, Context $context): Request
    {
        return $this->download($request, $context);
    }

    /**
     * Downloads an exported file. The Filename must be in the $request.
     *
     * @Route("/api/_action/scop/platform/redirecter/download-export", name="api.action.scop.platform.redirecter.download-export", defaults={"auth_required"=false}, methods={"GET"})
     */
    public function download(Request $request, Context $context)
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
        $stream = fopen($path . $filename, 'r');
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

    /**
     * Route for older Shopware Versions
     *
     * @Route("/api/v{version}/_action/scop/platform/redirecter/import", name="api.action.scop.platform.redirecter.iport-old", methods={"POST"})
     */
    public function importOLD(Request $request, Context $context)
    {
        return $this->import($request, $context);
    }

    /**
     * Imports an uploaded File.
     *
     * @Route("/api/_action/scop/platform/redirecter/import", name="api.action.scop.platform.redirecter.iport", methods={"POST"})
     */
    public function import(Request $request, Context $context)
    {
        $answer = array();

        /**
         * @var UploadedFile $file
         */
        $file = $request->files->get("file");
        $overrideID = ($request->get("overrideID") === "true");
        $override = ($request->get("override") === "true");

        //Opening the uploaded File
        $fileStream = fopen($file->getPathname(), 'r');

        //Get CSV headline, then checking if it matches with the original headline
        $title = fgetcsv($fileStream, 0, ';');
        if ($title === null) {
            $response['detail'] = "File not found";
            return new Response(json_encode($response), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        if ($title === false) {
            $response['detail'] = "Invalid File";
            return new Response(json_encode($response), Response::HTTP_OK);
        }

        if (count($title) != 5) {
            $response['detail'] = "File is not a Redirects Export";
            return new Response(json_encode($response), Response::HTTP_OK);
        }
        if ($title[0] !== "id" || $title[1] !== "source_url" || $title[2] !== "target_url" || $title[3] !== "http_code" || $title[4] !== "enabled") {
            $response['detail'] = "File is not a Redirects Export";
            return new Response(json_encode($response), Response::HTTP_OK);
        }

        $count = 0; //Amount of Redirects that where imported successfully
        $skip = 0; //Amount of Redirects that where skipped
        $error = 0; //Amount of Redirects that are invalid

        while ($line = fgetcsv($fileStream, 0, ";")) { //Trying to import each line as a Redirect
            $id = $line[0];
            $sourceURL = $line[1];
            $targetURL = $line[2];
            $httpCode = intval($line[3]);

            //Checking if this line has invalid data
            if ($sourceURL === "" || ($httpCode != 301 && $httpCode != 302) || ($line[4] !== "1" && $line[4] !== "0")) {
                $error++;
                continue;
            }

            $enabled = boolval($line[4]);

            //Search either for the id and the sourceURL in the Database, or if the id is empty, only for the sourceURL
            $criteria = new Criteria();
            if ($id !== "")
                $criteria->addFilter(new MultiFilter(MultiFilter::CONNECTION_OR, [
                    new EqualsFilter('id', $id),
                    new EqualsFilter('sourceURL', $sourceURL)
                ]));
            else
                $criteria->addFilter(
                    new EqualsFilter('sourceURL', $sourceURL)
                );
            $contained = $this->redirectRepository->search($criteria, $context);

            if (count($contained) > 0) { //A Redirect matches at least one requirement, updating or skipping it

                /**
                 * @var Redirect $redirect
                 */
                $redirect = $contained->first();

                if ($redirect->getId() === $id && $overrideID) { //The IDs match and should be updated, updating it
                    $this->redirectRepository->update([[
                        'id' => $id,
                        'sourceURL' => $sourceURL,
                        'targetURL' => $targetURL,
                        'httpCode' => $httpCode,
                        'enabled' => $enabled
                    ]], $context);
                    $count++;
                } else if (strcasecmp($redirect->getSourceURL(), $sourceURL) == 0 && $override) { //The SourceURLs match and should be updated, updating it
                    $this->redirectRepository->update([[
                        'id' => $redirect->getId(),
                        'sourceURL' => $sourceURL,
                        'targetURL' => $targetURL,
                        'httpCode' => $httpCode,
                        'enabled' => $enabled
                    ]], $context);
                    $count++;
                } else { //The Redirect should not be overridden, skipping it
                    $skip++;
                }

            } else { //No Redirect matches a requirement, creating a new Redirect...
                if ($id !== "") //... with the given ID
                    $this->redirectRepository->create([[
                        'id' => $id,
                        'sourceURL' => $sourceURL,
                        'targetURL' => $targetURL,
                        'httpCode' => $httpCode,
                        'enabled' => $enabled
                    ]], $context);
                else //... with a new ID
                    $this->redirectRepository->create([[
                        'sourceURL' => $sourceURL,
                        'targetURL' => $targetURL,
                        'httpCode' => $httpCode,
                        'enabled' => $enabled
                    ]], $context);
                $count++;
            }

        }

        //Returning the amount of imported, skipped and invalid Redirects
        $answer['detail'] = 'File Imported!';
        $answer['amount'] = $count;
        $answer['skipped'] = $skip;
        $answer['error'] = $error;
        return new Response(json_encode($answer), Response::HTTP_OK);
    }
}

