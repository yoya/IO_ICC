<?php

require_once dirname(__FILE__).'/../Bit.php';
require_once dirname(__FILE__).'/Base.php';

class IO_ICC_Type_Signature extends IO_ICC_Type_Base {
    const DESCRIPTION = 'Signature Type';
    var $type = null;
    var $xyz = null;
    var $signature = null;
    function parseContent($content, $opts = array()) {
        $reader = new IO_ICC_Bit();
    	$reader->input($content);
        $this->type = $reader->getData(4);
        $reader->incrementOffset(4, 0); // skip
        $this->signature = $reader->getData(4);
    }

    function dumpContent($opts = array()) {
        echo "        Signature:";
        foreach (str_split($this->signature) as $b) {
            printf(" %02X", ord($b));
        }
        printf(" (%s)", $this->signature);
        echo PHP_EOL;
    }

    function buildContent($opts = array()) {
        $writer = new IO_ICC_Bit();
        $writer->putData($this->type);
        $writer->putData("\0\0\0\0");
        //
        $sig = $this->signature;
        if (strlen($sig) < 4) {
            $sig = str_pad($sig, 4, " ");
        }
        $writer->putData($sig, 4);
    	return $writer->output();
    }
}
