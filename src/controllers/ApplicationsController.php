<?php

class ApplicationsController extends ApiController
{
    public function getApplication($request, $db)
    {
        if (! isset($request->user_id)) {
            throw new Exception("You must be logged in", 401);
        }

        $mapper = $this->getClientMapper($db, $request);

        $client = $mapper->getClientByIdAndUser(
            $this->getItemId($request),
            $request->user_id
        );

        return $client->getOutputView($request, $this->getVerbosity($request));
    }

    public function listApplications($request, $db)
    {
        if (! isset($request->user_id)) {
            throw new Exception("You must be logged in", 401);
        }

        $mapper = $this->getClientMapper($db, $request);

        $clients = $mapper->getClientsForUser(
            $request->user_id,
            $this->getResultsPerPage($request),
            $this->getStart($request)
        );

        return $clients->getOutputView($request, $this->getVerbosity($request));

    }

    public function createApplication($request, $db)
    {
        if (! isset($request->user_id)) {
            throw new Exception("You must be logged in", 401);
        }

        $app    = array();
        $errors = array();

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
            throw new Exception(implode(". ", $errors), 400);
        }

        $app['user_id']         = $request->user_id;

        $clientMapper = $this->getClientMapper($db, $request);
        $clientId = $clientMapper->createClient($app);

        $uri = $request->base . '/' . $request->version . '/applications/' . $clientId;
        $request->getView()->setResponseCode(201);
        $request->getView()->setHeader('Location', $uri);

        $mapper = $this->getClientMapper($db, $request);
        $newClient = $mapper->getClientByIdAndUser($clientId, $request->user_id);

        return $newClient->getOutputView($request);
    }

    public function editApplication($request, $db)
    {
        if (! isset($request->user_id)) {
            throw new Exception("You must be logged in", 401);
        }

        $app    = array();
        $errors = array();

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
            throw new Exception(implode(". ", $errors), 400);
        }

        $app['user_id'] = $request->user_id;

        $clientMapper = $this->getClientMapper($db, $request);
        $clientId = $clientMapper->updateClient($this->getItemId($request), $app);

        $uri = $request->base . '/' . $request->version . '/applications/' . $clientId;
        $request->getView()->setResponseCode(201);
        $request->getView()->setHeader('Location', $uri);

        $newClient = $clientMapper->getClientByIdAndUser($clientId, $request->user_id);

        return $newClient->getOutputView($request);

    }

    public function deleteApplication(Request $request, PDO $db)
    {
        if (! isset($request->user_id)) {
            throw new Exception("You must be logged in", 401);
        }

        $clientMapper = $this->getClientMapper($db, $request);

        $client = $clientMapper->getClientByIdAndUser(
            $this->getItemId($request),
            $request->user_id
        );

        if (! $client->getClients()) {
            throw new Exception('No application found', 404);
        }

        try {
            $clientMapper->deleteClient($this->getItemId($request));
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), 500, $e);
        }

        $request->getView()->setNoRender(true);
        $request->getView()->setResponseCode(204);
        $request->getView()->setHeader(
            'Location',
            $request->base . '/' . $request->version . '/applications'
        );
    }

    /**
     * @param $db
     * @param $request
     *
     * @return ClientMapper
     */
    private function getClientMapper($db, $request)
    {
        return new ClientMapper($db, $request);
    }
}
