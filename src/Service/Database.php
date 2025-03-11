<?php

namespace SLIMAPI\Service;

use Exception;
use PDO;
use PDOException;

class Database
{
    private $host;
    private $login;
    private $passwd;
    private $base;
    private PDO $connection;
    private $port;

    public function __construct()
    {
        $this->host = "localhost";
        $this->login = "root";
        $this->passwd = "";
        $this->base = "ap4";
        $this->port = "3306";
        $this->connection();
    }

    private function connection()
    {
        try {
            $this->connection = new PDO("mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->base . ";charset=utf8", $this->login, $this->passwd);
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }
    public function getTable($table)
    {
        $query = "SELECT * FROM $table";
        $req = $this->connection->prepare($query);
        $req->execute();
        return $req->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLigne($table, $id)
    {
        $query = "SELECT * FROM $table WHERE id = ?";
        $req = $this->connection->prepare($query);
        $req->execute([$id]);
        return $req->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteLigne($table, $id)
    {
        bool:
        $vRetour = false;
        try {
            $query = "DELETE FROM $table WHERE id = ?";
            $req = $this->connection->prepare($query);
            $req->execute([$id]);
            $vRetour = true;
        } catch (Exception $ex) {
            echo "La suppression a échoué : $ex";
            $vRetour = false;
        }
        return $vRetour;
    }

    public function put($table, $id, $params)
    {
        $vRetour = false;
        try {
            $query = "UPDATE $table SET ";
            foreach ($params as $key => $values) {
                $query .= " $key = ";
                $query .= ":$key,";
            }
            $query = substr($query, 0, -1) . " WHERE id = $id;";
            echo $query;
            $req = $this->connection->prepare($query);
            $req->execute($params);
            $vRetour = true;
        } catch (Exception $ex) {
            echo "La modification a échoué : $ex";
            $vRetour = false;
        }
        return $vRetour;
    }

    public function post($table, $params)
    {
        bool:
        $vRetour = false;
        try {
            $query = "INSERT INTO $table VALUES(";

            foreach ($params as $key => $value) {
                $query .= ":$key,";
            }
            $query = substr($query, 0, -1) . ");";
            echo $query;
            $req = $this->connection->prepare($query);
            $req->execute($params);
            $vRetour = true;
        } catch (Exception $ex) {
            echo "La création a échoué : $ex";
            $vRetour = false;
        }
        return $vRetour;
    }

    public function lastId($table)
    {
        try {
            $query = "SELECT id from $table order by id DESC LIMIT 1;";

            $req = $this->connection->prepare($query);
            $req->execute();

            return $req->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}

