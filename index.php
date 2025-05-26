<?php
    session_start(); // Démarre la session PHP
    $_SESSION['acces'] = false; // Initialise la session acces à false
    $_SESSION['admin'] = false; // Initialise la session admin à false
    $_SESSION['selection'] = "";
    $_SESSION['alerte1'] = true;
    $_SESSION['alerte2'] = true;

    if (!isset($_SESSION['dernierIdLu']))
    {
        $_SESSION['dernierIdLu'] = 0;
        $_SESSION['ID'] = ""; 
    }

    if (isset($_POST['badge']) && !empty($_POST['badge']))
    {
        $_SESSION['ID'] = $_POST['badge']; // Stocke le badge dans la session

        // Redirige vers la page choix.php
        header("Location: choix.php");
        exit();
    }
?>
<html lang="fr">
    <head>
        <meta charset="utf-8"/>
        <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
        <title>Accueil</title>
        <link href="css/style.css" rel="stylesheet"/>
        <link href="css/style-accueil.css" rel="stylesheet"/>
	<script src="js/souris.js"></script> <!-- script pour masquer la souris lorsqu'on ne clique pas -->
    </head>
    <body>
        <form id="badge-form" method="POST" action=""> <!-- Formulaire pour le badge -->
            <input type="hidden" name="badge" id="badge"> <!-- Champ caché -->
        </form>

        <div class="conteneur-accueil">
            <?php
                $heureActuelle = date("H"); // Récupère l'heure actuelle (format 24h)
                $messageSalutation = ($heureActuelle >= 6 && $heureActuelle < 18) ? "Bonjour" : "Bonsoir"; // Choisit le message en fonction de l'heure
            ?>
            <div class="contenu-centre">
                <img src="images/logo.png" style="max-width: 160px; height: auto;">
            </div>
            <h1><?php echo $messageSalutation?>. Veuillez scanner votre badge.</h1> <!-- Affiche un message de salutation -->
            <div id="heure" class="barre-haut"></div> <!-- Affiche l'heure en direct -->
            <div id="date" class="barre-bas"></div> <!-- Affiche la date en direct -->
        </div>

        <script>
            document.addEventListener("DOMContentLoaded", function ()
            {
                let champBadge = document.getElementById("badge"); // Récupère l'élément du formulaire badge
	            let valeurBadge = ""; // Variable pour stocker la valeur scannée

                document.addEventListener("keypress", function (event)
                {
                    if (event.key === "Enter") // Si on appuie sur la touche Entrée
                    {
                        event.preventDefault(); // Empêche l'action par défaut du formulaire

                        if (valeurBadge.trim() !== "") // Si la valeur n'est pas vide
                        {
                            champBadge.value = valeurBadge; // Insère la valeur scannée dans le champ caché

                            // Soumet le formulaire pour que PHP traite la valeur
                            document.getElementById("badge-form").submit();

                            valeurBadge = ""; // Réinitialise la variable après l'envoi
                        }
                    }
                    else
                    {
                        valeurBadge += event.key; // Construit le badge caractère par caractère
                    }
                });
            });

            // Fonction pour afficher la date et l'heure en direct
            function afficherDateHeure()
            {
                var date = new Date(); // Crée un objet Date
                var nomsMois = ["Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre"]; // Tableau des mois

                // Formatage de la date
                var jourFormatte = ("0" + date.getDate()).slice(-2); // Formate le jour avec 2 chiffres
                var moisFormatte = nomsMois[date.getMonth()]; // Formate le mois
                var anneeFormatte = date.getFullYear(); // Récupère l'année

                // Formatage de l'heure
                var heuresFormatte = date.getHours(); // Récupère l'heure
                var minutesFormatte = ("0" + date.getMinutes()).slice(-2); // Formate les minutes avec 2 chiffres
                var secondesFormatte = ("0" + date.getSeconds()).slice(-2); // Formate les secondes avec 2 chiffres

                // Construction de la date et de l'heure formatées
                var dateComplete = jourFormatte + ' ' + moisFormatte + ' ' + anneeFormatte;
                var heureComplete = ("0" + heuresFormatte).slice(-2) + ':' + minutesFormatte + ':' + secondesFormatte;
                
                // Mise à jour des éléments
                document.getElementById('date').innerHTML = dateComplete;
                document.getElementById('heure').innerHTML = heureComplete;
            }

            // Appel de la fonction toutes les secondes pour mettre à jour l'heure et le message
            setInterval(afficherDateHeure, 1000);
            afficherDateHeure();
            
            // Faire en sorte qu'on soit toujours focus sur le form peu importe où l'on clique
            document.addEventListener("click", function ()
            {
                document.getElementById("badge").focus(); // Focus sur le champ badge lorsqu'on clique
            });
            
            // Bloquer le clavier pour le form 
            document.getElementById("badge").addEventListener("keypress", function (event)
            {
                // Autorise uniquement les chiffres et la touche Entrée
                if (!(event.code.startsWith("Numpad") || event.code.startsWith("Digit") || event.key === "Enter"))
                {
                    event.preventDefault(); // Empêche les autres touches
                }
            });

            // Effacer la valeur de la vidéo du localStorage
            localStorage.removeItem("tempsVideo");

            // Script vérifiant si un tag RFID a été scanné
            setInterval(() =>
            {
                fetch("rfid-check-accueil.php")
                    .then(response => response.json())
                    .then(data =>
                    {
                        if (data.changement)
                        {
                            window.location.href = "choix.php";
                        }
                    })
            }, 1000);
        </script>
    </body>
</html>