<?php
session_start();
include '../../conexion.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $idpaciente = $_POST['idpaciente']; 
    $paciente = $_POST['paciente'];
    $idmedico = $_POST['idmedico']; 
    $medico = $_POST['medico'];
    $hora = $_POST['hora'];
    $motivo = $_POST['motivo']; 
    $estado = $_POST['estado'];
    $idhorario = $_POST['idHorario'];

    if (empty($idpaciente) || empty($idmedico) || empty($hora) || empty($motivo) || empty($estado) || empty($idhorario)) {
        $_SESSION['error'] = "Complete los campos obligatorios.";
        header('Location: ../ListadeCitas.php');
        exit();
    }

    if($paciente == "No encontrado" || $medico == "No encontrado") {
        $_SESSION['error'] = "Paciente o Médico no encontrado.";
        header('Location: ../ListadeCitas.php');
        exit();
    }

    try {
        $consulta = "SELECT * FROM Citas WHERE idPaciente = ? AND idMedico = ? AND idHorario = ? AND hora = ?";
        $statement = $conn->prepare($consulta);
        $statement->execute([$idpaciente, $idmedico, $idhorario, $hora]);

        if ($statement->fetch()) {
            $_SESSION['error'] = "Ya existe una cita con este paciente y médico en la misma fecha y hora.";
            header('Location: ../ListadeCitas.php');
            exit();
        }

        $consulta = "INSERT INTO Citas (idPaciente, idMedico, hora, motivo, estado, idHorario) VALUES (?, ?, ?, ?, ?, ?)";
        $statement = $conn->prepare($consulta);
        $statement->execute([$idpaciente, $idmedico, $hora, $motivo, $estado, $idhorario]);

        if($statement->rowCount() > 0) {
            $_SESSION['success'] = "Cita agregada correctamente.";

        } else {

        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error en la base de datos: " . $e->getMessage();
    }

    header("Location: ../ListadeCitas.php");
    exit;
}
?>
