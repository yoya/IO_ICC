<?php
/*
 * generates the synthetic test data in this directory (ICC.1:2010 / ICC.1:2001-04)
 * usage: php tests/data/make.php
 *   every run writes the same bytes. No library code is used.
 *   tests/run.php includes this file and compares makeFiles() with the files.
 *
 * layout: header, tag table, tag data in table order (4-byte aligned,
 * NUL padding), a tag with the same data as an earlier one shares it.
 */

$dir = dirname(__FILE__);

function s15Fixed16($v) { // s15Fixed16Number
    return pack('N', ((int) round($v * 65536)) & 0xffffffff);
}
function xyzNumber($x, $y, $z) {
    return s15Fixed16($x).s15Fixed16($y).s15Fixed16($z);
}
function pad4($s) {
    return str_pad($s, (strlen($s) + 3) & ~3, "\0");
}

/*
 * tag types
 */
function xyzType($x, $y, $z) {
    return "XYZ \0\0\0\0".xyzNumber($x, $y, $z);
}
function curvType($values) { // 1 value: gamma (u8Fixed8Number), else UInt16 table
    $out = "curv\0\0\0\0".pack('N', count($values));
    if (count($values) === 1) {
        return $out.pack('n', (int) round($values[0] * 256));
    }
    foreach ($values as $v) {
        $out .= pack('n', $v);
    }
    return $out;
}
function paraType($functionType, $params) {
    $out = "para\0\0\0\0".pack('n', $functionType)."\0\0";
    foreach ($params as $p) {
        $out .= s15Fixed16($p);
    }
    return $out;
}
function textType($text) {
    return "text\0\0\0\0".$text."\0";
}
// textDescriptionType (v2): ASCII, Unicode (UCS-2BE) and ScriptCode (67 bytes) parts
function descType($ascii, $langCode, $unicode, $scriptCode, $mac) {
    $out = "desc\0\0\0\0".pack('N', strlen($ascii) + 1).$ascii."\0";
    $ucs2 = ($unicode === null) ? '' : mb_convert_encoding($unicode, 'UCS-2BE', 'UTF-8')."\0\0";
    $out .= $langCode.pack('N', strlen($ucs2) / 2).$ucs2;
    $macBytes = ($mac === null) ? '' : $mac."\0";
    return $out.pack('nC', $scriptCode, strlen($macBytes)).str_pad($macBytes, 67, "\0");
}
// multiLocalizedUnicodeType (v4): [[lang, country, string], ...]
function mlucType($records) {
    $out = "mluc\0\0\0\0".pack('NN', count($records), 12);
    $offset = 16 + 12 * count($records);
    $strings = '';
    foreach ($records as $r) {
        $ucs2 = mb_convert_encoding($r[2], 'UCS-2BE', 'UTF-8');
        $out .= $r[0].$r[1].pack('NN', strlen($ucs2), $offset + strlen($strings));
        $strings .= $ucs2;
    }
    return $out.$strings;
}
function sigType($sig) {
    return "sig \0\0\0\0".$sig;
}
function sf32Type($values) {
    $out = "sf32\0\0\0\0";
    foreach ($values as $v) {
        $out .= s15Fixed16($v);
    }
    return $out;
}
// measurementType: observer, backing XYZ, geometry, flare (u16Fixed16), illuminant
function measType($observer, $xyz, $geometry, $flare, $illuminant) {
    return "meas\0\0\0\0".pack('N', $observer).xyzNumber($xyz[0], $xyz[1], $xyz[2])
        .pack('NNN', $geometry, (int) round($flare * 65536), $illuminant);
}
// viewingConditionsType: illuminant XYZ, surround XYZ, illuminant type
function viewType($illuminant, $surround, $type) {
    return "view\0\0\0\0".xyzNumber($illuminant[0], $illuminant[1], $illuminant[2])
        .xyzNumber($surround[0], $surround[1], $surround[2]).pack('N', $type);
}
// lut8Type / lut16Type: matrix, input tables, CLUT (grid^nIn x nOut), output tables
function lutType($bytes, $nIn, $nOut, $grid, $matrix, $inEntries, $outEntries, $seed) {
    $fmt = ($bytes === 1) ? 'C' : 'n';
    $max = ($bytes === 1) ? 0xff : 0xffff;
    $out = (($bytes === 1) ? "mft1" : "mft2")."\0\0\0\0".pack('CCCC', $nIn, $nOut, $grid, 0);
    foreach ($matrix as $v) {
        $out .= s15Fixed16($v);
    }
    if ($bytes === 2) {
        $out .= pack('nn', $inEntries, $outEntries);
    }
    foreach (lutValues($nIn * $inEntries, $max, $seed) as $v) {
        $out .= pack($fmt, $v);
    }
    foreach (lutValues(pow($grid, $nIn) * $nOut, $max, $seed + 1) as $v) {
        $out .= pack($fmt, $v);
    }
    foreach (lutValues($nOut * $outEntries, $max, $seed + 2) as $v) {
        $out .= pack($fmt, $v);
    }
    return $out;
}
function lutValues($n, $max, $seed) {
    $list = [];
    for ($i = 0 ; $i < $n ; $i++) {
        $list[] = ($seed * 7919 + $i * 104729) % ($max + 1);
    }
    return $list;
}
/*
 * lutAtoBType / lutBtoAType: [B curves, matrix (12), M curves, CLUT, A curves],
 * any of them null; written in this order (4-byte aligned) with the offsets
 * CLUT: [grid points per input, precision 1/2, values]
 */
