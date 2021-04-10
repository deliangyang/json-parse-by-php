<?php


class Parser
{
    protected string $str;

    protected int $len = 0;

    protected int $l = 0;

    public function __construct(string $str)
    {
        $this->str = $str;
        $this->len = strlen($str);
    }

    protected function parseString(): string|null
    {
        $this->skipWhiteSpace();

        if ($this->l < $this->len && $this->str[$this->l] === '"') {
            $this->l++;
            $key = '';
            while ($this->l < $this->len && $this->str[$this->l] !== '"') {
                $key .= $this->str[$this->l];
                $this->l++;
            }
            $this->l++;
            return $key;
        }
        return null;
    }

    protected function skipWhiteSpace()
    {
        if ($this->l < $this->len) {
            while ($this->str[$this->l] === ' '
                || $this->str[$this->l] === "\t"
                || $this->str[$this->l] === "\r"
                || $this->str[$this->l] === "\n") {
                $this->l++;
            }
        }
    }

    /**
     * @throws Exception
     */
    protected function parseColon()
    {
        if ($this->str[$this->l] !== ':') {
            throw new \Exception('except :');
        }
        $this->l++;
    }

    /**
     * @return array|null
     * @throws Exception
     */
    protected function parseObject(): array|null
    {
        $this->skipWhiteSpace();

        if ($this->str[$this->l] === '{') {
            $this->l++;
            $this->skipWhiteSpace();

            $object = [];
            $init = true;
            while ($this->str[$this->l] !== '}') {
                if (!$init) {
                    $this->parseComma();
                    $this->skipWhiteSpace();
                }
                $key = $this->parseString();
                $this->skipWhiteSpace();
                $this->parseColon();
                $value = $this->parseValue();
                $object[$key] = $value;
                $init = false;
                $this->skipWhiteSpace();
            }
            $this->l++;
            return $object;
        }
        return null;
    }

    /**
     * @return string|array|int|float|null
     * @throws Exception
     */
    protected function parseValue(): string|array|null|int|float
    {
        $value = $this->parseString()
            ?: ($this->parseObject()
                ?: ($this->parseArray()
                    ?: ($this->parseNumber()
                        ?: ($this->parseKeyword('true', true))
                            ?: ($this->parseKeyword('false', false)
                                ?: ($this->parseKeyword('null', null))))));
        $this->skipWhiteSpace();
        return $value;
    }

    protected function parseKeyword($name, $value)
    {
        if (substr($this->str, $this->l, strlen($name)) === $name) {
            $this->l += strlen($name);
            return $value;
        }
    }

    /**
     * @throws Exception
     */
    protected function parseComma()
    {
        if ($this->str[$this->l] !== ',') {
            throw new \Exception('except ,');
        }
        $this->l++;
    }

    /**
     * @return array|null
     * @throws Exception
     */
    protected function parseArray(): array|null
    {
        $this->skipWhiteSpace();

        if ($this->str[$this->l] === '[') {
            $this->l++;
            $this->skipWhiteSpace();

            $array = [];
            $init = true;
            while ($this->str[$this->l] !== ']') {
                if (!$init) {
                    $this->parseComma();
                    $this->skipWhiteSpace();
                }
                $value = $this->parseValue();
                $array[] = $value;
                $init = false;
            }
            $this->l++;
            return $array;
        }
        return null;
    }

    protected function parseNumber(): string|null|int|float
    {
        $type = 'int';
        $start = $this->l;
        if ($this->str[$this->l] === '-') $this->l++;
        if ($this->str[$this->l] === '0') {
            $this->l++;
        } else if ($this->str[$this->l] >= '1' && $this->str[$this->l] <= '9') {
            $this->l++;
            while ($this->l < $this->len && $this->str[$this->l] >= '0' && $this->str[$this->l] <= '9') {
                $this->l++;
            }
        }

        if ($this->l < $this->len && $this->str[$this->l] === '.') {
            $type = 'float';
            $this->l++;
            while ($this->l < $this->len && $this->str[$this->l] >= '0' && $this->str[$this->l] <= '9') {
                $this->l++;
            }
        }

        if ($this->l < $this->len && strtolower($this->str[$this->l]) === 'e') {
            $this->l++;
            if ($this->str[$this->l] === '-' || $this->str[$this->l] === '+') {
                $this->l++;
            }
            while ($this->l < $this->len && $this->str[$this->l] >= '0' && $this->str[$this->l] <= '9') {
                $this->l++;
            }
        }
        if ($this->l > $start) {
            $value = substr($this->str, $start, $this->l - $start);
            return $type === 'int'
                ? intval($value)
                : floatval($value);
        }

        return null;
    }

    /**
     * @return array|float|int|string|null
     * @throws Exception
     */
    public function decode(): array|float|int|string|null
    {
        return $this->parseValue();
    }
}


try {
    $parser = new Parser(<<<JSON
{
  "a": 1e2,
  "b": "c",
  "c": {
    "d": 3,
    "c": [1, 3, 4, {
      "d": 3
    }]
  },
  "e": 2.3
}
JSON
    );
    var_dump($parser->decode());

} catch (\Exception $ex) {
    var_dump($ex->getMessage());
}
