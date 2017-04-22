<?php
class Zend_View_Helper_Encrypt extends Zend_View_Helper_Abstract{

    public function encrypt($plain){
      $cipher = array();
		for ($x=0; $x<strlen($plain); $x++) {
			ord($plain[$x]);
		$cipher[] = ord($plain[$x]);
		}
		return implode('/', $cipher);
       
    }    

    
}
?>
