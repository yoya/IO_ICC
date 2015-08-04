<?php

require_once dirname(__FILE__).'/../Bit.php';
require_once dirname(__FILE__).'/Base.php';

class IO_ICC_Tag_Curve extends IO_ICC_Tag_Base {
    const DESCRIPTION = 'Curve Type';
    var $CurveValues = null;
    function parseContent($type, $content, $opts = array()) {
        $reader = new IO_ICC_Bit();
    	$reader->input($content);
        $this->type = $type;
        $reader->incrementOffset(8, 0); // skip head 8 bytes
        $count = $reader->getUI32BE();
        $values = array();
        if ($count === 1) {
            $values []= $reader->getUI16BE() / 0xFF; // u8Fixed8Number
        } else {
            for ($i = 0 ; $i < $count ; $i++) {
                $values []= $reader->getUI16BE();
            }
        }
        $this->CurveValues = $values;
    }

    function dumpContent($type, $opts = array()) {
        echo "        CurveValues:";
        $values = $this->CurveValues;
        if (count($values) === 1) {
            $value = $values[0];
            printf(" %.4f (gamma value)", $value);
        } else {
            foreach ($values as $idx => $value) {
                echo " [$idx]$value";
            }
        }
        echo PHP_EOL;
    }

    function buildContent($type, $opts = array()) {
        $writer = new IO_Bit();
    	return $writer->output();
    }
}
