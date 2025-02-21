<?php
include '../conexion.php';
$dni_filter = $_GET['dni'] ?? '';
$nombre_apellido_filter = $_GET['nombre_apellido'] ?? '';
$rol_filter = $_GET['rol'] ?? '';
$sql = "SELECT idusuario, dni, nombre, apellido, usuario, correo, rol FROM usuarios WHERE 1=1";

if($dni_filter) {
    $sql .= " AND dni LIKE '%$dni_filter%'";
}
if($nombre_apellido_filter) {
    $sql .= " AND nombre LIKE '%$nombre_apellido_filter%' OR apellido LIKE '%$nombre_apellido_filter%'";
}
if($rol_filter) {
    $sql .= " AND rol LIKE '%$rol_filter%'";
}

try {
    $query = $conn->prepare($sql);
    $query->execute();
    $usuarios = $query->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al ejecutar la consulta: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="../css/tabla.css">
    <style>
        .filter-container {
            background: #ffffff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            width: 100%;
            max-width: 1200px;
            margin: 20px auto;
        }

        .filter-container form {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .filter-container input,
        .filter-container select {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            flex: 1;
            min-width: 150px;
            transition: border-color 0.3s ease;
        }

        .filter-container input:focus,
        .filter-container select:focus {
            border-color: #0099ff;
            outline: none;
        }

        .filter-container button {
            background-color: #0099ff;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }

        .filter-container button:hover {
            background-color: #0077cc;
        }

        @media (max-width: 768px) {
            .filter-container form {
                flex-direction: column;
            }

            .filter-container input,
            .filter-container select,
            .filter-container button {
                width: 100%;
                margin-bottom: 10px;
            }
        }
    </style>
</head>

<body>
    <?php include 'header.php'; ?>
    <div class="contenedor">
        <?php include 'menu.php'; ?>
        <main class="contenido">
            <div class="filter-container">
                <form method="GET" action="">
                    <input type="text" name="dni" placeholder="Buscar por DNI" value="<?= $dni_filter ?>" autocomplete="off">
                    <input type="text" name="nombre_apellido" placeholder="Buscar por Nombre/Apellido" value="<?= $nombre_apellido_filter ?>" autocomplete="off">
                    <select name="rol">
                        <option value="">Permiso de Usuario</option>
                        <option value="Administrador" <?= $rol_filter == 'Administrador' ? 'selected' : '' ?>>Administrador</option>
                        <option value="Médico" <?= $rol_filter == 'Médico' ? 'selected' : '' ?>>Médico</option>
                        <option value="Paciente" <?= $rol_filter == 'Paciente' ? 'selected' : '' ?>>Paciente</option>
                    </select>
                    <button type="submit">Filtrar</button>
                </form>
            </div>
            <div class="table-container">
                <h2>TABLA DE USUARIOS</h2>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>DNI</th>
                                <th>NOMBRE</th>
                                <th>APELLIDO</th>
                                <th>USUARIO</th>
                                <th>CORREO</th>
                                <th>PERMISOS</th>
                                <th>ACCION</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (count($usuarios) > 0) {
                                foreach ($usuarios as $fila) {
                                    echo "<tr>
                                <td>{$fila['idusuario']}</td>
                                <td>{$fila['dni']}</td>
                                <td>{$fila['nombre']}</td>
                                <td>{$fila['apellido']}</td>
                                <td>{$fila['usuario']}</td>
                                <td>{$fila['correo']}</td>
                                <td>{$fila['rol']}</td>
                                <td>
                                    <a href='editar_usuario.php?id={$fila['idusuario']}'><img src=\"../img/edit.png\" width=\"35\" height=\"35\"></a>
                                    <a href='eliminar_usuario.php?id={$fila['idusuario']}'><img src=\"../img/delete.png\" width=\"35\" height=\"35\"></a>
                                </td>
                              </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='5'>No hay usuarios registrados</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>
</body>

</html>