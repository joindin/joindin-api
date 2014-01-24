<?php

class JsonView extends ApiView {
    public function render($content) {
        header('Content-Type: application/json; charset=utf8');
        echo $this->buildOutput($content);
        return true;
    }

    /**
     *  Function to build output, can be used by JSON and JSONP
     */
    public function buildOutput ($content) {
        $content = $this->addCount($content);
        // need to work out which fields should have been numbers
        // Don't use JSON_NUMERIC_CHECK because it eats talk stubs
        $output = $this->numeric_check($content);
        $retval = json_encode($output);
        return $retval;
    }

    protected function numeric_check($data)
    {
        $output = array();
        foreach($data as $key => $value) {
            
            // recurse as needed
            if(is_array($value)) {
                $output[$key] = $this->numeric_check($value);
            } else {

                // stubs are hex, but can look like scientific notation
                if(is_numeric($value) && $key != "stub") {
                    $output[$key] = (float)$value;
                } else {
                    $output[$key] = $value;
                }
            }
        }
        return $output;
    }

}
