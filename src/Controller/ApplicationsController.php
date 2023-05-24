<?php

namespace Joindin\Api\Controller;

use Exception;
use Joindin\Api\Model\ClientMapper;
use PDO;
use Joindin\Api\Request;
use Teapot\StatusCode\Http;

class ApplicationsController extends BaseApiController
{
    public function getApplication(Request $request, PDO $db): array
    {
        if (!isset($request->user_id)) {
            throw new Exception("You must be logged in", Http::UNAUTHORIZED);
        }

        $mapper = $this->getClientMapper($db, $request);

        $client = $mapper->getClientByIdAndUser(
            $this->getItemId($request),
            $request->user_id
        );

        return $client->getOutputView($request, $this->getVerbosity($request));
    }

    public function listApplications(Request $request, PDO $db): array
    {
        if (!isset($request->user_id)) {
            throw new Exception("You must be logged in", Http::UNAUTHORIZED);
        }

        $mapper = $this->getClientMapper($db, $request);

        $clients = $mapper->getClientsForUser(
            $request->user_id,
            $this->getResultsPerPage($request),
            $this->getStart($request)
        );

        return $clients->getOutputView($request, $this->getVerbosity($request));
    }

    public function createApplication(Request $request, PDO $db): array
    {
        if (!isset($request->user_id)) {
            throw new Exception("You must be logged in", Http::UNAUTHORIZED);
        }

        $app    = [];
        $errors = [];

        $app['name'] = filter_var(
            $request->getParameter("name"),
            FILTER_SANITIZE_STRING,
            FILTER_FLAG_NO_ENCODE_QUOTES
        );

        if (empty($app['name'])) {
            $errors[] = "'name' is a required field";
        }

        $app['description'] = filter_var(
            $request->getParameter("description"),
            FILTER_SANITIZE_STRING,
            FILTER_FLAG_NO_ENCODE_QUOTES
        );

        if (empty($app['description'])) {
            $errors[] = "'description' is a required field";
        }

        $app['callback_url'] = filter_var(
            $request->getParameter("callback_url"),
            FILTER_SANITIZE_URL
        );

        if (empty($app['callback_url'])) {
            $errors[] = "'callback_url' is a required field";
        }

        if ($errors) {
            throw new Exception(implode(". ", $errors), Http::BAD_REQUEST);
        }

        $app['user_id'] = $request->user_id;

        $clientMapper = $this->getClientMapper($db, $request);
        $clientId     = $clientMapper->createClient($app);

        $uri = $request->base . '/' . $request->version . '/applications/' . $clientId;
        $request->getView()->setResponseCode(Http::CREATED);
        $request->getView()->setHeader('Location', $uri);

        $mapper    = $this->getClientMapper($db, $request);
        $newClient = $mapper->getClientByIdAndUser($clientId, $request->user_id);

        return $newClient->getOutputView($request);
    }

    public function editApplication(Request $request, PDO $db): array
    {
        if (!isset($request->user_id)) {
            throw new Exception("You must be logged in", Http::UNAUTHORIZED);
        }

        $app    = [];
        $errors = [];

        $app['name'] = filter_var(
            $request->getParameter("name"),
            FILTER_SANITIZE_STRING,
            FILTER_FLAG_NO_ENCODE_QUOTES
        );

        if (empty($app['name'])) {
            $errors[] = "'name' is a required field";
        }

        $app['description'] = filter_var(
            $request->getParameter("description"),
            FILTER_SANITIZE_STRING,
            FILTER_FLAG_NO_ENCODE_QUOTES
        );

        if (empty($app['description'])) {
            $errors[] = "'description' is a required field";
        }

        $app['callback_url'] = filter_var(
            $request->getParameter("callback_url"),
            FILTER_SANITIZE_URL
        );

        if ($errors) {
            throw new Exception(implode(". ", $errors), Http::BAD_REQUEST);
        }

        $app['user_id'] = $request->user_id;

        $clientMapper = $this->getClientMapper($db, $request);
        $clientId     = $clientMapper->updateClient($this->getItemId($request), $app);

        $uri = $request->base . '/' . $request->version . '/applications/' . $clientId;
        $request->getView()->setResponseCode(Http::CREATED);
        $request->getView()->setHeader('Location', $uri);

        $newClient = $clientMapper->getClientByIdAndUser($clientId, $request->user_id);

        return $newClient->getOutputView($request);
    }

    public function deleteApplication(Request $request, PDO $db): void
    {
        if (!isset($request->user_id)) {
            throw new Exception("You must be logged in", Http::UNAUTHORIZED);
        }

        $clientMapper = $this->getClientMapper($db, $request);

        $client = $clientMapper->getClientByIdAndUser(
            $this->getItemId($request),
            $request->user_id
        );

        if (!$client->getClients()) {
            throw new Exception('No application found', Http::NOT_FOUND);
        }

        try {
            $clientMapper->deleteClient($this->getItemId($request));
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), Http::INTERNAL_SERVER_ERROR, $e);
        }

        $request->getView()->setNoRender(true);
        $request->getView()->setResponseCode(Http::NO_CONTENT);
        $request->getView()->setHeader(
            'Location',
            $request->base . '/' . $request->version . '/applications'
        );
    }

    /**
     * @param PDO     $db
     * @param Request $request
     *
     * @return ClientMapper
     */
    private function getClientMapper(PDO $db, Request $request): ClientMapper
    {
        return new ClientMapper($db, $request);
    }
}
