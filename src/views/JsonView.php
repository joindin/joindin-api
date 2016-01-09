<?php

class JsonView extends ApiView
{
    public function render($content)
    {
        header('Content-Type: application/json; charset=utf8');
        echo $this->buildOutput($content);

        return true;
    }

    /**
     *  Function to build output, can be used by JSON and JSONP
     */
    public function buildOutput($content)
    {
        $content = $this->addCount($content);
        // need to work out which fields should have been numbers
        // Don't use JSON_NUMERIC_CHECK because it eats things (e.g. talk stubs)

        // Specify a list of fields to NOT convert to numbers
        $this->string_fields = array("stub", "track_name", "comment");

        $output = $this->numericCheck($content);
        $retval = json_encode($output);

        return $retval;
    }

    protected function numericCheck($data)
    {
        if (! is_array($data)) {
            return $this->scalarNumericCheck('', $data);
        }
        $output = array();
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

    protected function scalarNumericCheck($key, $value)
    {
        if (is_numeric($value) && ! in_array($key, $this->string_fields)) {
            return (float) $value;
        }

        return $value;
    }
}
