<?php

namespace StateMachine;

final class Token
{
    const END = 1;              // end of document
    const OBJECT_BEGIN = 2;     // {
    const OBJECT_END = 4;       // }
    const ARRAY_BEGIN = 8;      // [
    const ARRAY_END = 16;        // ]
    const NUMBER = 32;           // 0-9...
    const COLON = 64;            // :
    const COMMA = 128;            // ,
    const STRING = 256;           // "..."
    const BOOLEAN = 512;          // true/false
    const VALUE_NULL = 1024;      // null

    const TokenMap = [
        self::END => 'EOF',
        self::OBJECT_BEGIN => '{',
        self::OBJECT_END => '}',
        self::ARRAY_BEGIN => '[',
        self::ARRAY_END => ']',
        self::NUMBER => 'NUMBER',
        self::COLON => ':',
        self::COMMA => ',',
        self::STRING => 'STRING',
        self::BOOLEAN => 'BOOLEAN',
        self::VALUE_NULL => 'NULL',
    ];

    public static function token2name(int $token): string
    {
        return self::TokenMap[$token] ?? '';
    }
}