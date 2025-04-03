<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');


if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Acceso no permitido']));
}


require_once '../conexion.php'; 
require '../vendor/autoload.php'; 

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$response = ['success' => false, 'message' => 'Error desconocido'];

try {
    
    if (empty($_POST['idCita']) || empty($_POST['estado'])) {
        throw new Exception("Datos incompletos");
    }

    $idCita = $_POST['idCita'];
    $nuevoEstado = $_POST['estado'];

    
    $conn->beginTransaction();
    
    
    $stmt = $conn->prepare("UPDATE Citas SET estado = ? WHERE idCita = ?");
    $stmt->execute([$nuevoEstado, $idCita]);

    
    $stmt = $conn->prepare("
        SELECT u.nombre, u.correo, c.motivo, c.hora, hm.fecha, 
               med.nombre AS medico, esp.nombreEspecialidad AS especialidad
        FROM Citas c
        JOIN Pacientes p ON c.idPaciente = p.idPaciente
        JOIN Usuarios u ON p.idUsuario = u.idUsuario
        JOIN HorariosMedicos hm ON c.idHorario = hm.idHorario
        JOIN Medicos m ON c.idMedico = m.idMedico
        JOIN Usuarios med ON m.idUsuario = med.idUsuario
        JOIN Especialidades esp ON m.idEspecialidad = esp.idEspecialidad
        WHERE c.idCita = ?
    ");
    $stmt->execute([$idCita]);
    $detallesCita = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$detallesCita) {
        throw new Exception("No se encontraron detalles de la cita");
    }

    
    $mail = new PHPMailer(true);
    $horaFormateada = date('g:i A', strtotime($detallesCita['hora']));
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'medicitas25@gmail.com';
        $mail->Password = 'thvx dbmb kcvn vhzz';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';

   
        $mail->setFrom('medicitas25@gmail.com', 'MediCitas');

        
        $mail->addAddress($detallesCita['correo']);

        
        $mail->isHTML(true);
        
        if ($nuevoEstado == 'Confirmada') {
            $mail->Subject = "Confirmación de Cita";
            $mail->Body = "
                <h2>Cita Confirmada</h2>
                <p>Estimado/a {$detallesCita['nombre']},</p>
                <p>Su cita con el Dr. {$detallesCita['medico']} ({$detallesCita['especialidad']}) 
                ha sido confirmada para el {$detallesCita['fecha']} a las {$horaFormateada}.</p>
                <p><strong>Motivo:</strong> {$detallesCita['motivo']}</p>
            ";
        } else {
            $mail->Subject = "Actualización de Estado de Cita";
            $mail->Body = "
                <h2>Estado de Cita Actualizado</h2>
                <p>Estimado/a {$detallesCita['nombre']},</p>
                <p>El estado de su cita ha sido actualizado a: <strong>{$nuevoEstado}</strong></p>
                <p><strong>Detalles:</strong></p>
                <ul>
                    <li><strong>Fecha:</strong> {$detallesCita['fecha']}</li>
                    <li><strong>Hora:</strong> {$horaFormateada}</li>
                    <li><strong>Médico:</strong> Dr. {$detallesCita['medico']}</li>
                    <li><strong>Especialidad:</strong> {$detallesCita['especialidad']}</li>
                </ul>
            ";
        }

        $mail->send();
        $response['message'] = "Cita actualizada y notificación enviada";
    } catch (Exception $e) {
        error_log("Error al enviar correo: " . $e->getMessage());
        $response['message'] = "Cita actualizada, pero no se pudo enviar el correo";
    }

    $conn->commit();
    $response['success'] = true;

} catch (Exception $e) {
    $conn->rollBack();
    $response['message'] = $e->getMessage();
    error_log("Error en edit-cita.php: " . $e->getMessage());
}

echo json_encode($response);
exit;