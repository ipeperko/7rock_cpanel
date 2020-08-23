<?php
    include_once 'core/session.php';

    clear_session();
   
    echo 'Session finished';
    header('Refresh: 1; URL = login.php');
?>
