<?php
    session_start(); // Démarre la session PHP
    $_SESSION['acces'] = false; // Initialise la session acces à false
    $_SESSION['panier'] = []; // Vide la variabe de session panier
    $_SESSION['selection'] = "";

    if (isset($_SESSION['ID'])) // Vérifie si un ID est fourni dans la variable de session ID
    {
        $id = $_SESSION['ID'];
    }
    else
    {
        header("Location: index.php"); // Sinon on renvoie vers la page principale
        exit();
    }
?>
<html lang="fr">
    <head>
        <meta charset="utf-8"/>
        <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
        <title>Page de Choix</title>
        <link href="css/style.css" rel="stylesheet"/>
        <link href="css/style-choix.css" rel="stylesheet"/>
        <script defer src="js/inactivite.js"></script> <!-- script d'inactivité -->
        <script src="js/souris.js"></script> <!-- script pour masquer la souris lorsqu'on ne clique pas -->
    </head>
    <body>
        <?php
            include("connexionPDO.php"); // On se connecte à la base de données
            $acces = false;
            $erreur = 0;

            // Prépare la requête pour vérifier l'existence de l'utilisateur dans la base de données
            $requete = $objet_PDO->prepare("SELECT * FROM utilisateurs WHERE badge_RFID = ?");
            $requete->execute([$id]);
            $utilisateur = $requete->fetch();

            // Si l'utilisateur est trouvé dans la base de données et que le compte est actif, on accorde l'accès
            if ($utilisateur) // l'utilisateur est reconnu
            {
                if ($utilisateur['actif'] == 1) // Vérifie si le compte est actif
                {
                    $_SESSION['acces'] = true; // Donne l'accès à l'utilisateur sur les pages suivantes
                    $acces = true;
                    // Si l'utilisateur est un administrateur, redirection vers le portail admin
                    if ($utilisateur['admin'] == 1) // Vérifie si l'utilisateur est un administrateur
                    {
                        $_SESSION['admin'] = true;
                        header("Location: admin/portail.php");
                        exit();
                    }
                }
                else // Le compte est désactivé
                {
                    $erreur = 1;
                }

                // On stocke les valeurs de l'utilisateur sans des variables de session
                $_SESSION['badge'] = $id;
                $_SESSION['nom'] = $utilisateur['nom'];
                $_SESSION['prenom'] = $utilisateur['prenom'];
                $_SESSION['email'] = $utilisateur['email'];
                $_SESSION['emprunts'] = $utilisateur['emprunts_en_cours'];
            }
            else // l'utilisateur n'est pas reconnu
            {
                $erreur = 0;
            }
        ?>

        <div id="conteneur-scan">
            <?php
            // Si l'utilisateur est reconnu et actif, affiche les options d'emprunt et de restitution
            if ($acces)
            {
                $_SESSION['transition'] = true;
                $heure = date("H"); // Récupère l'heure actuelle (format 24h)
                $salutation = ($heure >= 6 && $heure < 18) ? "Bonjour" : "Bonsoir";
                ?>
                <h1><?php echo $salutation . ", " . $utilisateur['prenom']; ?>.</h1>
                <p style="margin-top: 60px !important;"> Votre choix: </p>
                <div class="options">
                    <div class="boite-options" onclick="window.location.href='emprunter.php'">
                        <img src="images/livre.png" class="img">
                        <span>Emprunter un ouvrage</span>
                    </div>
                    <div class="boite-options" onclick="window.location.href='restituer.php'">
                        <img src="images/undo.png" class="img">
                        <span>Restituer un ouvrage</span>
                    </div>
                    <div class="boite-options" onclick="window.location.href='consulter.php'">
                        <img src="images/consulter.png" class="img">
                        <span>Consulter mes emprunts</span>
                    </div>
                </div>
                <!-- Bouton permettant à l'utilisateur de se déconnecter -->
                <div class="bouton-deconnexion">
                    <button onclick="window.location.href='endsession.php'">Déconnexion</button>
                </div>
                <?php
            }
            else
            {
                // Si l'utilisateur n'est pas reconnu ou si le compte est désactivé, affiche un message d'erreur
                if ($erreur == 0)
                {
                    ?>
                    <p style="font-size: 50; color: red; font-style: bold;"> Utilisateur non reconnu. </p>
                    <?php
                }
                else
                {
                    ?>
                    <p style="font-size: 50; color: red; font-style: bold;"> Utilisateur désactivé. </p>
                    <?php
                }
                echo '<script> setTimeout(function() { window.location.href = "index.php"; }, 5000); </script>'; // Laisse le message d'erreur s'afficher pendant 5 secondes, puis redirige vers la page d'accueil
            }
            ?>
        </div>

        <!-- Affiche un message de déconnexion si l'utilisateur est inactif pendant un certain temps -->
        <div id="popup">
            <img src="images/attention.png" alt="Attention" class="popup-icon">
            <p style="font-size: 24px;"> Inactivité détectée ! Déconnexion dans <span id="decompte">10</span> secondes...</p>
        </div>

	    <div class="watermark-fixe">
            <span class="texte-watermark">Bibliothèque Saint Paul de Tartas</span>
            <img src="images/logo.png" alt="Logo" style="height: 22px;">
        </div>

        <script>
            // Effacer la valeur de la vidéo du localStorage
            localStorage.removeItem("tempsVideo");
        </script>
    </body>
</html>