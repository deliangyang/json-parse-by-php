<?php

namespace StateMachine;

class State
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

    public static array $stateMap = [
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

}