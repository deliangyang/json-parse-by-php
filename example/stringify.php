<?php

require_once __DIR__ . '/../ToJson.php';

try {
    $toJSON = new ToJson();
    $res = $toJSON->stringify([1, 2, 4.3, 3, ['a' => 'b', 'c' => 1, 'd' => [1, 2, 3]]]);
    echo $res, PHP_EOL;
    echo PHP_EOL;
    var_dump(json_decode($res, true));
    echo PHP_EOL;
    $res = $toJSON->stringify([
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
            ]
        ]],
        'string' => 'sfsadf"sdfsadfsf'
    ], true);
    echo $res, PHP_EOL;
    echo PHP_EOL;
    var_dump(json_decode($res, true));
} catch (\Exception $ex) {
    var_dump($ex->getMessage());
}