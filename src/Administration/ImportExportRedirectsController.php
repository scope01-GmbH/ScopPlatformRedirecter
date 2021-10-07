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
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
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
     * @Route("/api/_action/scop/platform/redirecter/prepare-export", name="api.action.scop.platform.redirecter.prepare-export", methods={"POST"})
     * @throws \Exception
     */
    public function prepareExport(Context $context): Response
    {
        $filename = 'redirects_' . time() . '.csv';
        $path = $this->container->getParameter('kernel.project_dir') . '/files/redirects/';
        $file = $path . $filename;

        if (!file_exists($path)) {
            if (!mkdir($path, 0777, true)) {
                throw new \Exception('Could not create folder: ' . $path);
            }
        } else {
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

        $answer = array();
        $answer['file'] = $filename;

        $fileStream = fopen($file, 'w');
        if (!is_resource($fileStream)) {
            $answer['detail'] = 'File could not be created!';
            return new Response(json_encode($answer), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $redirects = $this->redirectRepository->search(new Criteria(), $context);
        /**
         * @var Redirect $redirect
         */

        fputcsv($fileStream, array('id', 'source_url', "target_url", "http_code"), ';');
        foreach ($redirects as $redirect) {
            fputcsv($fileStream, array($redirect->getId(), $redirect->getSourceURL(), $redirect->getTargetURL(), $redirect->getHttpCode()), ';');
        }

        fclose($fileStream);

        $answer['detail'] = 'File created!';
        return new Response(json_encode($answer), Response::HTTP_OK);
    }

    /**
     * @Route("/api/_action/scop/platform/redirecter/download-export", name="api.action.scop.platform.redirecter.download-export", defaults={"auth_required"=false}, methods={"GET"})
     */
    public function download(Request $request, Context $context)
    {
        $params = $request->query->all();

        $filename = $params['filename'];
        $path = '../files/redirects/';
        if (!file_exists($path . $filename)) {
            $response = array();
            $response['error'] = "File not found";
            return new Response(json_encode($response), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $headers = [
            'Content-Disposition' => HeaderUtils::makeDisposition(
                'attachment',
                $filename,
                // only printable ascii
                preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $filename)
            ),
            //'Content-Length' => filesize($path . $filename),
            'Content-Type' => 'text/csv',
            'Content-Transfer-Encoding' => 'binary'
        ];
        $stream = fopen($path . $filename, 'r');
        if (!\is_resource($stream)) {
            $response = array();
            $response['detail'] = "File not found";
            return new Response(json_encode($response), Response::HTTP_BAD_REQUEST);
        }

        return new StreamedResponse(function () use ($stream): void {
            fpassthru($stream);
        }, Response::HTTP_OK, $headers);
    }
}

