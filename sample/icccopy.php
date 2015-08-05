<?php

require 'IO/ICC.php';
// require dirname(__FILE__).'/../IO/ICC.php';

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
