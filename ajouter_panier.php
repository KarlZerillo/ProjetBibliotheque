<?php
    session_start();

    if (isset($_POST['codebarre']) && !empty($_POST['codebarre']))
    {
        $codebarre = $_POST['codebarre'];

        //Vérifie si le code barre est déjà présent dans le tableau
        if (!in_array($codebarre, $_SESSION['panier']))
        {
            $_SESSION['panier'][] = $codebarre;
        }
    }

    //On réactualise la page
    header("Location: emprunter.php");
    exit();
?>