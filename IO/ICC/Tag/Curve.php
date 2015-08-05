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
            $values []= $reader->getU8Fixed8Number();
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
        $count = count($values);
        if ($count === 1) {
            $value = $values[0];
            printf(" %.4f (gamma value)", $value);
            echo PHP_EOL;
        } else {
            $line_unit = 16;
            echo PHP_EOL;
            foreach ($values as $idx => $value) {
                if (($idx % $line_unit) === 0){
                    echo "\t[$idx]";
                }
                echo " $value";
                if ((($idx % $line_unit) === ($line_unit - 1)) || ($idx === ($count - 1))) {
                    echo PHP_EOL;
                }
            }
        }
    }

    function buildContent($type, $opts = array()) {
        $writer = new IO_Bit();
        $writer->putData($this->type);
        $writer->putData("\0\0\0\0");
        //
        $values = $this->CurveValues;
        $count = count($values);
        $writer->putUI32BE($count);
        if ($count === 1) {
            $writer->putU8Fixed8Number($value);
        } else {
            foreach ($values as $value)  {
                $writer->putUI16BE($value);
            }
        }
    	return $writer->output();
    }
}
