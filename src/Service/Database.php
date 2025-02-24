<?php

namespace API\Service;
use Exception;
use PDO;
use PDOException;
use PDOStatement;

class Database{
    private $host;
    private $password;
    private  $login;
    private $database;
    private $port;

    private $connexion;

    public function __construct(){
        $this->host = '127.0.0.1';
        $this->password = '';
        $this->login = 'root';
        $this->database = 'ap4';
        $this->port = "3306";
        $this->connexion();
    }
    private function connexion(){
        // try{
            $this->connexion = new PDO("mysql:host=" . $this->host . ";port=" . $this->port ." ;dbname=" . $this->database . ";charset=utf8", $this->login, $this->password);
        // }catch(PDOException $e){
        //     throw new Exception($e->getMessage());
        // }
    }

    
}