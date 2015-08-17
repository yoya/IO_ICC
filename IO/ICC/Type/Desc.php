<?php

require_once dirname(__FILE__).'/../Bit.php';
require_once dirname(__FILE__).'/../String.php';
require_once dirname(__FILE__).'/Base.php';


class IO_ICC_Type_Desc extends IO_ICC_Type_Base {
    const DESCRIPTION = 'Text Description';
    var $type = null;
    var $ascii = null;
    var $unicodeLanguage = null;
    var $unicode = null;
    var $scriptCode = null;
    var $macintosh = null;
    function parseContent($content, $opts = array()) {
        $reader = new IO_ICC_Bit();
    	$reader->input($content);
        $this->type = $reader->getData(4);
        $reader->incrementOffset(4, 0); // skip
        $asciiCount = $reader->getUI32BE();
        if ($asciiCount > 0) {
            $ascii = $reader->getData($asciiCount);
            $this->ascii = IO_ICC_String::trimNullTerminate($ascii);
        }
        $this->unicodeLanguage = $reader->getData(4);
        $unicodeCount = $reader->getUI32BE();
        if ($unicodeCount > 0) {
            $ucs2be = $reader->getData($unicodeCount * 2);
            $unicode = mb_convert_encoding($ucs2be, 'UTF-8', 'UCS-2BE');
            $this->unicode = IO_ICC_String::trimNullTerminate($unicode);
        }
        $this->scriptCode = $reader->getUI16BE();
        $macintoshCount = $reader->getUI8();
        // var_dump("MacintoshCount:$macintoshCount");
        if ($macintoshCount > 0) {
            $macintosh = $reader->getData($macintoshCount);
            $this->macintosh = IO_ICC_String::trimNullTerminate($macintosh);
        }
        // list($this->_contentLength, $dummy)  = $reader->getOffset();
    }

    function dumpContent($opts = array()) {
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

    function buildContent($opts = array()) {
        $writer = new IO_Bit();
        $writer->putData($this->type);
        $writer->putData("\0\0\0\0");
        //
        if (is_null($this->ascii)) {
            $writer->putUI32BE(0);
        } else {
            $ascii = IO_ICC_Util::fixAsciiZ($this->ascii);
            $writer->putUI32BE(strlen($ascii));
            $writer->putData($ascii);
        }
        $writer->putData($this->unicodeLanguage);
        if (is_null($this->unicode)) {
            $writer->putUI32BE(0);
        } else {
            $unicode = IO_ICC_Util::fixAsciiZ($this->unicode);
            $ucs2be = mb_convert_encoding($unicode, 'UCS-2BE', 'UTF-8');
            $writer->putUI32BE(strlen($ucs2be) / 2);
            $writer->putData($ucs2be);
        }
        $writer->putUI16BE($this->scriptCode);
        if (is_null($this->macintosh)) {
            $writer->putUI8(0);
        } else {
            $macintosh = IO_ICC_Util::fixAsciiZ($this->macintosh);
            $macintoshCount = strlen($macintosh);
            $writer->putUI8($macintoshCount);
            $macintosh_0filled = str_pad($macintosh, 67, "\0");
            $writer->putData($macintosh_0filled);
        }
    	return $writer->output();
    }
}
