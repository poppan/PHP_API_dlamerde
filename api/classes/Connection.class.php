<?php

Class Connection{

    private static $dbh;

    public static function get(){
        require('../config/db_config.php');
        if (is_null(self::$dbh)) {
            // try/catch pour lever les erreurs de connexion
            try {
                // on se connecte avec les acces,  IL FAUT QUE LA BASE EXISTE POUR MANIPULER
                self::$dbh = new PDO(
                    'mysql:host=' . $db_config['host'] . ':' . $db_config['port'] . ';dbname=' . $db_config['schema'] . ";charset=" . $db_config['charset'],
                    $db_config['user'],
                    $db_config['password']
                );
                //print_r(self::$dbh);
            } catch (Exception $e) {
                echo('cacaboudin exception');
                print_r($e);
            }
            return self::$dbh;
        }else{
            return self::$dbh;
        }
    }
}