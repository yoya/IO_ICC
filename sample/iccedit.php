<?php
/*
  ICC edit tool
  (c) 2015/08/06- yoya@awm.jp
*/

if (is_readable('vendor/autoload.php')) {
    require 'vendor/autoload.php';
} else {
    require 'IO/ICC/Editor.php';
}

if ($argc < 2) {
    echo "Usage: php iccedit.php <icc_file> [<sig> [<key>:<value> [...]]]\n";
    echo "ex) php iccedit.php test.icc\n";
    echo "ex) php iccedit.php test.icc desc\n";
    echo "    php iccedit.php test.icc desc ascii:foobaa\n";
    echo "ex) php iccedit.php test.icc gTRC\n";
    echo "    php iccedit.php test.icc gTRC CurveValues:0.82\n";
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
        if ((! is_array($value)) && (! ($value instanceof ArrayAccess))) {
            echo "$key_prefix$key:$value".PHP_EOL;
        } else if ((isset($value[0])) && (! is_array($value[0])) && (! ($value[0] instanceof ArrayAccess))) {
            echo "$key_prefix$key:".implode(",", (array) $value).PHP_EOL;
        } else {
            displayKeyValue($value, "$key_prefix$key.");
        }
    }
}

function setKeyValue($keys, $value, &$data) {
    $key = $keys[0];
    if (isset($data->$key))  {
        $next_data = & $data->$key;
    } else if (isset($data[$key]))  {
        $next_data = & $data[$key];
    } else {
        fprintf(STDERR, "key($key) not found\n");
        print_r($data);
        return false;
    }
    if (count($keys) === 1) {
        //        echo "setKeyValue(".join('.',$keys).", $value)";
        switch (gettype($next_data))  {
        case "boolean":
            $value = $value?true:false;
            break;
        case "integer":
            $value = intval($value);
            break;
        case "double":
            $value = floatval($value);
            break;
        case "object":
            if (! ($next_data instanceof SplFixedArray)) {
                break;
            }
            // no break;
        case "array":
            $value = explode(',', $value);
            break;
        }
        $next_data = $value;
    } else {
        array_shift($keys);
        $ret = setKeyValue($keys, $value, $next_data);
        return $ret;
    }
    return true;
}

if ($argc == 3) {
        displayKeyValue($data, "");
} else {
    foreach (array_slice($argv, 3) as $kv) {
        list($key, $value) = explode(':', $kv);
        $keys = explode('.', $key);
        $ret = setKeyValue($keys, $value, $data);
        if ($ret !== true) {
            fprintf(STDERR, "setKeyValue($key, ...) failed\n");
            exit(1);
        }
    }
    $tag->content = null; // rebuild
    echo $icc->build();
}


exit(0);
