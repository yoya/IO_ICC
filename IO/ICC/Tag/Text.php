<?php

require_once dirname(__FILE__).'/../Bit.php';
require_once dirname(__FILE__).'/Base.php';

class IO_ICC_Tag_Text extends IO_ICC_Tag_Base {
    const DESCRIPTION = 'Text Type';
    var $text = null;
    function parseContent($type, $content, $opts = array()) {
        $reader = new IO_ICC_Bit();
    	$reader->input($content);
        $this->type = $type;
        $reader->incrementOffset(8, 0); // skip head 8 bytes
        $this->text = $reader->getDataUntil(false);
    }

    function dumpContent($type, $opts = array()) {
        if (is_null($this->text) === false) {
            echo "\tTEXT: {$this->text}".PHP_EOL;
        }
    }

    function buildContent($type, $opts = array()) {
        $writer = new IO_Bit();
        $writer->putData($this->type);
        $writer->putData("\0\0\0\0");
        //
        $writer->putData($this->text);
    	return $writer->output();
    }
}
