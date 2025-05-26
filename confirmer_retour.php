<?php
    session_start();

    include("connexionPDO.php");

    if (!$_SESSION['acces'] || !isset($_SESSION['ID']) || empty($_SESSION['panier']))
    {
        header("Location: index.php"); // Si pas connecté ou panier vide, retour à l'accueil
        exit();
    }

    // Récupération des informations de l'utilisateur
    $requeteUtilisateur = $objet_PDO->prepare("SELECT * FROM utilisateurs WHERE badge_RFID = ?");
    $requeteUtilisateur->execute([$_SESSION['ID']]);
    $utilisateur = $requeteUtilisateur->fetch();

    if (!$utilisateur)
    {
        header("Location: index.php"); // Si l'utilisateur n'existe pas, on le redirige
        exit();
    }

    $dateRetour = date("Y-m-d");

    // Préparation des requêtes
    $requeteMajEmprunt = $objet_PDO->prepare("UPDATE emprunts SET date_retour = ? WHERE ouvrage_ID = ?");
    $requeteMajOuvrage = $objet_PDO->prepare("UPDATE ouvrages SET statut = 'Disponible' WHERE ISBN = ?");
    $requeteMajUtilisateur = $objet_PDO->prepare("UPDATE utilisateurs SET emprunts_en_cours = emprunts_en_cours - 1 WHERE badge_RFID = ?");

    // Traitement de chaque livre du panier
    foreach ($_SESSION['panier'] as $isbn)
    {
        try
        {
            $requeteMajEmprunt->execute([$dateRetour, $isbn]);
            $requeteMajOuvrage->execute([$isbn]);
            $requeteMajUtilisateur->execute([$_SESSION['ID']]);
        }
        catch (PDOException $erreur)
        {
            echo "Erreur lors de la mise à jour de la base de données : " . $erreur->getMessage();
        }
    }

    // Vider le panier après les retours
    unset($_SESSION['panier']);

    // Redirection vers la page de confirmation
    $_SESSION['Action'] = "Rendu";
    header("Location: confirmation.php");
    exit();
?>