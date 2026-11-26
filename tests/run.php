<?php
/*
 * regression tests (no dependency)
 * usage: php tests/run.php
 *   IO_Bit is loaded from vendor/ (composer install) or include_path.
 */

$root = dirname(dirname(__FILE__));
if (is_readable($root.'/vendor/autoload.php')) {
    require_once $root.'/vendor/autoload.php';
}
set_include_path($root.PATH_SEPARATOR.get_include_path());
require_once 'IO/ICC/Editor.php';

$failed = 0;
$passed = 0;

function check($name, $expect, $actual) {
    global $failed, $passed;
    if ($expect === $actual) {
        $passed++;
        return;
    }
    $failed++;
    echo "FAIL: $name".PHP_EOL;
    echo "  expect: ".var_export($expect, true).PHP_EOL;
    echo "  actual: ".var_export($actual, true).PHP_EOL;
}

function s15($v) {
    return pack('N', (int) round($v * 65536) & 0xffffffff);
}

// minimal v4.3 ICC profile: $tags = array(array(sig, content), ...)
function icc($tags) {
    $h  = pack('N', 0)."lcms"."\x04\x30\0\0"."mntr"."RGB "."XYZ ";
    $h .= pack('n6', 2020, 1, 2, 3, 4, 5)."acsp"."APPL";
    $h .= str_repeat("\0", 20).pack('N', 0).s15(0.9642).s15(1.0).s15(0.8249);
    $h  = str_pad($h, 128, "\0");
    $off = 128 + 4 + 12 * count($tags);
    $table = pack('N', count($tags));
    $body = '';
    foreach ($tags as $t) {
        while (($off + strlen($body)) % 4) {
            $body .= "\0";
        }
        $table .= $t[0].pack('N2', $off + strlen($body), strlen($t[1]));
        $body .= $t[1];
    }
    while (($off + strlen($body)) % 4) {
        $body .= "\0";
    }
    $d = $h.$table.$body;
    return substr_replace($d, pack('N', strlen($d)), 0, 4);
}

function parseTag($sig, $content) {
    $icc = new IO_ICC_Editor();
    $icc->parse(icc(array(array($sig, $content))));
    $tag = $icc->_tags[0];
    $tag->parseTagContent();
    return $tag->tag;
}

/*
 * measurementType: observer, XYZ, geometry, flare, illuminant (36 bytes)
 */
$meas = "meas\0\0\0\0".pack('N', 1).s15(0).s15(0).s15(0).pack('N', 2).pack('N', 0x10000).pack('N', 2);
$t = parseTag('meas', $meas);
check('meas illuminant', 2, $t->stdIlluminant);
check('meas flare', 0x10000, $t->measureFlare);
check('meas rebuild', bin2hex($meas), bin2hex($t->buildContent()));

echo "passed: $passed, failed: $failed".PHP_EOL;
exit($failed ? 1 : 0);
