<?php

require_once dirname(__FILE__).'/../Bit.php';
require_once dirname(__FILE__).'/Base.php';

class IO_ICC_Type_MFT2 extends IO_ICC_Type_Base {
    const DESCRIPTION = 'Multi Function Table with 2 byte presision';
    var $text = null;
    var $nInput, $nOutput;
    var $nCLUTGridPoints;
    var $parameters;
    static $paramNameList =
        array('e00', 'e01', 'e02', 'e10', 'e11', 'e12', 'e20', 'e21', 'e22');
    function parseContent($content, $opts = array()) {
        $reader = new IO_ICC_Bit();
    	$reader->input($content);
        $this->type = $reader->getData(4);
        $reader->incrementOffset(4, 0); // skip
        //
        $nInput = $reader->getUI8();
        $nOutput = $reader->getUI8();
        $this->nInput  = $nInput;
        $this->nOutput = $nOutput;
        $this->nCLUTGridPoints = $reader->getUI8();
        $reader->incrementOffset(1, 0); // reserved for padding
        //
        $parameters = array();
        foreach (self::$paramNameList as $param) {
            $parameters[$param] = $reader->getS15Fixed16Number();
        }
        $nInputTableEntries = $reader->getUI16BE();
        $nOutputTableEntries = $reader->getUI16BE();
        $inputTables = array();
        for($i = 0 ; $i < $nInput ; $i++) {
            $inputTableEntry = array();
            for($j = 0 ; $j < $nInputTableEntries ; $j++) {
                $inputTableEntry []= $reader->getUI16BE();
            }
            $inputTables []= array($inputTableEntry);
        }
        $this->inputTables = $inputTables;
        //
        $nCLUTPoints = pow(2, $nInput) * $nOutput;
        $clutTable;
        for ($i = 0 ; $i < $nCLUTPoints ; $i++) {
            $clutTable [] = $reader->getUI16BE();
        }
        $this->clutTable = $clutTable;
        //
        $outputTables = array();
        for($i = 0 ; $i < $nOutput ; $i++) {
            $outputTableEntry = array();
            for($j = 0 ; $j < $nOutputTableEntries ; $j++) {
                $outputTableEntry []= $reader->getUI16BE();
            }
            $outputTables []= array($outputTableEntry);
        }
        $this->outputTables = $outputTables;
    }

    function dumpContent($opts = array()) {
        var_dump($this);
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
