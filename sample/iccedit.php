<?php

require 'IO/ICC/Editor.php';

if ($argc < 2) {
    echo "Usage: php iccedit.php <icc_file> [<sig> [<key>:<value> [...]]]\n";
    echo "ex) php iccedit.php test.icc\n";
    echo "ex) php iccedit.php test.icc desc\n";
    echo "ex) php iccedit.php test.icc desc ascii:foobaa\n";
    exit(1);
}

function get_tag_idx($icc, $sig) {
    foreach ($icc->_tags as $idx => &$tag) {
        if ($sig === $tag->signature) {
            return $idx;
        }
    }
    return false;
}

assert(is_readable($argv[1]));

$iccdata = file_get_contents($argv[1]);

$icc = new IO_ICC();

$icc->parse($iccdata);

if ($argc == 2) {
    echo "header".PHP_EOL;
    foreach ($icc->_tags as $tag) {
        echo $tag->signature.":".$tag->type.PHP_EOL;
    }
    exit (0);
}

$sig = $argv[2];

$data = null;
if ($sig === 'header') {
    $data = & $icc->_header;
} else {
    $idx = get_tag_idx($icc, $sig);
    if ($idx === false) {
        echo "signature($sig) not found\n";
        exit(1);
    }
    $tag = &$icc->_tags[$idx];
    if (is_null($tag->tag)) {
        $tag->parseTagContent();
    }
    $data = & $tag->tag;
}

function displayKeyValue($key_value, $key_prefix) {
    foreach ($key_value as $key => $value) {
        if ($key[0] === "_") {
            continue; // skip internal value
        }
        if (is_array($value) === false) {
            echo "$key_prefix$key:$value".PHP_EOL;
        } else if ((isset($value[0])) && (is_array($value[0]) === false)) {
            echo "$key_prefix$key:".implode(",", $value).PHP_EOL;
        } else {
            displayKeyValue($value, "$key_prefix$key.");
        }
    }
}

function setKeyValue($keys, $value, &$data) {
    $key = $keys[0];
    if (count($keys) === 1) {
        //        echo "setKeyValue(".join('.',$keys).", $value)";
        switch (gettype($data->$key))  {
        case "boolean":
            $value = $value?true:false;
            break;
        case "integer":
            $value = intval($value);
            break;
        case "double":
            $value = floatval($value);
            break;
        case "array":
            $value = explode(',', $value);
            break;
        }
        $data->$key = $value;
    } else {
        array_shift($keys);
        setKeyValue($keys, $value, $data->$key);
    }
}

if ($argc == 3) {
        displayKeyValue($data, "");
} else {
    foreach (array_slice($argv, 3) as $kv) {
        list($key, $value) = explode(':', $kv);
        $keys = explode('.', $key);
        setKeyValue($keys, $value, $data);
    }
    $tag->content = null; // rebuild
    echo $icc->build();
}


exit(0);
