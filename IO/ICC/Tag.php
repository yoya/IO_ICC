<?php

/*
 * 2015/8/2- (c) yoya@awm.jp
 */

require_once dirname(__FILE__).'/../ICC.php';
require_once dirname(__FILE__).'/../ICC/Exception.php';
require_once dirname(__FILE__).'/Type.php';

class IO_ICC_Tag {
    var $tagInfo = null;
    var $iccInfo = null;
    var $signature = null;
    var $tag = null;
    //
    function __construct($iccInfo) {
        $this->iccInfo = $iccInfo;
    }
    function parse($reader, $tagInfo, $opts = array()) {
        $this->tagInfo = $tagInfo;
        $this->signature = $tagInfo['Signature'];
        $this->_Offset = $tagInfo['Offset'];
        $this->_Size = $tagInfo['Size'];
        $reader->setOffset($this->_Offset, 0);
        $this->content = $reader->getData($this->_Size);
        $this->type = substr($this->content, 0, 4);
    }
    function dump($opts = array()) {
        $tagInfo = $this->tagInfo;
        $hexdump = ! empty($opts['hexdump']);
        echo "Signature:{$tagInfo['Signature']}";
        echo " (Offset:{$tagInfo['Offset']} Size:{$tagInfo['Size']})".PHP_EOL;
        echo "    Type:{$this->type}".PHP_EOL;
        $opts['level'] = 0;
        if ($this->parseTagContent($opts)) {
            $this->tag->dumpContent($opts);
        }
        if ($hexdump) {
            $opts['iobit']->hexdump($tagInfo['Offset'], $tagInfo['Size']);
        }
    }
    function build($opts = array()) {
        $type = $this->type;
        if (is_null($this->content)) {
            $this->content = $this->buildTagContent();
        }
        return $this->content;
    }
    function parseTagContent($opts = array()) {
        if (is_null($this->tag) === false) {
            return true;
        }
        if (is_null($this->content)) {
            throw new IO_ICC_Exception("no tag and no content in ".var_export($this, true));
        }
        $tag = IO_ICC_Type::makeType($this->content, $this->iccInfo, $opts);
        if ($tag === false) {
            return false;
        }
        $this->tag = $tag;
        return true;
    }
    function buildTagContent() {
        if ((is_null($this->content) === false)) {
            return $this->content;
        }
        $type = $this->type;
        return $this->tag->buildContent($type);
    }
}


