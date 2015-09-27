<?php
/*
  IO_ICC_Type_Measure class
  (c) 2015/09/27- yoya@awm.jp
*/

require_once dirname(__FILE__).'/../Bit.php';
require_once dirname(__FILE__).'/Base.php';

class IO_ICC_Type_Measure extends IO_ICC_Type_Base {
    const DESCRIPTION = 'measurement Type';
    var $type;
    var $stdObserver;
    var $nCIEXYZ;
    var $measureGeometry;
    var $measureFlare;
    var $stdIlluminant;
    static $stdObserverType =
        array(
              0 => 'Unknown',
              1 => 'CIE 1931 standard colormetric observer',
              2 => 'CIE 1964 standard colormetric observer',
              );
    static $measureGeometryType =
        array(
              0 => 'Unknown',
              1 => '0:45 or 45:0',
              2 => '0:d or d:0',
              );
    static $measureFlareType =
        array(
              0 => '0 (0 %)',
              1 => '1.0 (or 100%)',
              );
    static $stdIlluminantType =
        array (
               0 => 'Unknown',
               1 => 'D50',
               2 => 'D65',
               3 => 'D93',
               4 => 'F2',
               5 => 'D55',
               6 => 'A',
               7 => 'Equi-Power (E)',
               8 => 'F8',
               );
    function parseContent($content, $opts = array()) {
        $reader = new IO_ICC_Bit();
    	$reader->input($content);
        $this->type = $reader->getData(4);
        $reader->incrementOffset(4, 0); // skip
        //
        $this->stdObserver = $reader->getUI32BE();
        $this->nCIEXYZ = $reader->getXYZNumber();
        $this->measureGeometry = $reader->getUI32BE();
        $this->stdIlluminant = $reader->getUI32BE();
    }

    function dumpContent($opts = array()) {
        $stdObserver = $this->stdObserver;
        $stdObserverStr = self::$stdObserverType[$stdObserver];
        $measureGeometry = $this->measureGeometry;
        $measureGeometryStr = self::$measureGeometryType[$measureGeometry];
        $stdIlluminant = $this->stdIlluminant;
        $stdIlluminantStr = self::$stdIlluminantType[$stdIlluminant];
        //
        echo "        StdObserver:$stdObserver $stdObserverStr ".PHP_EOL;
        echo "        nCIEXYZ:";
        foreach ($this->nCIEXYZ as $key => $value) {
            printf(" %s:%.4f", $key, $value);
        }
        echo PHP_EOL;
        echo "        measureGeometry:$measureGeometry $measureGeometryStr".PHP_EOL;
        echo "        stdIlluminant:$stdIlluminant $stdIlluminantStr".PHP_EOL;
    }

    function buildContent($opts = array()) {
        $writer = new IO_ICC_Bit();
        $writer->putData($this->type);
        $writer->putData("\0\0\0\0");
        $writer->incrementOffset(4, 0); // skip
        //
        $writer->putUI32BE($this->stdObserver);
        $writer->putXYZNumber($this->nCIEXYZ);
        $writer->putUI32BE($this->measureGeometry);
        $writer->putUI32BE($this->stdIlluminant);
        //
    	return $writer->output();
    }
}
