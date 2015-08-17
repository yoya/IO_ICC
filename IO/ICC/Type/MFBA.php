<?php

require_once dirname(__FILE__).'/../Bit.php';
require_once dirname(__FILE__).'/Base.php';
require_once dirname(__FILE__).'/Curve.php';

class IO_ICC_Type_MFBA extends IO_ICC_Type_Base {
    const DESCRIPTION = 'MultiFunction BtoA Table';
    var $_iccInfo = null;
    var $type = null;
    var $nInput, $nOutput;
    var $bCurves = null;
    var $matrix = null;
    var $mCurves = null;
    var $clut = null;
    var $aCurves = null;
    function __construct($iccInfo) {
        $this->_iccInfo = $iccInfo;
    }
    function parseContent($content, $opts = array()) {
        $reader = new IO_ICC_Bit();
    	$reader->input($content);
        $this->type = $reader->getData(4);
        $reader->incrementOffset(4, 0); // skip
        //
        $this->nInput = $reader->getUI8();
        $this->nOutput = $reader->getUI8();
        $reader->incrementOffset(2, 0); // reserved padding
        $offsetToBCurve = $reader->getUI32BE();
        $offsetToMatrix = $reader->getUI32BE();
        $offsetToMCurve = $reader->getUI32BE();
        $offsetToCLUT = $reader->getUI32BE();
        $offsetToACurve = $reader->getUI32BE();
        //        var_dump($offsetToBCurve, $offsetToMatrix, $offsetToMCurve, $offsetToCLUT, $offsetToACurve);
        // B Curves
        if ($offsetToBCurve === 0) {
            $this->bCurves = null;
        } else {
            $reader->setOffset($offsetToBCurve, 0);
            $bCurveContent = $reader->getData($offsetToMatrix - $offsetToBCurve);
            $bCurves = array();
            for ($i = 0 ; $i < $this->nInput; $i++ ) {
                $bCurve = IO_ICC_Type::makeType($bCurveContent, $this->_iccInfo);
                if ($bCurve === false) {
                    break;
                }
                $bCurve->parseContent($bCurveContent);
                $bCurves []= $bCurve;
                $bCurveContent = substr($bCurveContent, $bCurve->getContentLength());
            }
            $this->bCurves = $bCurves;
        }
        // Matrix
        if ($offsetToMatrix === 0) {
            $this->matrix = null;
        } else {
            $reader->setOffset($offsetToMatrix, 0);
            $matrix = array();
            for ($i = 0 ; $i < 12 ; $i++) {
                $matrix []= $reader->getS15Fixed16Number();
            }
            $this->matrix = $matrix;
        }
        // M Curves
        if ($offsetToMCurve === 0) {
            $this->mCurves = null;
        } else {
            $reader->setOffset($offsetToMCurve, 0);
            $mCurveContent = $reader->getData($offsetToMatrix - $offsetToMCurve);
            $mCurves = array();
            for ($i = 0 ; $i < $this->nInput; $i++ ) {
                $mCurve = IO_ICC_Type::makeType($mCurveContent, $this->_iccInfo);
                if ($mCurve === false) {
                    break;
                }
                $mCurve->parseContent($mCurveContent);
                $mCurves []= $mCurve;
                $mCurveContent = substr($mCurveContent, $mCurve->getContentLength());
            }
            $this->mCurves = $mCurves;
        }
        // CLUT
        if ($offsetToCLUT === 0) {
            $this->clut = null;
        } else {
            $reader->setOffset($offsetToCLUT, 0);
            $grid = array();
            for ($i = 0 ; $i < $this->nInput; $i++ ) {
                $grid []= $reader->getUI8();
            }
            $reader->setOffset($offsetToCLUT + 16, 0);
            $precision = $reader->getUI8();
            $reader->incrementOffset(3, 0); // reserved for padding
            $count = $this->nInput;
            foreach ($grid as $g) {
                $count *= $g;
            }
            $data = array();
            if ($precision === 1) {
                for ($i = 0 ; $i < $count ; $i++) {
                    $data []= $reader->getUI16BE();
                } 
            } else {
                for ($i = 0 ; $i < $count ; $i++) {
                    $data []= $reader->getUI16BE();
                }
            }
            $this->clut =
                array(
                      'Grid' => $grid,
                      'Precision' => $precision,
                      'Data' => $data,
                      );
        }
        // A Curves
        if ($offsetToACurve === 0) {
            $this->aCurves = null;
        } else {
            $reader->setOffset($offsetToACurve, 0);
            $aCurveContent = $reader->getDataUntil(null);
            $aCurves = array();
            for ($i = 0 ; $i < $this->nOutput; $i++ ) {
                $aCurve = IO_ICC_Type::makeType($aCurveContent, $this->_iccInfo);
                if ($aCurve === false) {
                    break;
                }
                $aCurve->parseContent($aCurveContent);
                $aCurves []= $aCurve;
                $aCurveContent = substr($aCurveContent, $aCurve->getContentLength());
            }
        }
        $this->aCurves = $aCurves;
    }

