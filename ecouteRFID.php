<?php
    // Activer l'affichage complet des erreurs pour le debug
    error_reporting(E_ALL);
    ini_set("display_errors", 1);

    // Ne pas limiter le temps d'exécution du script (écoute en continu)
    set_time_limit(0);

    // Configuration de l'adresse IP et du port d'écoute UDP
    $adresse_ip = "172.17.101.43";
    $port = 54957;

    // Chemin vers le fichier JSON qui stocke le tag et son ID
    $fichier_tag = __DIR__ . '/trame.json';

    echo "Démarrage de l'écoute UDP sur $adresse_ip:$port...\n";

    // Réinitialiser le fichier JSON au démarrage avec id à 0 et valeur vide
    file_put_contents($fichier_tag, json_encode(["id" => 0, "valeur" => ""]));

    // Initialiser l'identifiant (ID) des tags reçus à 0
    $id_courant = 0;

    // Création du socket UDP
    $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
    if ($socket === false)
    {
        die("Erreur lors de la création du socket : " . socket_strerror(socket_last_error()) . "\n");
    }

    // Liaison du socket à l'adresse IP et au port configurés
    if (!socket_bind($socket, $adresse_ip, $port))
    {
        die("Erreur lors du bind du socket : " . socket_strerror(socket_last_error($socket)) . "\n");
    }

    echo "En attente de tags RFID...\n";

    while (true)
    {
        $buffer = "";
        $ip_distance = "";
        $port_distance = 0;

        // Lecture des données reçues via UDP
        $octets_lus = socket_recvfrom($socket, $buffer, 1024, 0, $ip_distance, $port_distance);

        if ($octets_lus !== false && $octets_lus > 0)
        {
            $tag_hexadecimal = trim($buffer); // Ex: "A034D8F3"
            
            // Nettoyer la chaîne pour garder uniquement les caractères hexadécimaux
            $tag_nettoye = preg_replace('/[^0-9A-Fa-f]/', '', $tag_hexadecimal);

            // Convertir la valeur hexadécimale en entier décimal
            $tag_decimal = hexdec($tag_nettoye);

            // Incrémenter l'identifiant du tag reçu
            $id_courant++;

            // Préparer les données à sauvegarder dans le fichier JSON
            $donnees_a_sauvegarder =
            [
                "id" => $id_courant,
                "valeur" => $tag_decimal
            ];

            // Écrire les données dans le fichier JSON
            file_put_contents($fichier_tag, json_encode($donnees_a_sauvegarder));

            // Afficher la réception du tag dans la console
            echo "[" . date("H:i:s") . "] Tag reçu : $tag_hexadecimal (décimal : $tag_decimal), ID : $id_courant\n";
        }
    }

    // Fermeture du socket (normalement jamais atteint car boucle infinie)
    socket_close($socket);
?>