<?php

require_once 'IO/ICC/Bit.php';
require_once dirname(__FILE__).'/Base.php';

class IO_ICC_Tag_TextDesc extends IO_ICC_Tag_Base {
    const DESCRIPTION = 'Text Description';
    var $ascii = null;
    var $unicodeLanguage = null;
    var $unicode = null;
    var $scriptCode = null;
    var $macintosh = null;
    function parseContent($type, $content, $opts = array()) {
        $reader = new IO_ICC_Bit();
    	$reader->input($content);
        $this->type = $type;
        $reader->incrementOffset(8, 0); // skip head 8 bytes
        $asciiCount = $reader->getUI32BE();
        if ($asciiCount > 0) {
            $this->ascii = $reader->getData($asciiCount);
        }
        $this->unicodeLanguage = $reader->getData(4);
        $unicodeCount = $reader->getUI32BE();
        if ($unicodeCount > 0) {
            $ucs2be = $reader->getData($unicodeCount * 2);
            $this->unicode = mb_convert_encoding($ucs2be, 'UTF-8', 'UCS-2BE');
        }
        $this->scriptCode = $reader->getUI16BE();
        $macintoshCount = $reader->getUI8();
        // var_dump("MacintoshCount:$macintoshCount");
        if ($macintoshCount > 0) {
            $this->macintosh = $reader->getData($macintoshCount);
        }
    }

    function dumpContent($type, $opts = array()) {
        if (is_null($this->ascii) === false) {
            echo "\tASCII: {$this->ascii}".PHP_EOL;
        }
        echo "\tUnicodeLanguage: {$this->unicodeLanguage}".PHP_EOL;
        if (is_null($this->unicode) === false) {
            echo "\tUnicode: {$this->unicode}".PHP_EOL;
        }
        echo "\tScriptCode: {$this->scriptCode}".PHP_EOL;
        if (is_null($this->macintosh) === false) {
            echo "\tMacintosh: {$this->macintosh}".PHP_EOL;
        }
    }

    function buildContent($type, $opts = array()) {
        $writer = new IO_Bit();
    	return $writer->output();
    }
}
