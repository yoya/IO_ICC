<?php

class IO_ICC_FixedArray extends SplFixedArray {
    // ref) https://github.com/devi/Salt/blob/master/FieldElement.php
    function slice($offset, $length) {
        $origsize = $this->getSize();
        if ($offset < 0) {
            $offset += $origsize;
        }
        if (($length === 0) || ($origsize  < $offset + $length)) {
            $length =  $origsize - $offset;
        }
        $slice = new IO_ICC_FixedArray($length);
        $j = $offset;
        for ($i = 0 ; $i < $length ; ++$i) {
            $slice[$i] = $this->offsetGet($j);
            $j++;
        }
        return $slice;
    }
    function join($glue) {
        $arr = $this->toArray();
        return join($glue, $arr);
    }
    function shuffle() {
        $size = $this->getSize();
        $newArr = new IO_ICC_FixedArray($size);
        $arr = (array) $this;
        shuffle($arr);
        for ($i = 0 ; $i < $size ; $i++) {
            $newArr[$i] = $arr[$i];
        }
    }
}

