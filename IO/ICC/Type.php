<?php

/*
 * 2015/8/10- (c) yoya@awm.jp
 */

require_once dirname(__FILE__).'/../ICC.php';
require_once dirname(__FILE__).'/../ICC/Exception.php';

class IO_ICC_Type {
    static $typeMap =
        array(
              // signature => array(klass)
              'desc' => array('klass' => 'Desc', "version" => 2),
              'curv' => array('klass' => 'Curve'),
              'para' => array('klass' => 'PCurve'),
              'XYZ ' => array('klass' => 'XYZ', "version" => 2),
              'text' => array('klass' => 'Text', "version" => 2),
              'mluc' => array('klass' => 'MLUC', "version" => 4),
              'sf32' => array('klass' => 'SF32'),
              'mAB ' => array('klass' => 'MFAB'),
              'mBA ' => array('klass' => 'MFBA'),
              'sig ' => array('klass' => 'Signature'),
              );
    static function getTypeInfo($tagType, $key) {
        if (isset(self::$typeMap[$tagType][$key])) {
            return self::$typeMap[$tagType][$key];
        }
        return false;
    }
    static function makeType($content, $iccInfo, $count = 1) {
        $type = substr($content, 0, 4);
        $klass = self::getTypeInfo($type, 'klass');
        if ($klass === false) {
            return false; // no parse
        }
        require_once dirname(__FILE__)."/Type/$klass.php";
        $klass = "IO_ICC_Type_$klass";
        $obj = new $klass($iccInfo);
        $opts['Version'] = $iccInfo['Version'];
        $opts['type'] = $type;
        $obj->parseContent($content, $opts);
        $obj->_contentLength = strlen($content);
        return $obj;
    }
}