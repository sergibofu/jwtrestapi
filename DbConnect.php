<?php

    class DbConnect{
        private $server = "localhost";
        private $dbname = "jwtapi";
        private $user = "root";
        private $pass = "";
        private $dsn = "";

        public function connect(){

            try{
                //first we create the dsn
                $this->dsn = "mysql:host=$this->server;dbname=$this->dbname";

                //we create our pdo object
                $conn = new PDO($this->dsn, $this->user, $this->pass);

                //we set our atributes
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                //we return our connection object 
                return $conn;
            }catch(Exception $e){
                echo "Database Error: " . $e->getMessage();
            }

        }
    }

  

?>