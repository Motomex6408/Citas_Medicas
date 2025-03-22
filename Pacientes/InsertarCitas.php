<?php
session_start();
include '../conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $dni = $_POST["dni"];
    $motivo = trim($_POST["motivo"]);
    $idMedico = $_POST["medico"];
    $idHorario = $_POST["horario"];
    $hora = $_POST["hora"];
    $estado = "pendiente";

    if (empty($dni)) {
        echo "error: DNI no proporcionado.";
        exit;
    }

    try {
        $sqlUsuario = "SELECT idUsuario FROM Usuarios WHERE dni = :dni";
        $stmtUsuario = $conn->prepare($sqlUsuario);
        $stmtUsuario->bindParam(":dni", $dni);
        $stmtUsuario->execute();
        $usuario = $stmtUsuario->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            $idUsuario = $usuario['idUsuario'];
            $sqlPaciente = "SELECT idPaciente FROM Pacientes WHERE idUsuario = :idUsuario";
            $stmtPaciente = $conn->prepare($sqlPaciente);
            $stmtPaciente->bindParam(":idUsuario", $idUsuario);
            $stmtPaciente->execute();
            $paciente = $stmtPaciente->fetch(PDO::FETCH_ASSOC);

            if ($paciente) {
                $idPaciente = $paciente['idPaciente'];

                $sql = "INSERT INTO Citas (idPaciente, idMedico, hora, motivo, estado, idHorario) 
                        VALUES (:idPaciente, :idMedico, :hora, :motivo, :estado, :idHorario)";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(":idPaciente", $idPaciente);
                $stmt->bindParam(":idMedico", $idMedico);
                $stmt->bindParam(":hora", $hora);
                $stmt->bindParam(":motivo", $motivo);
                $stmt->bindParam(":estado", $estado);
                $stmt->bindParam(":idHorario", $idHorario);

                if ($stmt->execute()) {
                    echo "success";
                } else {
                    echo "error: No se pudo insertar la cita.";
                }
            } else {
                echo "error: No se encontró un paciente asociado al DNI proporcionado.";
            }
        } else {
            echo "error: No se encontró un usuario con el DNI proporcionado.";
        }
    } catch (Exception $e) {
        echo "error: " . $e->getMessage();
    }
}
?>