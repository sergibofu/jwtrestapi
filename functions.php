<?php
    /*spl_autoload_register is automatically executed everytime that a new class is created */
    spl_autoload_register(function($className){
        $path = strtolower($className) . '.php';
        
        if(file_exists($path)){
            require_once($path);
        }else{
            echo "File $path is not found.";
        }

    });

?>