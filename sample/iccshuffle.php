<?php

$threshold = 10;

require 'IO/ICC/Editor.php';

if ($argc != 2) {
    echo "Usage: php iccshuffle.php <icc_file>\n";
    echo "ex) php iccshuffle.php test.icc\n";
    exit(1);
}

assert(is_readable($argv[1]));

$iccdata = file_get_contents($argv[1]);

$icc = new IO_ICC_Editor();

$icc->parse($iccdata);
echo $icc->rebuild();
foreach ($icc->_tags as &$tag){
    shuffleICC($tag, $threshold);
}
echo $icc->build();

exit(0);

// subroutine

function shuffleICC(&$icc, $threshold) {
    shuffleICC_recursive($icc, $threshold);
}

function shuffleICC_recursive(&$iccNode, $threshold) {
    if (is_null($iccNode)) {
        return ;
    }
    $isFixedArray = is_object($iccNode) && (get_class($iccNode) === 'IO_ICC_FixedArray');
    if (is_object($iccNode) && ($isFixedArray === false)) {
        $keys = array_keys((array)$iccNode);
        foreach ($keys as $key) {
            shuffleICC_recursive($iccNode->$key, $threshold);
        }
    }
    if (is_array($iccNode) || $isFixedArray) {
        if ($threshold <= count($iccNode)) {
            if ($isFixedArray) {
                $iccNode->shuffle();
            } else if (is_assoc($iccNode) === false) {
                shuffle($iccNode);
            }
        }
    }
    if (is_array($iccNode)) {
        foreach ($iccNode as $key => $value) {
            shuffleICC_recursive($iccNode[$key], $threshold);
        }
    }
}

// http://stackoverflow.com/questions/5996749/determine-whether-an-array-is-associative-hash-or-not
function is_assoc($arr)
{
    // Keys of the array
    $keys = array_keys($arr);

    // If the array keys of the keys match the keys, then the array must
    // not be associative (e.g. the keys array looked like {0:0, 1:1...}).
    return array_keys($keys) !== $keys;
}
