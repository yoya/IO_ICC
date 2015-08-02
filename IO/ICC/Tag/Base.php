<?php

abstract class IO_ICC_Tag_Base {
    var $swfInfo;
    function __construct($iccInfo = null) {
        $this->swfInfo = $iccInfo;
    }
    abstract function parseContent($type, $content, $opts = array());
    abstract function dumpContent($type, $opts = array());
    abstract function buildContent($type, $opts = array());
}
