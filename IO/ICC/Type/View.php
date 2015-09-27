<?php
/*
  IO_ICC_Type_Measure class
  (c) 2015/09/27- yoya@awm.jp
*/

require_once dirname(__FILE__).'/../Bit.php';
require_once dirname(__FILE__).'/Base.php';
require_once dirname(__FILE__).'/Measure.php';

class IO_ICC_Type_View extends IO_ICC_Type_Base {
    const DESCRIPTION = 'viewing conditions Type';
    var $type;
    var $xyzIlluminant;
    var $xyzSurround;
    var $illuminantType;
    static $illuminantTypeEncodings;
    function __construct() {
        self::$illuminantTypeEncodings = IO_ICC_Type_Measure::$stdIlluminantEncodings;
    }
    function parseContent($content, $opts = array()) {
        //
        $reader = new IO_ICC_Bit();
    	$reader->input($content);
        $this->type = $reader->getData(4);
        $reader->incrementOffset(4, 0); // skip
        //
        $this->xyzIlluminant = $reader->getXYZNumber();
        $this->xyzSurround = $reader->getXYZNumber();
        $this->illuminantType = $reader->getUI32BE();
    }

    function dumpContent($opts = array()) {
        $illuminantType = $this->illuminantType;
        $illuminantTypeStr = self::$illuminantTypeEncodings[$illuminantType];
        //
        echo "        xyzIlluminant:";
        foreach ($this->xyzIlluminant as $key => $value) {
            printf(" %s:%.4f", $key, $value);
        }
        echo PHP_EOL;
        echo "        xyzSurround:";
        foreach ($this->xyzSurround as $key => $value) {
            printf(" %s:%.4f", $key, $value);
        }
        echo PHP_EOL;
        echo "        illuminantType:$illuminantType $illuminantTypeStr".PHP_EOL;
    }

    function buildContent($opts = array()) {
        $writer = new IO_ICC_Bit();
        $writer->putData($this->type);
        $writer->putData("\0\0\0\0");
        $writer->incrementOffset(4, 0); // skip
        //
        $writer->putXYZNumber($this->xyzIlluminant);
        $writer->putXYZNumber($this->xyzSurround);
        $writer->putUI32BE($this->illuminantType);
        //
    	return $writer->output();
    }
}
