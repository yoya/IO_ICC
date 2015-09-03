<?php

require_once dirname(__FILE__).'/../Bit.php';
require_once dirname(__FILE__).'/Base.php';

class IO_ICC_Type_MFT2 extends IO_ICC_Type_Base {
    const DESCRIPTION = 'Multi Function Table with 2 byte presision';
    var $text = null;
    var $nInput, $nOutput;
    var $nCLUTGridPoints;
    var $matrix;
    var $nInputTableEntries, $nOutputTableEntries;
    var $inputTables, $clutTable, $outputTables;
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
        $nCLUTGridPoints = $reader->getUI8();
        $this->nCLUTGridPoints = $nCLUTGridPoints;
        $reader->incrementOffset(1, 0); // reserved for padding
        //
        $matrix = array();
        for ($i = 0 ; $i < 9 ; $i++) {
            $matrix[] = $reader->getS15Fixed16Number();
        }
        $this->matrix = $matrix;
        //
        $nInputTableEntries = $reader->getUI16BE();
        $nOutputTableEntries = $reader->getUI16BE();
        $this->nInputTableEntries = $nInputTableEntries;
        $this->nOutputTableEntries = $nOutputTableEntries;
        $inputTables = array();
        for($i = 0 ; $i < $nInput ; $i++) {
            $inputTableEntry = array();
            for($j = 0 ; $j < $nInputTableEntries ; $j++) {
                $inputTableEntry []= $reader->getUI16BE();
            }
            $inputTables []= $inputTableEntry;
        }
        $this->inputTables = $inputTables;
        //
        $nCLUTPoints = pow($nCLUTGridPoints, $nInput) * $nOutput;
        $clutTable = array();
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
            $outputTables []= $outputTableEntry;
        }
        $this->outputTables = $outputTables;
    }

    function dumpContent($opts = array()) {
        $opts2 = $opts;
        $opts2["level"]++;
        $nInput  = $this->nInput;
        $nOutput = $this->nOutput;
        $nCLUTGridPoints = $this->nCLUTGridPoints;
        $this->echoIndentSpace($opts);
        echo "nInput:{$nInput} nOutput:{$nOutput} nCLUTGridPoints:{$nCLUTGridPoints}".PHP_EOL;
        $this->echoIndentSpace($opts);
        echo "Matrix:".PHP_EOL;
        for ($y = 0 ; $y < 3 ; $y++) {
            $this->echoIndentSpace($opts2);
            for ($x = 0 ; $x < 3 ; $x++) {
                printf("  %2.4f", $this->matrix[$x + 3*$y]);
            }
            echo PHP_EOL;
        }
        //
        $nInputTableEntries = $this->nInputTableEntries;
        $nOutputTableEntries = $this->nOutputTableEntries;
        $this->echoIndentSpace($opts);
        echo "nInputTableEntries:$nInputTableEntries nOutputTableEntries:$nOutputTableEntries".PHP_EOL;
        //
        $this->echoIndentSpace($opts);
        $inputTables = $this->inputTables;
        echo "InputTable(count=".count($inputTables)."):".PHP_EOL;
        foreach ($inputTables as $idx => $inputTableEntry) {
            $this->echoIndentSpace($opts2);
            echo "InputTable[$idx]:";
            if (count($inputTableEntry) <= 16) {
                foreach ($inputTableEntry as $value) {
                    echo " $value";
                }
            } else {
                echo " ".join(" ", array_slice($inputTableEntry, 0, 4))." ... ".join(" ", array_slice($inputTableEntry, -4, 4));
            }
            echo PHP_EOL;
        }
        //
        $nCLUTPoints = pow($nCLUTGridPoints, $nInput) * $nOutput;
        $clutTable  = $this->clutTable;
        if (count($clutTable) !== $nCLUTPoints) {
            new IO_ICC_Exception("count(clutTable):".count($clutTable)." !== nCLUTPoints:$nCLUTPoints");
        }
        $this->echoIndentSpace($opts);
        echo "CLUTTable(len=".$nCLUTPoints."):";
        if ($nCLUTPoints <= 16) {
            foreach ($clutTable as $value) {
                echo " $value";
            }
        } else {
            echo " ".join(" ", array_slice($clutTable, 0, 4))." ... ".join(" ", array_slice($clutTable, -4, 4));
        }
        echo PHP_EOL;
        //
        $this->echoIndentSpace($opts);
        $outputTables = $this->outputTables;
        echo "OutputTable(count=".count($outputTables)."):".PHP_EOL;
        foreach ($outputTables as $idx => $outputTableEntry) {
            $this->echoIndentSpace($opts2);
            echo "OutputTable[$idx]:";
            if (count($outputTableEntry) <= 16) {
                foreach ($outputTableEntry as $value) {
                    echo " $value";
                }
            } else {
                echo " ".join(" ", array_slice($outputTableEntry, 0, 4))." ... ".join(" ", array_slice($outputTableEntry, -4, 4));
            }
            echo PHP_EOL;
        }

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
