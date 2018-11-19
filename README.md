ICC Profile I/O library

# prepare

```
% composer require yoya/io_icc
```

# usage

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

```
% php vendor/yoya/io_icc/sample/iccgbr.php sRGB.icc > sGBR.icc
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


