<?php
class Zend_View_Helper_WaCommon extends Zend_View_Helper_Abstract{  
    
    public function WaCommon()
    {
       return $this; 
    }
    
  public function setEncrypt($data)
    {
        $encryption_key  = "@#$%^&*!";
        $iv_size = mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_ECB);
        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
        $encrypted_string = mcrypt_encrypt(MCRYPT_BLOWFISH, $encryption_key, utf8_encode($data), MCRYPT_MODE_ECB, $iv);
        return base64_encode($encrypted_string);
    }
    
    public function setDecrypt($data)
    {
        $encryption_key    = utf8_encode("@#$%^&*!");        
        $iv_size = mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_ECB);
        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
        $decrypted_string = mcrypt_decrypt(MCRYPT_BLOWFISH, $encryption_key, base64_decode($data), MCRYPT_MODE_ECB, $iv);
        return $decrypted_string;
    }
    
}
