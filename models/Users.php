<?php

class Application_Model_Users
{
    protected $_usr_id;
    protected $_usr_username;
    protected $_usr_password;
    protected $_usr_type;
 
    public function __construct(array $options = null)
    {
        if (is_array($options)) {
            $this->setOptions($options);
        }
    }
 
    public function setOptions(array $options)
    {
        $methods = get_class_methods($this);
        foreach ($options as $key => $value) {
            $method = 'set' . ucfirst($key);
            if (in_array($method, $methods)) {
                $this->$method($value);
            }
        }
 
        return $this;
    }
 
    public function setId($id)
    {
        $this->_usr_id = (int) $id;
 
        return $this;
    }
 
    public function getId()
    {
        return $this->_usr_id;
    }
 
    public function setEmail($username)
    {
        $this->_usr_username = (string) $username;
 
        return $this;
    }
 
    public function getUsername()
    {
        return $this->_usr_username;
    }
 
    public function setPassword($password)
    {
        $this->_usr_password = (string) $password;
 
        return $this;
    }
 
    public function getPassword()
    {
        return $this->_usr_password;
    }
 
    public function setRole($role)
    {
        $this->_usr_type = (string) $role;
 
        return $this;
    }
 
    public function getRole()
    {
        return $this->_role;
    }
}