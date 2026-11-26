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

/*
 * writer padding must be NUL bytes
 */
$b = new IO_ICC_Bit();
$b->putData("abcde");
$b->nByteAlign(4, "\0");
check('nByteAlign writer padding', '6162636465000000', bin2hex($b->output()));
$curv = "curv\0\0\0\0".pack('N', 1).pack('n', 0x0233); // 14 bytes
$xyz = "XYZ \0\0\0\0".s15(0.9642).s15(1.0).s15(0.8249);
$icc = new IO_ICC_Editor();
$icc->parse(icc(array(array('rTRC', $curv), array('wtpt', $xyz))));
$out = @$icc->build();
check('build padding between tags', '0000', bin2hex(substr($out, 128 + 4 + 24 + 14, 2)));

/*
 * build must not raise diagnostics (dynamic property deprecation)
 */
$errors = array();
set_error_handler(function ($no, $str) use (&$errors) {
    $errors []= $str;
    return true;
});
$icc = new IO_ICC_Editor();
$icc->parse(icc(array(array('rTRC', $curv))));
$icc->build();
restore_error_handler();
check('build without diagnostics', array(), $errors);

/*
 * mAB / mBA: B and M curves must not depend on the matrix offset
 */
$identity = "curv\0\0\0\0".pack('N', 0); // 12 bytes
$matrix = s15(1).s15(0).s15(0).s15(0).s15(1).s15(0).s15(0).s15(0).s15(1).s15(0).s15(0).s15(0);
foreach (array('A2B0' => 'mAB ', 'B2A0' => 'mBA ') as $sig => $type) {
    $name = trim($type);
    $hdr = $type."\0\0\0\0".chr(3).chr(3)."\0\0";
    // B curves only (no matrix)
    $lut = $hdr.pack('N5', 32, 0, 0, 0, 0).$identity.$identity.$identity;
    try {
        $t = parseTag($sig, $lut);
        check("$name B curves only", 3, count($t->bCurves));
        check("$name B curves only rebuild", bin2hex($lut), bin2hex($t->buildContent()));
    } catch (Exception $e) {
        check("$name B curves only", 'no exception', get_class($e).': '.$e->getMessage());
    }
    // M curves, matrix, B curves (B placed after the matrix)
    $offM = 32; $offMat = $offM + 36; $offB = $offMat + 48;
    $lut = $hdr.pack('N5', $offB, $offMat, $offM, 0, 0).$identity.$identity.$identity.$matrix.$identity.$identity.$identity;
    try {
        $t = parseTag($sig, $lut);
        check("$name B after matrix", array(3, 3), array(count($t->bCurves), count($t->mCurves)));
    } catch (Exception $e) {
        check("$name B after matrix", 'no exception', get_class($e).': '.$e->getMessage());
    }
}

/*
 * mAB / mBA build: the matrix must start on a 4-byte boundary
 */
$gamma = "curv\0\0\0\0".pack('N', 1).pack('n', 0x0233); // 14 bytes
foreach (array('A2B0' => 'mAB ', 'B2A0' => 'mBA ') as $sig => $type) {
    $lut = $type."\0\0\0\0".chr(3).chr(3)."\0\0".pack('N5', 32, 80, 0, 0, 0).
        $gamma."\0\0".$gamma."\0\0".$gamma."\0\0".$matrix;
    $t = parseTag($sig, $lut);
    $offsets = unpack('N2', substr($t->buildContent(), 12, 8));
    check(trim($type).' matrix offset aligned', 80, $offsets[2]);
}

echo "passed: $passed, failed: $failed".PHP_EOL;
exit($failed ? 1 : 0);
