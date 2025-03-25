<?php
include '../../conexion.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $_SESSION['form_data'] = $_POST;
    $idCita = $_POST['idCita'];
    $tipoDocumento = $_POST['tipoDocumento'];
    $descripcion = $_POST['descripcion'];
    $fechaSubida = date("Y-m-d");

    if (empty($idCita) || empty($tipoDocumento) || empty($descripcion) || empty($fechaSubida)) {
        $_SESSION['error'] = "Complete los campos obligatorios.";
        header("Location: ../documentosmedicos.php");
        exit();
    }

    try {
        // Verificar si ya existe un documento con los mismos datos
        $consulta = "SELECT * FROM DocumentosMedicos WHERE idCita = ? AND tipoDocumento = ?";
        $statement = $conn->prepare($consulta);
        $statement->execute([$idCita, $tipoDocumento, $descripcion]);

        if ($statement->fetch()) {
            $_SESSION['error'] = "Ya existe un documento con los mismos datos de Cita y tipo de documento.";
            header("Location: ../documentosmedicos.php");
            exit();
        }

        // Insertar el nuevo documento
        $consulta = "INSERT INTO DocumentosMedicos (idCita, tipoDocumento, descripcion, fechaSubida) VALUES (?, ?, ?, ?)";
        $statement = $conn->prepare($consulta);
        $statement->execute([$idCita, $tipoDocumento, $descripcion, $fechaSubida]);

        if ($statement->rowCount() > 0) {
            $_SESSION['success'] = "Documento agregado correctamente.";
            unset($_SESSION['form_data']);
            header("Location: ../documentosmedicos.php");
        } else {
            $_SESSION['error'] = "Hubo un problema al agregar el documento.";
            header("Location: ../documentosmedicos.php");
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error en la base de datos: " . $e->getMessage();
    }

    header("Location: ../documentosmedicos.php");
    exit();
}
?>