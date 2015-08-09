<?php

require_once dirname(__FILE__).'/../Bit.php';
require_once dirname(__FILE__).'/Base.php';

class IO_ICC_Type_SF32 extends IO_ICC_Type_Base {
    const DESCRIPTION = 'Text Type';
    var $text = null;
    var $values = null;
    function parseContent($type, $content, $opts = array()) {
        $reader = new IO_ICC_Bit();
    	$reader->input($content);
        $this->type = $type;
        $reader->incrementOffset(8, 0); // skip head 8 bytes
        //
        $values = array();
        while ($reader->hasNextData(2)) {
            $values []= $reader->getS15Fixed16Number();
        }
        $this->values = $values;
    }

    function dumpContent($type, $opts = array()) {
        $values_approx = array();
        foreach ($this->values as $value) {
            $values_approx []= round($value, 4);
        }
        echo "\tValues:".implode(',', $values_approx).PHP_EOL;
    }

    function buildContent($type, $opts = array()) {
        $writer = new IO_Bit();
        $writer->putData($this->type);
        $writer->putData("\0\0\0\0");
        //
        foreach ($this->values as $value) {
            $writer->putS15Fixed16Number($value);
        }
    	return $writer->output();
    }
}
