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

    function build() {
        $writer = new IO_ICC_Bit();
        // Header
        $header = $this->_header;
        $writer->putUI32BE(0);
        $writer->putUI32BE($header['CMMType']);
        $writer->putUIBCD8($header['ProfileVersion']['Major']);
        $writer->putUIBCD8($header['ProfileVersion']['Minor']);
        $writer->putData("\0\0", 2); // Profie Version Reserved
        $writer->putData($header['ProfileDeviceClass'], 4);
        $writer->putData($header['ColorSpace'], 4);
        $writer->putData($header['ConnectionSpace'], 4);
        $writer->putDateTimeNumber($header['DataTimeCreated']);
        $writer->putData($header['acspSignature'], 4);
        $writer->putData($header['PrimaryPlatform'], 4);
        $cmmOptions1 = 
            ($header['CMMOptions']['EmbedProfile']?1:0) |
            ($header['CMMOptions']['Independently']?2:0);
        $cmmOptions2 = 0;
        $writer->putUI16BE($cmmOptions1);
        $writer->putUI16BE($cmmOptions2);
        $writer->putUI32BE($header['DeviceManufacturer']);
        $writer->putUI32BE($header['DeviceModel']);
        $deviceAttribute1 = 
            ($header['DeviceAttribute']['ReflectiveOrTransparency']?1:0) |
            ($header['DeviceAttribute']['GlossyOnMatte']?2:0);
        $deviceAttribute2 = 0;
        $writer->putUI32BE($deviceAttribute1);
        $writer->putUI32BE($deviceAttribute2);
        $writer->putUI32BE($header['RenderingIntent']);
        $writer->putXYZNumber($header['XYZvalueD50']);

        $writer->putUI32BE($header['CreatorID']);
        // Body
        list($byte_offset, $dummy) = $writer->getOffset();
        $writer->putData('', self::HEADER_SIZE - $byte_offset, "\0");
        //
        $tagTable = $this->_tagTable;
        $tagTableCount = count($tagTable);
        $writer->putUI32BE($tagTableCount);
        list($tableOffset, $dummy) = $writer->getOffset();
        foreach ($tagTable as $tagInfo) {
            $writer->putData($tagInfo['Signature'], 4);
            $writer->putUI32BE(0); // Offset
            $writer->putUI32BE(0); // Size
        }
        $tags = $this->_tags;
        $currTableOffset = $tableOffset;
        foreach ($tags as $idx => $tag) {
            list($offset, $dummy) = $writer->getOffset();
            $writer->setUI32BE($offset, $currTableOffset + 4);
            $tagData = $tag->build();
            $writer->putData($tagData);
            list($next_offset, $dummy) = $writer->getOffset();
            $writer->setUI32BE($next_offset - $offset, $currTableOffset + 8);
            $currTableOffset += 12;
        }
        $data = $writer->output();
        $writer->setUI32BE(strlen($data), 0);
        return $writer->output();
    }

    function dump($opts = array()) {
        echo "Header:".PHP_EOL;
        $header = $this->_header;
        $hexdump = ! empty($opts['hexdump']);
        if ($hexdump) {
            $iobit = new IO_Bit();
            $iobit->input($this->_iccdata);
            $opts['iobit'] = $iobit;
        }
        foreach ($header as $key => $value) {
            if (is_array($value)) {
                echo "    $key:";
                foreach ($value as $k => $v) {
                    if (is_bool($v)) {
                        echo " $k:".($v?"true":"false");
                    } else if (is_float($v)) {
                        printf(" %s:%.04f", $k, round($v, 4));
                    } else {
                        echo " $k:$v";
                    }
                }
                echo PHP_EOL;
            } else {
                if (is_bool($value)) {
                    echo "    $key:".($value?"true":"false");
                } else if (is_float($value)) {
                    printf("    %s:%.04f", $key, round($value, 4));
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
        if ($hexdump) {
            $iobit->hexdump(0, self::HEADER_SIZE);
        }
        // tagTable
        $tagTable = $this->_tagTable;
        $tagTableCount = count($tagTable);
        echo "TagTable: (Count:$tagTableCount)".PHP_EOL;
        foreach ($tagTable as $tagInfo) {
            echo "    Signature:{$tagInfo['Signature']} Offset:{$tagInfo['Offset']} Size:{$tagInfo['Size']}".PHP_EOL;
        }
        if ($hexdump) {
            // tagTableCount(4) + tagTableCount * (signature + offset + size)
            $tagTableSize = 4 + $tagTableCount * (4 + 4 + 4);
            $iobit->hexdump(self::HEADER_SIZE, $tagTableSize);
        }
        // Tags
        foreach ($this->_tags as $tag) {
            $tag->dump($opts);
        }
    }
}

