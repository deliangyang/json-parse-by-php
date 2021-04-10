# JSON parsing by PHP

> Just to learn JSON parsing.

```php
<?php

require_once __DIR__ . '/Parser.php';


try {
    $parser = new Parser(<<<JSON
{
  "a": 1e2,
  "b": "c",
  "c": {
    "d": 3,
    "c": [1, 3, 4, {
      "d": 3
    }]
  },
  "e": 2.3,
  "de": {
    "true": true,
    "false": false,
    "null": null
  }
}
JSON
    );
    var_dump($parser->decode());

} catch (\Exception $ex) {
    var_dump($ex->getMessage());
}

// php8 parser.php | sed 's/^/\/\/ /g'
// array(5) {
//   ["a"]=>
//   int(100)
//   ["b"]=>
//   string(1) "c"
//   ["c"]=>
//   array(2) {
//     ["d"]=>
//     int(3)
//     ["c"]=>
//     array(4) {
//       [0]=>
//       int(1)
//       [1]=>
//       int(3)
//       [2]=>
//       int(4)
//       [3]=>
//       array(1) {
//         ["d"]=>
//         int(3)
//       }
//     }
//   }
//   ["e"]=>
//   float(2.3)
//   ["de"]=>
//   array(3) {
//     ["true"]=>
//     bool(true)
//     ["false"]=>
//     bool(false)
//     ["null"]=>
//     NULL
//   }
// }

```

## JSON decode
```php
<?php

require_once __DIR__ . '/ToJson.php';

try {
    $toJSON = new ToJson();
    $res = $toJSON->stringify([1, 2, 4.3, 3, ['a' => 'b', 'c' => 1, 'd' => [1, 2, 3]]]);
    echo $res, PHP_EOL;
    var_dump(json_decode($res, true));
    echo PHP_EOL;
    $res = $toJSON->stringify([
        'a' => 2.3,
        'b' => false,
        'd' => true,
        'null' => null,
        'c' => -1,
        'eee' => [1, 2, 3],
        'string' => 'sfsadf"sdfsadfsf'
    ]);
    echo $res, PHP_EOL;
    var_dump(json_decode($res, true));
} catch (\Exception $ex) {
    var_dump($ex->getMessage());
}

// [1, 2, 4.3, 3, {"a": "b", "c": 1, "d": [1, 2, 3]}]
// array(5) {
//   [0]=>
//   int(1)
//   [1]=>
//   int(2)
//   [2]=>
//   float(4.3)
//   [3]=>
//   int(3)
//   [4]=>
//   array(3) {
//     ["a"]=>
//     string(1) "b"
//     ["c"]=>
//     int(1)
//     ["d"]=>
//     array(3) {
//       [0]=>
//       int(1)
//       [1]=>
//       int(2)
//       [2]=>
//       int(3)
//     }
//   }
// }
// 
// {"a": 2.3, "b": false, "d": true, "null": null, "c": -1, "eee": [1, 2, 3], "string": "sfsadf\"sdfsadfsf"}
// array(7) {
//   ["a"]=>
//   float(2.3)
//   ["b"]=>
//   bool(false)
//   ["d"]=>
//   bool(true)
//   ["null"]=>
//   NULL
//   ["c"]=>
//   int(-1)
//   ["eee"]=>
//   array(3) {
//     [0]=>
//     int(1)
//     [1]=>
//     int(2)
//     [2]=>
//     int(3)
//   }
//   ["string"]=>
//   string(16) "sfsadf"sdfsadfsf"
// }


```