<?php
    session_start();

    include("connexionPDO.php");

    if (!$_SESSION['acces'] || !isset($_SESSION['ID']) || empty($_SESSION['panier']))
    {
        header("Location: index.php"); // Si pas connecté ou panier vide, retour à l'accueil
        exit();
    }

    $query = $objet_PDO->prepare("SELECT * FROM utilisateurs WHERE badge_RFID = ?");
    $query->execute([$_SESSION['ID']]);
    $utilisateur = $query->fetch();

    if (!$utilisateur)
    {
        header("Location: index.php"); // Si l'utilisateur n'existe pas, on le redirige
        exit();
    }

    $utilisateur_ID = $utilisateur['badge_RFID'];
    $date_emprunt = date("Y-m-d");
    $date_limite_pret = date("Y-m-d", strtotime("+{$_SESSION['delai']} days"));
    $_SESSION['date_limite_pret'] = $date_limite_pret;

    // Préparation de la requête d'insertion
    $query = $objet_PDO->prepare("INSERT INTO emprunts (utilisateur_ID, ouvrage_ID, date_emprunt, date_retour, date_limite_pret) VALUES (?, ?, ?, NULL, ?)");

    // Insérer chaque livre du panier
    foreach ($_SESSION['panier'] as $isbn)
    {
        try
        {
            $query->execute([$utilisateur_ID, $isbn, $date_emprunt, $date_limite_pret]);
            $updateQuery = $objet_PDO->prepare("UPDATE ouvrages SET statut = 'Emprunté' WHERE ISBN = ?");
            $updateQuery->execute([$isbn]);
            $updateUserQuery = $objet_PDO->prepare("UPDATE utilisateurs SET emprunts_en_cours = emprunts_en_cours + 1 WHERE badge_RFID = ?");
            $updateUserQuery->execute([$utilisateur_ID]);
        } 
        catch (PDOException $e)
        {
            echo "Erreur lors de l'insertion dans la base de données: " . $e->getMessage();
        }
    }

    // Vider le panier après l’emprunt
    unset($_SESSION['panier']);

    // Redirection vers la page de confirmation avec la date limite d'emprunt
    header("Location: confirmation.php");
    exit();
?>