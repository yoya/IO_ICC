<?php

$threshold = 10;

require 'IO/ICC/Editor.php';

if ($argc < 2) {
    echo "Usage: php icclisttags.php <icc_file> [<icc_file2> [...]]\n";
    echo "ex) php icclisttags.php test.icc\n";
    exit(1);
}


$iccFiles = array_slice($argv, 1);


foreach ($iccFiles as $iccfile) {
    assert(is_readable($iccfile));
    echo "$iccfile".PHP_EOL;
    $iccdata = file_get_contents($iccfile);
    $icc = new IO_ICC_Editor();

    $icc->parse($iccdata);

    foreach ($icc->_tags as $idx => $tag){
        $sig = $tag->signature;
        if (isset($tag->tag->type)) {
            $type = $tag->tag->type;
        } else {
            $type = substr($tag->content, 0, 4);
        }
        $tagInfo = $tag->tagInfo;
        $offset = $tagInfo['Offset'];
        $size = $tagInfo['Size'];
        $idxStr = sprintf("%02d", $idx);
        $version = IO_ICC_Type::getTypeInfo($type, 'version');
        if ($version === false) {
            $version = '(unknown)';
        }
        echo "    [$idxStr] sig:$sig type:$type offset:$offset size:$size version:$version".PHP_EOL;
    }
}

exit(0);
