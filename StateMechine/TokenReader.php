<?php

interface TokenReader
{

    public function readNextToken(): int;

    public function readBoolean(): bool;

    public function readNumber(): int|float;

    public function readString(): string;

    public function readNull(): void;

}