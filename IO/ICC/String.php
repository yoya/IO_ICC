<?php

class IO_ICC_String {
    static function fixAsciiZ($str) {
        $pos = strpos($str, "\0");
        if ($pos !== false) {
            if ($pos === (strlen($str) - 1)) {
                return $str;
            }
            $str = substr($str, 0, $pos);
        }
        $str .= "\0";
        return $str;
    }
    static function trimNullTerminate($str) {
        $pos = strpos($str, "\0");
        if ($pos === false) {
            return $str;
        }
        return substr($str, 0, $pos);
    }
}
















