<?php
    session_start();

    if (isset($_GET['index']) && isset($_SESSION['panier'][$_GET['index']]))
    {
        // Supprimer l'élément à l'index spécifié
        unset($_SESSION['panier'][$_GET['index']]);

        // Réindexer le tableau après la suppression (cela réorganise l'indexation sans changer l'ordre des éléments)
        $_SESSION['panier'] = array_values($_SESSION['panier']); // Réindexer le tableau
    }

    header("Location: emprunter.php");
    exit();
?>