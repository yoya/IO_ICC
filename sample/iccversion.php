<?php
/*
  ICC verion tool
  (c) 2015/08/27- yoya@awm.jp
*/

require_once 'IO/ICC/Bit.php';

function usage() {
    echo "Usage: php iccversion.php <iccfile> [<iccfile2> [...]]".PHP_EOL;
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
    $reader = new IO_ICC_Bit();
    $reader->input($iccdata);
    //
    $reader->setOffset(8, 0);
    $majorVersion = $reader->getUIBCD8();
    $minorVersion = $reader->getUIBCD8();
    //
    echo "$iccfile: {$majorVersion}.{$minorVersion}".PHP_EOL;
}

exit(0);
