<?php
include '../conexion.php';
include '../Principal/header.php';

if (!$conn) {
    die("Error: No se pudo conectar a la base de datos.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $idPaciente = trim($_POST['idPaciente']);
    $idMedico   = trim($_POST['idMedico']);
    $fecha      = trim($_POST['fecha']);
    $hora       = trim($_POST['hora']);
    $motivo     = trim($_POST['motivo']);

    $stmt = $conn->prepare("SELECT idCita FROM Citas WHERE idMedico = ? AND fecha = ? AND hora = ?");
    $stmt->execute([$idMedico, $fecha, $hora]);
    
    if ($stmt->fetch()) {
        $mensaje = "La cita ya está reservada en ese horario. Por favor, elija otro horario.";
    } else {
        $stmt = $conn->prepare("INSERT INTO Citas (idPaciente, idMedico, fecha, hora, motivo, estado)
                                VALUES (?, ?, ?, ?, ?, 'Pendiente')");
        if ($stmt->execute([$idPaciente, $idMedico, $fecha, $hora, $motivo])) {
            $mensaje = "Cita reservada exitosamente. Usted recibirá una confirmación por correo electrónico.";
        } else {
            $mensaje = "Ha ocurrido un error al intentar reservar la cita. Por favor, intente nuevamente.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservar Cita Médica</title>
    <link rel="stylesheet" href="../css/index.css"> 
    <link rel="stylesheet" href="../css/Reserva.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <h1>Reservar Cita Médica</h1>
    
    <?php if (isset($mensaje)) { echo "<div class='mensaje'><strong>$mensaje</strong></div>"; } ?>
    
    <form method="GET" action="">
        <label for="fecha">Seleccione la fecha:</label>
        <input type="date" name="fecha" id="fecha" required>
        <input type="submit" value="Consultar horarios">
    </form>
    
    <?php
    if (isset($_GET['fecha'])) {
        $fecha = $_GET['fecha'];
        
        $sql = "SELECT hm.idHorario, m.idMedico, e.nombreEspecialidad, hm.diaSemana, hm.horaInicio, hm.horaFin,
                        u.nombre AS nombreMedico
                FROM HorariosMedicos hm
                INNER JOIN Medicos m ON hm.idMedico = m.idMedico
                INNER JOIN Especialidades e ON m.idEspecialidad = e.idEspecialidad
                INNER JOIN Usuarios u ON m.idUsuario = u.idUsuario
                WHERE NOT EXISTS (
                    SELECT 1 FROM Citas c
                    WHERE c.idMedico = m.idMedico
                      AND c.fecha = ?
                      AND c.hora = hm.horaInicio
                )
                ORDER BY hm.horaInicio";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$fecha]);
        $horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($horarios) {
            echo "<h2>Horarios disponibles para el $fecha</h2>";
            echo "<table>
                    <tr>
                        <th>Médico</th>
                        <th>Especialidad</th>
                        <th>Hora Inicio</th>
                        <th>Hora Fin</th>
                        <th>Reservar</th>
                    </tr>";
            foreach ($horarios as $row) {
                echo "<tr>
                        <td>" . htmlspecialchars($row['nombreMedico']) . "</td>
                        <td>" . htmlspecialchars($row['nombreEspecialidad']) . "</td>
                        <td>" . htmlspecialchars($row['horaInicio']) . "</td>
                        <td>" . htmlspecialchars($row['horaFin']) . "</td>
                        <td>
                            <button class='reservar' 
                                    data-medico='" . $row['idMedico'] . "' 
                                    data-fecha='" . $fecha . "' 
                                    data-hora='" . $row['horaInicio'] . "'>Reservar</button>
                        </td>
                      </tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No hay horarios disponibles para esa fecha.</p>";
        }
    }
    ?>

    <div id="formularioReserva" style="display:none;">
        <h2>Confirmar Cita</h2>
        <form method="POST" action="">
            <input type="hidden" name="idMedico" id="idMedico">
            <input type="hidden" name="fecha" id="fechaCita">
            <input type="hidden" name="hora" id="horaCita">
            <label>ID Paciente:</label>
            <input type="text" name="idPaciente" required placeholder="Ingrese su ID">
            <label>Motivo de la consulta:</label>
            <input type="text" name="motivo" required placeholder="Describa el motivo">
            <input type="submit" value="Confirmar Cita">
        </form>
    </div>
    
    <script>
    $(document).ready(function() {
        $('.reservar').click(function() {
            $('#idMedico').val($(this).data('medico'));
            $('#fechaCita').val($(this).data('fecha'));
            $('#horaCita').val($(this).data('hora'));
            $('#formularioReserva').show();
        });
    });
    </script>
</body>
</html>
