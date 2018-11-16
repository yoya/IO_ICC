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

function convertCLUT2GBR_A2B($grid, $data) {
    list($grid1, $grid2, $grid3) = $grid;
    $gridNum = $grid1 * $grid2 * $grid3;
    $arr = new IO_ICC_FixedArray($gridNum * 3); // XXX 3
    for ($i1= 0 ; $i1 < $grid1  ; $i1++) {
        for ($i2 = 0 ; $i2 < $grid2  ; $i2++) {
            for ($i3 = 0 ; $i3 < $grid3  ; $i3++) {
                $src_i = ((($i1 * $grid1) + $i2) * $grid2) + $i3;
                $dst_i = ((($i2 * $grid2) + $i3) * $grid3) + $i1;
                $arr[3*$dst_i + 0] = $data[3*$src_i + 0];
                $arr[3*$dst_i + 1] = $data[3*$src_i + 1];
                $arr[3*$dst_i + 2] = $data[3*$src_i + 2];
            }
        }
    }
    return [[$grid2, $grid3, $grid1], $arr];
}

function convertCLUT2GBR_B2A($grid, $data) {
    list($grid1, $grid2, $grid3) = $grid;
    $gridNum = $grid1 * $grid2 * $grid3;
    $arr = new IO_ICC_FixedArray($gridNum * 3); // XXX 3
    for ($i = 0 ; $i < $gridNum  ; $i++) {
        $arr[3*$i + 0] = $data[3*$i + 1];
        $arr[3*$i + 1] = $data[3*$i + 2];
        $arr[3*$i + 2] = $data[3*$i + 0];
    }
    return [[$grid2, $grid3, $grid1], $arr];
}


$rgbXYZtags = [];
foreach ($icc->_tags as $idx => &$tag) {
    $sig = $tag->signature;
    switch ($sig) {
    case 'rXYZ':
    case 'gXYZ':
    case 'bXYZ':
        $tag->parseTagContent();
        $rgbXYZtags[$sig] = $tag->tag->xyz;
        break;
    case 'A2B0':
    case 'A2B1':
        $tag->parseTagContent();
        $grid = $tag->tag->clut['Grid'];
        $data = $tag->tag->clut['Data'];
        list($grid, $data) = convertCLUT2GBR_A2B($grid, $data);
        $tag->tag->clut['Grid'] = $grid;
        $tag->tag->clut['Data'] = $data;
        break;
    case 'B2A0':
    case 'B2A1':
        $tag->parseTagContent();
        $grid = $tag->tag->clut['Grid'];
        $data = $tag->tag->clut['Data'];
        list($grid, $data) = convertCLUT2GBR_B2A($grid, $data);
        $tag->tag->clut['Grid'] = $grid;
        $tag->tag->clut['Data'] = $data;
        break;
    }
}

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
}

$icc->rebuild();

echo $icc->build();

exit(0);
