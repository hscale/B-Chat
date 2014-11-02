#!/usr/local/bin/php

<?php
/*
	@Author: Behnam Azizi
	@Description: This class allows you to store and retrieve tabular information in a text file (flat-file db)
				 without the need to have a database server



ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);
*/
class SimpleDB {
    public function __construct($table_name){
        $this->table_name = $table_name;
        $this->file_obj = NULL;
        $this->file_write_obj = fopen($this->table_name, "a");
        //echo $this->read($this->table_name);
        preg_match_all('/\n([0-9]+)\|/i', $this->read($this->table_name), $matches);
        //echo var_dump($matches);
        if (count($matches[1]) == 0){
            $this->rowIndex = -1;
        }else{
            $this->rowIndex = $matches[1][count($matches[1]) - 1];
        }
    }

    public function getRows($cells_per_row)
    {
        $contents = $this->read($this->table_name);
        $rs = array();
        array_push($rs, array());
        //echo $contents;
        preg_match_all('/\|([^\|\n]+)/', $contents, $matches);
        //echo var_dump($matches);
        $row = 0;
        $col = 0;
        foreach ($matches[1] as $cell) {
            if ($col != 0 && fmod($col, $cells_per_row) == 0){
                array_push($rs, array());
                $col = 0;
                $row++;
            }
            array_push($rs[$row], $cell);
            $col++;
        }
        return $rs;
    }

    public function insert($array){
        for($i=0; $i<count($array); $i++){
            $array[$i] = str_replace("|", "", $array[$i]);
        }

        $this->rowIndex++;
        fwrite($this->file_write_obj, PHP_EOL . $this->rowIndex . "|");
        foreach ($array as $cell) {
            fwrite($this->file_write_obj, $cell . '|');
        }
        fwrite($this->file_write_obj, PHP_EOL);
    }

    public function read($table_name){
        $file_read_obj = fopen($table_name, "r");
        $rs = "";
        while(! feof($file_read_obj)){
            $rs .= fgets($file_read_obj);

        }

        return $rs;
    }

    public function clear_db(){
        $file = fopen($this->table_name, "w");
        fwrite($file, "");
        $file->close();
    }

    public function get_msg_count(){
        preg_match_all("/\n/", $this->read($this->table_name), $matches);
        return count($matches[0])/2;
    }

    function removeslashes($string)
    {
        $string=implode("",explode("\\",$string));
        return stripslashes(trim($string));
    }
    
}

//======================================================================


//create db object
$sdb = new SimpleDB("chat_data");


//clear chat if needed
if(isset($_GET['action']) and $_GET['action'] == "delete"){
    $sdb->clear_db();
}

//return message count if asked for
if(isset($_GET['action']) and $_GET['action'] == "get_msg_count"){
    echo $sdb->get_msg_count();
}




//insert
if(isset($_GET['msg_data'])){
    if($_GET['msg_data'] != ""){
        $sdb->insert(array(addslashes($_GET['msg_data'])));
    }else{
        $result = "";
        foreach($sdb->getRows(1) as $msg){
            $result = '<pre><div class=\"msg\">' . $msg[0] . '</div></pre>' . $result;
        }
        //echo $sdb->removeslashes($result);
    }
}

