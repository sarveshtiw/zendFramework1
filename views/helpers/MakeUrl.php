<?php
class Zend_View_Helper_MakeUrl extends Zend_View_Helper_Abstract{

    public function makeUrl($url = false){
       $fc =  Zend_controller_front::getInstance();
       $baseUrl = $fc->getBaseUrl();
       $completeUrl = ($url)?$baseUrl.$url:$baseUrl;
       
       return $completeUrl;
       
    }    
    
}
