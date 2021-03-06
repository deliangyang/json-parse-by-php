<?php

namespace Parser;

class ToJson
{

    protected bool $pretty = false;

    /**
     * @param $obj
     * @param bool $pretty
     * @return string|bool|int|float|null
     * @throws \Exception
     */
    public function stringify($obj, bool $pretty = false): string|bool|int|float|null
    {
        $indent = 0;
        $this->pretty = $pretty;
        return $this->encode($obj, $indent, $pretty);
    }

    protected function encodeBasicValue($obj): int|float|string|null
    {
        if (is_int($obj) || is_float($obj)) {
            return $obj;
        } else if (is_string($obj)) {
            return '"' . str_replace('"', '\"', $obj) . '"';
        } else if (is_bool($obj)) {
            return $obj ? 'true' : 'false';
        } else if (is_null($obj)) {
            return 'null';
        }
        return null;
    }

    /**
     * @param $obj
     * @param int $indent
     * @param bool $pretty
     * @return float|int|string
     * @throws \Exception
     */
    protected function encode($obj, int &$indent, bool $pretty = false): string|float|int
    {
        $value = $this->encodeBasicValue($obj);
        if (null !== $value) {
            return $value;
        }
        if (!is_array($obj)) {
            throw new \Exception('unknown struct');
        }

        $count = count($obj);
        if (0 === $count) {
            return '[]';
        }
        $itemType = 'object';
        $subItems = [];
        $indent++;
        $start = 0;
        foreach ($obj as $k => $value) {
            if ($start >= 0 && $k === $start) {
                $itemType = 'array';
                $subItems[] = $this->encode($value, $indent, $pretty);
                $start++;
            } else {
                $start = -1;
                $subItems[] = sprintf('"%s":%s%s', $k, $this->whiteSpaces(), $this->encode($value, $indent, $pretty));
            }
        }
        if (!$pretty) {
            return 'object' === $itemType
                ? '{' . implode(',' . $this->whiteSpaces(), $subItems) . '}'
                : '[' . implode(',' . $this->whiteSpaces(), $subItems) . ']';
        }
        $prefix = $this->indent($indent);
        $shortPrefix = $this->indent($indent - 1);
        $itemStr = implode(',' . PHP_EOL . $prefix, $subItems);

        $result = 'object' === $itemType
            ? '{' . PHP_EOL . $prefix . $itemStr . PHP_EOL . $shortPrefix . '}'
            : '[' . PHP_EOL . $prefix . $itemStr . PHP_EOL . $shortPrefix . ']';
        $indent--;
        return $result;
    }

    /**
     * @param int $indent
     * @return string
     */
    protected function indent(int $indent): string
    {
        return str_repeat("    ", $indent);
    }

    protected function whiteSpaces(): string
    {
        return $this->pretty
            ? ' '
            : '';
    }

}
