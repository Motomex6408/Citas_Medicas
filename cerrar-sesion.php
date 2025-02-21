<?php 
    session_start();
    session_destroy();
    header('Location: ../Citas_Medicas/principal');
?>