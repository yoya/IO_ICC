<?php
/*
  IO_ICC class
  (c) 2015/08/02- yoya@awm.jp
*/

require_once 'IO/Bit.php';

$IO_ICC_Header_Type =
    array(
          'RenderingIntent' =>
          array(0 => "Perceptual",
                1 => "Relative Colorimetric",
                2 => "Saturation",
                3 => "Absolute Colorimetric"),
          );

class IO_ICC {
    var $_iccdata = null;
    var $_header = null;
    const HEADER_SIZE = 128;
    var $_headerType = null;
    function __construct() {
        global $IO_ICC_Header_Type;
        $this->_headerType = $IO_ICC_Header_Type;
    }
    function getDateTimeNumber($bitin) {
        $dateTime = array(
                          'Year' => $bitin->getUI16BE(),
                          'Month' => $bitin->getUI16BE(),
                          'Day' => $bitin->getUI16BE(),
                          'Hours' => $bitin->getUI16BE(),
                          'Minutes' => $bitin->getUI16BE(),
                          'Seconds' => $bitin->getUI16BE(),
                          );
        return $dateTime;
    }
    function getXYZNumber($bitin) {
        return $bitin->getData(0x12);
    }
    function parse($iccdata) {
        $this->_iccdata = $iccdata;
        $bitin = new IO_Bit();
        $bitin->input($iccdata);
        // Header
        if ($bitin->hasNextData(self::HEADER_SIZE) === false) {
            
            throw new Exception('header is too short('. strlen($iccdata).')');
        }
        $header = array();
        $header['ProfileSize'] = $bitin->getUI32BE();
        $header['CMMType'] = $bitin->getUI32BE();
        $header['ProfileVersion'] =
            array(
                  'Major' => $bitin->getUIBCD8(),
                  'Minor' => $bitin->getUIBCD8(),
                  );
        $bitin->getData(2); // Profie Version Reserved
        $header['ProfileDeviceClass'] = $bitin->getData(4);
        $header['ColorSpace'] = $bitin->getData(4);
        $header['ConnectionSpace'] = $bitin->getData(4);
        $header['DataTimeCreated'] = $this->getDateTimeNumber($bitin);
        $header['acspSignature'] = $bitin->getUI32BE();
        $header['PrimaryPlatform'] = $bitin->getData(4);
        $header['CMMOptions'] = $bitin->getUI32BE();
        $header['DeviceManufacturer'] = $bitin->getUI32BE();
        $header['DeviceModel'] = $bitin->getUI32BE();
        $deviceAttribute1 = $bitin->getUI32BE();
        $deviceAttribute2 = $bitin->getUI32BE();
        $header['DeviceAttribute'] =
            array (
                   'ReflectiveOrTransparency' => ($deviceAttribute1 & 1) != 0,
                   'GlossyOnMatte' => ($deviceAttribute1 & 2) != 0,
                   );
        $header['RenderingIntent'] = $bitin->getUI32BE();
        $header['XYZvalueD50'] = $this->getXYZNumber($bitin);
        $header['CreatorID'] = $bitin->getUI32BE();
        $this->_header = $header;
        // Body
        $bitin->setOffset(self::HEADER_SIZE, 0);
    }
    function dump($opts = array()) {
        $header = $this->_header;
        foreach ($header as $key => $value) {
            if (is_array($value)) {
                echo "$key:";
                foreach ($value as $k => $v) {
                    if (is_bool($v)) {
                        echo " $k:".($v?"true":"false");
                    } else {
                        echo " $k:$v";
                    }
                }
                echo PHP_EOL;
            } else {
                if (is_bool($value)) {
                    echo "$key:".($value?"true":"false");
                } else {
                    echo "$key:$value";
                }
                if (isset($this->_headerType[$key][$value])) {
                    $typestr = $this->_headerType[$key][$value];
                    echo "($typestr)";
                }
                echo PHP_EOL;
            }
        }
    }
}

