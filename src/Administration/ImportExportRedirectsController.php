<?php
/**
 * Implemented by scope01 GmbH team https://scope01.com
 *
 * @copyright scope01 GmbH https://scope01.com
 * @license MIT License
 * @link https://scope01.com
 */
declare(strict_types = 1);
/**
 * Implemented by scope01 GmbH team https://scope01.com
 *
 * @copyright scope01 GmbH https://scope01.com
 * @license MIT
 * @link https://scope01.com
 */

namespace Scop\PlatformRedirecter\Administration;

/**
 * @RouteScope(scopes={"api"})
 */
class ImportExportRedirectsController extends AbstractController
{
    /**
     * @Route("/api/_action/scop/platform/redirecter/prepare-export", name="api.action.scop.platform.redirecter.prepare-export", methods={"GET"})
     *
     */
    public function prepareExport(Request $request, Context $context): Response
    {
        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}

