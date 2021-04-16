<?php

namespace StateMachine;

interface ReaderInterface
{

    public function nextToken(): int;

    public function readBoolean(): bool;

    public function readNumber(): int|float;

    public function readString(): string;

    public function readNull(): bool;

}