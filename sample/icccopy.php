<?php
/*
  ICC copy tool
  (c) 2015/08/05- yoya@awm.jp
*/

if (is_readable('vendor/autoload.php')) {
    require 'vendor/autoload.php';
} else {
    require_once 'IO/ICC.php';
}

if ($argc != 2) {
    echo "Usage: php icccopy.php <icc_file>\n";
    echo "ex) php iccdopy.php test.icc\n";
    exit(1);
}

assert(is_readable($argv[1]));

$iccdata = file_get_contents($argv[1]);

$icc = new IO_ICC();

$icc->parse($iccdata);

echo $icc->build();

exit(0);
