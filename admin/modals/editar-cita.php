<style>
    .modal-content .tabla-container {
        display: none;
    }

    .autocomplete-container {
        position: relative;
    }

    .suggestions {
        position: absolute;
        width: 100%;
        border: 1px solid #ccc;
        border-top: none;
        background: white;
        max-height: 150px;
        overflow-y: auto;
        display: none;
    }

    .suggestions div {
        padding: 8px;
        cursor: pointer;
    }

    .suggestions div:hover {
        background-color: #f0f0f0;
    }
</style>
<link rel="stylesheet" href="../css/modal-usuario.css">
<div id="modalEditarCita" class="modalEditarCita">
    <div class="modal-content">
        <span class="close">&times;</span>
        <form id="formEditarCita" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?action=edit_cita" method="POST" onsubmit="return enviarFormularioCita(event)">
            <div class="title">Actualizar datos de la Cita</div>
            <div class="form-group">
                <label for="edit-idCita">ID Cita</label>
                <input id="edit-idCita" type="text" name="idCita" autocomplete="off" readonly>

                <label for="edit-dnipaciente">DNI Paciente</label>
                <input id="edit-dnipaciente" type="text" name="dnipaciente" autocomplete="off" readonly>

                <label for="edit-idpaciente">ID Paciente</label>
                <input id="edit-idpaciente" type="text" name="idPaciente" autocomplete="off" readonly>

                <label for="edit-buscarpaciente">Paciente</label>
                <div class="autocomplete-container">
                    <input type="text" id="edit-buscarpaciente" placeholder="Buscar paciente..." autocomplete="off">
                    <div class="suggestions" id="suggestionsEditar"></div>
                </div>

                <label for="edit-fecha">Fecha</label>
                <input id="edit-fecha" onchange="obtenerHorariosDisponibles()" type="date" name="fecha" autocomplete="off">
            </div>
            <div class="tabla-container" id="tablaHorariosDisponiblesEditar">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>FECHA</th>
                                <th>DIA</th>
                                <th>MEDICO</th>
                                <th>HORARIO</th>
                                <th>CUPOS</th>
                                <th>-</th>
                            </tr>
                        </thead>
                        <tbody>

                        </tbody>
                    </table>
                </div>
            </div>
            <div class="form-group">
                <label for="edit-dnimedico">DNI Médico</label>
                <input id="edit-dnimedico" type="text" name="dnimedico" autocomplete="off">

                <label for="edit-idmedico">ID Medico</label>
                <input id="edit-idmedico" type="text" name="idMedico" autocomplete="off" readonly>

                <label for="edit-medico">Médico</label>
                <input id="edit-medico" type="text" name="medico" autocomplete="off" readonly>

                <label for="edit-hora">Hora</label>
                <input id="edit-hora" type="time" name="hora" autocomplete="off">

                <label for="edit-motivo">Motivo</label>
                <input id="edit-motivo" type="text" name="motivo" autocomplete="off">

                <label for="edit-estado">Estado</label>
                <select id="edit-estado" name="estado">
                    <option value="Pendiente">Pendiente</option>
                    <option value="Confirmada">Confirmada</option>
                    <option value="Cancelada">Cancelada</option>
                </select>

                <label for="edit-idhorario">ID Horario</label>
                <input id="edit-idhorario" type="text" name="idHorario" autocomplete="off" readonly>
            </div>
            <button type="submit" class="modificar">Modificar Cita</button>
        </form>
    </div>
</div>

