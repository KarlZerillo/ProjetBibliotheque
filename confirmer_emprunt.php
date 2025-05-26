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

    $idUtilisateur = $utilisateur['badge_RFID'];
    $dateEmprunt = date("Y-m-d");
    $dateLimitePret = date("Y-m-d", strtotime("+{$_SESSION['delai']} days"));
    $_SESSION['date_limite_pret'] = $dateLimitePret;
    $dateRappel = date("Y-m-d", strtotime($dateLimitePret . " +{$_SESSION['delai_rappel']} days"));

    // Préparation de la requête d'insertion
    $requeteInsertionEmprunt = $objet_PDO->prepare("INSERT INTO emprunts (utilisateur_ID, ouvrage_ID, date_emprunt, date_retour, date_limite_pret, date_rappel) VALUES (?, ?, ?, NULL, ?, ?)");

    // Préparation des autres requêtes
    $requeteMajOuvrage = $objet_PDO->prepare("UPDATE ouvrages SET statut = 'Emprunté' WHERE ISBN = ?");
    $requeteMajUtilisateur = $objet_PDO->prepare("UPDATE utilisateurs SET emprunts_en_cours = emprunts_en_cours + 1 WHERE badge_RFID = ?");

    // Insérer chaque livre du panier
    foreach ($_SESSION['panier'] as $isbn)
    {
        try
        {
            $requeteInsertionEmprunt->execute([$idUtilisateur, $isbn, $dateEmprunt, $dateLimitePret, $dateRappel]);
            $requeteMajOuvrage->execute([$isbn]);
            $requeteMajUtilisateur->execute([$idUtilisateur]);
        }
        catch (PDOException $erreur)
        {
            echo "Erreur lors de l'insertion dans la base de données : " . $erreur->getMessage();
        }
    }

    // Vider le panier après l’emprunt
    unset($_SESSION['panier']);

    // Redirection vers la page de confirmation avec la date limite d'emprunt
    $_SESSION['Action'] = "Emprunt";
    header("Location: confirmation.php");
    exit();
?>