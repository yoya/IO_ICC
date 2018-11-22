<?php

/*
 * 2010/8/12- (c) yoya@awm.jp
 */

require_once dirname(__FILE__).'/Exception.php';
require_once dirname(__FILE__).'/../ICC.php';

class IO_ICC_Editor extends IO_ICC {
    // var $_headers = array(); // protected
    // var $_tags = array();    // protected

    function rebuild($opts = array()) {
        assert(is_array($opts));
        foreach ($this->_tags as &$tag) {
            if ($tag->parseTagContent($opts)) {
                $tag->content = null;
            }
        }
    }
    function deleteTag($delsig) {
        foreach ($this->_tagTable as $idx => &$tag) {
            if ($tag['Signature'] === $delsig)  {
                unset($this->_tagTable[$idx]);
            }
        }
        foreach ($this->_tags as $idx => &$tag) {
            if ($tag->signature === $delsig)  {
                unset($this->_tags[$idx]);
            }
        }
    }
}
