<?php

namespace StateMachine;

class Reader implements ReaderInterface
{

    protected string $json;

    protected int $p = 0;

    protected int $l;

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
                        $s .= '"';
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

    /**
     * @return bool
     * @throws \Exception
     */
    public function readBoolean(): bool
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

    public function readNull(): bool
    {
        if ($this->expect('null')) {
            $this->p += strlen('null');
            return true;
        }
        throw new \Exception('except null value');
    }

    public function readNumber(): int|float
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
     * @param string $value
     * @return bool
     */
    protected function expect(string $value): bool
    {
        return substr($this->json, $this->p, strlen($value)) === $value;
    }
}