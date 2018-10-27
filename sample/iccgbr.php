<?php

require 'IO/ICC/Editor.php';

if ($argc != 2) {
    echo "Usage: php iccgbr.php <icc_file>\n";
    echo "ex) php iccgbr.php rgb.icc > gbr.icc\n";
    exit(1);
}

assert(is_readable($argv[1]));

$iccdata = file_get_contents($argv[1]);

$icc = new IO_ICC_Editor();

$icc->parse($iccdata);

$rgbXYZtags = [];
foreach ($icc->_tags as $idx => &$tag) {
    $sig = $tag->signature;
    switch ($sig) {
    case 'rXYZ':
    case 'gXYZ':
    case 'bXYZ':
        $tag->parseTagContent();
        $rgbXYZtags[$sig] = $tag->tag->xyz;
        break;
    }
}

foreach ($icc->_tags as $idx => &$tag) {
    $sig = $tag->signature;
    switch ($sig) {
    case 'rXYZ':
        $tag->tag->xyz = $rgbXYZtags["gXYZ"];
        break;
    case 'gXYZ':
        $tag->tag->xyz = $rgbXYZtags["bXYZ"];
        break;
    case 'bXYZ':
        $tag->tag->xyz = $rgbXYZtags["rXYZ"];
        break;
    }
}

$icc->rebuild();

echo $icc->build();

exit(0);
