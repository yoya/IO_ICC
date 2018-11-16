<?php
/*
  ICC dump tool
  (c) 2015/08/02- yoya@awm.jp
*/

if (is_readable('vendor/autoload.php')) {
    require 'vendor/autoload.php';
} else {
    require_once 'IO/ICC.php';
}

$options = getopt("f:hdr");

function usage() {
    echo "Usage: php iccdump.php [-h] [-d] -f <iccfile>".PHP_EOL;
}

if ((isset($options['f']) === false) ||
    (is_readable($options['f']) === false)) {
    usage();
    exit(1);
}
$opts = array();

if (isset($options['h'])) {
  $opts['hexdump'] = true;
}
if (isset($options['d'])) {
  $opts['detail'] = true;
}
if (isset($options['r'])) {
  $opts['restrict'] = true;
}


$iccfile = $options['f'];
$iccdata = file_get_contents($iccfile);

$icc = new IO_ICC();
$icc->parse($iccdata);

$icc->dump($opts);

exit(0);
