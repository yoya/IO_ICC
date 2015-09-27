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
              // version 2
              'desc' => array('klass' => 'Desc'     , "version" => 2),
              'curv' => array('klass' => 'Curve'    , 'version' => 2),
              'XYZ ' => array('klass' => 'XYZ'      , "version" => 2),
              'text' => array('klass' => 'Text'     , "version" => 2),
              'sf32' => array('klass' => 'SF32'     , "version" => 2),
              'mft1' => array('klass' => 'MFT1'     , "version" => 2),
              'mft2' => array('klass' => 'MFT2'     , "version" => 2),
              'sig ' => array('klass' => 'Signature', "version" => 2),
              'meas' => array('klass' => 'Measure'  , 'version' => 2),
              'view' => array('klass' => 'View'     , "version" => 2),
              // version 4
              'para' => array('klass' => 'PCurve'   , "version" => 4),
              'mluc' => array('klass' => 'MLUC'     , "version" => 4),
              'mAB ' => array('klass' => 'MFAB'     , "version" => 4),
              'mBA ' => array('klass' => 'MFBA'     , "version" => 4),
              // max
              'dict' => array(/*'klass' => 'Dict'   ,*/ "version" => 5),
              'gbd ' => array(/*'klass' => 'Gamut'  ,*/ "version" => 5),
              'ZXML' => array(/*'klass' => 'ZXML'   ,*/ "version" => 5),
              );
    static function getTypeInfo($tagType, $key) {
        if (isset(self::$typeMap[$tagType][$key])) {
            return self::$typeMap[$tagType][$key];
        }
        return false;
    }
    static function makeType($content, $iccInfo, $opts = array()) {
        $type = substr($content, 0, 4);
        $klass = self::getTypeInfo($type, 'klass');
        if ($klass === false) {
            if (empty($opts['restrict'])) {
                return false; // no parse
            }
            throw new IO_ICC_Exception("klass === false (type:$type)");
        }
        require_once dirname(__FILE__)."/Type/$klass.php";
        $klass = "IO_ICC_Type_$klass";
        $obj = new $klass($iccInfo);
        $opts['Version'] = $iccInfo['Version'];
        $opts['type'] = $type;
        $obj->_contentLength = strlen($content);
        $obj->parseContent($content, $opts);
        return $obj;
    }
}
