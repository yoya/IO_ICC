<?php
/*
  ICC deltags tool
  (c) 2018/11/22- yoya@awm.jp
*/

if (is_readable('vendor/autoload.php')) {
    require 'vendor/autoload.php';
} else {
    require 'IO/ICC/Editor.php';
}

if ($argc < 2) {
    echo "Usage: php iccdeltags.php <icc_file> [tagsig1 [tagsig2 [...]]]\n";
    echo "ex) php iccdeltags rgb.icc # list tags\n";
    echo "ex) php iccdeltags rgb.icc B2A0 B2A1 > onlyA2B.icc # execute tags removing\n";
    exit(1);
}

assert(is_readable($argv[1]));

$iccdata = file_get_contents($argv[1]);
$deltags = array_slice($argv, 2);
$icc = new IO_ICC_Editor();

$icc->parse($iccdata);

if (count($deltags) === 0) {
    foreach ($icc->_tags as $idx => &$tag) {
        $sig = $tag->signature;
        echo "$sig".PHP_EOL;
    }
    exit(0);
}


foreach ($deltags as $sig) {
    $icc->deleteTag($sig);
}

$icc->rebuild();

echo $icc->build();

exit(0);
