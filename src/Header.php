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
namespace Joindin\Api;

use ArrayIterator;

/**
 * Represents a header and all of the values stored by that header
 */
class Header
{
    protected array $values = [];
    protected string $header;
    protected string $glue;

    /**
     * @param string       $header Name of the header
     * @param array|string $values Values of the header as an array or a scalar
     * @param string       $glue   Glue used to combine multiple values into a string
     */
    public function __construct(string $header, array|string $values = [], string $glue = ',')
    {
        $this->header = trim($header);
        $this->glue   = $glue;

        foreach ((array) $values as $value) {
            foreach ((array) $value as $v) {
                $this->values[] = $v;
            }
        }
    }

    public function __toString()
    {
        return implode($this->glue . ' ', $this->toArray());
    }

    public function getName(): string
    {
        return $this->header;
    }

    public function setName(string $name): static
    {
        $this->header = $name;

        return $this;
    }

    public function setGlue(string $glue): static
    {
        $this->glue = $glue;

        return $this;
    }

    public function getGlue(): string
    {
        return $this->glue;
    }

    /**
     * Normalize the header to be a single header with an array of values.
     *
     * If any values of the header contains the glue string value (e.g. ","), then the value will be exploded into
     * multiple entries in the header.
     *
     * @return static
     */
    public function normalize(): static
    {
        $values = $this->toArray();

        for ($i = 0, $total = count($values); $i < $total; $i++) {
            if (strpos($values[$i], $this->glue) !== false) {
                // Explode on glue when the glue is not inside of a comma
                foreach (preg_split('/' . preg_quote($this->glue, '/') . '(?=([^"]*"[^"]*")*[^"]*$)/', $values[$i]) as $v) {
                    $values[] = trim($v);
                }
                unset($values[$i]);
            }
        }

        $this->values = array_values($values);

        return $this;
    }

    public function hasValue(string $searchValue): bool
    {
        return in_array($searchValue, $this->toArray(), false);
    }

    public function removeValue(mixed $searchValue): static
    {
        $this->values = array_values(
            array_filter(
                $this->values,
                static function ($value) use ($searchValue) {
                    return $value != $searchValue;
                }
            )
        );

        return $this;
    }

    public function toArray(): array
    {
        return $this->values;
    }

    public function count(): int
    {
        return count($this->toArray());
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->toArray());
    }

    public function buildEntityArray(): array
    {
        $assocArray = [];

        foreach ($this->values as $value) {
            $parts = explode('=', $value);
            $key   = ucwords($parts[0]);

            if (count($parts) === 1) {
                if (array_key_exists(0, $assocArray)) {
                    $assocArray[0][] = $parts[0];
                } else {
                    $assocArray[0]   = [];
                    $assocArray[0][] = $parts[0];
                }
            } elseif (array_key_exists($key, $assocArray)) {
                $assocArray[$key][] = $parts[1];
            } else {
                $assocArray[$key]   = [];
                $assocArray[$key][] = $parts[1];
            }
        }

        return $assocArray;
    }

    public function parseParams(): array
    {
        $params   = $matches = [];
        $callback = [$this, 'trimHeader'];

        // Normalize the header into a single array and iterate over all values
        foreach ($this->normalize()->toArray() as $val) {
            $part = [];

            foreach (preg_split('/;(?=([^"]*"[^"]*")*[^"]*$)/', $val) as $kvp) {
                if (!preg_match_all('/<[^>]+>|[^=]+/', $kvp, $matches)) {
                    continue;
                }
                $pieces           = array_map($callback, $matches[0]);
                $part[$pieces[0]] = $pieces[1] ?? '';
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
     * @param string $str
     *
     * @return string
     */
    protected function trimHeader(string $str): string
    {
        static $trimmed = "\"'  \n\t";

        return trim($str, $trimmed);
    }
}
