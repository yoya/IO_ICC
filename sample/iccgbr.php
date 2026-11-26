<?php
/*
  ICC gbr tool
  (c) 2015/08/05- yoya@awm.jp
*/

if (is_readable('vendor/autoload.php')) {
    require 'vendor/autoload.php';
} else {
    require 'IO/ICC/Editor.php';
}

if ($argc != 2) {
    echo "Usage: php iccgbr.php <icc_file>\n";
    echo "ex) php iccgbr.php rgb.icc > gbr.icc\n";
    exit(1);
}

assert(is_readable($argv[1]));

$iccdata = file_get_contents($argv[1]);

$icc = new IO_ICC_Editor();

$icc->parse($iccdata);

// The GBR profile reads channel 1,2,3 as the original G,B,R:
// (new rXYZ, gXYZ, bXYZ) = (old gXYZ, bXYZ, rXYZ), the TRCs likewise.

// A2B: CLUT input is the device RGB, so the grid axes are reordered.
function convertCLUT2GBR_A2B($grid, $data) {
    list($grid1, $grid2, $grid3) = $grid;
    $gridNum = $grid1 * $grid2 * $grid3;
    $arr = new IO_ICC_FixedArray($gridNum * 3); // XXX 3
    for ($i1= 0 ; $i1 < $grid1  ; $i1++) {
        for ($i2 = 0 ; $i2 < $grid2  ; $i2++) {
            for ($i3 = 0 ; $i3 < $grid3  ; $i3++) {
                $src_i = ((($i1 * $grid2) + $i2) * $grid3) + $i3;
                $dst_i = ((($i2 * $grid3) + $i3) * $grid1) + $i1;
                $arr[3*$dst_i + 0] = $data[3*$src_i + 0];
                $arr[3*$dst_i + 1] = $data[3*$src_i + 1];
                $arr[3*$dst_i + 2] = $data[3*$src_i + 2];
            }
        }
    }
    return [[$grid2, $grid3, $grid1], $arr];
}

// B2A: CLUT input is the PCS (grid unchanged), the output channels are reordered.
function convertCLUT2GBR_B2A($grid, $data) {
    list($grid1, $grid2, $grid3) = $grid;
    $gridNum = $grid1 * $grid2 * $grid3;
    $arr = new IO_ICC_FixedArray($gridNum * 3); // XXX 3
    for ($i = 0 ; $i < $gridNum  ; $i++) {
        $arr[3*$i + 0] = $data[3*$i + 1];
        $arr[3*$i + 1] = $data[3*$i + 2];
        $arr[3*$i + 2] = $data[3*$i + 0];
    }
    return $arr;
}

$rgbXYZtags = [];
$rgbTRCtags = [];
foreach ($icc->_tags as $idx => &$tag) {
    $sig = $tag->signature;
    switch ($sig) {
    case 'rXYZ':
    case 'gXYZ':
    case 'bXYZ':
        $tag->parseTagContent();
        $rgbXYZtags[$sig] = $tag->tag->xyz;
        break;
    case 'rTRC':
    case 'gTRC':
    case 'bTRC':
        $rgbTRCtags[$sig] = [$tag->content, $tag->type];
        break;
    case 'A2B0':
    case 'A2B1':
    case 'A2B2':
    case 'B2A0':
    case 'B2A1':
    case 'B2A2':
        $tag->parseTagContent();
        $lut = $tag->tag;
        // device side: A curves and CLUT (mAB: A->CLUT->M->Matrix->B, mBA: reverse)
        if ((($lut->type !== 'mAB ') && ($lut->type !== 'mBA ')) ||
            ($lut->nInput !== 3) || ($lut->nOutput !== 3) ||
            is_null($lut->clut) || is_null($lut->aCurves)) {
            fwrite(STDERR, "$sig: unsupported (type:{$tag->type}, need mAB/mBA 3ch with A curves and CLUT)\n");
            exit(1);
        }
        $a = $lut->aCurves;
        $lut->aCurves = [$a[1], $a[2], $a[0]];
        if ($lut->type === 'mAB ') {
            list($lut->clut['Grid'], $lut->clut['Data']) =
                convertCLUT2GBR_A2B($lut->clut['Grid'], $lut->clut['Data']);
        } else {
            $lut->clut['Data'] =
                convertCLUT2GBR_B2A($lut->clut['Grid'], $lut->clut['Data']);
        }
        break;
    }
}
unset($tag);

if (count($rgbXYZtags) == 3) {
    foreach ($icc->_tags as $idx => &$tag) {
        $sig = $tag->signature;
        switch ($sig) {
        case 'rXYZ':
            $tag->tag->xyz = $rgbXYZtags["gXYZ"];
            break;
        case 'gXYZ':
            $tag->tag->xyz = $rgbXYZtags["bXYZ"];
            break;
        case 'bXYZ':
            $tag->tag->xyz = $rgbXYZtags["rXYZ"];
            break;
        }
    }
    unset($tag);
}

if (count($rgbTRCtags) == 3) {
    $from = ['rTRC' => 'gTRC', 'gTRC' => 'bTRC', 'bTRC' => 'rTRC'];
    foreach ($icc->_tags as $idx => &$tag) {
        $sig = $tag->signature;
        if (isset($from[$sig])) {
            list($tag->content, $tag->type) = $rgbTRCtags[$from[$sig]];
            $tag->tag = null;
        }
    }
    unset($tag);
}

$icc->rebuild();

echo $icc->build();

exit(0);
