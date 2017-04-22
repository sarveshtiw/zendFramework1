<?php
class Zend_View_Helper_SetActive extends Zend_View_Helper_Abstract{

    public function setActive($language="en"){
       $request = new Zend_Controller_Request_Http();
       $locale = ($request->getCookie('lang'))?$request->getCookie('lang'):"en";
       return ($locale==$language)?"Active":"";
    }    

    
}
?>
