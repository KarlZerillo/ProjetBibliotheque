<?php
    include("connexionPDO.php");

    if (isset($_GET['isbn']))
    {
        $isbn = $_GET['isbn'];

        // Récupérer les informations du livre depuis la base de données
        $requete = $objet_PDO->prepare("SELECT * FROM ouvrages WHERE ISBN = ?");
        $requete->execute([$isbn]);
        $ouvrage = $requete->fetch();

        if ($ouvrage)
        {
            // Renvoyer les informations en format JSON
            echo json_encode(
            [
                'titre' => $ouvrage['titre'],
                'auteur' => $ouvrage['auteur'],
                'type' => $ouvrage['type'],
                'description' => $ouvrage['description'],
                'photo' => base64_encode($ouvrage['photo'])
            ]);
        }
    }
?>