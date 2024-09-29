<?php

require_once dirname(__FILE__).'/../Bit.php';
require_once dirname(__FILE__).'/Base.php';

class IO_ICC_Type_MLUC extends IO_ICC_Type_Base {
    const DESCRIPTION = 'MultiLocalazed Unicode';
    var $type = null;
    var $strings = null;
    var $records = null;
    var $ascii = null;
    function parseContent($content, $opts = array()) {
        $reader = new IO_ICC_Bit();
    	$reader->input($content);
        $this->type = $reader->getData(4);
        $reader->incrementOffset(4, 0); // skip
        $recordNum = $reader->getUI32BE();
        $recordSize = $reader->getUI32BE();
        $records = array();
        for ($i = 0 ; $i < $recordNum ; $i++) {
            $record = array();
            $langCode = $reader->getData(2); // UI16BE on spec
            $countryCode = $reader->getData(2); // UI16BE on spec
            $size = $reader->getUI32BE();
            $offset = $reader->getUI32BE();
            $records []=
                array(
                      'LangCode' => $langCode,
                      'CountryCode' => $countryCode,
                      '_size' => $size,
                      '_offset' => $offset,
                      );
        }
        foreach ($records as &$record) {
            $reader->setOffset($record['_offset'], 0);
            $ucs2be = $reader->getData($record['_size']);
            $record['String'] = mb_convert_encoding($ucs2be, 'UTF-8', 'UCS-2BE');
        }
        $this->records = $records;
    }

    function dumpContent($opts = array()) {
        foreach ($this->records as $record) {
            $langCode = $record['LangCode'];
            $countryCode = $record['CountryCode'];
            $string = $record['String'];
            echo "\tCode:{$langCode}_$countryCode String:$string".PHP_EOL;
        }
    }

    function buildContent($opts = array()) {
        $writer = new IO_Bit();
        $writer->putData($this->type);
        $writer->putData("\0\0\0\0");
        //
        $writer->putUI32BE(count($this->records));
        $writer->putUI32BE(12);
        list($recordOffset, $dummy) = $writer->getOffset();
        foreach ($this->records as $record) {
            $writer->putData($record['LangCode'], 2);
            $writer->putData($record['CountryCode'], 2);
            $writer->putUI32BE(0); // size
            $writer->putUI32BE(0); // offset
        }
        $recordOffsetCurr = $recordOffset;
        foreach ($this->records as $record) {
            $string = $record['String'];
            $ucs2be = mb_convert_encoding($string, 'UCS-2BE', 'UTF-8');
            $size = strlen($ucs2be);
            list($offset, $dummy) = $writer->getOffset();
            $writer->setUI32BE($size, $recordOffsetCurr + 4);
            $writer->setUI32BE($offset, $recordOffsetCurr + 8);
            $writer->putData($ucs2be);
            $recordOffsetCurr += 12;
        }
    	return $writer->output();
    }
}
