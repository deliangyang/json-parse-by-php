<?php

use StateMachine\StateParser;

require_once __DIR__ . '/../vendor/autoload.php';

$testcases = [
    ['a', 'b', 'c', ['a', 'b', ['4', '32', 'ccc', ['cc', 'ddd', 'eee']]]],
    ['a' => 'b', 'c' => 'd', 'e' => ['a', 'b', 'c',], 'cd' => ['d' => 'e']],
    ['d' => 'c', ['a', 'b']],
    "hello world",
    "hello \", ' world",
    "null",
    null,
    [null,],
    [null, null, null],
    ['a' => null, 'b' => null, 'c' => true, 'd' => false,],
];

foreach ($testcases as $testcase) {
    $case = json_encode($testcase);
    $stateParser = new StateParser($case);
    try {
        $result = $stateParser->parser();
        assert($case === $result);
    } catch (\Exception $ex) {
        echo $case, PHP_EOL;
        echo $ex->getMessage(), PHP_EOL;
    }
}