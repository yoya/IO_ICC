<?php

abstract class IO_ICC_Tag_Base {
    var $_iccInfo;
    function __construct($iccInfo = null) {
        $this->_iccInfo = $iccInfo;
    }
    abstract function parseContent($type, $content, $opts = array());
    abstract function dumpContent($type, $opts = array());
    abstract function buildContent($type, $opts = array());
}
