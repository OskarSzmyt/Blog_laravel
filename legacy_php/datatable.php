<?php
//----------------------------------------------------------------------------//
//
//   Data table model 
//
//----------------------------------------------------------------------------//
class Datatable
{
    protected $db, $table, $names, $key, $autoincrement;

    // method declaration
    // Constructor
    // &$db - PDO database handler
    // $table - name of data table
    // $names - array of data fields names
    // $filename - name of file to store the data
    // $key - unic primary key identifier field name
    // $autoincrenent - if true $key value will be autoincrement by insert()
    //
    public function __construct( &$db, $table, $names, $key='id', $autoincrement=true) {
       $this->db = $db;
       $this->table=$table;
       $this->names = $names;
       $this->key = $key;
       $this->autoincrement = $autoincrement;

       $query="CREATE TABLE IF NOT EXISTS ".$this->table." ( ";
       foreach( $this->names as $v ) {
         if( $this->autoincrement ){ 
            if($this->key==$v) $query .= " $v INTEGER PRIMARY KEY AUTOINCREMENT, ";
            else $query .= " $v TEXT, ";
         }else{
            if($this->key==$v) $query .= " $v TEXT PRIMARY KEY, ";
            else $query .= " $v TEXT, ";
         }
       }
       $query = substr($query,0, strlen($query)-2);  
       $query.=" )";
       try{  $this->db->exec($query); }
       catch(PDOException $e){ echo $e->getMessage().": ".$e->getCode(); exit; }
    }

    protected function query_insert($data){
       $query="insert into ".$this->table." ( ";
       foreach( $this->names as $v ) {
         if( $this->autoincrement and ($this->key==$v) ) continue; 
         $query .= " $v, ";
       }
       $query = substr($query,0, strlen($query)-2);
       $query.=" ) values ( ";
       foreach( $this->names as $v ) {
         if( $this->autoincrement and ($this->key==$v) ) continue; 
         $query .= " '$data[$v]', ";
       }
       $query = substr($query,0, strlen($query)-2);
       $query.=" )";
       return $query;
    }    
    protected function query_update($data,$value,$key=NULL){
       if(!$key) $key=$this->key;
       $query="update ".$this->table." set ";
       foreach( $data as $k=>$v ) $query .= " $k = '$v', ";
       $query = substr($query,0, strlen($query)-2);
       $query.=" where $key='$value' ";
       return $query;
    }    

    public function insert($data) {
       $query = $this->query_insert($data);
       try{ $r = $this->db->exec($query); }
       catch(PDOException $e){ echo $e->getMessage().": ".$e->getCode()."<br />\nQuery: $query";  exit;}
       return $r;           
    }
    
    public function update($data) {
       $query = $this->query_update($data,$data[$this->key]);
       try{ $r = $this->db->exec($query); }
       catch(PDOException $e){ echo $e->getMessage().": ".$e->getCode()."<br />\nQuery: $query";  exit;}
       return $r;           
    }

    public function delete($id,$key=false) {
       if(!$key) $key=$this->key;
       $query="delete from ".$this->table." where $key='$id'";
       try{ $r = $this->db->exec($query); }
       catch(PDOException $e){ echo $e->getMessage().": ".$e->getCode()."<br />\nQuery: $query";  exit;}
       return $r;           
    }

    public function get($val,$key=false) {
       if(!$key) $key=$this->key;
       $query="select * from ".$this->table." where $key='$val'";
       try{ $r = $this->db->query($query); }
       catch(PDOException $e){ echo $e->getMessage().": ".$e->getCode()."<br />\nQuery: $query"; exit; }
       return $r->fetch(\PDO::FETCH_ASSOC);           
    }

    public function getAll($val=false,$key=false, $order="") {
       if(!$key) $key=$this->key;
       if($val) $query="select * from ".$this->table." where $key='$val'".(($order)?" ORDER BY $order ":"");
       else $query="select * from ".$this->table.(($order)?" ORDER BY $order ":""); 
       try{ $r = $this->db->query($query); }
       catch(PDOException $e){ echo $e->getMessage().": ".$e->getCode()."<br />\nQuery: $query"; exit;}
       $result=array();
       while( $data = $r->fetch(\PDO::FETCH_ASSOC) ){
          $result[$data[$this->key]] = $data;
       }
       return $result;           
    }
    
    public function getNames(){ return $this->names; }
    
    public function getLastItem($key="date"){
       $query="select max($this->key) from ".$this->table;
       try{ $r = $this->db->query($query); }
       catch(PDOException $e){ echo $e->getMessage().": ".$e->getCode()."<br />\nQuery: $query";  exit;}
       $d = $r->fetch(\PDO::FETCH_NUM);
       $query="select * from ".$this->table." where ".$this->key."='$d[0]' ";
       try{ $r = $this->db->query($query); }
       catch(PDOException $e){ echo $e->getMessage().": ".$e->getCode()."<br />\nQuery: $query";  exit;}
       return $r->fetch(\PDO::FETCH_ASSOC);
    }
    
// end of class datatable
}

