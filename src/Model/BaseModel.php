<?php

namespace Joindin\Api\Model;

use DateTime;
use DateTimeZone;
use Joindin\Api\Request;

/**
 * @property string|null $event_tz_cont For an event or event-related item, the first half of a TZ identifier
 * @property string|null $event_tz_place For an event or event-related item, the second half of a TZ identifier
 */
abstract class BaseModel
{
    public function __construct(protected array $data)
    {
    }

    /**
     * Retrieve a single element from the model or null if it doesn't exist
     *
     * @param string $field
     *
     * @return mixed
     */
    public function __get(string $field): mixed
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
    abstract protected function getDefaultFields(): array;

    /**
     * Verbose fields in the output view
     *
     * format: [public facing name => database column]
     *
     * @return array
     */
    abstract protected function getVerboseFields(): array;

    /**
     * List of subresource keys that may be in the data set from the mapper
     * but are not database columns that need to be in the output view
     *
     * format: [public facing name => field in $this->data]
     *
     * @return array
     */
    public function getSubResources(): array
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
    public function getOutputView(Request $request, bool $verbose = false): array
    {
        $item = [];

        if ($verbose) {
            $fields = $this->getVerboseFields();
        } else {
            $fields = $this->getDefaultFields();
        }

        $fields = array_merge($fields, $this->getSubResources());

        // special handling for dates; uses magic getters for event TZ columns
        $tz = $this->event_tz_place != '' && $this->event_tz_cont != '' ?
            new DateTimeZone($this->event_tz_cont . '/' . $this->event_tz_place) : new DateTimeZone('UTC');

        foreach ($fields as $output_name => $name) {
            $value = $this->$name;

            // override if it is a date
            if (str_ends_with(strval($output_name), '_date') && ! empty($value)) {
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
