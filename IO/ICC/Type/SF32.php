<?php

require_once dirname(__FILE__).'/../Bit.php';
require_once dirname(__FILE__).'/Base.php';

class IO_ICC_Type_SF32 extends IO_ICC_Type_Base {
    const DESCRIPTION = 'Signed Fixed 32';
    var $text = null;
    var $values = null;
    var $type = null;
    function parseContent($content, $opts = array()) {
        $reader = new IO_ICC_Bit();
    	$reader->input($content);
        $this->type = $reader->getData(4);
        $reader->incrementOffset(4, 0); // skip
        //
        $values = array();
        while ($reader->hasNextData(2)) {
            $values []= $reader->getS15Fixed16Number();
        }
        $this->values = $values;
    }

    function dumpContent($opts = array()) {
        $values_approx = array();
        foreach ($this->values as $value) {
            $values_approx []= round($value, 4);
        }
        echo "\tValues:".implode(',', $values_approx).PHP_EOL;
    }

    function buildContent($opts = array()) {
        $writer = new IO_ICC_Bit();
        $writer->putData($this->type);
        $writer->putData("\0\0\0\0");
        //
        foreach ($this->values as $value) {
            $writer->putS15Fixed16Number($value);
        }
    	return $writer->output();
    }
}
