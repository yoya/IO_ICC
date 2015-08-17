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
    function getU8Fixed8Number() {
        return $this->getUI16BE() / 0x100;
    }
    function putU8Fixed8Number($value) {
        return $this->putUI16BE($value * 0x100);
    }
    function getS15Fixed16Number() {
        return $this->getSI32BE() / 0x10000;
    }
    function putS15Fixed16Number($value) {
        return $this->putSI32BE($value * 0x10000);
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
    function putDateTimeNumber($datetime) {
        $this->putUI16BE($datetime['Year']);
        $this->putUI16BE($datetime['Month']);
        $this->putUI16BE($datetime['Day']);
        $this->putUI16BE($datetime['Hours']);
        $this->putUI16BE($datetime['Minutes']);
        $this->putUI16BE($datetime['Seconds']);
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
    function putXYZNumber($xyz) {
        $this->putS15Fixed16Number($xyz['X']);
        $this->putS15Fixed16Number($xyz['Y']);
        $this->putS15Fixed16Number($xyz['Z']);
        return $xyz;
    }
    function getAsciiZ($len = null) {
        $this->byteAlign();
        if (is_null($len)) {
            $pos = strpos($this->_data, "\0", $this->_byte_offset);
            if ($pos === false) {
                return $this->getDataUntil(null);
            }
            $len = $pos - $this->_byte_offset;
            $str = $this->getData($len);
            $this->incrementOffset(1, 0); // skip "\0"
            return $str;
        }
        $str = $this->getData($len);
        $pos = strpos($str, "\0");
        if ($pos === false) {
            return $str;
        }
        return substr($str, 0, $pos);
    }
    function putAsciiZ($str, $len = null, $padstr = ' ') {
        $pos = strpos($d, "\0");
        if ($pos !== false) {
            $str = substr($str, 0, $pos);
        }
        $slen = substr($str);
        if ($slen + 1 < $len) {
            $str = str_pad($str, $len - 1, $padstr);
        }
        $str .= "\0";
        $this->putdata($str, $len);
    }
}