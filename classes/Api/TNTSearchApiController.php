<?php

declare(strict_types=1);

namespace Grav\Plugin\TNTSearch\Api;

use Grav\Plugin\Api\Controllers\AbstractApiController;
use Grav\Plugin\Api\Response\ApiResponse;
use Grav\Plugin\TNTSearchPlugin;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Admin2 endpoints behind the "Search Index Status" field: read the index
 * status, and rebuild the index. Same permissions as the plugin settings.
 */
class TNTSearchApiController extends AbstractApiController
{
    /**
     * GET /tntsearch/status
     */
    public function status(ServerRequestInterface $request): ResponseInterface
    {
        $this->requirePermission($request, 'api.config.read');

        [$status, $message] = TNTSearchPlugin::indexStatus();

        return ApiResponse::create(['indexed' => $status, 'message' => $message]);
    }

    /**
     * POST /tntsearch/reindex
     */
    public function reindex(ServerRequestInterface $request): ResponseInterface
    {
        $this->requirePermission($request, 'api.config.write');

        error_reporting(1);
        set_time_limit(0);

        [$status, $message] = TNTSearchPlugin::indexJob();

        return ApiResponse::create(['indexed' => (bool) $status, 'message' => $message]);
    }
}
