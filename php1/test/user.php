<?php


class User {

    public $name;
    public $username;
    public $password;

    function __construct($name, $username, $password)
    {

    $this->name = $name;
    $this->username = $username;
    $this->password = $password;
        
    }

    function set_username($u){
        return $this->username = $u;

    }
    
     function get_username(){
        return $this->username;
        
    }

    function xuatthongtin() {
        echo $this->name ."<br>";
        echo $this->username ."<br>";
        echo $this->password . "<br>";
    }

}

$logined = new user("An","NTDA","******");

$logined->xuatthongtin();
$logined->set_username("ntda27107");
$logined->xuatthongtin();
$logined->get_username();

?>