<?php

namespace Telegram\Library;

use PDO;

class PDODriver{
    private $pdo = null;
    private $query = null;
    public function __construct($host, $db, $user, $pass){
        $pdo = new PDO('mysql:host='.$host.';dbname='.$db.';', $user, $pass);
        $pdo->exec('set names utf8 ;');
        $this->pdo = $pdo;
    }
    public function query($sql, $execute = []){
        $prepare = $this->pdo->prepare($sql);
        $prepare->execute($execute);
        $this->query = $prepare;
        return $this;
    }
    public function result(){
        if($this->query == null){
            return false;
        }else{
            return $this->query->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    public function row(){
        if($this->query == null){
            return false;
        }else{
            return $this->query->fetch(PDO::FETCH_ASSOC);
        }
    }
    public function insert($table, $data = []){
        $prepare = $this->pdo->prepare("INSERT INTO `{$table}` (`". implode('`,`', array_keys($data)) ."`) VALUES (". substr(str_repeat('?, ', count($data)), 0, -2) .") ;");
        $prepare->execute(array_values($data));
        return $this->pdo->lastInsertId();
    }
    public function count(){
        if($this->query == null){
            return false;
        }else{
            return $this->query->rowCount();
        }
    }
}
