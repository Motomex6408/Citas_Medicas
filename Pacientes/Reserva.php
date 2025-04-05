<?php
require_once 'session-control.php'; 
if (isset($_SESSION['alert_message'])) {
    $alertType = $_SESSION['alert_type'];
    $alertMessage = addslashes($_SESSION['alert_message']);
    $alertScript = <<<EOT
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        Swal.fire({
            icon: '$alertType',
            title: '$alertType' === 'success' ? 'Éxito' : 'Error',
            text: '$alertMessage',
            confirmButtonText: "Entendido"
        });
    });
    </script>
EOT;
    unset($_SESSION['alert_type']);
    unset($_SESSION['alert_message']);
} else {
    $alertScript = '';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediCitas - Citas Médicas</title>
    <link rel="stylesheet" href="../css/estilo.css">
    <link rel="stylesheet" href="../css/estilo-admin.css">
    <link rel="stylesheet" href="../css/tabla.css">
    <link rel="stylesheet" href="../css/Reserva.css">
    <link rel="stylesheet" href="../css/modal-usuario.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>
<body>

<?php
include 'header.php';
include 'menu.php';
echo $alertScript;
?>

<main class="contenido">

    <div class="table-container">
        <h2>Selecciona una Especialidad Para Tu Cita</h2>
        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Seleccionar</th>
                </tr>
            </thead>
            <tbody>
                <?php
                include '../conexion.php';
                $sql = "SELECT * FROM Especialidades";
                $consulta = $conn->prepare($sql);
                $consulta->execute();
                while ($row = $consulta->fetch(PDO::FETCH_ASSOC)) {
                    echo '<tr>
                        <td>'.htmlspecialchars($row["nombreEspecialidad"]).'</td>
                        <td>'.htmlspecialchars($row["descripcion"]).'</td>
                        <td><button class="btn-seleccionar" data-especialidad="'.htmlspecialchars($row["nombreEspecialidad"]).'">Seleccionar</button></td>
                    </tr>';
                }
                ?>
            </tbody>
        </table>
    </div>


    <div id="cita-container" style="display:none;">
        <button id="btn-regresar" class="btn-cancelar">Regresar</button>
        <h3>Selecciona una Fecha Para Tu Cita</h3>
        <input type="text" id="fecha-cita" placeholder="Selecciona una fecha">

        <div id="horarios-disponibles" style="display:none; margin-top:20px;">
            <h3>Horarios Disponibles</h3>
            <div class="scrollable-table">
                <table>
                    <thead>
                        <tr>
                            <th>Hora Inicio</th>
                            <th>Hora Fin</th>
                            <th>Médico</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody id="horarios-list"></tbody>
                </table>
            </div>
        </div>

        <div id="motivo-cita" style="display:none; margin-top:20px;">
            <h3>Motivo de la Cita</h3>
            <div class="form-group">
                <label for="dni">DNI:</label>
                <input type="text" id="dni" value="<?= htmlspecialchars($_SESSION['usuario']['dni'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="motivo">Motivo:</label>
                <textarea id="motivo" placeholder="Describe el motivo de tu cita" required></textarea>
            </div>
            <button id="btn-confirmar" class="btn-aceptar">Confirmar Cita</button>
        </div>
    </div>
</main>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    let especialidadSeleccionada = null;
    let horarioSeleccionado = null;
    let fechaSeleccionada = null;


    $('.btn-seleccionar').click(function() {
        especialidadSeleccionada = $(this).data('especialidad');
        $('.table-container').fadeOut(300, function() {
            $('#cita-container').fadeIn(300);
        });
    });


    flatpickr('#fecha-cita', {
        dateFormat: 'Y-m-d',
        minDate: 'today',
        locale: 'es',
        onChange: function(selectedDates, dateStr) {
            fechaSeleccionada = dateStr;
            $('#horarios-disponibles').hide();
            
            if(especialidadSeleccionada && fechaSeleccionada) {
                cargarHorariosDisponibles();
            }
        }
    });

    
    function cargarHorariosDisponibles() {
        $.ajax({
            url: 'obtenerHorarios.php',
            type: 'POST',
            data: {
                fecha: fechaSeleccionada,
                especialidad: especialidadSeleccionada
            },
            beforeSend: function() {
                $('#horarios-list').html('<tr><td colspan="4">Cargando horarios...</td></tr>');
            },
            success: function(response) {
                $('#horarios-list').html(response);
                $('#horarios-disponibles').fadeIn();
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudieron cargar los horarios'
                });
            }
        });
    }
  
    $(document).on('click', '.btn-horario', function() {
        $('.btn-horario').removeClass('selected');
        $(this).addClass('selected');
        horarioSeleccionado = $(this).data('horario');
        $('#motivo-cita').fadeIn();
    });

    
    $('#btn-regresar').click(function() {
        resetearProcesoReserva();
    });

   
    function resetearProcesoReserva() {
        $('#cita-container').fadeOut(300, function() {
        $('.table-container').fadeIn(300);
        });
        $('#fecha-cita').val('');
        $('#horarios-list').empty();
        $('#horarios-disponibles').hide();
        $('#motivo-cita').hide();
        $('.btn-horario').removeClass('selected');
        especialidadSeleccionada = null;
        horarioSeleccionado = null;
        fechaSeleccionada = null;
    }

    
    $('#btn-confirmar').click(function() {
        const motivo = $('#motivo').val().trim();
        
        if(!motivo) {
            Swal.fire({
                icon: 'warning',
                title: 'Motivo requerido',
                text: 'Por favor describe el motivo de tu cita'
            });
            return;
        }

        if(!horarioSeleccionado) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Por favor selecciona un horario disponible'
            });
            return;
        }

        const datosCita = {
            dni: $('#dni').val(),
            motivo: motivo,
            medico: $('.btn-horario.selected').data('medico'),
            horario: horarioSeleccionado,
            hora: $('.btn-horario.selected').data('hora')
        };

        Swal.fire({
            title: 'Confirmar cita',
            text: '¿Estás seguro de confirmar esta cita?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Confirmar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                enviarConfirmacionCita(datosCita);
            }
        });
    });

    
    function enviarConfirmacionCita(datos) {
    Swal.fire({
        title: 'Procesando',
        html: 'Estamos registrando tu cita...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    $.ajax({
        url: 'InsertarCitas.php',
        type: 'POST',
        data: datos,
        dataType: 'json',
        success: function(response) {
            if(response.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Éxito',
                    text: response.message,
                    willClose: () => {
                        window.location.reload();
                    }
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message
                });
            }
        },
        error: function(xhr, status, error) {
            let errorMsg = 'No se pudo conectar con el servidor';
            if (xhr.responseText) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    errorMsg = response.message || errorMsg;
                } catch (e) {
                    errorMsg = xhr.responseText;
                }
            }
            Swal.fire({
                icon: 'error',
                title: 'Error de conexión',
                text: errorMsg
            });
        }
    });
}

   
    window.addEventListener('beforeunload', function(e) {
        if ($('#motivo-cita').is(':visible') && $('#motivo').val().trim() !== '') {
            e.preventDefault();
            e.returnValue = '¿Estás seguro de salir? Los datos no guardados se perderán.';
        }
    });
});
</script>

</body>
</html>