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