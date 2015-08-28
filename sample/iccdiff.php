<?php
/*
  ICC diff tool
  (c) 2015/08/27- yoya@awm.jp
*/

require 'IO/ICC.php';

if ($argc != 3) {
    echo "Usage: php iccdiff.php <icc_file1> <icc_file2>\n";
    echo "ex) php iccdiff.php sRGB.icc AdobeRGB.icc \n";
    exit(1);
}

assert(is_readable($argv[1]));
assert(is_readable($argv[2]));

$iccdata1 = file_get_contents($argv[1]);
$iccdata2 = file_get_contents($argv[2]);

$icc1 = new IO_ICC();
$icc1->parse($iccdata1);

$icc2 = new IO_ICC();
$icc2->parse($iccdata2);

verifyCheckICC($icc1, $icc2);

exit(0);

function verifyCheckICC($icc1, $icc2) {
    verifyCheckICC_recursive($icc1, $icc2, array());
}

function verifyCheckICC_recursive($icc1, $icc2, $key_list) {
    $exclude_key_list = array('_iccdata', '_tagTable', '_headerType',
                              'content', 'iccInfo', 'tagInfo');
    // 'ProfileSize', '_contentLength');
    if (gettype($icc1) !== gettype($icc2)) {
        echo "icc1:".join(".",$key_list);
        print_r($icc2).PHP_EOL;
        echo "icc2:".join(".",$key_list);
        print_r($icc2).PHP_EOL;
        return ;
    }
    if (is_null($icc1)) {
        ;
    } else if (is_array($icc1) || is_object($icc1)) {
        if (is_object($icc1)) {
            $icc1 = (array) $icc1;
            $icc2 = (array) $icc2;
        }
        foreach ($icc1 as $key => $value) {
            if (in_array($key, $exclude_key_list)) {
                // echo ":::: $key\n";
                continue; // skip internal value
            }
            $new_key_list = $key_list;
            $new_key_list []= $key;
            verifyCheckICC_recursive($icc1[$key], $icc2[$key], $new_key_list);
        }
    } else {
        if ($icc1 === $icc2) {
            ; //  echo "===:".join(".",$key_list).": $icc1, $icc2\n";
        } else {
            echo "XXX:".join(".",$key_list).": $icc1, $icc2\n";
        }
    }
}
