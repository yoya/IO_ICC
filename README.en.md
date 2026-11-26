IO_ICC
======

Japanese version: [README.md](README.md)

A PHP library for interpreting and editing ICC (color) profiles. It requires IO_Bit.
It reads the header and the tags, dumps them, changes or deletes tag values, and builds the ICC profile again.
It interprets the main ICC v2 and v4 tag types (curv, para, XYZ, desc, mluc, mft1, mft2, mAB, mBA, ...).
IO_ISOBMFF (the colr box of HEIF/AVIF) uses it.
Not limited in use, free to copy and modify under the MIT license.
Please contact me if it does not work in your environment or if you want new features.

# License

MIT License

# Install

```
% composer require yoya/io_icc
```

# Usage

The examples below use the profiles in `test/`, copied to the current directory.

- iccdump.php (dump of the header, the tag table and each tag's contents. `-h` adds a hex dump)

```
% php vendor/yoya/io_icc/sample/iccdump.php -f sRGB_IEC61966-2-1_black_scaled.icc
Header:
    ProfileSize:3048
    CMMType:
    ProfileVersion: Major:2 Minor:0
    ProfileDeviceClass:mntr
    ColorSpace:RGB 
    ConnectionSpace:XYZ 
    DataTimeCreated: Year:2009 Month:3 Day:27 Hours:21 Minutes:36 Seconds:31
    acspSignature:acsp
(omit)
TagTable: (Count:16)
    Signature:desc Offset:324 Size:121
    Signature:bXYZ Offset:448 Size:20
    Signature:bTRC Offset:468 Size:2060
(omit)
Signature:desc (Offset:324 Size:121)
    Type:desc
	ASCII: sRGB IEC61966-2-1 black scaled
	UnicodeLanguage:     
	ScriptCode: 0
Signature:bXYZ (Offset:448 Size:20)
    Type:XYZ 
        XYZ: X:0.1431 Y:0.0606 Z:0.7141
(omit)
```

- icclisttags.php (list of tags)

```
% php vendor/yoya/io_icc/sample/icclisttags.php sRGB_v4_ICC_preference.icc
sRGB_v4_ICC_preference.icc
    [00] sig:desc type:mluc offset:240 size:118 version:4
    [01] sig:A2B0 type:mAB  offset:360 size:29712 version:4
    [02] sig:A2B1 type:mAB  offset:30072 size:436 version:4
    [03] sig:B2A0 type:mBA  offset:30508 size:29748 version:4
    [04] sig:B2A1 type:mBA  offset:60256 size:508 version:4
    [05] sig:rig0 type:sig  offset:60764 size:12 version:2
    [06] sig:wtpt type:XYZ  offset:60776 size:20 version:2
    [07] sig:cprt type:mluc offset:60796 size:118 version:4
    [08] sig:chad type:sf32 offset:60916 size:44 version:2
```

- iccedit.php (show and change values. With only a file it lists the tags, with a tag it shows its values, and with `<key>:<value>` it outputs the changed ICC)

```
% php vendor/yoya/io_icc/sample/iccedit.php sRGB_IEC61966-2-1_black_scaled.icc wtpt
type:XYZ 
xyz.X:0.96420288085938
xyz.Y:1
xyz.Z:0.82490539550781
% php vendor/yoya/io_icc/sample/iccedit.php sRGB_IEC61966-2-1_black_scaled.icc header RenderingIntent:1 > out.icc
```

- iccdeltags.php (delete tags. Without tags it lists them)

```
% php vendor/yoya/io_icc/sample/iccdeltags.php sRGB_v4_ICC_preference.icc | grep B2A
B2A0
B2A1
% php vendor/yoya/io_icc/sample/iccdeltags.php sRGB_v4_ICC_preference.icc B2A0 B2A1 > noB2A.icc
```

- iccgbr.php (convert an RGB profile into one that reads the channels in G, B, R order)

```
% php vendor/yoya/io_icc/sample/iccgbr.php sRGB_IEC61966-2-1_black_scaled.icc > sGBR.icc
```

- Using it from PHP (show the description and white point, delete a tag)

```php
<?php
require 'vendor/autoload.php';

$icc = new IO_ICC_Editor();
$icc->parse(file_get_contents('sRGB_IEC61966-2-1_black_scaled.icc'));
echo "Version: {$icc->_header['ProfileVersion']['Major']}.{$icc->_header['ProfileVersion']['Minor']}".PHP_EOL;
foreach ($icc->_tags as $tag) {
    if ($tag->signature === 'desc' && $tag->parseTagContent()) {
        echo "desc: {$tag->tag->ascii}".PHP_EOL;
    }
    if ($tag->signature === 'wtpt' && $tag->parseTagContent()) {
        $xyz = $tag->tag->xyz;
        printf("wtpt: X:%.4f Y:%.4f Z:%.4f".PHP_EOL, $xyz['X'], $xyz['Y'], $xyz['Z']);
    }
}
$icc->deleteTag('vued');   // remove the viewing conditions description
file_put_contents('out.icc', $icc->build());
```

```
% php wtpt.php
Version: 2.0
desc: sRGB IEC61966-2-1 black scaled
wtpt: X:0.9642 Y:1.0000 Z:0.8249
```

# Design

| Class | Role |
|---|---|
| `IO_ICC` | The whole profile. parse / build / dump of the header (`_header`), tag table (`_tagTable`) and tags (`_tags`) |
| `IO_ICC_Editor` | `IO_ICC` plus `rebuild()` (rebuild every tag) and `deleteTag()` for editing |
| `IO_ICC_Tag` | One tag. Holds the signature (`signature`) and the raw bytes (`content`), and interprets the contents (`tag`) only when needed |
| `IO_ICC_Type_*` | `parseContent` / `buildContent` / `dumpContent` per tag type (Curve, PCurve, XYZ, Desc, MLUC, MFT1, MFT2, MFAB, MFBA, Measure, View, SF32, Signature, Text) |
| `IO_ICC_Bit` | IO_Bit plus the ICC number types (s15Fixed16, u8Fixed8, XYZ, date-time) and 4-byte alignment |
| `IO_Bit` (separate package) | Byte and bit level reading and writing |

- Lazy interpretation: on parse, each tag keeps its contents as raw bytes. `parseTagContent()` interprets them.
  On build, a tag whose bytes are still there is written as is; only tags whose `content` was set to `null` are rebuilt with `buildContent()`.
  Tag types it cannot interpret (v5 dict, ...) are kept as raw bytes.
- build stores tags with identical contents once and points the tag table entries to the same place (e.g. when rTRC, gTRC and bTRC are the same curve).
  Tags are aligned to 4 bytes and ProfileSize is rewritten.
- Numbers are held as PHP floats converted from the ICC fixed-point types. Large arrays (curves, CLUTs) are `IO_ICC_FixedArray` (SplFixedArray).

# Features

| Feature | Sample (`sample/`) |
|---|---|
| Dump | `iccdump.php`, `iccheader.php`, `icclisttags.php`, `iccversion.php` |
| Compare | `iccdiff.php` (compares the interpreted values of two profiles and prints the differences) |
| Edit | `iccedit.php` (change header and tag values), `iccdeltags.php` (delete tags) |
| Rebuild | `icccopy.php` (build as is), `iccrebuild.php` (rebuild every tag, then build) |
| Convert | `iccgbr.php` (RGB → GBR), `iccshuffle.php` (makes a broken profile for testing by shuffling arrays of 10 or more elements) |

# Known limitations

- build writes the header's Profile ID (MD5) as 0. It is lost even in an unedited copy.
- The header's Profile flags (`CMMOptions`) and Device attributes (`DeviceAttribute`) are read and written from the upper word, not from the low bits that ICC defines. build can drop ICC-defined bits.
- The version is shown as 2-digit BCD. v4.2.0 shows as `Major:4 Minor:20`.
- `iccgbr.php` swaps only rXYZ/gXYZ/bXYZ and the A2B/B2A CLUTs. Per-channel curves such as rTRC/gTRC/bTRC are not swapped.

# Test

```
% php -d include_path=.:../IO_Bit tests/run.php
```

IO_Bit is loaded from `vendor/` (after composer install) or include_path.
`test/alltest.php` parses, rebuilds and re-parses the profiles in `test/` and prints the differences (for visual checks).

# Reference

- Where to get ICC profiles
  - http://www.color.org/registry/index.xalter
  - http://git.ghostscript.com/?p=ghostpdl.git;a=tree;f=iccprofiles
  - RGB
    - http://www.color.org/srgbprofiles.xalter
    - https://www.adobe.com/digitalimag/adobergb.html
    - https://www.adobe.com/support/downloads/iccprofiles/iccprofiles_win.html
    - http://www.color.org/chardata/rgb/rommrgb.xalter
  - CMYK
    - http://japancolor.jp/icc.html
  - XYZ
    - http://www.color.org/XYZprofiles.xalter
