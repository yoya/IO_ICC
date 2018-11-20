<?php

function verifyCheckICC($icc1, $icc2) {
    verifyCheckICC_recursive($icc1, $icc2, array());
}

function verifyCheckICC_recursive($icc1, $icc2, $key_list) {
    $exclude_key_list = array('_iccdata', '_tagTable', '_headerType',
                              'content', 'iccInfo', 'tagInfo');
    if (gettype($icc1) !== gettype($icc2)) {
        echo "icc1:".join(".",$key_list);
        print_r($icc1).PHP_EOL;
        echo "icc2:".join(".",$key_list);
        print_r($icc2).PHP_EOL;
        return ;
    }
    if (is_null($icc1)) {
        ;
    } else if (is_array($icc1) || is_object($icc1)) {
        if (! is_array($icc1)) {
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
