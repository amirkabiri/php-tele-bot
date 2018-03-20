<?php

namespace Telegram\Plugin;

class Fdb {

    private $databse_name = '';
    private $schema = [];

    public function __construct($database)
    {
        if(!is_dir(APP_PATH.'/'.$database)){
            mkdir(APP_PATH.'/'.$database);
        }
        $this->databse_name = $database;
    }
    public function insert($table, $data, $id = null){
        $this->checkTable($table);
        $id = is_null($id) ? $this->newId($table) : $id;
        foreach ($this->schema[$table] as $key => $val) {
            if(!isset($data[$key])) $data[$key] = $val;
        }
        $this->write($table, $id, $data);
        return $id;
    }
    public function select($table, $id){
        $this->checkTable($table);
        return $this->read($table, $id);
    }
    public function delete($table, $id){
        $this->checkTable($table);
        if($this->exists($table, $id)){
            return unlink(APP_PATH.'/'.$this->databse_name.'/'.$table.'/'.$id);
        }else {
            return false;
        }
    }
    public function scan($table){
        $this->checkTable($table);
        $array = array_diff(scandir(APP_PATH.'/'.$this->databse_name.'/'.$table.'/'), ['.', '..']);
        array_multisort($array);
        return $array;
    }
    public function schema($table, $columns){
        $this->checkTable($table);
        $this->schema[$table] = $columns;
    }
    public function update($table, $id, $update){
        $this->checkTable($table);
        if($this->exists($table, $id)){
            $read = $this->read($table, $id);;
            foreach ($update as $key => $val){
                if(isset($read->{$key})) $read->{$key} = $val;
            }
            $this->write($table, $id, $read);
            return true;
        }else{
            return false;
        }
    }
    public function updateId($table, $id, $newId){
        $this->checkTable($table);
        if($this->exists($table, $id)){
            return var_dump(rename(APP_PATH.'/'.$this->databse_name.'/'.$table.'/'.$id, APP_PATH.'/'.$this->databse_name.'/'.$table.'/'.$newId));
        }else {
            return false;
        }
    }
    public function exists($table, $id){
        if(file_exists(APP_PATH.'/'.$this->databse_name.'/'.$table.'/'.$id)) return true;
        else return false;
    }
    private function checkTable($table){
        if(!is_dir(APP_PATH.'/'.$this->databse_name.'/'.$table)){
            mkdir(APP_PATH.'/'.$this->databse_name.'/'.$table);
        }
    }
    private function read($table, $id){
        if($this->exists($table, $id)){
            $read = file_get_contents(APP_PATH.'/'.$this->databse_name.'/'.$table.'/'.$id);
            $read = json_decode($read);
        }else{
            $read = [];
        }
        return $read;
    }
    private function write($table, $id, $data){
        return file_put_contents(APP_PATH.'/'.$this->databse_name.'/'.$table.'/'.$id, json_encode($data));
    }
    private function newId($table, $len = 10){
        $chars = '1234567890qwertyuiopasdfghjklzxcvbnm';
        $out = '';
        for($i = 0 ; $i < $len ; $i++){
            $out .= substr($chars, rand(0, strlen($chars)-1), 1);
        }

        if($this->exists($table, $out)) $this->newId($table, $len);
        else return $out;
    }
}