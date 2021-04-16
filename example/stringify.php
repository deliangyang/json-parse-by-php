<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Parser\ToJson;

$testcases = [
    [1, 2, 4.3, 3, ['a' => 'b', 'c' => 1, 'd' => [1, 2, 3]]],
    [
        'a' => 2.3,
        'b' => false,
        'd' => true,
        'null' => null,
        'c' => -1,
        'dccc' => [1 => 3, 4 => 4],
        'dccbbb' => [0 => 3, 1 => 4],
        'eee' => [1, 2, 3, [
            'c' => [
                'a' => 'c',
                'c' => [2, 3]
            ],
            'x' => [
                'a' => '1',
                1 => 2,
                2 => 3,
            ],
        ]],
        'string' => 'sfsadf"sdfsadfsf'
    ],
];

$toJSON = new ToJson();
foreach ($testcases as $testcase) {
    $res = $toJSON->stringify($testcase);
    assert(json_encode($res) === $res);
    $res = $toJSON->stringify($testcase, true);
    assert(json_encode($res, JSON_PRETTY_PRINT) === $res);
}

