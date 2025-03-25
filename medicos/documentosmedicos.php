<?php
require '../php/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\PhpWord;

include '../conexion.php';
$medico_filter = $_GET['medico'] ?? '';
$paciente_filter = $_GET['paciente'] ?? '';
$fecha_filter = $_GET['fecha'] ?? '';

$sql = "SELECT 
        T1.idDocumento, T2.idCita, 
        T3.idPaciente, T5.nombre + ' ' + T5.apellido AS paciente,
        T4.idMedico, T6.nombre + ' ' + T6.apellido AS medico,
        T1.tipoDocumento, T1.descripcion, T1.fechaSubida
        FROM DocumentosMedicos T1
        INNER JOIN Citas T2 ON T2.idCita = T1.idCita
        INNER JOIN Pacientes T3 ON T3.idPaciente = T2.idPaciente
        INNER JOIN Medicos T4 ON T4.idMedico = T2.idMedico
        INNER JOIN Usuarios T5 ON T5.idUsuario = T3.idUsuario
        INNER JOIN Usuarios T6 ON T6.idUsuario = T4.idUsuario
        WHERE 1=1";

if ($medico_filter) {
    $sql .= " AND T6.nombre LIKE '%$medico_filter%'";
}
if ($paciente_filter) {
    $sql .= " AND T5.nombre LIKE '%$paciente_filter%'";
}
if ($fecha_filter) {
    $sql .= " AND T1.fechaSubida = '$fecha_filter'";
}

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $documentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

if (isset($_GET['ajax'])) {
    echo json_encode($documentos);
    exit;
}

if (isset($_GET['export_pdf'])) {
    $html = "<h1>Lista de Documentos Médicos</h1>";
    $html .= "<table border='1' cellpadding='10' cellspacing='0'>";
    $html .= "<thead>
                <tr>
                    <th>Paciente</th>
                    <th>Cita</th>
                    <th>Médico</th>
                    <th>Tipo</th>
                    <th>Descripción</th>
                    <th>Fecha Subida</th>
                </tr>
              </thead><tbody>";

    foreach ($documentos as $fila) {
        $html .= "<tr>
                    <td>{$fila['paciente']}</td>
                    <td>{$fila['fechaCita']} {$fila['horaCita']}</td>
                    <td>{$fila['Medico']}</td>
                    <td>{$fila['tipoDocumento']}</td>
                    <td>{$fila['descripcion']}</td>
                    <td>{$fila['fechaSubida']}</td>
                  </tr>";
    }
    $html .= "</tbody></table>";

    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isPhpEnabled', true);
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $dompdf->stream("documentos_medicos.pdf", array("Attachment" => true));
    exit;
}

if (isset($_GET['export_excel'])) {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setCellValue('A1', 'Paciente');
    $sheet->setCellValue('B1', 'Cita');
    $sheet->setCellValue('C1', 'Médico');
    $sheet->setCellValue('D1', 'Tipo');
    $sheet->setCellValue('E1', 'Descripción');
    $sheet->setCellValue('F1', 'Fecha Subida');

    $row = 2;
    foreach ($documentos as $fila) {
        $sheet->setCellValue("A$row", $fila['paciente']);
        $sheet->setCellValue("B$row", $fila['fechaCita'] . ' ' . $fila['horaCita']);
        $sheet->setCellValue("C$row", $fila['Medico']);
        $sheet->setCellValue("D$row", $fila['tipoDocumento']);
        $sheet->setCellValue("E$row", $fila['descripcion']);
        $sheet->setCellValue("F$row", $fila['fechaSubida']);
        $row++;
    }

    $writer = new Xlsx($spreadsheet);
    $filename = 'documentos_medicos.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header("Content-Disposition: attachment;filename=\"$filename\"");
    $writer->save('php://output');
    exit;
}

