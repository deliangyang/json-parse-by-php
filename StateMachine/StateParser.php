<?php

namespace StateMachine;

class StateParser
{

    const EXPECT_SINGLE_VALUE = 0x1;

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

    protected array $stateMap = [
        self::EXPECT_SINGLE_VALUE => 'EXPECT_SINGLE_VALUE',
        self::EXPECT_BEGIN_OBJECT => 'EXPECT_BEGIN_OBJECT',
        self::EXPECT_BEGIN_ARRAY => 'EXPECT_BEGIN_ARRAY',
        self::EXPECT_END_DOCUMENT => 'EXPECT_END_DOCUMENT',
        self::EXPECT_COMMA => 'EXPECT_COMMA',
        self::EXPECT_COLON => 'EXPECT_COLON',
        self::EXPECT_OBJECT_KEY => 'EXPECT_OBJECT_KEY',
        self::EXPECT_OBJECT_VALUE => 'EXPECT_OBJECT_VALUE',
        self::EXPECT_END_OBJECT => 'EXPECT_END_OBJECT',
        self::EXPECT_END_ARRAY => 'EXPECT_END_ARRAY',
        self::EXPECT_ARRAY_VALUE => 'EXPECT_ARRAY_VALUE',
    ];

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
     * @throws \Exception
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
                case ',':
                    $this->p++;
                    return Token::COMMA;
                case ':':
                    $this->p++;
                    return Token::COLON;
                case '"':
                    return Token::STRING;
                case 'f':
                case 't':
                    return Token::BOOLEAN;
                case 'n':
                    return Token::VALUE_NULL;
                case '-':
                    return Token::NUMBER;
            }
            if ($ch >= '0' && $ch <= '9') {
                return Token::NUMBER;
            }
        }
    }

    /**
     * @throws \Exception
     */
    public function parser()
    {
        $result = null;
        $stack = [];
        $this->state = self::EXPECT_SINGLE_VALUE | self::EXPECT_BEGIN_ARRAY | self::EXPECT_BEGIN_OBJECT;
        for (; ;) {
            $token = $this->nextToken();
            // echo implode(' => ', [Token::token2name($token), $this->state()]), PHP_EOL;
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
                        if (empty($stack)) {
                            array_push($stack, $lastValue);
                            $this->state = self::EXPECT_END_DOCUMENT;
                            break;
                        }
                        $topValue = array_pop($stack);
                        if (is_string($topValue)) {
                            $object = array_pop($stack);
                            $object[$topValue] = $lastValue;
                            array_push($stack, $object);
                            $this->state = self::EXPECT_COMMA | self::EXPECT_END_OBJECT;
                            break;
                        } else if (is_array($topValue)) {
                            // 前面是一个数组
                            $topValue[] = $lastValue;
                            array_push($stack, $topValue);
                            $this->state = self::EXPECT_COMMA | self::EXPECT_END_ARRAY;
                            break;
                        }
                    }
                    throw new \Exception(sprintf(
                        'expected char }, but got %s, current state: %s',
                        Token::token2name($token),
                        $this->state()
                    ));
                case Token::ARRAY_BEGIN:
                    if ($this->hasState(self::EXPECT_BEGIN_ARRAY)) {
                        array_push($stack, []);
                        $this->state = self::EXPECT_ARRAY_VALUE | self::EXPECT_BEGIN_OBJECT | self::EXPECT_BEGIN_ARRAY | self::EXPECT_END_ARRAY;
                        break;
                    }
                    throw new \Exception(sprintf(
                        'expected char [, but got %s, current state: %s',
                        Token::token2name($token),
                        $this->state()
                    ));
                case Token::ARRAY_END:
                    if ($this->hasState(self::EXPECT_END_ARRAY)) {
                        if (1 >= count($stack)) {
                            $this->state = self::EXPECT_END_DOCUMENT;
                            break;
                        }
                        $val = array_pop($stack);
                        $first = array_pop($stack);
                        if (is_string($first)) {
                            $obj = array_pop($stack);
                            $obj[$first] = $val;
                            array_push($stack, $obj);
                            $this->state = self::EXPECT_COMMA | self::EXPECT_END_OBJECT;
                        } else if (is_array($first)) {
                            array_push($first, $val);
                            array_push($stack, $first);
                            $this->state = self::EXPECT_COMMA | self::EXPECT_END_ARRAY;
                            break;
                        }
                        break;
                    }
                    throw new \Exception(sprintf(
                        'expected char ], but got %s, current state: %s',
                        Token::token2name($token),
                        $this->state()
                    ));
                case Token::NUMBER:
                    if ($this->hasState(self::EXPECT_SINGLE_VALUE)) {
                        array_push($stack, $this->readNumber());
                        $this->state = self::EXPECT_END_DOCUMENT;
                        break;
                    } else if ($this->hasState(self::EXPECT_ARRAY_VALUE)) {
                        $number = $this->readNumber();
                        $array = array_pop($stack);
                        $array[] = $number;
                        array_push($stack, $array);
                        $this->state = self::EXPECT_COMMA | self::EXPECT_END_ARRAY;
                        break;
                    } else if ($this->hasState(self::EXPECT_OBJECT_VALUE)) {
                        $number = $this->readNumber();
                        $key = array_pop($stack);
                        $object = array_pop($stack);
                        $object[$key] = $number;
                        array_push($stack, $object);
                        $this->state = self::EXPECT_COMMA | self::EXPECT_END_OBJECT;
                        break;
                    }
                    break;
                case Token::STRING:
                    if ($this->hasState(self::EXPECT_SINGLE_VALUE)) {
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
                        $object[$key] = $value;
                        array_push($stack, $object);
                        $this->state = self::EXPECT_COMMA | self::EXPECT_END_OBJECT;
                        break;
                    }
                    throw new \Exception('unexpected string');
                case Token::BOOLEAN:
                    if ($this->hasState(self::EXPECT_SINGLE_VALUE)) {
                        array_push($stack, $this->readBoolean());
                        $this->state = self::EXPECT_END_DOCUMENT;
                        break;
                    } else if ($this->hasState(self::EXPECT_OBJECT_VALUE)) {
                        // {'aa': bool,?},
                        $value = $this->readBoolean();
                        $key = array_pop($stack);
                        $object = array_pop($stack);
                        $object[$key] = $value;
                        array_push($object);
                        $this->state = self::EXPECT_END_OBJECT | self::EXPECT_COMMA;
                        break;
                    } else if ($this->hasState(self::EXPECT_ARRAY_VALUE)) {
                        $value = $this->readBoolean();
                        $array = array_pop($stack);
                        $array[] = $value;
                        array_push($array);
                        $this->state = self::EXPECT_END_ARRAY | self::EXPECT_COMMA;
                        break;
                    }
                    throw new \Exception('unexpected boolean.');
                case Token::VALUE_NULL:
                    $isNull = $this->readNull();
                    if ($isNull && $this->hasState(self::EXPECT_SINGLE_VALUE)) {
                        array_push($stack, null);
                        $this->state = self::EXPECT_END_DOCUMENT; // self::EXPECT_COMMA | self::EXPECT_END_DOCUMENT | self::EXPECT_END_OBJECT | self::EXPECT_END_ARRAY;
                        break;
                    } else if ($isNull && $this->hasState(self::EXPECT_ARRAY_VALUE)) {
                        $array = array_pop($stack);
                        $array[] = null;
                        array_push($stack, $array);
                        $this->state = self::EXPECT_COMMA | self::EXPECT_END_ARRAY;
                        break;
                    } else if ($isNull && $this->hasState(self::EXPECT_OBJECT_VALUE)) {
                        $key = array_pop($stack);
                        $obj = array_pop($stack);
                        $obj[$key] = null;
                        array_push($stack, $obj);
                        $this->state = self::EXPECT_COMMA | self::EXPECT_END_OBJECT;
                        break;
                    }
                    throw new \Exception(sprintf(
                        'expected char NULL , but got %s, current state: %s',
                        Token::token2name($token),
                        $this->state()
                    ));
                case Token::END:
                    if ($this->hasState(self::EXPECT_END_DOCUMENT)) {
                        $value = array_pop($stack);
                        if (count($stack) <= 0) {
                            return $value;
                        }
                    }
                    throw new \Exception(sprintf(
                        'expected char EOF , but got %s, current state: %s',
                        Token::token2name($token),
                        $this->state()
                    ));
                case Token::COMMA:
                    // ,
                    if ($this->hasState(self::EXPECT_COMMA)) {
                        if ($this->hasState(self::EXPECT_END_OBJECT)) {
                            $this->state = self::EXPECT_OBJECT_KEY;
                            break;
                        } else if ($this->hasState(self::EXPECT_END_ARRAY)) {
                            $this->state = self::EXPECT_ARRAY_VALUE | self::EXPECT_BEGIN_ARRAY | self::EXPECT_BEGIN_OBJECT;
                            break;
                        }
                    }
                    throw new \Exception(sprintf(
                        'expected char "," , but got %s, current state: %s',
                        Token::token2name($token),
                        $this->state()
                    ));
                case Token::COLON:
                    // :
                    if (self::EXPECT_COLON === $this->state) {
                        $this->state = self::EXPECT_OBJECT_VALUE | self::EXPECT_BEGIN_OBJECT | self::EXPECT_BEGIN_ARRAY;
                        break;
                    }
                    throw new \Exception(sprintf(
                        'expected char ":", but got %s, current state: %s',
                        Token::token2name($token),
                        $this->state()
                    ));
                default:
                    throw new \Exception(sprintf(
                        'unknown token %s, current state: %s',
                        Token::token2name($token),
                        $this->state()
                    ));
            }
        }
    }

    protected function hasState(int $state): bool
    {
        return $this->state & $state;
    }

    /**
     * @param string $value
     * @return bool
     */
    protected function expect(string $value): bool
    {
        return substr($this->json, $this->p, strlen($value)) === $value;
    }

    /**
     * @return bool
     * @throws \Exception
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

    protected function readNull(): bool
    {
        if ($this->expect('null')) {
            $this->p += strlen('null');
            return true;
        }
        throw new \Exception('except null value');
    }

    protected function readNumber(): int|float
    {
        $start = $this->p;
        if ($this->json[$this->p] === '-') $this->p++;
        if ($this->json[$this->p] === '0') {
            $this->p++;
        } else if ($this->json[$this->p] >= '1' && $this->json[$this->p] <= '9') {
            $this->p++;
            while ($this->p < $this->l && $this->json[$this->p] >= '0' && $this->json[$this->p] <= '9') {
                $this->p++;
            }
        }

        if ($this->p < $this->l && $this->json[$this->p] === '.') {
            $this->p++;
            while ($this->p < $this->l && $this->json[$this->p] >= '0' && $this->json[$this->p] <= '9') {
                $this->p++;
            }
        }

        if ($this->p < $this->l && strtolower($this->json[$this->p]) === 'e') {
            $this->p++;
            if ($this->json[$this->p] === '-' || $this->json[$this->p] === '+') {
                $this->p++;
            }
            while ($this->p < $this->l && $this->json[$this->p] >= '0' && $this->json[$this->p] <= '9') {
                $this->p++;
            }
        }
        if ($this->p > $start) {
            $value = substr($this->json, $start, $this->p - $start);
            return str_contains($value, '.')
                ? floatval($value)
                : intval($value);
        }

        throw new \Exception('excepted number');
    }

    /**
     * @return string
     */
    protected function state(): string
    {
        $states = [];
        foreach ($this->stateMap as $state => $name) {
            if ($state & $this->state) {
                $states[] = $name;
            }
        }
        return implode(', ', $states);
    }

}
