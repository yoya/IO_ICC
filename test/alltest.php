<?php

require 'IO/ICC/Editor.php';
require __DIR__.'/verifyCheckICC.php';

if ($argc < 1) {
    echo "Usage: php alltest.php [iccfile [iccfile2 [...]]]\n";
    echo "ex) php alltest.php\n";
    echo "ex) php alltest.php sRGB.icc AdobeRGB.icc\n";
    exit(1);
}

$opts = array('restrict' => true);

$iccdir = dirname(__FILE__);
$dh = opendir($iccdir);

function null_callback() {
    return NULL;
}

$files = array();

if ($argc < 2) {
    while (($file = readdir($dh)) !== false) {
        if (preg_match('/\.icc$/i', $file, $matches) === 0) {
            continue;
        }
        $files []= "$iccdir/$file";
    }
} else {
    $files = array_slice($argv, 1);
}

foreach ($files as $file) {
    echo "$file".PHP_EOL;
    $iccdata = file_get_contents($file);

    echo "    test1.".PHP_EOL;
    $icc1 = new IO_ICC_Editor();
    $icc1->parse($iccdata);
    ob_start('null_callback');
    $icc1->dump();
    ob_end_clean();
    $iccdata2 = $icc1->build();
    $icc1->rebuild($opts);
    $iccdata3 = $icc1->build();
    //
    echo "    test2.".PHP_EOL;
    $icc2 = new IO_ICC_Editor();
    $icc2->parse($iccdata2);
    ob_start('null_callback');
    $icc2->dump();
    ob_end_clean();
    $icc2->build();
    $icc2->rebuild($opts);
    $icc2->build();
    //
    echo "    test3.".PHP_EOL;
    $icc3 = new IO_ICC_Editor();
    $icc3->parse($iccdata3);
    ob_start('null_callback');
    $icc3->dump();
    ob_end_clean();
    $icc3->build();
    $icc3->rebuild($opts);
    $icc3->build();
    verifyCheckICC($icc1, $icc3);
}

exit (0);
