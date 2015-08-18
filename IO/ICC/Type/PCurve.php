<?php

require_once dirname(__FILE__).'/../Exception.php';
require_once dirname(__FILE__).'/../Bit.php';
require_once dirname(__FILE__).'/Base.php';

class IO_ICC_Type_PCurve extends IO_ICC_Type_Base {
    const DESCRIPTION = 'PCurve Type';
    var $type = null;
    var $functionType = null;
    var $params = null;
    static $fieldLengthByFunctionType =
        array(
              0x0000 =>  4,
              0x0001 => 12,
              0x0002 => 16,
              0x0003 => 20,
              0x0004 => 28,
              );
    function parseContent($content, $opts = array()) {
        $reader = new IO_ICC_Bit();
    	$reader->input($content);
        $this->type = $reader->getData(4);
        $reader->incrementOffset(4, 0); // skip
        $functionType = $reader->getUI16BE();
        $this->functionType = $functionType;
        $reader->incrementOffset(2, 0); // reserved, shall be set to 0
        $fieldLength = self::$fieldLengthByFunctionType[$functionType];
        $count = $fieldLength / 4;
        $params = array();
        for ($i = 0 ; $i < $count ; $i++) {
            $params []= $reader->getS15Fixed16Number();
        }
        $this->params = $params;
    }

    function dumpContent($opts = array()) {
        $this->echoIndentSpace($opts);
        echo "FunctionType:{$this->functionType}".PHP_EOL;
        $this->echoIndentSpace($opts);
        echo "    Params:";
        foreach ($this->params as $param) {
            printf(" %.04f", $param);
        }
        echo PHP_EOL;
    }

    function buildContent($opts = array()) {
        $writer = new IO_ICC_Bit();
        $writer->putData($this->type);
        $writer->putData("\0\0\0\0");
        //
        $functionType = $this->functionType;
        $writer->putUI16BE($functionType);
        $fieldLength = self::$fieldLengthByFunctionType[$functionType];
        $count = $fieldLength / 4;
        $params = $this->params;
        if (count($params) !== $count) {
            throw new IO_ICC_Exception("count(params):{count($params)} !== count:$count");
        }
        foreach ($params as $param) {
            $writer->putS15Fixed16Number($param);
        }
    	return $writer->output();
    }
}
