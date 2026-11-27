IO_ICC
======

English version: [README.en.md](README.en.md)

ICC プロファイル (カラープロファイル) を解釈・編集する為の PHP ライブラリです。IO_Bit が必要です。
ヘッダとタグを読み込み、ダンプ、タグの値の書き換えや削除をして、ICC プロファイルを作り直せます。
ICC v2 と v4 の主なタグ型 (curv, para, XYZ, desc, mluc, mft1, mft2, mAB, mBA など) を解釈します。
IO_ISOBMFF (HEIF/AVIF の colr ボックス) がこれを使っています。
利用に制限はかけません。コピーも改変もご自由にどうぞ。MIT ライセンスにしました。
自分の環境だと動かないとか、機能が欲しいとかいった要望があれば連絡下さい。

# License

MIT License

# Install

```
% composer require yoya/io_icc
```

# Usage

以下の例では、`test/` にあるプロファイルをカレントディレクトリにコピーして使っています。

- iccdump.php (ヘッダ・タグ表・各タグの中身のダンプ。`-h` で 16 進ダンプも)

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

- icclisttags.php (タグの一覧)

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

- iccedit.php (値の表示と書き換え。引数がファイルだけならタグの一覧、タグを指定するとその値、`<key>:<value>` を付けると書き換えた ICC を出力)

```
% php vendor/yoya/io_icc/sample/iccedit.php sRGB_IEC61966-2-1_black_scaled.icc wtpt
type:XYZ 
xyz.X:0.96420288085938
xyz.Y:1
xyz.Z:0.82490539550781
% php vendor/yoya/io_icc/sample/iccedit.php sRGB_IEC61966-2-1_black_scaled.icc header RenderingIntent:1 > out.icc
```

- iccdeltags.php (タグの削除。タグを指定しなければ一覧を表示)

```
% php vendor/yoya/io_icc/sample/iccdeltags.php sRGB_v4_ICC_preference.icc | grep B2A
B2A0
B2A1
% php vendor/yoya/io_icc/sample/iccdeltags.php sRGB_v4_ICC_preference.icc B2A0 B2A1 > noB2A.icc
```

- iccgbr.php (RGB プロファイルを、チャンネルを G, B, R の順に解釈するプロファイルに変換)

```
% php vendor/yoya/io_icc/sample/iccgbr.php sRGB_IEC61966-2-1_black_scaled.icc > sGBR.icc
```

- PHP から使う例 (説明と白色点の表示、タグの削除)

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

# 設計概要

| クラス | 役割 |
|---|---|
| `IO_ICC` | プロファイル全体。ヘッダ (`_header`)、タグ表 (`_tagTable`)、タグ (`_tags`) の parse / build / dump |
| `IO_ICC_Editor` | `IO_ICC` に編集用の `rebuild()` (全タグを作り直す) と `deleteTag()` を足したもの |
| `IO_ICC_Tag` | タグ 1 個。シグネチャ (`signature`) と中身のバイト列 (`content`) を持ち、必要になったときだけ中身を解釈する (`tag`) |
| `IO_ICC_Type_*` | タグ型ごとの `parseContent` / `buildContent` / `dumpContent` (Curve, PCurve, XYZ, Desc, MLUC, MFT1, MFT2, MFAB, MFBA, Measure, View, SF32, Signature, Text) |
| `IO_ICC_Bit` | IO_Bit に ICC の数値型 (s15Fixed16, u8Fixed8, XYZ, 日時) と 4 バイト整列を足したもの |
| `IO_Bit` (別パッケージ) | バイト・ビット単位の読み書き |

- 遅延解釈: parse 時、各タグは中身をバイト列のまま持ちます。`parseTagContent()` で中身を解釈します。
  build 時、バイト列が残っていればそのまま書き、`content` を `null` にしたタグだけ `buildContent()` で作り直します。
  解釈できないタグ型 (v5 の dict など) はバイト列のまま残します。
- build は、中身が同じタグを 1 つにまとめ、タグ表から同じ場所を指します (rTRC, gTRC, bTRC が同じカーブのときなど)。
  タグは 4 バイト境界に整列し、ProfileSize を書き直します。
  Profile ID (MD5) は `_header['ProfileID']` (16 バイト) に読み、build ではバージョン 4 以上なら常に、
  バージョン 2 なら元に ID があったときだけ計算して書きます (ID の無い v2 は 0 のまま)。
- ヘッダの `CMMOptions` (Profile flags) と `DeviceAttribute` (Device attributes) は、ICC が定めるビットを真偽値で、
  それ以外の ICC 用のビットを `Reserved` (元のビット位置のまま)、ベンダー用のビットを `Vendor` に持ち、build で全ビットを書き戻します。
- 数値は ICC の固定小数点を PHP の float にして持ちます。大きな配列 (カーブ、CLUT) は `IO_ICC_FixedArray` (SplFixedArray) です。

# 機能

| 機能 | サンプル (`sample/`) |
|---|---|
| ダンプ | `iccdump.php`, `iccheader.php`, `icclisttags.php`, `iccversion.php` |
| 比較 | `iccdiff.php` (2 つのプロファイルの解釈結果を比べ、違う値を表示) |
| 編集 | `iccedit.php` (ヘッダ・タグの値の書き換え), `iccdeltags.php` (タグの削除) |
| 作り直し | `icccopy.php` (そのまま build), `iccrebuild.php` (全タグを作り直して build) |
| 変換 | `iccgbr.php` (RGB → GBR), `iccshuffle.php` (要素数 10 以上の配列を並べ替えた、試験用の壊れたプロファイルを作る) |

# 既知の制限

- バージョンは BCD 2 桁として表示します。v4.2.0 は `Major:4 Minor:20` になります。
- `iccgbr.php` が扱うのは RGB のプロファイルで、rXYZ/gXYZ/bXYZ と rTRC/gTRC/bTRC、3 チャンネルの mft1/mft2 と、A カーブと CLUT を持つ mAB/mBA (A2B0-2, B2A0-2) です。
  それ以外 (RGB 以外のプロファイル、CLUT の無い mAB/mBA など) はエラーで終了します。

# Test

```
% php -d include_path=.:../IO_Bit tests/run.php
```

IO_Bit は `vendor/` (composer install した場合) か include_path から読みます。
`test/alltest.php` は `test/` のプロファイルを parse → rebuild → parse して違いを表示します (目視用)。

# Reference

- ICC プロファイルの入手先
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

## IO_Bit の利用箇所

`IO_ICC_Bit` は `IO_Bit` を継承します。プロファイル内の 32 ビット値やオフセットの読み書きにはビッグエンディアンのメソッドを使います。

[IO_Bit Wiki](https://github.com/yoya/IO_Bit/wiki)
