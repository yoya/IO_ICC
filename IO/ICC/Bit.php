<?php
/*
  IO_ICC class
  (c) 2015/08/02- yoya@awm.jp
*/

if (is_readable('vendor/autoload.php')) {
    require 'vendor/autoload.php';
} else {
    require_once 'IO/Bit.php';
}

class IO_ICC_Bit extends IO_Bit {
    function getS15Fixed16Number() {
        return $this->getSI32BE() / 0x10000;
    }
    function getDateTimeNumber() {
        $dateTime = array(
                          'Year' => $this->getUI16BE(),
                          'Month' => $this->getUI16BE(),
                          'Day' => $this->getUI16BE(),
                          'Hours' => $this->getUI16BE(),
                          'Minutes' => $this->getUI16BE(),
                          'Seconds' => $this->getUI16BE(),
                          );
        return $dateTime;
    }
    function getXYZNumber() {
        $xyz =
            array(
                  'X' => $this->getS15Fixed16Number(),
                  'Y' => $this->getS15Fixed16Number(),
                  'Z' => $this->getS15Fixed16Number(),
                  );
        return $xyz;
    }
}