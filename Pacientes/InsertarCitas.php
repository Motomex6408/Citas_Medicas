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
        $mail->Username = 'ajair3635@gmail.com';
        $mail->Password = 'urml ojju qdmw wmhk'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        

        $mail->CharSet = 'UTF-8'; 
        $mail->Encoding = 'base64'; 
        $mail->addCustomHeader('Precedence', 'bulk');
        

        $mail->setFrom('ajair3635@gmail.com', 'MediCitas');
        $mail->addReplyTo('no-responder@medicitas.com', 'No Responder');
        $mail->addAddress($destinatario);
        

        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body = $cuerpoHTML;
        $mail->AltBody = strip_tags($cuerpoHTML); 
        $mail->Priority = 1;
        
        if(!$mail->send()) {
            error_log("Error al enviar correo: " . $mail->ErrorInfo);
            return false;
        }
        return true;
    } catch (Exception $e) {
        error_log("Excepción al enviar correo: " . $e->getMessage());
        return false;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $dni = $_POST["dni"];
    $motivo = trim($_POST["motivo"]);
    $idMedico = $_POST["medico"];
    $idHorario = $_POST["horario"];
    $hora = $_POST["hora"];
    $estado = "pendiente";

    if (empty($dni)) {
        echo json_encode(['success' => false, 'message' => 'DNI no proporcionado.']);
        exit;
    }

    try {
        $conn->beginTransaction();

       
        $stmtUsuario = $conn->prepare("SELECT idUsuario FROM Usuarios WHERE dni = :dni");
        $stmtUsuario->bindParam(":dni", $dni);
        $stmtUsuario->execute();
        
        if (!$usuario = $stmtUsuario->fetch(PDO::FETCH_ASSOC)) {
            throw new Exception("No se encontró un usuario con el DNI proporcionado.");
        }

        
        $stmtPaciente = $conn->prepare("SELECT idPaciente FROM Pacientes WHERE idUsuario = :idUsuario");
        $stmtPaciente->bindParam(":idUsuario", $usuario['idUsuario']);
        $stmtPaciente->execute();
        
        if (!$paciente = $stmtPaciente->fetch(PDO::FETCH_ASSOC)) {
            throw new Exception("No se encontró un paciente asociado al DNI.");
        }

        
        $stmt = $conn->prepare("
            INSERT INTO Citas (idPaciente, idMedico, hora, motivo, estado, idHorario) 
            VALUES (:idPaciente, :idMedico, :hora, :motivo, :estado, :idHorario)
        ");
        
        $params = [
            ':idPaciente' => $paciente['idPaciente'],
            ':idMedico' => $idMedico,
            ':hora' => $hora,
            ':motivo' => $motivo,
            ':estado' => $estado,
            ':idHorario' => $idHorario
        ];
        
        if (!$stmt->execute($params)) {
            throw new Exception("No se pudo insertar la cita.");
        }

        $idCita = $conn->lastInsertId();

       
        $infoCita = $conn->query("
            SELECT 
                hm.fecha, 
                c.hora, 
                u_med.nombre AS medico, 
                u_pac.nombre AS paciente, 
                u_pac.correo
            FROM Citas c
            JOIN HorariosMedicos hm ON c.idHorario = hm.idHorario
            JOIN Medicos m ON c.idMedico = m.idMedico
            JOIN Usuarios u_med ON m.idUsuario = u_med.idUsuario
            JOIN Pacientes p ON c.idPaciente = p.idPaciente
            JOIN Usuarios u_pac ON p.idUsuario = u_pac.idUsuario
            WHERE c.idCita = $idCita
        ")->fetch(PDO::FETCH_ASSOC);

        if (!$infoCita) {
            throw new Exception("No se pudieron obtener los datos para el correo.");
        }

       
        $logoPath = __DIR__ . '/../img/logo-medicitas.png';
        if (!file_exists($logoPath)) {
            throw new Exception("No se encontró el archivo del logo.");
        }

        $logoData = base64_encode(file_get_contents($logoPath));
        $logoMime = mime_content_type($logoPath);

        
        $asunto = "Cita Médica Registrada - En espera de aprobación"; 
        $mensaje = "
            <!DOCTYPE html>
            <html lang='es'>
            <head>
                <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
            </head>
            <body style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h2 style='color: #2c3e50;'>¡Hola {$infoCita['paciente']}!</h2>
                <p style='color: #34495e;'>Hemos recibido tu solicitud de cita médica con los siguientes detalles:</p>
                
                <ul style='color: #34495e;'>
                    <li><strong>Fecha:</strong> {$infoCita['fecha']}</li>
                    <li><strong>Hora:</strong> {$infoCita['hora']}</li>
                    <li><strong>Médico:</strong> Dr. {$infoCita['medico']}</li>
                </ul>
                
                <p style='color: #34495e;'><em>Actualmente tu cita está en <strong>espera de aprobación</strong>. 
                Recibirás un nuevo correo cuando sea confirmada por nuestro equipo.</em></p>
                
                <p style='color: #34495e;'>Gracias por confiar en nosotros,<br>El equipo de <strong>MediCitas</strong></p>
            </body>
            </html>
        ";

        $envioExitoso = enviarCorreo($infoCita['correo'], $asunto, $mensaje);
        
        if (!$envioExitoso) {
            error_log("Falló el envío de correo pero la cita se registró. Destinatario: ".$infoCita['correo']);
            $mensajeRespuesta = 'Cita registrada. Hubo un problema al enviar el correo de confirmación.';
        } else {
            $mensajeRespuesta = 'Cita registrada. Revisa tu correo para más detalles.';
        }

        $conn->commit();
        echo json_encode([
            'success' => true,
            'message' => $mensajeRespuesta
        ]);

    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}