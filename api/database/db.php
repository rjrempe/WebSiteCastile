<?php

class Db{
  
    // specify your own database credentials

    private $host = "localhost";
    private $db_name = ".";
    private $username = "root";
    private $password = "";
    private $errorMessage = "";

    private $localTZ = "";

    public $conn = null;
  
    function __construct(string $dbName, string $userName = 'root', string $pwd = ''){
        
        $this->host = $_SERVER['SERVER_NAME'];
        $this->db_name = $dbName;
        $this->username = $userName;
        $this->password = $pwd;

        $this->localTZ = date_default_timezone_get();
        $this->conn = null;

        // date_default_timezone_set('UTC');
    }

    // get the database connection
    public function getConnection(){

        if($this->conn == null){
	        $cxnString = "mysql:host={$this->host};dbname={$this->db_name}";

	        //echo "- connection string {$cxnString} ";  

            try{
	            $this->conn = new PDO($cxnString, $this->username, $this->password); 

                $this->conn->exec("set names utf8");
            }
            catch(PDOException $exception){
	            $errorMessage = "getConnection error: $exception->getMessage() using connection string $cxnString for user $this->username";

                echo $errorMessage;
            }
        }  
        return $this->conn;
    }

    public static function setLocalTZ(string $tz){
        $localTZ = $tz;
	}

    //  Everything store as UTC?
    //
    // time() is in UTC
    public static function getDT_Now(){
        //return date('m/d/Y h:i:s a', time());
        
        return new DateTime("now", $localTZ);
	}
    public static function local2UTC(DateTime $localDT){
        $localDT->setTimezone(new DateTimeZone('UTC'));

        return $localDT;
    }
    public static function UTC2local(DateTime $dt){
        $dt->setTimezone(new DateTimeZone($localTZ));

        return $dt;
    }
    //
    //  Use 'setUTCColumn' and  'getUTCColumn' when column DT is maintained as UTC time
    //
    public static function setUTCColumn(DateTime $columnDT,  DateTime $localDT){
        // convert from local to UTC 
        $columnDT = $localDT;
        $columnDT->setTimezone(new DateTimeZone('UTC'));
    }

    public static function getUTCColumn(DateTime $columnDT){
        // convert from UTC to local

        $localDT = $columnDT;
        $localDT->setTimezone(new DateTimeZone($localTZ));
        return $localDT;
    }
}

?>