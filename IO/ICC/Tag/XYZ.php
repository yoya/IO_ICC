<?php

require_once dirname(__FILE__).'/../Bit.php';
require_once dirname(__FILE__).'/Base.php';

class IO_ICC_Tag_XYZ extends IO_ICC_Tag_Base {
    const DESCRIPTION = 'XYZ Type';
    var $xyz = null;
    function parseContent($type, $content, $opts = array()) {
        $reader = new IO_ICC_Bit();
    	$reader->input($content);
        $this->type = $type;
        $reader->incrementOffset(8, 0); // skip head 8 bytes
        $this->xyz = $reader->getXYZNumber();
    }

    function dumpContent($type, $opts = array()) {
        echo "        XYZ:";
        foreach ($this->xyz as $key => $value) {
            printf(" %s:%.4f", $key, $value);
        }
        echo PHP_EOL;
    }

    function buildContent($type, $opts = array()) {
        $writer = new IO_ICC_Bit();
        $writer->putData($this->type);
        $writer->putData("\0\0\0\0");
        //
        $writer->putXYZNumber($this->xyz);
    	return $writer->output();
    }
}
