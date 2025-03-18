<?php
    session_start();
    $_SESSION['access'] = false;
?>
<html lang="fr">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Page d'Emprunt</title>
    <link href="css/style.css" rel="stylesheet"/>
    <link href="css/style-choix.css" rel="stylesheet"/>
</head>
    <body>
        <?php
            include("connexionPDO.php");
        ?>

        <script>
        </script>
    </body>
</html>