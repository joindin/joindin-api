<?php

namespace Joindin\Api\Model;

use DateTime;
use DateTimeZone;
use Joindin\Api\Request;

abstract class BaseModel
{
    /**
     * @var array
     */
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Retrieve a single element from the model or null if it doesn't exist
     *
     * @param  string $field
     *
     * @return mixed
     */
    public function __get($field)
    {
        if (isset($this->data[$field])) {
            return $this->data[$field];
        }

        return null;
    }

    /**
     * Default fields in the output view
     *
     * format: [public facing name => database column]
     *
     * @return array
     */
    abstract protected function getDefaultFields();

    /**
     * Verbose fields in the output view
     *
     * format: [public facing name => database column]
     *
     * @return array
     */
    abstract protected function getVerboseFields();

    /**
     * List of subresource keys that may be in the data set from the mapper
     * but are not database columns that need to be in the output view
     *
     * format: [public facing name => field in $this->data]
     *
     * @return array
     */
    public function getSubResources()
    {
        return [];
    }

    /**
     * Return this object with client-facing fields and hypermedia, ready for output
     *
     * @param Request $request
     * @param bool    $verbose
     *
     * @return array
     */
    public function getOutputView(Request $request, $verbose = false)
    {
        $item = [];

        if ($verbose) {
            $fields = $this->getVerboseFields();
        } else {
            $fields = $this->getDefaultFields();
        }

        $fields = array_merge($fields, $this->getSubResources());

        // special handling for dates
        $tz = new DateTimeZone('UTC');
        if (property_exists($this, 'event_tz_place')
            && property_exists($this, 'event_tz_cont')
            && $this->event_tz_place !== ''
            && $this->event_tz_cont !== ''
        ) {
            $tz = new DateTimeZone($this->event_tz_cont . '/' . $this->event_tz_place);
        }

        foreach ($fields as $output_name => $name) {
            $value = $this->$name;

            // override if it is a date
            if (substr($output_name, -5) == '_date' && ! empty($value)) {
                if (is_numeric($value)) {
                    $value = '@' . $value;
                }
                $value = (new DateTime($value))->setTimezone($tz)->format('c');
            }

            $item[$output_name] = $value;
        }

        return $item;
    }
}
