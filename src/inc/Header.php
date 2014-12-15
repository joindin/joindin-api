<?php

/**
 * The basis for this class has been borrowed from the excellent Guzzle
 * Project.
 *
 * https://github.com/guzzle/guzzle
 * Copyright (c) 2014 Michael Dowling, https://github.com/mtdowling <mtdowling@gmail.com>
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * Represents a header and all of the values stored by that header
 */
class Header
{
    protected $values = array();
    protected $header;
    protected $glue;

    /**
     * @param string       $header Name of the header
     * @param array|string $values Values of the header as an array or a scalar
     * @param string       $glue   Glue used to combine multiple values into a string
     */
    public function __construct($header, $values = array(), $glue = ',')
    {
        $this->header = trim($header);
        $this->glue   = $glue;

        foreach ((array)$values as $value) {
            foreach ((array)$value as $v) {
                $this->values[] = $v;
            }
        }
    }

    public function __toString()
    {
        return implode($this->glue . ' ', $this->toArray());
    }

    public function getName()
    {
        return $this->header;
    }

    public function setName($name)
    {
        $this->header = $name;

        return $this;
    }

    public function setGlue($glue)
    {
        $this->glue = $glue;

        return $this;
    }

    public function getGlue()
    {
        return $this->glue;
    }

    /**
     * Normalize the header to be a single header with an array of values.
     *
     * If any values of the header contains the glue string value (e.g. ","), then the value will be exploded into
     * multiple entries in the header.
     *
     * @return self
     */
    public function normalize()
    {
        $values = $this->toArray();

        for ($i = 0, $total = count($values); $i < $total; $i++) {
            if (strpos($values[$i], $this->glue) !== false) {
                // Explode on glue when the glue is not inside of a comma
                foreach (preg_split(
                    '/' . preg_quote($this->glue)
                    . '(?=([^"]*"[^"]*")*[^"]*$)/', $values[$i]
                ) as $v
                ) {
                    $values[] = trim($v);
                }
                unset($values[$i]);
            }
        }

        $this->values = array_values($values);

        return $this;
    }

    public function hasValue($searchValue)
    {
        return in_array($searchValue, $this->toArray());
    }

    public function removeValue($searchValue)
    {
        $this->values = array_values(
            array_filter(
                $this->values, function ($value) use ($searchValue) {
                    return $value != $searchValue;
                }
            )
        );

        return $this;
    }

    public function toArray()
    {
        return $this->values;
    }

    public function count()
    {
        return count($this->toArray());
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->toArray());
    }

    public function buildEntityArray()
    {
        $assocArray = array();
        foreach ($this->values as $value) {
            $parts = explode('=', $value);
            $key = ucwords($parts[0]);
            if(count($parts) == 1){
                if(array_key_exists(0,$assocArray)) {
                    $assocArray[0][] = $parts[0];
                }else{
                    $assocArray[0] = array();
                    $assocArray[0][] = $parts[0];
                }
            }elseif (array_key_exists($key, $assocArray)) {
                $assocArray[$key][] = $parts[1];
            } else {
                $assocArray[$key]   = array();
                $assocArray[$key][] = $parts[1];
            }
        }
        return $assocArray;
    }

    public function parseParams()
    {
        $params   = $matches = array();
        $callback = array($this, 'trimHeader');

        // Normalize the header into a single array and iterate over all values
        foreach ($this->normalize()->toArray() as $val) {
            $part = array();
            foreach (preg_split('/;(?=([^"]*"[^"]*")*[^"]*$)/', $val) as $kvp) {
                if (!preg_match_all('/<[^>]+>|[^=]+/', $kvp, $matches)) {
                    continue;
                }
                $pieces           = array_map($callback, $matches[0]);
                $part[$pieces[0]] = isset($pieces[1]) ? $pieces[1] : '';
            }
            if ($part) {
                $params[] = $part;
            }
        }

        return $params;
    }

    /**
     * Trim a header by removing excess spaces and wrapping quotes
     *
     * @param $str
     *
     * @return string
     */
    protected function trimHeader($str)
    {
        static $trimmed = "\"'  \n\t";

        return trim($str, $trimmed);
    }
}