    function dumpContent($opts = array()) {
        $nInput = $this->nInput;
        $nOutput = $this->nOutput;
        $this->echoIndentSpace($opts);
        echo "nInput:{$this->nInput} nOutput:{$nOutput}".PHP_EOL;
        $opts2 = $opts;
        $opts2["level"]++;
        // B Curves
        $this->echoIndentSpace($opts);
        echo "bCurves:".PHP_EOL;
        foreach ($this->bCurves as $bCurve) {
            $this->echoIndentSpace($opts2);
            echo "Type:{$bCurve->type}\n";
            $bCurve->dumpContent($opts2);
        }
        // Matrix
        $this->echoIndentSpace($opts);
        echo "Matrix:".PHP_EOL;
        for ($y = 0 ; $y < 3 ; $y++) {
            $this->echoIndentSpace($opts);
            for ($x = 0 ; $x < 3 ; $x++) {
                printf("  %2.4f", $this->matrix[$x + $y*3]);
            }
            printf("    %2.4f", $this->matrix[9 + $y]);
            echo PHP_EOL;
        }
        // M Curves
        $this->echoIndentSpace($opts);
        echo "mCurves:".PHP_EOL;
        foreach ($this->mCurves as $mCurve) {
            $this->echoIndentSpace($opts2);
            echo "Type:{$mCurve->type}\n";
            $mCurve->dumpContent($opts2);
        }
        // CLUT
        $this->echoIndentSpace($opts);
        echo "CLUT:".PHP_EOL;
        if (is_null($this->clut) === false) {
            $this->echoIndentSpace($opts2);
            $clut = $this->clut;
            echo "Grids:";
            for ($i = 0 ; $i < $nInput; $i++ ) {
                echo " ".$clut['Grid'][$i];
            }
            echo PHP_EOL;
            $this->echoIndentSpace($opts2);
            $precision = $clut['Precision'];
            echo "Precision:$precision".PHP_EOL;
            $this->echoIndentSpace($opts2);
            $data = $clut['Data'];
            $dataLen = count($data);
            $gridNum = $dataLen / $nOutput;
            echo "Data(gridNum=$gridNum):".PHP_EOL;
            for ($i = 0 ; $i < $nOutput ; $i++) {
                $this->echoIndentSpace($opts2);
                echo "  [$i]:";
                if ($gridNum < 16) {
                    for ($j = 0 ; $j < $gridNum ; $j++) {
                        echo " ".$data[$nInput*$j + $i];
                    }
                } else {
                    for ($j = 0 ; $j < 8 ; $j++) {
                        echo " ".$data[$nInput*$j + $i];
                    }
                    echo " ...";
                    for ($j = $gridNum - 8 ; $j < $gridNum ; $j++) {
                        echo " ".$data[$nInput*$j + $i];
                    }
                }
                echo PHP_EOL;
            }
        }
        //
        $this->echoIndentSpace($opts);
        echo "aCurves:".PHP_EOL;
        foreach ($this->aCurves as $aCurve) {
            $this->echoIndentSpace($opts2);
            echo "Type:{$aCurve->type}\n";
            $aCurve->dumpContent($opts2);
        }
    }

    function buildContent($opts = array()) {
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