<?php

require_once dirname(__FILE__).'/../Bit.php';
require_once dirname(__FILE__).'/../String.php';
require_once dirname(__FILE__).'/Base.php';

class IO_ICC_Type_Text extends IO_ICC_Type_Base {
    const DESCRIPTION = 'Text Type';
    var $type = null;
    var $text = null;
    function parseContent($content, $opts = array()) {
        $reader = new IO_ICC_Bit();
    	$reader->input($content);
        $this->type = $reader->getData(4);
        $reader->incrementOffset(4, 0); // skip
        $text = $reader->getDataUntil(false);
        $this->text = IO_ICC_String::trimNullTerminate($text);
    }

    function dumpContent($opts = array()) {
        if (is_null($this->text) === false) {
            echo "\tTEXT: {$this->text}".PHP_EOL;
        }
    }

    function buildContent($opts = array()) {
        $writer = new IO_Bit();
        $writer->putData($this->type);
        $writer->putData("\0\0\0\0");
        //
        $text = IO_ICC_Util::fixAsciiZ($this->text);
        $writer->putData($text);
    	return $writer->output();
    }
}
