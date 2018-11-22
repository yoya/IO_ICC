ICC Profile I/O library

# prepare

```
% composer require yoya/io_icc
```

# usage

## iccdump

```
% php vendor/yoya/io_icc/sample/iccdump.php -f sRGB.icc | head
Header:
    ProfileSize:588
    CMMType:lcms
    ProfileVersion: Major:4 Minor:30
    ProfileDeviceClass:mntr
    ColorSpace:RGB
    ConnectionSpace:XYZ
    DataTimeCreated: Year:2017 Month:7 Day:24 Hours:10 Minutes:0 Seconds:38
    acspSignature:acsp
    PrimaryPlatform:APPL
...
```

## iccgbr

```
% php vendor/yoya/io_icc/sample/iccgbr.php sRGB.icc > sGBR.icc
```

## iccedit

```
% php vendor/yoya/io_icc/sample/iccedit.php GBR.icc
gTRC:curv
gXYZ:XYZ
% php vendor/yoya/io_icc/sample/iccedit.php GBR.icc gTRC
type:curv
CurveValues:2.19921875
% php vendor/yoya/io_icc/sample/iccedit.php GBR.icc gTRC CurveValues:0.82 > GBR_gTRC-0.82.icc
```
## iccdeltags

```
% php vendor/yoya/io_icc/sample/iccdeltags.php sRGB_v4_ICC_preference_displayclass.icc | grep B2A
B2A0
B2A1
% php vendor/yoya/io_icc/sample/iccdeltags.php sRGB_v4_ICC_preference_displayclass.icc B2A0 B2A1 > sRGB_v4_ICC_preference_displayclass_noB2A.icc
```

# icc profile sample

- http://www.color.org/registry/index.xalter
- http://git.ghostscript.com/?p=ghostpdl.git;a=tree;f=iccprofiles

## RGB

- http://www.color.org/srgbprofiles.xalter
- https://www.adobe.com/digitalimag/adobergb.html
- https://www.adobe.com/support/downloads/iccprofiles/iccprofiles_win.html
- http://www.color.org/chardata/rgb/rommrgb.xalter

## CMYK

- http://japancolor.jp/icc.html

## XYZ

- http://www.color.org/XYZprofiles.xalter
