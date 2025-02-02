<?php

class Conexion{

    public function ConexionBD(){
    $host='DESKTOP-GRAHMR9\MSSQLSERVER01';//aquí pone nombre que sale en SQL Server Management Studio
    $user='sa'; //usuario con el que se mete a sql
    $password='Anderson1224'; //contraseña con la que se mete a sql
    $db='DBCARRITO';

    try{
        $conexion = new PDO("sqlsrv:Server=$host;Database=$db",$user,$password);
        echo "Conexión exitosa";
        return $conexion;
    }
    catch(PDOException $e){
        echo "Error: ".$e->getMessage();
        echo "Error en la conexión";
    }
    }
}
?>