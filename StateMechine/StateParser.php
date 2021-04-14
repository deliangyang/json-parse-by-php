<?php

require_once __DIR__ . '/Token.php';


class StateParser
{

    const EXPECT_STRING_VALUE = 0x1;

    const EXPECT_BEGIN_OBJECT = 0x2;

    const EXPECT_BEGIN_ARRAY = 0x4;

    const EXPECT_END_DOCUMENT = 0x8;

    const EXPECT_COMMA = 0x10;

    const EXPECT_COLON = 0x20;

    const EXPECT_OBJECT_KEY = 0x40;

    const EXPECT_OBJECT_VALUE = 0x80;

    const EXPECT_END_OBJECT = 0x100;

    const EXPECT_END_ARRAY = 0x200;

    const EXPECT_ARRAY_VALUE = 0x400;

    protected string $json;

    protected int $p = 0;

    protected int $l;

    protected $state = 0;

    public function __construct(string $json)
    {
        $this->json = $json;
        $this->l = strlen($this->json);
    }

    /**
     * @return string
     * @throws Exception
     */
    public function readString(): string
    {
        $ch = $this->json[$this->p];
        if ('"' !== $ch) {
            throw new \Exception('expect " but actual is :' . $ch);
        }
        $this->p++;
        $s = '';
        for (; ;) {
            $ch = $this->json[$this->p++];
            if ('\\' === $ch) {
                $ch = $this->json[$this->p++];
                switch ($ch) {
                    case '"':
                        $s .= '\"';
                        break;
                    case '\\':
                        $s .= '\\';
                        break;
                    case '/':
                        $s .= '/';
                        break;
                    case 'b':
                        $s .= '\b';
                        break;
                    default:
                        throw new \Exception('unexpected char: ' . $ch);

                }
            } else if ('"' === $ch) {
                break;
            } else if ("\r" === $ch || "\n" === $ch) {
                throw new \Exception('Unexpect char: ' . "\\$ch");
            } else {
                $s .= $ch;
            }
        }
        return $s;
    }

    public function nextToken(): int
    {
        for (; ;) {
            // 如果结束了
            if ($this->l <= $this->p) {
                return Token::END;
            }
            $ch = $this->json[$this->p];
            switch ($ch) {
                case '{':
                    $this->p++;
                    return Token::OBJECT_BEGIN;
                case '}':
                    $this->p++;
                    return Token::OBJECT_END;
                case '[':
                    $this->p++;
                    return Token::ARRAY_BEGIN;
                case ']':
                    $this->p++;
                    return Token::ARRAY_END;
                case ':':
                    $this->p++;
                    return Token::COLON;
                case ',':
                    return Token::COMMA;
                case '"':
                    return Token::STRING;
                case 'f':
                case 't':
                    return Token::BOOLEAN;
            }
            if ($ch >= '0' && $ch <= '9') {
                $this->p++;
                return Token::NUMBER;
            }
        }
    }

