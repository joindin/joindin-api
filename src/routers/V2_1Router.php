<?php

/**
 * A Router to route v2.1 Routes
 */
class V2_1Router extends Router
{
    /**
     * {@inheritdoc}
     */
    public function route(Request $request, $db)
    {
        if (isset($request->url_elements[2])) {
            $class = ucfirst($request->url_elements[2]) . 'Controller';
            if (class_exists($class)) {
                $handler = new $class($this->config);
                return $handler->handle($request, $db); // the DB is set by the database config
            }
            throw new Exception('Unknown controller ' . $request->url_elements[2], 400);
        }
        
        throw new Exception('Request not understood', 404);
    }

}