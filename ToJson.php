<?php

class ToJson
{
    /**
     * @param $obj
     * @return string|bool|int|float|null
     * @throws Exception
     */
    public function stringify($obj): string|bool|int|float|null
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
            foreach ($obj as $k => $value) {
                if (is_int($k)) {
                    $isObj = 'array';
                    $s[] = $this->stringify($value);
                } else {
                    $s[] = sprintf('"%s": %s', $k, $this->stringify($value));
                }
            }
            return 'object' === $isObj
                ? '{' . implode(', ', $s) . '}'
                : '[' . implode(', ', $s) . ']';
        }
        throw new \Exception('unknown struct');
    }

}