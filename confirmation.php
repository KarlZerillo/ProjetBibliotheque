<?php
    session_start();
    $date_limite = $_SESSION['date_limite_pret'];

    if (!$_SESSION['acces']) // Si on n'a pas l'accès à la session
    {
        header("Location: index.php"); // On redirige vers la page d'accueil
        exit();
    }

    if (!isset($_SESSION['ID'])) // Si l'ID de l'utilisateur a été perdu, cela signifie qu'il est déconncté
    {
        header("Location: index.php"); // On redirige vers la page d'accueil
        exit();
    }

    function formatDate($date)
    {
        $jours = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
        $mois = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
        
        $timestamp = strtotime($date);
        
        $jour_semaine = $jours[date('w', $timestamp)];
        $jour_mois = date('d', $timestamp);
        $mois_name = $mois[date('n', $timestamp) - 1];
        $annee = date('Y', $timestamp);
        
        return ucfirst($jour_semaine) . ' ' . $jour_mois . ' ' . $mois_name . ' ' . $annee;
    }
?>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Confirmation d'emprunt</title>
        <link href="css/style.css" rel="stylesheet"/>
        <link href="css/style-confirmation.css" rel="stylesheet"/>
    </head>
    <body>
        <?php
            include("connexionPDO.php");
            $query = $objet_PDO->prepare("SELECT * FROM utilisateurs WHERE badge_RFID = ?");
            $query->execute([$_SESSION['ID']]);
            $utilisateur = $query->fetch();
            $_SESSION['acces'] = false; // On retire l'accès aux pages car nous sommes arrivés au bout de l'intéraction
            $_SESSION['ID'] = null; // On déconnecte l'utilisateur en vidant la variable de session ID
        ?>
        <div class="conteneur">
            <h2>Merci pour votre emprunt, <?php echo $utilisateur['prenom'] ?> !</h2>
            <p> La date limite pour rendre les ouvrages est fixée au <strong><?php echo formatDate($date_limite); ?></strong>.</p>
            <a href="index.php" class="bouton" id="deconnexion">Déconnexion dans <span id="decompte">20</span> secondes...</a>
        </div>
    </body>
    <script>
        let compteur = 20; // Début du décompte
        const decompte = document.getElementById("decompte");

        const timerDecompte = setInterval(() =>
        {
            compteur--; 
            decompte.textContent = compteur; 

            if (compteur <= 0)
            {
                clearInterval(timerDecompte); 
                console.log("Session terminée.");
                window.location.href = 'endsession.php';
            }
        }, 1000);
    </script>
</html>