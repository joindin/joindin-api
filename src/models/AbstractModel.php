<?php

abstract class AbstractModel
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
     * Retrieve a single element from the model or false if it doesn't exist
     *
     * @param  string $field
     * @return mixed
     */
    public function __get($field)
    {
        if (isset($this->data[ $field ])) {
            return $this->data[ $field ];
        }

        return false;
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
     */
    public function getOutputView(Request $request, $verbose = false)
    {
        $item = array();

        if ($verbose) {
            $fields = $this->getVerboseFields();
        } else {
            $fields = $this->getDefaultFields();
        }

        $fields = array_merge($fields, $this->getSubResources());

        // special handling for dates
        if ($this->event_tz_place != '' && $this->event_tz_cont != '') {
            $tz = $this->event_tz_cont . '/' . $this->event_tz_place;
        } else {
            $tz = 'UTC';
        }

        foreach ($fields as $output_name => $name) {
            $value = $this->$name;

            // override if it is a date
            if (substr($output_name, - 5) == '_date' && ! empty($value)) {
                $value = Timezone::formattedEventDatetimeFromUnixtime($value, $tz, 'c');
            }

            $item[$output_name] = $value;
        }

        return $item;
    }
}
