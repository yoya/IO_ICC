<?php

abstract class IO_ICC_Type_Base {
    var $_iccInfo;
    function __construct($iccInfo = null) {
        $this->_iccInfo = $iccInfo;
    }
    abstract function parseContent($content, $opts = array());
    abstract function dumpContent($opts = array());
    abstract function buildContent($opts = array());
}
