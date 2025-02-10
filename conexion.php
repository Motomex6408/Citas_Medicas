<?php
    $server='DESKTOP-GRAHMR9\MSSQLSERVER01';//aquí pone nombre que sale en SQL Server Management Studio
    $database = 'SistemaCitasMedicas';
    $username = 'sa';
    $password = 'Anderson1224';

    try {
        $conn = new PDO("sqlsrv:server=$server;Database=$database", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
    } catch (PDOException $e) {
        echo "Error de conexion: " . $e->getMessage();
    }
?>