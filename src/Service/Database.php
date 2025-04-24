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
        try {
            $query = "SELECT * FROM $table WHERE id = ?";
            $req = $this->connection->prepare($query);
            $req->execute([$id]);
            return $req->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            echo "Error";
        }

    }

    public function getVisitesPatient($id, $table)
    {
        try {
            if ($table == 'patient') {
                $para = 'id';
            } else {
                $para = 'patient';
            }
            $query = "SELECT * FROM $table WHERE $para = ?";
            $req = $this->connection->prepare($query);
            $req->execute([$id]);
            return $req->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            echo "Error";
        }

    }

    public function getVisitesInfirmiere($id, $table)
    {
        if ($table == 'infirmiere') {
            $para = 'id';
        } else {
            $para = 'infirmiere';
        }
        $query = "SELECT * FROM $table WHERE $para = ?";
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

    public function loginExist($login, $mdp)
    {
        try {
            $query = "SELECT id FROM personne_login WHERE login = ? and mp = MD5(?)";
            $req = $this->connection->prepare($query);
            $req->execute([$login, $mdp]);
            $result = $req->fetch(PDO::FETCH_ASSOC);


            if ($result) {
                return $result['id'];
            } else {
                return null;
            }
        } catch (Exception $ex) {
            echo "Erreur : $ex";
            return null;
        }
    }

    public function checkId($id)
    {

        $vRetour = '';
        try {
            if ($this->isInfirmiereEnChef($id)) {
                $vRetour = "infChef";
            } elseif ($this->isInfirmiere($id)) {
                $vRetour = "infirmiere";
            }


            if ($this->isAdmin($id)) {
                $vRetour = "admin";
            }
            if ($this->isPatient($id)) {
                $vRetour = "patient";
            }


        } catch (Exception $e) {
            echo "erreur : $e";
            $vRetour = "error";
        }

        return $vRetour;

    }
    public function isInfirmiere($id)
    {
        bool:
        $vRetour = false;
        try {
            $query = "SELECT * FROM infirmiere where id = ?";
            $req = $this->connection->prepare($query);
            $req->execute([$id]);
            if ($req->fetch(PDO::FETCH_ASSOC)) {
                $vRetour = true;
            }
        } catch (Exception $e) {
            echo "erreur : $e";
            $vRetour = false;
        }
        return $vRetour;
    }

    public function isInfirmiereEnChef($id)
    {
        bool:
        $vRetour = false;
        try {
            $query = "SELECT id FROM infirmiere where isChef = 1";
            $req = $this->connection->prepare($query);
            $req->execute();
            if ($req->fetch(PDO::FETCH_ASSOC)['id'] == $id) {
                $vRetour = true;
            }
        } catch (Exception $e) {
            echo "erreur : $e";
            $vRetour = false;
        }
        return $vRetour;
    }

    public function isAdmin($id)
    {
        bool:
        $vRetour = false;
        try {
            $query = "SELECT * FROM administrateur where id = ?";
            $req = $this->connection->prepare($query);
            $req->execute([$id]);
            if ($req->fetch(PDO::FETCH_ASSOC)) {
                $vRetour = true;
            }
        } catch (Exception $e) {
            echo "erreur : $e";
            $vRetour = false;
        }
        return $vRetour;
    }

    public function isPatient($id)
    {
        bool:
        $vRetour = false;
        try {
            $query = "SELECT * FROM patient where id = ?";
            $req = $this->connection->prepare($query);
            $req->execute([$id]);
            if ($req->fetch(PDO::FETCH_ASSOC)) {
                $vRetour = true;
            }
        } catch (Exception $e) {
            echo "erreur : $e";
            $vRetour = false;
        }
        return $vRetour;
    }

    public function isPostByThisInfi($idVisite, $idInf)
    {
        $vRetour = false;
        try {
            $query = "SELECT * FROM visite WHERE id = ?";
            $req = $this->connection->prepare($query);
            $req->execute([$idVisite]);
            $result = $req->fetch(PDO::FETCH_ASSOC);
            if ($result['infirmiere'] === $idInf) {
                $vRetour = true;
            }

        } catch (Exception $e) {
            echo "Error : $e";
        }
        return $vRetour;
    }
}

