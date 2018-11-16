<?php
/*
  ICC verion tool
  (c) 2018/11/17- yoya@awm.jp
*/

require_once 'IO/ICC.php';

function usage() {
    echo "Usage: php iccheader.php <iccfile> [<iccfile2> [...]]".PHP_EOL;
}

if ($argc < 2) {
    usage();
    exit(1);
}

$iccfiles = array_slice($argv, 1);

foreach ($iccfiles as $iccfile) {
    if (is_readable($iccfile) === false) {
        echo "Can't read iccfile($iccfile)".PHP_EOL;
        usage();
        exit(1);
    }
}

$iccdata = file_get_contents($iccfile);

foreach ($iccfiles as $iccfile) {
    $icc = new IO_ICC();
    $icc->parse($iccdata);
    $icc->DumpHeader();
}

exit(0);
