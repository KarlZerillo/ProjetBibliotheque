<?php
      session_start();
      session_destroy();

      session_start();
      $_SESSION['deconnexion'] = true;

      header('Location:index.php');
      exit();
?>