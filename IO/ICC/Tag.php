<?php

/*
 * 2015/8/2- (c) yoya@awm.jp
 */

require_once dirname(__FILE__).'/../ICC.php';
require_once dirname(__FILE__).'/../ICC/Exception.php';

class IO_ICC_Tag {
    var $tagInfo = null;
    var $iccInfo = null;
    var $signature = null;
    var $tag = null;
    //
    static $typeMap =
        array(
              // signature => array(klass)
              'desc' => array('klass' => 'Desc', "version" => 2),
              'curv' => array('klass' => 'Curve', "version" => 4),
              'XYZ ' => array('klass' => 'XYZ', "version" => 2),
              'text' => array('klass' => 'Text', "version" => 2),
              'mluc' => array('klass' => 'MLUC', "version" => 4),
              'sf32' => array('klass' => 'SF32'),
              );
    function __construct($iccInfo) {
        $this->iccInfo = $iccInfo;
    }
    function getTypeInfo($tagType, $key) {
        if (isset(self::$typeMap[$tagType][$key])) {
            return self::$typeMap[$tagType][$key];
        }
        return false;
    }
    function parse($reader, $tagInfo, $opts = array()) {
        $this->tagInfo = $tagInfo;
        $this->signature = $tagInfo['Signature'];
        $reader->setOffset($tagInfo['Offset'], 0);
        $this->content = $reader->getData($tagInfo['Size']);
        $this->type = substr($this->content, 0, 4);
    }
    function dump($opts = array()) {
        $tagInfo = $this->tagInfo;
        echo "    Signature:{$tagInfo['Signature']} Type:{$this->type}";
        echo " (Offset:{$tagInfo['Offset']} Size:{$tagInfo['Size']})".PHP_EOL;
        if ($this->parseTagContent($opts)) {
            $this->tag->dumpContent($this->type, $opts);
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
        $type = $this->type;
        $klass = self::getTypeInfo($type, 'klass');
        if ($klass === false) {
            return false; // no parse
        }
        require_once dirname(__FILE__)."/Type/$klass.php";
        $klass = "IO_ICC_Type_$klass";
        $obj = new $klass($this->iccInfo);
        $opts['Version'] = $this->iccInfo['Version'];
        $opts['type'] = $type;
        $obj->parseContent($type, $this->content, $opts);
        $this->tag = $obj;
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


