<?php
/*
  IO_ICC class
  (c) 2015/08/02- yoya@awm.jp
*/

require_once dirname(__FILE__).'/ICC/Bit.php';
require_once dirname(__FILE__).'/ICC/Tag.php';

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
    var $_tagTable = null;
    var $_tags = null;
    //
    var $_headerType = null;
    //
    function __construct() {
        global $IO_ICC_Header_Type;
        $this->_headerType = $IO_ICC_Header_Type;
    }
    //
    function parse($iccdata) {
        $this->_iccdata = $iccdata;
        $reader = new IO_ICC_Bit();
        $reader->input($iccdata);
        // Header
        if ($reader->hasNextData(self::HEADER_SIZE) === false) {
            throw new Exception('header is too short('. strlen($iccdata).')');
        }
        $header = array();
        $header['ProfileSize'] = $reader->getUI32BE();
        $header['CMMType'] = $reader->getUI32BE();
        $header['ProfileVersion'] =
            array(
                  'Major' => $reader->getUIBCD8(),
                  'Minor' => $reader->getUIBCD8(),
                  );
        $reader->getData(2); // Profie Version Reserved
        $header['ProfileDeviceClass'] = $reader->getData(4);
        $header['ColorSpace'] = $reader->getData(4);
        $header['ConnectionSpace'] = $reader->getData(4);
        $header['DataTimeCreated'] = $reader->getDateTimeNumber();
        $header['acspSignature'] = $reader->getData(4);
        $header['PrimaryPlatform'] = $reader->getData(4);
        $cmmOptions1 = $reader->getUI16BE();
        $cmmOptions2 = $reader->getUI16BE();
        $header['CMMOptions'] =
            array (
                   'EmbedProfile' => ($cmmOptions1 & 1) != 0,
                   'Independently' => ($cmmOptions1 & 2) != 0,
                   );
        $header['DeviceManufacturer'] = $reader->getUI32BE();
        $header['DeviceModel'] = $reader->getUI32BE();
        $deviceAttribute1 = $reader->getUI32BE();
        $deviceAttribute2 = $reader->getUI32BE();
        $header['DeviceAttribute'] =
            array (
                   'ReflectiveOrTransparency' => ($deviceAttribute1 & 1) != 0,
                   'GlossyOnMatte'            => ($deviceAttribute1 & 2) != 0,
                   );
        $header['RenderingIntent'] = $reader->getUI32BE();
        $header['XYZvalueD50'] = $reader->getXYZNumber();

        $header['CreatorID'] = $reader->getUI32BE();
        $this->_header = $header;
        // Body
        $reader->setOffset(self::HEADER_SIZE, 0);
        $tagTableCount = $reader->getUI32BE();
        $tagTable = array();
        for ($i = 0 ; $i < $tagTableCount ; $i++) {
            $tagTable []=
                array(
                      'Signature' => $reader->getData(4),
                      'Offset' => $reader->getUI32BE(),
                      'Size' => $reader->getUI32BE(),
                 );
        }
        $this->_tagTable = $tagTable;
        $iccInfo =
            array(
                  'Version' => $header['ProfileVersion'],
                  );
        foreach ($tagTable as $tagInfo) {
            $tag = new IO_ICC_Tag($iccInfo);
            $tag->parse($reader, $tagInfo);
            $this->_tags[] = $tag;
        }
    }
    function dump($opts = array()) {
        echo "Header:".PHP_EOL;
        $header = $this->_header;
        foreach ($header as $key => $value) {
            if (is_array($value)) {
                echo "    $key:";
                foreach ($value as $k => $v) {
                    if (is_bool($v)) {
                        echo " $k:".($v?"true":"false");
                    } else if (is_float($v)) {
                        printf(" %s:%.4f", $k, $v);
                    } else {
                        echo " $k:$v";
                    }
                }
                echo PHP_EOL;
            } else {
                if (is_bool($value)) {
                    echo "    $key:".($value?"true":"false");
                } else if (is_float($value)) {
                    printf("    %s:%.4f", $key, $value);
                } else {
                    echo "    $key:$value";
                }
                if (isset($this->_headerType[$key][$value])) {
                    $typestr = $this->_headerType[$key][$value];
                    echo "($typestr)";
                }
                echo PHP_EOL;
            }
        }
        $tagTable = $this->_tagTable;
        $tagTableCount = count($tagTable);
        echo "TagTableCount: $tagTableCount".PHP_EOL;
        foreach ($this->_tags as $tag) {
            $tag->dump($opts);
        }
    }
}

