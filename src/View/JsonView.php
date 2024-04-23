<?php

namespace Joindin\Api\View;

class JsonView extends ApiView
{
    protected array $string_fields;

    public function render(array|string|null $content): bool
    {
        $this->setHeader('Content-Type', 'application/json; charset=utf8');
        $this->setHeader('Access-Control-Allow-Origin', '*');

        return parent::render($content);
    }

    /**
     *  Function to build output, can be used by JSON and JSONP
     *
     * @param mixed $content data to be rendered
     *
     * @return false|string
     */
    public function buildOutput(mixed $content): string|false
    {
        $content = $this->addCount($content);
        // need to work out which fields should have been numbers
        // Don't use JSON_NUMERIC_CHECK because it eats things (e.g. talk stubs)

        // Specify a list of fields to NOT convert to numbers
        $this->string_fields = ["stub", "track_name", "comment", "username"];

        $output = $this->numericCheck($content);

        return json_encode($output);
    }

    protected function numericCheck(mixed $data): mixed
    {
        if (!is_array($data)) {
            return $this->scalarNumericCheck('', $data);
        }
        $output = [];

        foreach ($data as $key => $value) {
            // recurse as needed
            if (is_array($value)) {
                $output[$key] = $this->numericCheck($value);
            } else {
                $output[$key] = $this->scalarNumericCheck($key, $value);
            }
        }

        return $output;
    }

    protected function scalarNumericCheck(string $key, mixed $value): mixed
    {
        if (is_numeric($value) && ! in_array($key, $this->string_fields) && $value < PHP_INT_MAX) {
            return (float) $value;
        }

        return $value;
    }
}
