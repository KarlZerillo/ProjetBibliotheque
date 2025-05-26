<?php
session_start();

header('Content-Type: application/json');

// Fichier JSON qui contient le dernier tag RFID reçu
$fichier = 'trame.json';

// Lecture du contenu JSON et décodage en tableau associatif
$contenu = file_get_contents($fichier);
$data = json_decode($contenu, true);

// Récupération sécurisée de l'ID et de la valeur du dernier tag
$dernierId = isset($data['id']) ? (int)$data['id'] : 0;
$dernierTag = isset($data['valeur']) ? trim((string)$data['valeur']) : "";

// Cas particulier : après une déconnexion, on réinitialise la variable de session sans signaler de changement
if (isset($_SESSION['deconnexion']) && $_SESSION['deconnexion'] === true)
{
    $_SESSION['dernierIdLu'] = $dernierId;  // Met à jour l'ID lu en session
    unset($_SESSION['deconnexion']);        // Supprime le flag de déconnexion
    exit;                                   // On quitte sans renvoyer de données JSON
}

// Première fois que l'on vérifie : on initialise les variables de session
if (!isset($_SESSION['dernierIdLu']))
{
    $_SESSION['dernierIdLu'] = $dernierId;
    $_SESSION['ID'] = $dernierTag;

    // Si la valeur est vide ou nulle, on considère qu'il n'y a pas de changement à signaler
    $changement = ($dernierTag !== "" && $dernierTag !== "0");

    echo json_encode(
    [
        "changement" => $changement,
        "valeur" => $dernierTag,
        "id" => $dernierId
    ]);
    exit;
}

// Si un nouveau tag a été scanné (ID différent) et que la valeur est valide
if ($_SESSION['dernierIdLu'] !== $dernierId && $dernierTag !== "" && $dernierTag !== "0")
{
    // Mise à jour de la session avec le nouvel ID et la nouvelle valeur
    $_SESSION['dernierIdLu'] = $dernierId;
    $_SESSION['ID'] = $dernierTag;

    echo json_encode(
    [
        "changement" => true,
        "valeur" => $dernierTag,
        "id" => $dernierId
    ]);
}
else
{
    // Pas de changement détecté, on renvoie false
    echo json_encode(
    [
        "changement" => false
    ]);
}
?>