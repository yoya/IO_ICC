<?php

require_once dirname(__FILE__).'/../Bit.php';
require_once dirname(__FILE__).'/../FixedArray.php';
require_once dirname(__FILE__).'/Base.php';

class IO_ICC_Type_MFT1 extends IO_ICC_Type_Base {
    const DESCRIPTION = 'Multi Function Table with 1 byte presision';
    var $text = null;
    var $nInput, $nOutput;
    var $nCLUTGridPoints;
    var $matrix;
    var $inputTables, $clutTable, $outputTables;
    var $nInputTableEntries, $nOutputTableEntries;
    var $type;
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
            $matrix []= $reader->getS15Fixed16Number();
        }
        $this->matrix = $matrix;
        //
        $nInputTableEntries  = 256;
        $nOutputTableEntries = 256;
        $this->nInputTableEntries = $nInputTableEntries;
        $this->nOutputTableEntries = $nOutputTableEntries;
        //
        $inputTables = array();
        for($i = 0 ; $i < $nInput ; $i++) {
            $inputTableEntry = new IO_ICC_FixedArray($nInputTableEntries);
            for($j = 0 ; $j < $nInputTableEntries ; $j++) {
                $inputTableEntry[$j] = $reader->getUI8();
            }
            $inputTables []= $inputTableEntry;
        }
        $this->inputTables = $inputTables;
        //
        $nCLUTPoints = pow($nCLUTGridPoints, $nInput) * $nOutput;
        $clutTable = new IO_ICC_FixedArray($nCLUTPoints);
        for ($i = 0 ; $i < $nCLUTPoints ; $i++) {
            $clutTable[$i] = $reader->getUI8();
        }
        $this->clutTable = $clutTable;
        //
        $outputTables = array();
        for($i = 0 ; $i < $nOutput ; $i++) {
            $outputTableEntry = new IO_ICC_FixedArray($nOutputTableEntries);
            for($j = 0 ; $j < $nOutputTableEntries ; $j++) {
                $outputTableEntry[$j] = $reader->getUI8();
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
                echo " ".$inputTableEntry->slice(0, 4)->join(" ")." ... ".$inputTableEntry->slice(-4, 4)->join(" ");
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
            echo " ".$clutTable->slice(0, 4)->join(" ")." ... ".$clutTable->slice(-4, 4)->join(" ");
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
                echo " ".$outputTableEntry->slice(0, 4)->join(" ")." ... ".$outputTableEntry->slice(-4, 4)->join(" ");
            }
            echo PHP_EOL;
        }

    }

    function buildContent($opts = array()) {
        $writer = new IO_ICC_Bit();
        $writer->putData($this->type);
        $writer->putData("\0\0\0\0");
        //
        $nInput  = $this->nInput;
        $nOutput = $this->nOutput;
        $writer->putUI8($nInput);
        $writer->putUI8($nOutput);
        $nCLUTGridPoints = $this->nCLUTGridPoints;
        $writer->putUI8($nCLUTGridPoints);
        $writer->putUI8(0); // reserved for padding
        //
        $matrix = $this->matrix;
        for ($i = 0 ; $i < 9 ; $i++) {
            $writer->putS15Fixed16Number($matrix[$i]);
        }
        //
        $nInputTableEntries  = 256;
        $nOutputTableEntries = 256;
        //
        $inputTables = $this->inputTables;
        for($i = 0 ; $i < $nInput ; $i++) {
            $inputTableEntry = $inputTables[$i];
            for($j = 0 ; $j < $nInputTableEntries ; $j++) {
                $writer->putUI8($inputTableEntry[$j]);
            }
        }
        //
        $nCLUTPoints = pow($nCLUTGridPoints, $nInput) * $nOutput;
        $clutTable = $this->clutTable;
        for ($i = 0 ; $i < $nCLUTPoints ; $i++) {
            $writer->putUI8($clutTable[$i]);
        }
        //
        $outputTables = $this->outputTables;
        for($i = 0 ; $i < $nOutput ; $i++) {
            $outputTableEntry = $outputTables [$i];
            for($j = 0 ; $j < $nOutputTableEntries ; $j++) {
                $writer->putUI8($outputTableEntry[$j]);
            }
        }
    	return $writer->output();
    }
}