<script>
    function enviarFormularioCita(event) {
        event.preventDefault();
        
        const idCita = document.getElementById('edit-idCita').value;
        if (!idCita) {
            Swal.fire('Error', 'No se ha seleccionado una cita válida', 'error');
            return false;
        }

        Swal.fire({
            title: 'Actualizando cita...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        const form = document.getElementById('formEditarCita');
        const formData = new FormData(form);
        
        const actionUrl = '/Citas_Medicas/admin/edit-cita.php';

        fetch(actionUrl, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error HTTP ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            Swal.fire({
                icon: data.success ? 'success' : 'error',
                title: data.success ? 'Éxito' : 'Error',
                text: data.message || (data.success ? 'Cita actualizada' : 'Error al actualizar'),
                confirmButtonText: 'Aceptar'
            }).then((result) => {
                if (data.success) {
                    document.querySelector('.modalEditarCita').style.display = 'none';
                    window.location.reload();
                }
            });
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error de conexión',
                html: `<p>No se pudo conectar con el servidor</p>
                    <p><strong>Ruta intentada:</strong> ${actionUrl}</p>
                    <p><small>${error.message}</small></p>`,
                confirmButtonText: 'Entendido'
            });
        });

        return false;
    }

    document.getElementById("edit-fecha").addEventListener("input", function() {
        const fecha = this.value;
        const tablaContainer = document.getElementById("tablaHorariosDisponiblesEditar");

        if (!fecha) {
            tablaContainer.style.display = "none";

            // Limpiar los campos del médico
            document.getElementById("edit-dnimedico").value = "";
            document.getElementById("edit-idmedico").value = "";
            document.getElementById("edit-medico").value = "";
            document.getElementById("edit-idhorario").value = "";
        }
    });

    function obtenerHorariosDisponibles(origen) {
        const fecha = document.getElementById(origen === "editar" ? "edit-fecha" : "add-fecha").value;
        const tablaID = origen === "editar" ? "tablaHorariosDisponiblesEditar" : "tablaHorariosDisponiblesAgregar";
        const tablaContainer = document.getElementById(tablaID);

        console.log(`Obteniendo horarios para: ${origen}, Fecha: ${fecha}`);

        if (!fecha) {
            return;
        }

        const xhr = new XMLHttpRequest();
        xhr.open("GET", `php/obtener-horarios.php?fecha=${fecha}`, true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                const respuesta = JSON.parse(xhr.responseText);
                console.log("Respuesta del servidor:", respuesta);

                if (respuesta.success && respuesta.horarios.length > 0) {
                    actualizarTablaHorarios(respuesta.horarios, tablaID);
                    tablaContainer.style.display = "block";
                } else {
                    actualizarTablaHorarios([], tablaID);
                    tablaContainer.style.display = "block";
                    //tablaContainer.style.display = "none";
                }
            }
        };
        xhr.send();
    }

    function actualizarTablaHorarios(horarios, tablaID) {
        const tbody = document.querySelector(`#${tablaID} tbody`);
        tbody.innerHTML = ""; // Limpiar la tabla

        if (horarios.length > 0) {
            horarios.forEach(horario => {
                const tr = document.createElement("tr");
                tr.innerHTML = `
            <td>${horario.fecha}</td>
            <td>${horario.diaSemana}</td>
            <td>${horario.medico}</td>
            <td>${horario.HoraInicio} - ${horario.HoraFin}</td>
            <td>${horario.cupos}</td>
            <td>
                <a href="#" class="edit-btn"
                    data-idhorario="${horario.idHorario}"
                    data-dnimedico="${horario.DNIMedico}"
                    data-idmedico="${horario.idMedico}"
                    data-medico="${horario.medico}"></a>
            </td>
        `;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = "<tr><td colspan='6' style='text-align:center;'>No hay médicos disponibles en este horario.</td></tr>";
        }
    }

    // Llamadas en los inputs de fecha
    document.getElementById("edit-fecha").addEventListener("change", () => obtenerHorariosDisponibles("editar"));

    // Limpiar campos de pacientes si está vacío
    document.getElementById("edit-buscarpaciente").addEventListener("input", function() {
        const paciente = this.value;

        if(!paciente) {
            document.getElementById("edit-idpaciente").value = "";
            document.getElementById("edit-dnipaciente").value = "";
        }
    });

    // Lista dinamica de pacientes
    document.addEventListener("DOMContentLoaded", function() {
        const inputEditar = document.getElementById("edit-buscarpaciente");
        const suggestionsEditar = document.getElementById("suggestionsEditar");

        inputEditar.addEventListener("input", function() {
            let query = this.value.trim();
            suggestionsEditar.innerHTML = "";

            if (query.length === 0) {
                suggestionsEditar.style.display = "none";
                return;
            }

            fetch("php/buscar-paciente.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: "query=" + encodeURIComponent(query)
                })
                .then(response => response.json())
                .then(data => {
                    suggestionsEditar.innerHTML = "";
                    if (data.length > 0) {
                        suggestionsEditar.style.display = "block";
                        data.forEach(paciente => {
                            let div = document.createElement("div");
                            div.textContent = paciente.Paciente;
                            div.dataset.id = paciente.idPaciente;
                            div.dataset.dni = paciente.dni;
                            div.addEventListener("click", function() {
                                inputEditar.value = this.textContent;
                                document.getElementById("edit-idpaciente").value = this.dataset.id;
                                document.getElementById("edit-dnipaciente").value = this.dataset.dni;
                                suggestionsEditar.style.display = "none";
                            });
                            suggestionsEditar.appendChild(div);
                        });
                    } else {
                        suggestionsEditar.style.display = "none";
                    }
                })
                .catch(error => console.error("Error:", error));
        });

        document.addEventListener("click", function(e) {
            if (!document.querySelector(".autocomplete-container").contains(e.target)) {
                suggestionsEditar.style.display = "none";
            }
        });
    });


    document.addEventListener("click", function(event) {
        if (event.target.closest(".edit-btn")) {
            event.preventDefault();
            let btn = event.target.closest(".edit-btn");

            document.getElementById("edit-dnimedico").value = btn.dataset.dnimedico;
            document.getElementById("edit-idmedico").value = btn.dataset.idmedico;
            document.getElementById("edit-medico").value = btn.dataset.medico;
            document.getElementById("edit-idhorario").value = btn.dataset.idhorario;
        }
    });
</script>