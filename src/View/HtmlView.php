<?php

namespace Joindin\Api\View;

/**
 * HTML View class: renders HTML 5
 *
 * @category View
 * @package  API
 * @author   Lorna Mitchel <lorna.mitchell@gmail.com>
 * @author   Rob Allen <rob@akrabat.com>
 * @license  BSD see doc/LICENSE
 */
class HtmlView extends ApiView
{
    /**
     * @param array|string $content
     *
     * @return bool
     */
    public function render($content)
    {
        $this->setHeader('Content-Type', 'text/html; charset=utf8');

        return parent::render($content);
    }

    /**
     * Render the view
     *
     * @param array|string|bool $content data to be rendered
     *
     * @return null
     */
    public function buildOutput($content)
    {
        $content = $this->addCount($content);

        $this->layoutStart();

        if (is_array($content)) {
            $this->printArray($content);
        } else {
            $this->printUrlOrString($content);
        }
        $this->layoutStop();

        return null;
    }

    /**
     * Recursively render an array to an HTML list
     *
     * @param array $content data to be rendered
     *
     * @return null
     */
    protected function printArray(array $content)
    {
        echo "<ul>\n";

        // field name
        foreach ($content as $field => $value) {
            echo "<li><strong>" . $field . ":</strong> ";

            if (is_array($value)) {
                // recurse
                $this->printArray($value);
            } else {
                // value, with hyperlinked hyperlinks
                $this->printUrlOrString($value);
            }
            echo "</li>\n";
        }
        echo "</ul>\n";
    }

    /**
     * Renders the passed value, either raw or as a link (if prepended by http
     * or https)
     *
     * @param string|bool $value
     *
     * @return void
     */
    protected function printUrlOrString($value)
    {
        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        }
        $value = htmlentities($value, ENT_COMPAT, 'UTF-8');

        if ((strpos($value, 'http://') === 0) || (strpos($value, 'https://') === 0)) {
            echo "<a href=\"" . $value . "\">" . $value . "</a>";
        } else {
            echo $value;
        }
    }

    /**
     * Render start of HTML page
     *
     * @return null
     */
    protected function layoutStart()
    {
        echo <<<EOT
<!DOCTYPE html>
<html>
<head>
    <title>API v2</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <style>
    body {
        font-family: Helvetica, Arial, sans-serif;
        font-size: 14px;
        color: #000;
        padding: 5px;
    }

    ul {
        padding-bottom: 15px;
        padding-left: 20px;
    } 
    a {
        color: #2368AF;
    }
    </style>
</head>
<body>
EOT;
    }

    /**
     * Render end of HTML page
     *
     * @return null
     */
    protected function layoutStop()
    {
        echo <<<EOT
</body>
</html>

EOT;
    }
}
