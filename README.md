#ANDERSON JAIR GARCIA(202310050158) HERRAMIENTAS USADAS: PHP, HTML, CSS, SQL SERVER, VISUAL STUDIO CODE 

--01/02/25-- Se inicio creando tanto el archivo Index.php y el Archivo Index.css, al igual que se creo el archivo de conexion para la base de datos, tambien se creo la carpeta "Imagenes" donde se guardarian las primeras imagenes que aparecerian en el Index Principal y donde se guardarian las demas imagenes en un futuro. Ademas se creo el apartado de Login donde el usuario podria logearse o registrarse en el sistema.

--03/02/25-- Se hicieron modificaciones en el Css del Index.php, al igual que se reemplazo la carpeta "Imagenes" por una llmada "Img", donde se agregarian un par de imagenes mas que se  mostrarian en el Index principal.

--08/02/25-- Iniciaria la creacion del Modulo de Pacientes, donde se crearia una primera idea de como funcionaria el aparatado de Reserva, que, mas tarde fue modificado por una funcion mas sencillas y ordenada.

--09/02/25-- Un par de modificaciones en el archivo de conexion a la base de datos y en el manejo de sesiones del sistema.

--18/02/25-- Se instalo Composer dentro del sistema para el manejo de librerias y paquetes que servirian para el manejo de exportacion de Archivos PDF, Word y Excel.

--21/02/25-- Primera implemenatacion del modulo de Admin en el sistema a la par que el primer rediseño del sistema tanto para Admin y Pacientes, en un futuro tambien para Medicos, implementando un header mas apropiado y un menu lateral para el manejo de distintas pestañas del sistema.

--15/03/25-- Se implemento una gran parte de la funcionalidad del Modulo de Administrador, tambien se implemento un nuevo header y un menu lateral para navegar por los disntintos modulos, a excepcion del Modulo Principal, que sera el modulo que se mostrara cuando no haya una sesion iniciada, y tambien de momento el Modulo de Medicos, que se espera implementar sus funcionalidades en el proximo commit. Por parte del modulo de pacientes, se trabajo en las pestanias de Especilades, donde se mostraran las especilidades del centro medico, la pestania de Medicos, donde se mostraran los medicos que hay ahora en el centro, al igual que una pestania de Contacto, y por supuesto, la pestania de reservar cita, que sera donde el paciente podra reservar su cita de formaOnline, de momento una buena parte de esta funcionalidad esta implementa a falta de hacer el insert a la tabla, tambien se espera terminar esta parte en el proximo commit.

--20/03/25-- Actualizacion del sistema que incluye el rediseño total del modulo de medicos y la implementacion de sus funciones, tales como la posibilidad de agregar un horario medico y los cupos que tendra, esto desde la pestania llamada "Horarios Medicos". Tambien, la posibilidad de agregar y subir documentos medicos y exportarlos a PDF, Excel y Word, esto desde la pestania llamada: "Documentos Medicos". La pestania: "Expedientes Medicos", aun esta siendo trabajada por lo que si se intenta acceder no le llevara a ningun lado.

--22/03/25-- Se agrego la interfaz principal para Expedientes Medicos y se agregaron los complementos para el modulo de pacientes, ademas de que se corrigieron problemas de insercion en citas medicas para Administrador o Medico en el sistema. Se implementa actualizacion dinamica sin recargar a la tabla de pacientes en el modulo de Medicos.

--22/03/25-- Se finalizo la parte de programacion del apartado de Reservar citas, ahora ya podras insertar los datos a la tabla citas. Aun hay detalles por pulir en ese apartado que se esperan abarcar proximamente

---24/03/2025--- Se agrega una mejora en la asignacion de horarios medicos donde se puede obtener sugerencias de medicos existentes en la base de datos del sistema para una busqueda mas rapida y facil de hacer. Se implementan las mismas actualizaciones en Médicos para las secciones de Horarios y Citas Médicas como en Administrador.

--25/03/2025-- Se corrigio el error al descargar Archivos PDF, Excel y Word, ademas se mejora la logica de la seccion de Documentos medicos y se actualiza el modalAgregarDocumento para poder seleccionar las citas registradas de forma mas facil y rapida, se agrega actualizacion dinamica a la tabla de documentos medicos.

--26/03/2025-- Se inicio con la implementacion del envio de correos electronicos al paciente, de momento solo aplica para cuando la cita ha sido registrada y esta a la espera de confirmacion por parte de la Administracion, pronto se implementara para los demas modulos que se tienen pensados.

--28/03/2025-- Se mejoro el sistema de envios de correos para pacientes, ahora hay sweetalerts que confirman el registro de la cita. Se hacen cambios en la modalEditarDocumentos en la seccion de Documentos Medicos para poder actualizar la información en base a los cambios realizados en modalAgregarDocumentos y se corrige un error que autorrellenaba la misma formación de paciente y medico en la modalAgregarDocumentos desde presionar editar. Se implementa una mejora en los botones de la tablas que cambian su color a uno mas llamativo al momento de colocar el cursor sobre él, ademas, se implementa la funcionalidad en la seccion Documentos Medicos para generar reportes pdf que contiene las Recetas y Constancias medicas en una plantilla minimalista y facil de leer. Se corrige un error que permitia insercion de duplicados de numeros de Licencias y telefonos al registrar/editar medicos en Administrador. Se actualiza el formulario de registro de usuarios y de pacientes donde verifica si el paciente es un niño o menor de edad, el formulario se adapta para registrar unicamente la información del niño y de los tutores.

--30/03/2025-- Se finalizo es sistema de correos del sistema y se implementaron nuevas funciones en el modulo de Medicos.

--03/04/2025--Tabla Auditoria es ahora completamente funcional y se puede visualizar desde el modulo de Administrador en la pestaña "Auditoria", ademas las funcionalidades del correo ahora tambien estan disponibles en el modulo de Medicos.

--- 04/04/2025 --- Se desarrolla en el modulo de pacientes la seccion para la reserva de citas, donde se implementan las primeras secciones de codigo necesarias para la insercion de reserva de citas, todavia en desarrollo. Se corrigen algunos errores que impedian el funcionamiento correcto para verificar la disponibilidad del horario medico y al Insertar Citas, incluye correccion de algunas consultas y parametros mal definidos.

--- 05/04/2025 --- Correccion de errores al exportar reportes en el modulo de medicos y mejoras en las pantallas de inicio para Medicos y Administrador. Funcion de recuperar contraseña disponible.