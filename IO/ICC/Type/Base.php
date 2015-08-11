<?php

abstract class IO_ICC_Type_Base {
    var $_iccInfo;
    var $_contentLength;
    function __construct($iccInfo = null) {
        $this->_iccInfo = $iccInfo;
    }
    abstract function parseContent($content, $opts = array());
    abstract function dumpContent($opts = array());
    abstract function buildContent($opts = array());
    static function echoIndentSpace($opts) {
        $indent_level = $opts['level'] + 1;
        echo str_repeat(" ", $indent_level*4);
    }
    function getContentLength() {
        return $this->_contentLength;
    }
}