    /**
     * @throws Exception
     */
    public function parser()
    {
        $result = null;
        $stack = [];
        $this->state = self::EXPECT_STRING_VALUE | self::EXPECT_BEGIN_ARRAY | self::EXPECT_BEGIN_OBJECT;
        for (; ;) {
            $token = $this->nextToken();
            switch ($token) {
                case Token::OBJECT_BEGIN:
                    if ($this->hasState(self::EXPECT_BEGIN_OBJECT)) {
                        /**
                         * {{
                         * { "a"
                         * {}
                         */
                        array_push($stack, []);
                        $this->state = self::EXPECT_OBJECT_KEY | self::EXPECT_BEGIN_OBJECT | self::EXPECT_END_OBJECT;
                        break;
                    }
                    throw new \Exception('unexpected char: {');
                case Token::OBJECT_END:
                    if ($this->hasState(self::EXPECT_END_OBJECT)) {
                        $lastValue = array_pop($stack);
                        // var_dump($lastValue, $stack);
                        var_dump($stack);
                        $topValue = array_pop($stack);
                        if (is_string($topValue)) {
                            $object = array_pop($stack);
                            $object[$topValue] = $lastValue;
                            array_push($stack, $object);
                            $this->state = self::EXPECT_COMMA | self::EXPECT_END_OBJECT;
                            break;
                        } else if (is_array($topValue)) {
                            // 前面是一个数组
                            $objectKey = array_pop($topValue);
                            var_dump($objectKey, $topValue);
//                            $object = array_pop($stack);
//
//                            $object[$objectKey] = $lastValue;
//                            array_push($stack, $object);
//                            var_dump($object);
                            //var_dump($objectKey);
//
//                            //$object = array_pop($stack);
//                            $topValue[$objectKey] = $lastValue;
//                            array_push($stack, $topValue);
//                            var_dump($stack);
                            $this->state = self::EXPECT_COMMA | self::EXPECT_END_ARRAY;
                            break;
                        }
                    }
                    throw new \Exception('unexpected char, }');
                case Token::ARRAY_BEGIN:
                    if ($this->hasState(self::EXPECT_BEGIN_ARRAY)) {
                        array_push($stack, []);
                        $this->state = self::EXPECT_ARRAY_VALUE | self::EXPECT_BEGIN_OBJECT
                            | self::EXPECT_BEGIN_ARRAY | self::EXPECT_END_ARRAY;
                        break;
                    }
                    throw new \Exception('unexpected char [ .');
                case Token::ARRAY_END:
                    if ($this->hasState(self::EXPECT_END_ARRAY)) {
                        $value = array_pop($stack);
                        if (0 === count($stack)) {
                            array_push($stack, $value);
                            $this->state = self::EXPECT_END_DOCUMENT;
                            break;
                        }
                        $val = array_pop($stack);
                        if (is_array($val)) {
                            $val[] = $value;
                            array_push($stack, $val);
                            $this->state = self::EXPECT_COMMA | self::EXPECT_END_OBJECT;
                            break;
                        }
                        $key = array_pop($stack);
                        $array = array_pop($stack);
                        $array[$key] = $value;
                        array_push($stack, $array);
                        $this->state = self::EXPECT_COMMA | self::EXPECT_END_ARRAY;
                        break;
                    }
                    throw new \Exception('unexpected char ] .');
                case Token::NUMBER:
                    break;
                case Token::STRING:
                    if ($this->hasState(self::EXPECT_STRING_VALUE)) {
                        $value = $this->readString();
                        array_push($stack, $value);
                        $this->state = self::EXPECT_END_DOCUMENT;
                        break;
                    } else if ($this->hasState(self::EXPECT_ARRAY_VALUE)) {
                        $str = $this->readString();
                        $array = array_pop($stack);
                        $array[] = $str;
                        array_push($stack, $array);
                        $this->state = self::EXPECT_COMMA | self::EXPECT_END_ARRAY;
                        break;
                    } else if ($this->hasState(self::EXPECT_OBJECT_KEY)) {
                        $value = $this->readString();
                        array_push($stack, $value);
                        $this->state = self::EXPECT_COLON;
                        break;
                    } else if ($this->hasState(self::EXPECT_OBJECT_VALUE)) {
                        $value = $this->readString();
                        $key = array_pop($stack);
                        $object = array_pop($stack);
                        var_dump($key, $object, $value);
                        $object[$key] = $value;

                        array_push($stack, $object);

                        $this->state = self::EXPECT_COMMA | self::EXPECT_END_OBJECT;
                        break;
                    }
                    throw new \Exception('unexpected string');
                case Token::BOOLEAN:
                    if ($this->hasState(self::EXPECT_STRING_VALUE)) {
                        array_push($stack, $this->readBoolean());
                        $this->state = self::EXPECT_END_DOCUMENT;
                        break;
                    } else if ($this->hasState(self::EXPECT_ARRAY_VALUE)) {
                        // {'aa': bool,?},
                        $value = $this->readBoolean();
                        $key = array_pop($stack);
                        $object = array_pop($stack);
                        $object[$key] = $value;
                        array_push($object);
                        $this->state = self::EXPECT_END_OBJECT | self::EXPECT_COMMA;
                        break;
                    } else if ($this->hasState(self::EXPECT_OBJECT_VALUE)) {
                        $value = $this->readBoolean();
                        $array = array_pop($stack);
                        $array[] = $value;
                        array_push($array);
                        $this->state = self::EXPECT_END_ARRAY | self::EXPECT_COMMA;
                        break;
                    }
                    throw new \Exception('unexpected boolean.');
                case Token::VALUE_NULL:
                    break;
                case Token::END:
                    if ($this->hasState(self::EXPECT_END_DOCUMENT)) {
                        $value = array_pop($stack);
                        if (count($stack) <= 0) {
                            return $value;
                        }
                    }
                    throw new \Exception('Unexpected EOF.');
                case Token::COMMA:
                    $this->p++;
                    $this->state = self::EXPECT_ARRAY_VALUE | self::EXPECT_BEGIN_ARRAY | self::EXPECT_BEGIN_OBJECT;
                    break;
                case Token::COLON:
                    $this->state = self::EXPECT_OBJECT_VALUE | self::EXPECT_BEGIN_OBJECT | self::EXPECT_BEGIN_ARRAY;
                    break;
                default:
                    throw new \Exception('unknown token: ' . $token);
            }
        }
    }

    protected function hasState(int $state): bool
    {
        return $this->state & $state;
    }

    protected function expect(string $value): bool
    {
        return substr($this->json, $this->p, strlen($value)) === $value;
    }

    /**
     * @return bool
     * @throws Exception
     */
    protected function readBoolean(): bool
    {
        $ch = $this->json[$this->p];
        if ('t' === $ch && $this->expect('true')) {
            $this->p += strlen('true');
            return true;
        } else if ('f' === $ch && $this->expect('false')) {
            $this->p += strlen('false');
            return false;
        }
        throw new \Exception('except boolean value');
    }

}

//$d = json_encode(true);
//echo $d, PHP_EOL;
//$stateParser = new StateParser($d);
//
//var_dump($stateParser->parser());
//$d = json_encode(false);
//echo $d, PHP_EOL;
//$stateParser = new StateParser($d);
//
//var_dump($stateParser->parser());
//
//
//$d = json_encode(false);
//echo $d, PHP_EOL;
$res = json_encode([['a', 'b'], ['c', 'd',], 'd', ['a' => 'b', 'c' => 'd',]]);
$res = json_encode(['a' => 'b', 'c' => 'd', 'e' => ['a', 'b', 'c',], 'cd' => ['d' => 'e']]);
echo $res, PHP_EOL;
$stateParser = new StateParser($res);

var_dump($stateParser->parser());