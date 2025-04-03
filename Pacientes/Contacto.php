<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediCitas - Citas Médicas</title>
    <link rel="stylesheet" href="../css/estilo.css">
    <link rel="stylesheet" href="../css/estilo-admin.css">
    <link rel="stylesheet" href="../css/tabla.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css"/>
</head>

<body>
    <?php include 'header.php'; ?>
    <?php include 'menu.php'; ?>

    <main class="contenido">
    <div class="table-container">
        <h2>CONTACTO MÉDICOS</h2>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Teléfono</th>
                        <th>Correo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    include '../conexion.php';
                    $sql = "SELECT U.*, M.telefono 
                            FROM Usuarios U
                            JOIN Medicos M ON U.idUsuario = M.idUsuario
                            WHERE U.rol = 'Médico'";
                    
                    $query = $conn->prepare($sql);
                    $query->execute();
                    $Usuarios = $query->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach ($Usuarios as $row) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row["nombre"]) . " " . htmlspecialchars($row["apellido"]) . "</td>";
                        echo "<td>" . htmlspecialchars($row["telefono"]) . "</td>";
                        echo "<td>" . htmlspecialchars($row["correo"]) . "</td>";
                        echo "</tr>";
                    }
                    
                    if (empty($Usuarios)) {
                        echo "<tr><td colspan='3'>No se encontraron médicos registrados</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

</body>