<?php

class Zend_View_Helper_SetLanguageFlag extends Zend_View_Helper_Abstract {

    public function SetLanguageFlag() {
        $langSess = new Zend_Session_Namespace('language');
        return "/menu/".$langSess->locale."_flag.png";
    }

}

?>
