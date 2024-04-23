<?php

namespace Joindin\Api\Controller;

use Exception;
use Joindin\Api\Request;
use Teapot\StatusCode\Http;

abstract class BaseApiController
{
    public function __construct(protected array $config = [])
    {
    }

    public function getItemId(Request $request, ?string $errorMessage = null): int
    {
        // item ID
        if (
            !empty($request->url_elements[3])
             && is_numeric($request->url_elements[3])
        ) {
            return (int) $request->url_elements[3];
        }

        throw new Exception($errorMessage ?? 'Item not found', Http::NOT_FOUND);
    }

    public function getVerbosity(Request $request): bool
    {
        if (!isset($request->parameters['verbose'])) {
            return false;
        }

        if ($request->parameters['verbose'] !== 'yes') {
            return false;
        }

        return true;
    }

    public function getStart(Request $request): int
    {
        return $request->paginationParameters['start'] ?? 0;
    }

    public function getResultsPerPage(Request $request): int
    {
        return (int) $request->paginationParameters['resultsperpage'];
    }

    protected function getRequestParameter(Request $request, string $parameter, mixed $default = false): mixed
    {
        if (isset($request->parameters[$parameter])) {
            return $request->parameters[$parameter];
        }

        return $default;
    }
}