function lutABType($type, $nIn, $nOut, $b, $matrix, $m, $clut, $a) {
    $body = '';
    $offsets = [];
    $put = function ($elements) use (&$body) {
        $body = pad4($body);
        $offset = 32 + strlen($body);
        foreach ($elements as $e) {
            $body = pad4($body).$e;
        }
        return $offset;
    };
    $offsets[] = ($b === null) ? 0 : $put($b);
    if ($matrix === null) {
        $offsets[] = 0;
    } else {
        $offsets[] = $put([implode('', array_map('s15Fixed16', $matrix))]);
    }
    $offsets[] = ($m === null) ? 0 : $put($m);
    if ($clut === null) {
        $offsets[] = 0;
    } else {
        list($grid, $precision, $values) = $clut;
        $c = str_pad(pack('C*', ...$grid), 16, "\0").pack('C', $precision)."\0\0\0";
        foreach ($values as $v) {
            $c .= pack(($precision === 1) ? 'C' : 'n', $v);
        }
        $offsets[] = $put([$c]);
    }
    $offsets[] = ($a === null) ? 0 : $put($a);
    return $type."\0\0\0\0".pack('CCn', $nIn, $nOut, 0).pack('N5', ...$offsets).$body;
}

/*
 * profile: $header fields, $tags [[sig, data], ...]
 */
function makeICC($h, $tags) {
    $out = pack('N', 0).$h['cmm'].pack('CC', $h['major'], $h['minor'])."\0\0"
        .$h['class'].$h['space'].$h['pcs'].pack('n6', ...$h['date'])."acsp".$h['platform']
        .pack('NNN', $h['flags'], $h['manufacturer'], $h['model'])
        .pack('NN', $h['attrVendor'], $h['attr']).pack('N', $h['intent'])
        .xyzNumber(0.9642, 1.0, 0.8249).$h['creator'];
    $out = str_pad($out, 128, "\0"); // Profile ID (filled below) and reserved
    $table = pack('N', count($tags));
    $offset = 128 + 4 + 12 * count($tags);
    $body = '';
    $placed = []; // data => [offset, size]
    foreach ($tags as $t) {
        list($sig, $data) = $t;
        if (! isset($placed[$data])) {
            $body = pad4($body);
            $placed[$data] = [$offset + strlen($body), strlen($data)];
            $body .= $data;
        }
        $table .= $sig.pack('NN', $placed[$data][0], $placed[$data][1]);
    }
    $out = pad4($out.$table.$body);
    $out = substr_replace($out, pack('N', strlen($out)), 0, 4);
    if ($h['id']) { // MD5 with profile flags, rendering intent and profile ID set to 0
        $z = substr_replace($out, "\0\0\0\0", 44, 4);
        $z = substr_replace($z, "\0\0\0\0", 64, 4);
        $out = substr_replace($out, md5($z, true), 84, 16);
    }
    return $out;
}

