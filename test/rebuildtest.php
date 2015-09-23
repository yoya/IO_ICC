<?php

require 'IO/ICC/Editor.php';
require __DIR__.'/verifyCheckICC.php';

if ($argc != 2) {
    echo "Usage: php rebuildtest.php <icc_file>\n";
    echo "ex) php rebuildtest.php test.icc\n";
    exit(1);
}

assert(is_readable($argv[1]));

$iccdata = file_get_contents($argv[1]);

$icc1 = new IO_ICC_Editor();
$icc1->parse($iccdata);
$icc1->rebuild();
$icc1->build();

$icc2 = new IO_ICC_Editor();
$icc2->parse($iccdata);
$icc2->rebuild();
$iccdata2 = $icc2->build();

$icc3 = new IO_ICC_Editor();
$icc3->parse($iccdata2);
$icc3->rebuild();
$icc3->build();
verifyCheckICC($icc1, $icc3);

exit(0);
