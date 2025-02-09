<?php
// Ajusta la ruta según la ubicación real del archivo
include '../conexion.php';

// Instanciar la clase y obtener la conexión PDO
$conexionObj = new Conexion();
$conn = $conexionObj->ConexionBD();

if (!$conn) {
    die("Error: No se pudo conectar a la base de datos.");
}

// Procesamiento del formulario de reserva (cuando se envía por POST)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Recoger datos del formulario
    $idPaciente = trim($_POST['idPaciente']);
    $idMedico   = trim($_POST['idMedico']);
    $fecha      = trim($_POST['fecha']);
    $hora       = trim($_POST['hora']);
    $motivo     = trim($_POST['motivo']);

    // Verificar si ya existe una cita para ese médico, fecha y hora
    $stmt = $conn->prepare("SELECT idCita FROM Citas WHERE idMedico = ? AND fecha = ? AND hora = ?");
    $stmt->execute([$idMedico, $fecha, $hora]);
    
    if ($stmt->fetch()) {
        $mensaje = "La cita ya está reservada en ese horario. Por favor, elija otro horario.";
    } else {
        // Insertar la nueva cita con estado 'Pendiente'
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

<!DOCTYPE htm>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediCitas - Citas Médicas</title>
    <title>Reservar Cita Médica</title>
    <link rel="stylesheet" href="../css/index.css"> 
    <link rel="stylesheet" href="../css/Reserva.css">
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</head>

<body>
<?php include '../Principal/header.php'; ?>

<h1>Reservar Cita Médica</h1>
    
    <!-- Mostrar mensaje de confirmación o error si existe -->
    <?php
    if (isset($mensaje)) {
        $claseMensaje = strpos($mensaje, 'Error') !== false ? 'error' : 'success';
        echo "<div class='mensaje $claseMensaje'><strong>$mensaje</strong></div>";
    }
    ?>

    <!-- Formulario para seleccionar la fecha -->
    <form method="GET" action="">
        <label for="fecha">Seleccione la fecha de la cita:</label>
        <input type="date" name="fecha" id="fecha" required>
        <input type="submit" value="Consultar disponibilidad de horarios">
    </form>
    
    <?php
    // Si se ha seleccionado una fecha, mostrar la disponibilidad
    if (isset($_GET['fecha'])) {
        $fecha = $_GET['fecha'];
        
        // Obtener el día de la semana en inglés y mapearlo a español
        $dayOfWeek = date('l', strtotime($fecha));
        $daysMap = [
            "Monday"    => "Lunes",
            "Tuesday"   => "Martes",
            "Wednesday" => "Miércoles",
            "Thursday"  => "Jueves",
            "Friday"    => "Viernes",
            "Saturday"  => "Sábado",
            "Sunday"    => "Domingo"
        ];
        $diaSemana = isset($daysMap[$dayOfWeek]) ? $daysMap[$dayOfWeek] : $dayOfWeek;
        
        // Consulta para obtener los horarios disponibles
        $sql = "SELECT 
                    hm.idHorario, 
                    m.idMedico, 
                    m.numeroLicenciaMedica, 
                    e.nombreEspecialidad, 
                    hm.diaSemana, 
                    hm.horaInicio, 
                    hm.horaFin,
                    u.nombre AS nombreMedico, 
                    u.correo AS correoMedico
                FROM HorariosMedicos hm
                INNER JOIN Medicos m ON hm.idMedico = m.idMedico
                INNER JOIN Especialidades e ON m.idEspecialidad = e.idEspecialidad
                INNER JOIN Usuarios u ON m.idUsuario = u.idUsuario
                WHERE hm.diaSemana = ?
                AND NOT EXISTS (
                    SELECT 1 FROM Citas c
                    WHERE c.idMedico = m.idMedico
                      AND c.fecha = ?
                      AND c.hora = hm.horaInicio
                )
                ORDER BY hm.horaInicio";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$diaSemana, $fecha]);
        $horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($horarios) {
            echo "<h2>Horarios disponibles para el $fecha ($diaSemana)</h2>";
            echo "<table>
                    <tr>
                        <th>Nombre del Médico</th>
                        <th>Especialidad</th>
                        <th>Correo</th>
                        <th>Hora de Inicio</th>
                        <th>Hora Fin</th>
                        <th>Acción</th>
                    </tr>";
            foreach ($horarios as $row) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['nombreMedico']) . "</td>";
                echo "<td>" . htmlspecialchars($row['nombreEspecialidad']) . "</td>";
                echo "<td>" . htmlspecialchars($row['correoMedico']) . "</td>";
                echo "<td>" . htmlspecialchars($row['horaInicio']) . "</td>";
                echo "<td>" . htmlspecialchars($row['horaFin']) . "</td>";
                echo "<td>
                        <!-- Formulario para reservar la cita en este horario -->
                        <form method='POST' action=''>
                            <!-- En un sistema real, el idPaciente se tomaría de la sesión -->
                            <label>ID Paciente:</label>
                            <input type='text' name='idPaciente' required placeholder='Ingrese su ID'>
                            <input type='hidden' name='idMedico' value='" . $row['idMedico'] . "'>
                            <input type='hidden' name='fecha' value='" . $fecha . "'>
                            <input type='hidden' name='hora' value='" . $row['horaInicio'] . "'>
                            <br>
                            <label>Motivo de la consulta:</label>
                            <input type='text' name='motivo' required placeholder='Describa el motivo'>
                            <br>
                            <input type='submit' value='Reservar Cita'>
                        </form>
                      </td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No hay horarios disponibles para esa fecha. Por favor, seleccione otra.</p>";
        }
    }
    ?>

</body>
</html>


