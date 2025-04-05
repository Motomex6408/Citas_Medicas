<?php
header('Content-Type: application/json');
require_once '../conexion.php';
require_once '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$dni = $_POST['dni'] ?? '';
$fecha = $_POST['fecha'] ?? '';
$hora = $_POST['hora'] ?? '';
$medico = $_POST['medico'] ?? '';
$motivo = $_POST['motivo'] ?? '';

try {
    // Obtener email del paciente
    $stmt = $conn->prepare("SELECT U.correo FROM Pacientes P JOIN Usuarios U ON P.idUsuario = U.idUsuario WHERE U.dni = ?");
    $stmt->execute([$dni]);
    $paciente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$paciente || empty($paciente['correo'])) {
        throw new Exception("No se encontró el email del paciente");
    }

    $email_paciente = $paciente['correo'];

    // Enviar correo
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'smtp.tudominio.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'tucorreo@tudominio.com';
    $mail->Password = 'tucontraseña';
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('no-reply@tudominio.com', 'MediCitas');
    $mail->addAddress($email_paciente);
    $mail->isHTML(true);
    $mail->Subject = 'Confirmación de cita médica';
    $mail->Body = "
        <h2>Confirmación de Cita</h2>
        <p>Su cita ha sido registrada con los siguientes datos:</p>
        <ul>
            <li><strong>Fecha:</strong> $fecha</li>
            <li><strong>Hora:</strong> $hora</li>
            <li><strong>Médico:</strong> $medico</li>
            <li><strong>Motivo:</strong> $motivo</li>
        </ul>
        <p>Gracias por usar MediCitas.</p>
    ";

    $mail->AltBody = "Cita confirmada:\nFecha: $fecha\nHora: $hora\nMédico: $medico\nMotivo: $motivo";

    $mail->send();
    echo json_encode(['estado' => 'exito']);
} catch (Exception $e) {
    error_log("Error al enviar correo: " . $e->getMessage());
    echo json_encode(['estado' => 'mail_error']);
}