function makeFiles() {
    $files = [];
    /*
     * v2.1 RGB display profile
     */
    $gamma = curvType([2.2]);
    $files['v2-rgb.icc'] = makeICC(
        ['cmm' => "ioic", 'major' => 2, 'minor' => 0x10, 'class' => 'mntr', 'space' => 'RGB ', 'pcs' => 'XYZ ',
         'date' => [2026, 11, 28, 1, 2, 3], 'platform' => 'APPL', 'flags' => 0, 'manufacturer' => 0x74657374,
         'model' => 1, 'attrVendor' => 0, 'attr' => 0, 'intent' => 0, 'creator' => 'ioic', 'id' => false], [
        ['desc', descType("v2 test", "enUS", "v2 テスト", 0, "mac")],
        ['cprt', textType("No copyright, synthetic data")],
        ['wtpt', xyzType(0.9642, 1.0, 0.8249)],
        ['bkpt', xyzType(0, 0, 0)],
        ['rXYZ', xyzType(0.4361, 0.2225, 0.0139)],
        ['gXYZ', xyzType(0.3851, 0.7169, 0.0971)],
        ['bXYZ', xyzType(0.1431, 0.0606, 0.7141)],
        ['rTRC', $gamma],                                   // rTRC and gTRC share the data
        ['gTRC', $gamma],
        ['bTRC', curvType([0, 1000, 30000, 65535])],
        ['tech', sigType('vidm')],
        ['meas', measType(1, [0, 0, 0], 1, 1.0, 2)],
        ['view', viewType([19.6445, 20.3718, 16.8089], [3.9289, 4.0744, 3.3618], 1)],
        ['chad', sf32Type([1.0479, 0.0229, -0.0502, 0.0296, 0.9904, -0.0171, -0.0092, 0.0151, 0.7519])],
        ['A2B0', lutType(1, 3, 3, 2, [1, 0, 0, 0, 1, 0, 0, 0, 1], 256, 256, 1)],
        ['B2A0', lutType(2, 3, 3, 2, [1.5, 0, 0, 0, 1, 0, 0, 0, -0.5], 2, 3, 4)],
        ['zzzz', "abcd\0\0\0\0\1\2\3\4\5"],                 // unknown type, 13 bytes
        ]);
    /*
     * v4.3 RGB display profile: flags, attributes, Profile ID
     */
    $files['v4-rgb.icc'] = makeICC(
        ['cmm' => "ioic", 'major' => 4, 'minor' => 0x30, 'class' => 'mntr', 'space' => 'RGB ', 'pcs' => 'XYZ ',
         'date' => [2026, 1, 2, 23, 59, 58], 'platform' => 'MSFT',
         'flags' => 0x00000001 | 0x00000004 | (0xBEEF << 16), // embedded, reserved bit 2, vendor bits
         'manufacturer' => 0x74657374, 'model' => 0x12345678,
         'attrVendor' => 0xCAFEF00D, 'attr' => 0x00000002 | 0x00000008 | 0x00000100, // matte, B/W, reserved bit 8
         'intent' => 1, 'creator' => 'ioic', 'id' => true], [
        ['desc', mlucType([['en', 'US', "v4 test"], ['ja', 'JP', "テスト"]])],
        ['cprt', mlucType([['en', 'US', "No copyright"]])],
        ['wtpt', xyzType(0.9642, 1.0, 0.8249)],
        ['rXYZ', xyzType(0.4361, 0.2225, 0.0139)],
        ['gXYZ', xyzType(0.3851, 0.7169, 0.0971)],
        ['bXYZ', xyzType(0.1431, 0.0606, 0.7141)],
        ['rTRC', paraType(0, [2.4])],
        ['gTRC', paraType(3, [2.4, 1 / 1.055, 0.055 / 1.055, 1 / 12.92, 0.04045])],
        ['bTRC', paraType(4, [2.2, 0.9, 0.1, 0.5, 0.2, -0.01, 0.02])],
        ['chad', sf32Type([1.0479, 0.0229, -0.0502, 0.0296, 0.9904, -0.0171, -0.0092, 0.0151, 0.7519])],
        ['A2B0', lutABType('mAB ', 3, 3,
                           [curvType([]), paraType(1, [1.8, 1.1, -0.1]), paraType(2, [2.0, 1.0, 0.0, 0.05])],
                           [1, 0, 0, 0, 1, 0, 0, 0, 1, 0.1, -0.2, 0.3],
                           [paraType(0, [1.0]), paraType(0, [1.5]), paraType(0, [0.5])],
                           [[2, 3, 2], 2, lutValues(2 * 3 * 2 * 3, 0xffff, 7)],
                           [curvType([1.0]), curvType([0, 65535]), curvType([])])],
        ['B2A0', lutABType('mBA ', 3, 3,
                           [paraType(0, [1.0]), paraType(0, [1.0]), paraType(0, [1.0])],
                           [2, 0, 0, 0, 2, 0, 0, 0, 2, 0, 0, 0],
                           [curvType([]), curvType([]), curvType([])],
                           [[2, 2, 2], 1, lutValues(8 * 3, 0xff, 8)],
                           [curvType([0, 32768, 65535]), paraType(3, [2.4, 1 / 1.055, 0.055 / 1.055, 1 / 12.92, 0.04045]),
                            curvType([2.2])])],
        ['A2B1', lutABType('mAB ', 3, 3, [curvType([]), curvType([]), curvType([])], null, null, null, null)],
        ['meas', measType(2, [0.1, 0.2, 0.3], 2, 0, 8)],
        ['meta', "dict\0\0\0\0".pack('NN', 0, 16)],         // dictType: no class in the library
        ]);
    /*
     * v4.2 gray input profile, Lab PCS (known tag types only)
     */
    $files['v4-gray.icc'] = makeICC(
        ['cmm' => "\0\0\0\0", 'major' => 4, 'minor' => 0x20, 'class' => 'scnr', 'space' => 'GRAY', 'pcs' => 'Lab ',
         'date' => [2000, 12, 31, 0, 0, 0], 'platform' => "\0\0\0\0", 'flags' => 2, 'manufacturer' => 0,
         'model' => 0, 'attrVendor' => 0, 'attr' => 1, 'intent' => 3, 'creator' => "\0\0\0\0", 'id' => true], [
        ['desc', mlucType([['en', 'US', "gray"]])],
        ['wtpt', xyzType(0.9642, 1.0, 0.8249)],
        ['kTRC', curvType([0, 16384, 32768, 49152, 65535])],
        ]);
    // cut in the header, and in the data of the last tag
    $files['truncated-header.icc'] = substr($files['v4-gray.icc'], 0, 100);
    $files['truncated-tag.icc'] = substr($files['v4-gray.icc'], 0, strlen($files['v4-gray.icc']) - 4);
    return $files;
}

if (realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {
    foreach (makeFiles() as $name => $data) {
        file_put_contents("$dir/$name", $data);
    }
}
