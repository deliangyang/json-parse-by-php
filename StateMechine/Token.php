<?php

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
}