if (isset($_GET['export_word'])) {
    try {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();

        $section->addText("Lista de Documentos Médicos", ['bold' => true, 'size' => 16]);
        $table = $section->addTable();

        $table->addRow();
        $table->addCell(2000)->addText("Paciente");
        $table->addCell(2000)->addText("Cita");
        $table->addCell(2000)->addText("Médico");
        $table->addCell(2000)->addText("Tipo");
        $table->addCell(2000)->addText("Descripción");
        $table->addCell(2000)->addText("Fecha Subida");

        foreach ($documentos as $fila) {
            $table->addRow();
            $table->addCell(2000)->addText($fila['paciente']);
            $table->addCell(2000)->addText($fila['fechaCita'] . ' ' . $fila['horaCita']);
            $table->addCell(2000)->addText($fila['Medico']);
            $table->addCell(2000)->addText($fila['tipoDocumento']);
            $table->addCell(2000)->addText($fila['descripcion']);
            $table->addCell(2000)->addText($fila['fechaSubida']);
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header("Content-Disposition: attachment;filename=\"documentos_medicos.docx\"");
        header('Cache-Control: max-age=0');

        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save('php://output');
        exit;
    } catch (Exception $e) {
        echo 'Error al generar el documento Word: ',  $e->getMessage();
    }
}
$documentos = [];
$stmt = $conn->prepare($sql);
$stmt->execute();
$documentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<style>
    .add-btn,
    .btn-pdf,
    .btn-excel,
    .btn-word {
        display: inline-block;
        background-color: #0b5471;
        color: white;
        margin-right: 10px;
        margin-bottom: 10px;
        padding: 10px 20px;
        border-radius: 5px;
        text-decoration: none;
        font-size: 14px;
    }

    .add-btn:hover,
    .btn-pdf:hover,
    .btn-excel:hover,
    .btn-word:hover {
        background-color: rgb(10, 60, 80);
    }

    @media (max-width: 768px) {


        .export-buttons {
            flex-direction: column;
        }

        .add-btn,
        .btn-pdf,
        .btn-excel,
        .btn-word {
            width: 100%;
            margin-right: 0;
        }
    }
</style>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Citas</title>
    <link rel="stylesheet" href="../css/tabla.css">
    <link rel="stylesheet" href="../css/filter.css">
</head>

<body>
    <?php include 'header.php'; ?>
    <div class="contenedor">
        <?php include 'menu.php'; ?>
        <main class="contenido">
            <?php include 'modals/editar-documento.php'; ?>
            <?php include 'modals/agregar-documento.php'; ?>

            <div class="filter-container">
                <form method="GET" action="">
                    <input type="text" id="searchPaciente" name="paciente" placeholder="Buscar por Paciente" value="" autocomplete="off">
                    <input type="text" id="searchMedico" name="medico" placeholder="Buscar por Médico" value="" autocomplete="off">
                    <input type="date" id="searchFecha" name="fecha" value="">
                </form>
            </div>

            <div class="table-container">
                <h2>Documentos Médicos</h2>
                <div class="export-buttons">
                    <a href="#" class="add-btn">Agregar Documento</a>
                    <a href="?export_pdf" class="btn-pdf">Exportar a PDF</a>
                    <a href="?export_excel" class="btn-excel">Exportar a Excel</a>
                    <a href="?export_word" class="btn-word">Exportar a Word</a>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>ID Cita</th>
                                <th>Paciente</th>
                                <th>Médico</th>
                                <th>Tipo</th>
                                <th>Descripción</th>
                                <th>Fecha Subida</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="documentosTable">
                            <?php if ($documentos) {
                                foreach ($documentos as $fila) {
                                    echo "<tr>
                                        <td>{$fila['idDocumento']}</td>
                                        <td>{$fila['idCita']}</td>
                                        <td>{$fila['paciente']}</td>
                                        <td>{$fila['medico']}</td>
                                        <td>{$fila['tipoDocumento']}</td>
                                        <td>{$fila['descripcion']}</td>
                                        <td>{$fila['fechaSubida']}</td>
                                        <td>
                                            <a href='#' class='edit-btn' 
                                                data-id='{$fila['idDocumento']}'
                                                data-idcita='{$fila['idCita']}'
                                                data-paciente='{$fila['paciente']}'
                                                data-medico='{$fila['medico']}'
                                                data-tipo='{$fila['tipoDocumento']}'
                                                data-descripcion='{$fila['descripcion']}'
                                                data-fecha='{$fila['fechaSubida']}'>
                                                <img src='../img/edit.png' width='35' height='35'></a>
                                            <a href='#' class='delete-btn' data-id='{$fila['idDocumento']}'>
                                                <img src='../img/delete.png' width='35' height='35'>
                                            </a>
                                        </td>
                                    </tr>";
                                }
                            }

                            if (empty($documentos)) {
                                echo "<tr><td colspan='7'>No hay documentos medicos registrados</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const searchPaciente = document.getElementById("searchPaciente");
        const searchMedico = document.getElementById("searchMedico");
        const searchFecha = document.getElementById("searchFecha");
        const documentosTable = document.getElementById("documentosTable");

        window.fetchDocuments = function() {
            const paciente = searchPaciente.value.trim();
            const medico = searchMedico.value.trim();
            const fecha = searchFecha.value.trim();

            const params = new URLSearchParams({
                paciente,
                medico,
                fecha,
                ajax: 1
            });

            fetch(`documentosmedicos.php?${params}`)
                .then(response => response.json())
                .then(data => {
                    documentosTable.innerHTML = "";
                    if (data.length > 0) {
                        data.forEach(documentos => {
                            const row = `
                    <tr>
                        <td>${documentos.idDocumento}</td>
                        <td>${documentos.idCita}</td>
                        <td>${documentos.paciente}</td>
                        <td>${documentos.medico}</td>
                        <td>${documentos.tipoDocumento}</td>
                        <td>${documentos.descripcion}</td>
                        <td>${documentos.fechaSubida}</td>
                        <td>
                            <a href="#" class="edit-btn" 
                                data-id="${documentos.idDocumento}"
                                data-idcita="${documentos.idCita}"
                                data-paciente="${documentos.paciente}"
                                data-medico="${documentos.medico}"
                                data-tipo="${documentos.tipoDocumento}"
                                data-descripcion="${documentos.descripcion}"
                                data-fecha="${documentos.fechaSubida}">
                                <img src="../img/edit.png" width="35" height="35"></a>
                            <a href="#" class="delete-btn" data-id="${documentos.idDocumento}">
                                <img src="../img/delete.png" width="35" height="35">
                            </a>
                        </td>
                    </tr>
                `;
                            documentosTable.innerHTML += row;
                        });
                    } else {
                        documentosTable.innerHTML = "<tr><td colspan='8'>No hay usuarios registrados</td></tr>";
                    }

                    // Vuelve a asignar eventos a los botones después de actualizar la tabla
                    asignarEventosBotones();
                })
                .catch(error => console.error("Error en la búsqueda:", error));
        }
        // Eventos para filtrar en tiempo real
        searchPaciente.addEventListener("keyup", fetchDocuments);
        searchMedico.addEventListener("keyup", fetchDocuments);
        searchFecha.addEventListener("change", fetchDocuments);
    });

    function asignarEventosBotones() {
        const editButtons = document.querySelectorAll(".edit-btn");
        const deleteButtons = document.querySelectorAll(".delete-btn");

        editButtons.forEach(btn => {
            btn.addEventListener("click", function(event) {
                event.preventDefault();
                const idDocumento = btn.dataset.id;
                const idPaciente = btn.dataset.idpaciente;
                const paciente = btn.dataset.paciente;
                const idCita = btn.dataset.idcita;
                const cita = btn.dataset.cita;
                const medico = btn.dataset.medico;
                const tipo = btn.dataset.tipo;
                const descripcion = btn.dataset.descripcion;
                const fecha = btn.dataset.fecha;
                const idMedico = btn.dataset.idmedico;

                document.getElementById("edit-idDocumento").value = idDocumento;
                document.getElementById("edit-idPaciente").value = idPaciente;
                document.getElementById("edit-nombrePaciente").value = paciente;
                document.getElementById("edit-idCita").value = idCita;
                document.getElementById("edit-fechaCita").value = cita;
                document.getElementById("edit-tipoDocumento").value = tipo;
                document.getElementById("edit-descripcion").value = descripcion;
                document.getElementById("edit-fechaSubida").value = fecha;
                document.getElementById("edit-idMedico").value = idMedico;
                document.getElementById("edit-nombreMedico").value = medico;

                document.getElementById("modalEditarDocumento").style.display = "block";
            });
        });

        deleteButtons.forEach(btn => {
            btn.addEventListener("click", async event => {
                event.preventDefault();
                const idDocumento = btn.dataset.id;

                // Confirmación de eliminación
                const confirmacion = await Swal.fire({
                    title: `¿Eliminar el documento Nº ${idDocumento}?`,
                    text: "Esta acción no se puede deshacer.",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#d33",
                    cancelButtonColor: "#3085d6",
                    confirmButtonText: "Eliminar",
                    cancelButtonText: "Cancelar"
                });

                if (!confirmacion.isConfirmed) return;

                try {

                    const response = await fetch("php/delete-documento.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded"
                        },
                        body: `idDocumento=${idDocumento}`
                    });

                    const data = await response.json();

                    if (data.status === "success") {
                        await Swal.fire({
                            title: "Éxito",
                            text: data.message,
                            icon: "success"
                        });
                        location.reload();
                    } else {
                        await Swal.fire({
                            title: "Error",
                            text: data.message,
                            icon: "error"
                        });
                    }
                } catch (error) {
                    console.error("Error:", error);
                    await Swal.fire({
                        title: "Error",
                        text: "Hubo un problema al eliminar el documento.",
                        icon: "error"
                    });
                }
            });
        });
    }

    document.addEventListener("DOMContentLoaded", function() {
        const modals = document.querySelectorAll(".modalAgregarDocumento, .modalEditarDocumento");
        const closeButtons = document.querySelectorAll(".close");
        const addButtons = document.querySelectorAll(".add-btn");

        addButtons.forEach(btn => {
            btn.addEventListener("click", function(event) {
                event.preventDefault();
                document.getElementById("modalAgregarDocumento").style.display = "block";
            });
        });

        closeButtons.forEach(button => {
            button.addEventListener("click", function() {
                modals.forEach(modal => modal.style.display = "none");
            });
        });

        window.onclick = function(event) {
            modals.forEach(modal => {
                if (event.target == modal) {
                    modal.style.display = "none";
                }
            });
        };
    });
</script>
<?php include 'alert.php'; ?>

</html>