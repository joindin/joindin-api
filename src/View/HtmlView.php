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
    public function render(array|string|null $content): bool
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
    public function buildOutput(array|string|bool $content): null
    {
        $content = $this->addCount($content);

        $this->layoutStart();

        if (is_array($content)) {
            $this->printArray($content);
        } elseif (is_string($content) || is_bool($content)) {
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
    protected function printArray(array $content): null
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

        return null;
    }

    protected function printUrlOrString(string|bool|null $value): void
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
     * @return void
     */
    protected function layoutStart(): void
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
     * @return void
     */
    protected function layoutStop(): void
    {
        echo <<<EOT
</body>
</html>

EOT;
    }
}
