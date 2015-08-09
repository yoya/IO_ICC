<?php

require_once dirname(__FILE__).'/../Bit.php';
require_once dirname(__FILE__).'/Base.php';

class IO_ICC_Type_XYZ extends IO_ICC_Type_Base {
    const DESCRIPTION = 'XYZ Type';
    var $type = null;
    var $xyz = null;
    function parseContent($content, $opts = array()) {
        $reader = new IO_ICC_Bit();
    	$reader->input($content);
        $this->type = $reader->getData(4);
        $reader->incrementOffset(4, 0); // skip
        $this->xyz = $reader->getXYZNumber();
    }

    function dumpContent($opts = array()) {
        echo "        XYZ:";
        foreach ($this->xyz as $key => $value) {
            printf(" %s:%.4f", $key, $value);
        }
        echo PHP_EOL;
    }

    function buildContent($opts = array()) {
        $writer = new IO_ICC_Bit();
        $writer->putData($this->type);
        $writer->putData("\0\0\0\0");
        //
        $writer->putXYZNumber($this->xyz);
    	return $writer->output();
    }
}
