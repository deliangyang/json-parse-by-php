<?php

class ToJson
{
    /**
     * @param $obj
     * @param bool $pretty
     * @return string|bool|int|float|null
     * @throws Exception
     */
    public function stringify($obj, bool $pretty = false): string|bool|int|float|null
    {
        $indent = 0;
        return $this->encode($obj, $indent, $pretty);
    }

    protected function encode($obj, int &$indent, bool $pretty = false)
    {
        if (is_int($obj) || is_float($obj)) {
            return $obj;
        } else if (is_string($obj)) {
            return '"' . str_replace('"', '\"', $obj) . '"';
        } else if (is_bool($obj)) {
            return $obj ? 'true' : 'false';
        } else if (is_null($obj)) {
            return 'null';
        } else if (is_array($obj)) {
            $count = count($obj);
            if (0 === $count) {
                return '[]';
            }
            $isObj = 'object';
            $s = [];
            $indent++;
            foreach ($obj as $k => $value) {
                if (is_int($k)) {
                    $isObj = 'array';
                    $s[] = $this->encode($value, $indent, $pretty);
                } else {
                    $s[] = sprintf('"%s": %s', $k, $this->encode($value, $indent, $pretty));
                }
            }
            if (!$pretty) {
                return 'object' === $isObj
                    ? '{' . implode(', ', $s) . '}'
                    : '[' . implode(', ', $s) . ']';
            }
            $prefix = $this->indent($indent);
            $shortPrefix = $this->indent($indent - 1);

            $result = 'object' === $isObj
                ? '{' . PHP_EOL .
                $prefix . implode(', ' . PHP_EOL . $prefix, $s) . PHP_EOL .
                $shortPrefix . '}'
                : '[' . PHP_EOL .
                $prefix . implode(', ' . PHP_EOL . $prefix, $s) . PHP_EOL .
                $shortPrefix . ']';
            $indent--;
            return $result;
        }

        throw new \Exception('unknown struct');
    }

    protected function indent(int $indent): string
    {
        return str_repeat("  ", $indent);
    }

}
