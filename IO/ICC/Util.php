<?php
/*
  IO_ICC_Util class
  (c) 2015/09/25- yoya@awm.jp
*/

class IO_ICC_Util {
    static function alignedLength($length, $align) {
        $remainder = $length % $align;
        if ($remainder) {
            $length += $align - $remainder;
        }
        return $length;
    }
}

