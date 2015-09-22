<?php

require_once dirname(__FILE__).'/../Bit.php';
require_once dirname(__FILE__).'/../FixedArray.php';
require_once dirname(__FILE__).'/Base.php';

class IO_ICC_Type_Curve extends IO_ICC_Type_Base {
    const DESCRIPTION = 'Curve Type';
    var $type = null;
    var $CurveValues = null;
    function parseContent($content, $opts = array()) {
        $reader = new IO_ICC_Bit();
    	$reader->input($content);
        $this->type = $reader->getData(4);
        $reader->incrementOffset(4, 0); // skip
        $count = $reader->getUI32BE();
        $values = new IO_ICC_FixedArray($count);
        if ($count === 1) {
            $values[0] = $reader->getU8Fixed8Number();
        } else {
            for ($i = 0 ; $i < $count ; $i++) {
                $values[$i] = $reader->getUI16BE();
            }
        }
        list($this->_contentLength, $dummy)  = $reader->getOffset();
        $this->CurveValues = $values;
    }

    function dumpContent($opts = array()) {
        $this->echoIndentSpace($opts);
        echo "CurveValues:";
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
                    $this->echoIndentSpace($opts);
                    echo "[$idx]";
                }
                echo " $value";
                if ((($idx % $line_unit) === ($line_unit - 1)) || ($idx === ($count - 1))) {
                    echo PHP_EOL;
                }
            }
        }
    }

    function buildContent($opts = array()) {
        $writer = new IO_ICC_Bit();
        $writer->putData($this->type);
        $writer->putData("\0\0\0\0");
        //
        $values = $this->CurveValues;
        $count = count($values);
        $writer->putUI32BE($count);
        if ($count === 1) {
            $writer->putU8Fixed8Number($values[0]);
        } else {
            foreach ($values as $value)  {
                $writer->putUI16BE($value);
            }
        }
    	return $writer->output();
    }
}
