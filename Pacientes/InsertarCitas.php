<?php
session_start();
include '../conexion.php';

require '../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

function enviarCorreo($destinatario, $asunto, $cuerpoHTML) {
    $mail = new PHPMailer(true);
    
    try {
        $mail->SMTPDebug = SMTP::DEBUG_OFF; 
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'medicitas25@gmail.com';
        $mail->Password = 'thvx dbmb kcvn vhzz'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];

        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        $mail->setFrom('medicitas25@gmail.com', 'MediCitas');
        $mail->addAddress($destinatario);
        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body = $cuerpoHTML;
        $mail->AltBody = strip_tags($cuerpoHTML);
        
        return $mail->send();
    } catch (Exception $e) {
        error_log("Error al enviar correo: " . $e->getMessage());
        return false;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $required = ['dni', 'motivo', 'medico', 'horario', 'hora'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => "El campo $field es requerido."
            ]);
            exit;
        }
    }

    $dni = $_POST["dni"];
    $motivo = trim($_POST["motivo"]);
    $idMedico = $_POST["medico"];
    $idHorario = $_POST["horario"];
    $hora = $_POST["hora"];
    $estado = "pendiente";

    try {
        $conn->beginTransaction();


        $stmt = $conn->prepare("
            SELECT p.idPaciente, u.nombre, u.correo 
            FROM Usuarios u
            JOIN Pacientes p ON u.idUsuario = p.idUsuario
            WHERE u.dni = ?
        ");
        $stmt->execute([$dni]);
        $paciente = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$paciente) {
            throw new Exception("No se encontró un paciente con el DNI proporcionado.");
        }


        $stmt = $conn->prepare("
            INSERT INTO Citas (idPaciente, idMedico, hora, motivo, estado, idHorario) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $paciente['idPaciente'],
            $idMedico,
            $hora,
            $motivo,
            $estado,
            $idHorario
        ]);

        $idCita = $conn->lastInsertId();


        $stmt = $conn->prepare("
            SELECT hm.fecha, c.hora, u.nombre AS medico 
            FROM Citas c
            JOIN HorariosMedicos hm ON c.idHorario = hm.idHorario
            JOIN Medicos m ON c.idMedico = m.idMedico
            JOIN Usuarios u ON m.idUsuario = u.idUsuario
            WHERE c.idCita = ?
        ");
        $stmt->execute([$idCita]);
        $cita = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cita) {
            throw new Exception("No se pudieron obtener los detalles de la cita.");
        }

        $asunto = "Cita Médica Registrada - En espera de aprobación";
        $hora_formateada = date("H:i", strtotime($cita['hora']));
        $mensaje = "
        <!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Cita Médica Registrada</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background-color: #f4f4f4;
                    margin: 0;
                    padding: 20px;
                    text-align: center;
                }
                .container {
                    max-width: 600px;
                    background: white;
                    padding: 20px;
                    margin: auto;
                    border-radius: 8px;
                    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
                }
                .header {
                    background-color: #2c8dfb;
                    color: white;
                    padding: 15px;
                    font-size: 18px;
                    font-weight: bold;
                    border-radius: 8px 8px 0 0;
                }
                .content {
                    text-align: left;
                    padding: 20px;
                }
                .details {
                    background: #f9f9f9;
                    padding: 15px;
                    border-radius: 5px;
                    margin: 20px 0;
                }
                .highlight {
                    color: #2c8dfb;
                    font-weight: bold;
                }
                .footer {
                    font-size: 14px;
                    color: #555;
                    margin-top: 20px;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>Cita Médica Registrada</div>
                <div class='content'>
                    <p>¡Hola <span class='highlight'>{$paciente['nombre']}</span>!</p>
                    <p>Hemos recibido tu solicitud de cita médica con los siguientes detalles:</p>
                    <div class='details'>
                        <p><strong>Fecha:</strong> {$cita['fecha']}</p>
                        <p><strong>Hora:</strong> {$hora_formateada}</p>
                        <p><strong>Médico:</strong> Dr. {$cita['medico']}</p>
                    </div>
                    <p><em>Actualmente, tu cita está en espera de aprobación.</em></p>
                    <p class='footer'>Gracias por confiar en nosotros,<br><strong>El equipo de MediCitas</strong></p>
                </div>
            </div>
        </body>
        </html>
    ";

        $envioExitoso = enviarCorreo($paciente['correo'], $asunto, $mensaje);

        $conn->commit();
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => $envioExitoso 
                ? 'Cita registrada correctamente. Se ha enviado un correo de confirmación.'
                : 'Cita registrada, pero no se pudo enviar el correo de confirmación.'
        ]);
        
    } catch (Exception $e) {
        $conn->rollBack();
        $errorMessage = 'Hubo un error al confirmar la cita: ' . $e->getMessage();
        error_log($errorMessage); 
        
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $errorMessage
        ]);
        exit;
    }
} else {
    header('Content-Type: application/json');
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
}