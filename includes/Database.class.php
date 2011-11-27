<?php

class Database 
{
   public static $instance = false;
  protected $mysqli;
  protected $local_args = array(
      'user' => "root",
      'pass' => "",
      'host' => "localhost",
      'db'   => "",
      'port' => '3306');
  protected $remote_args = array(
      'user' => "root",
      'pass' => "",
      'host' => "localhost",
      'db'   => "",
      'port' => '3307');
  protected $args = array();
  protected $tables = array(
    'TableName' => 'table');

  protected $debug = false;
  protected $lastresult = null;
  public $insert_id = null;

  private function __construct(){
    $this->args = (getenv('SERVER_NAME') != LIVE_SERVER) ? $this->local_args : $this->remote_args;
    $this->debug = defined('_DEBUG_') ? DEBUG : false;
    $this->mysqli = new mysqli($this->args['host'],$this->args['user'],$this->args['pass'], $this->args['db'], $this->args['port']);
    if(mysqli_connect_errno()){
      throw new Exception('A connection to the database could not be made');
    }
    $this->create_tables();
  }
  
  protected function create_tables(){
    $db_tables = array();
    foreach($db_tables as $v){
      $this->query($v);
    }
  }
  
  // provides methods for getting table names
  public function __get($k){
    if(array_key_exists($k, $this->tables)){
      return $this->tables[$k];
    }
    return '';
  }
  
  public function getDB(){
    if(!self::$instance)
      self::$instance = new Database();
    return self::$instance;
  }
  
  public function quote($str){
    return "'".$this->escape($str)."'";
  }
  
  public function escapeArray($array){
    foreach($array as $k => $v){
      $array[$k] = $this->escape($v);
    }
    return $array;
  }
  
  public function escape($term){
    return $this->mysqli->escape_string($term);
  }
  
  public function query($sql){
    try{
      $result = $this->mysqli->query($sql);
    } catch(Exception $e){
      throw new Exception("Something went wrong");
    }
    if(!$result){
      throw new SQLException($sql, $this->mysqli->error);
    }
    $this->insert_id = $this->mysqli->insert_id;
    $this->lastresult = $result;
    return $result;
  }
  
  public function insert($sql){
    $this->query($sql);
    return $this->insert_id;
  }
  
  public function queryToRow($sql){
    $result = $this->query($sql);
    return $result->fetch_assoc();
  }
  
  public function queryToArray($sql){
    $result = $this->query($sql);
    $out = array();
    while($row = $result->fetch_assoc()){
      $out[] = $row;
    }
    return $out;
  }
  
  public function createUpdate($orig, $new){
    $updates = array();
    foreach($orig as $key => $val){
      if(array_key_exists($key, $new)){
        if($orig[$key] != $new[$key]){
          $safe = $this->escape($new[$key]);
          $updates[] = "{$key}='{$safe}'";
        }
      }
    }
    return implode(',',$updates);
  }
  
}

class SQLException extends Exception
{
  protected $query;
  protected $sqlerror;
  
  public function __construct($query, $sqlerror) {
    $this->query = $query;
    $this->sqlerror = $sqlerror;
    parent::__construct(__CLASS__, '0');
  }

    // custom string representation of object
  public function __toString() {
    $out = parent::__toString() . "\n\nQuery : {$this->query}\n\nError : {$this->sqlerror}\n\n";
    return $out;
  }
}



?>
