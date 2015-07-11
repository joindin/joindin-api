<?php

/**
 * A Router to route versioned Routes
 */
class VersionedRouter extends Router
{
    /**
     * The version this Router represents
     * 
     * @var float 
     */
    protected $version;

    /**
     * An array of rules for this Router to process
     * - Each rule is made of four parts:
     *   - path: a regular expression (minus the regex delimiters) to match
     *   - controller: the controller to route the request to
     *   - action: the method on the controller to route to
     *   - verbs: if specified, the HTTP Verbs allowed for this route
     * 
     * @var array 
     */
    protected $rules;

    /**
     * Constructs a new V2_1Router
     * 
     * @param array $config
     * @param array $rules
     */
    public function __construct($version, array $config, array $rules = array())
    {
        parent::__construct($config);
        $this->version = $version;
        $this->rules = $rules;
    }

    /**
     * {@inheritdoc}
     */
    public function getRoute(Request $request)
    {
        $badMethod = false;
        foreach ($this->rules as $rule) {
            if (preg_match('%^/v' . $this->version . $rule['path'] . '%', $request->getPathInfo(), $matches)) {
                if (isset($rule['verbs']) && !in_array($request->getVerb(), $rule['verbs'])) {
                    $badMethod = true;
                    continue;
                }
                // Determine numeric keys
                $exclude = array_filter(
                            array_keys($matches),
                            function ($val) {
                                return is_integer($val);
                            });
                // Remove numeric keys from matches
                $params = array_diff_key($matches, array_flip($exclude));
                return new Route($rule['controller'], $rule['action'], $params);
            }
        }

        if ($badMethod) {
            throw new Exception('Method not supported', 415);
        }
        throw new Exception('Endpoint not found', 404);
    }
}
