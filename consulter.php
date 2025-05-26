<?php
    session_start();
    include("connexionPDO.php"); // On se connecte à la base de données

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

    $requete = $objet_PDO->prepare("SELECT emprunts_en_cours FROM utilisateurs WHERE badge_RFID = ?"); // On récupère des informations dans la table utilisateurs par rapport au badge_RFID
    $requete->execute([$_SESSION['ID']]); // On exécute la requête pour récupérer les données de la table en renseignant badge_RFID par la variable de session ID, ce qui va transferer l'ID de l'utilisateur connecté actuellement
    $utilisateur = $requete->fetch(); // On récupère le résultat de la requête

    $emprunts_utilisateur = $utilisateur['emprunts_en_cours']; // On définit la variable emprunts_utilisateur selon la valeur qu'on a trouvé dans la table

    $requete = $objet_PDO->prepare("SELECT ouvrage_ID FROM emprunts WHERE utilisateur_ID = ? AND date_retour IS NULL"); // On récupère les ID des ouvrages depuis la table emprunts qui sont associés à l'ID de l'utilisateur. On filtre également les ouvrages en cours d'emprunt en vérifiant que la colonne "date_retour" est vide, signifiant que l'ouvrage n'est pas encore rendu.
    $requete->execute([$_SESSION['ID']]); // On exécute la requête pour récupérer les données de la table en renseignant utilisateur_ID par la variable de session ID, ce qui va transferer l'ID de l'utilisateur connecté actuellement
    $ouvrages = $requete->fetchAll(PDO::FETCH_COLUMN); // On récupère le résultat de la requête

    // Stocker uniquement les ouvrages en cours d'emprunt dans le panier
    $_SESSION['panier'] = $ouvrages;

    if (isset($_POST['selection']))
    {
        $_SESSION['selection'] = $_POST['selection'];
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
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Consulter</title>
    <link href="css/style.css" rel="stylesheet"/>
    <link href="css/style-consulter.css" rel="stylesheet"/>
    <script defer src="js/inactivite.js"></script> <!-- script d'inactivité -->
</head>
    <body>
        <div class="conteneur" <?php if($_SESSION['transition']){echo 'style="animation: fadeIn 1s ease-in-out"';}?>> <!-- Si la variable de session "transition" est sur vrai, on ajoute une entrée sur la page fluide -->
            <div class="contenu-gauche" style="max-width: 30%;">
                <div class="bloc-1">
                    <h2 style="font-size: 40px; text-shadow: 0 0px 35px rgba(0, 0, 0, 0.2);"> Liste d'emprunts </h2>
                </div>
                <?php
                if (!empty($_SESSION['panier']))  // Si la liste n'est pas vide
                {
                    echo '<div class="bloc-2" style="height: 70%;">';
                        echo '<div class="conteneur-ascenceur">';
                        
                        foreach (array_reverse($_SESSION['panier']) as $index => $codebarre)
                        {
                            $requete = $objet_PDO->prepare("SELECT * FROM ouvrages WHERE ISBN = ?");
                            $requete->execute([$codebarre]);
                            $ouvrage = $requete->fetch();

                            $requete = $objet_PDO->prepare("SELECT date_limite_pret FROM emprunts WHERE ouvrage_ID = ? AND date_retour IS NULL"); // On récupère des informations dans la table emprunts par rapport a l'ID de l'ouvrage sélectionné
                            $requete->execute([$codebarre]); // On exécute la requête pour récupérer les données de la table en renseignant ouvrage_ID par la variable "codebarre", ce qui va transferer l'ID de l'ouvrage selectionné actuellement
                            $emprunt = $requete->fetch(); // On récupère le résultat de la requête
                            ?>
                            <form method="post" action="" style="margin: 0;">
                                <button type="submit" name="selection" value="<?php echo $codebarre; ?>" style="all: unset; width: 100%;">
                                    <div class="liste"
                                        <?php
                                        if ($_SESSION['selection'] == $codebarre)
                                        {
                                            if (strtotime($emprunt['date_limite_pret']) < time())
                                            {
                                                echo 'style="background: rgba(213, 0, 37, 0.54); border: 1px solid #d92828;"';
                                            }
                                            else
                                            {
                                                echo 'style="background: #dadada; border: 1px solid #a3a3a3;"';
                                            }
                                        }
                                        else
                                        {
                                            if (strtotime($emprunt['date_limite_pret']) < time())
                                            {
                                                echo 'style="background: rgba(162, 8, 35, 0.1); border: 1px solid rgba(217, 40, 40, 0.25);"';
                                            }
                                        }
                                        ?>
                                    >
                                        <?php
                                        if ($ouvrage['photo'] != null)
                                        {
                                            $img = '<img style="width: 60px; height: auto;" src="data:image/jpeg;base64,' . base64_encode($ouvrage['photo']) . '" />';
                                            echo $img;
                                        }
                                        else
                                        {
                                            echo '<img src="images/default.png" style="width: 60px; height: auto;">';
                                        }
                                        ?>
                                        <div style="padding-left: 10px;">
                                            <span style="font-size: 19px; text-align: left;"><?php echo $ouvrage['titre']; ?></span>
                                            <br>
                                            <span style="font-size: 16px; color: #555;"><?php echo $ouvrage['auteur']; ?></span>
                                        </div>
                                    </div>
                                </button>
                            </form>
                        <?php
                        }
                        ?>
                        </div>
                    </div>
                    <div class="bloc-3"><p style="font-size: 20px; font-weight: bold;"> Emprunts en cours: <?php echo $emprunts_utilisateur ?></p></div>
                    <?php
                }
                else
                {
                    ?>
                    <div class="bloc-2"><p class="selection-vide">Vous n'avez pas d'emprunts en cours.</p></div>
                    <div class="bloc-3" style="height:8%;"></div>
                    <?php
                    if ($_SESSION['alerte2'])
                    { ?>
                        <script>
                            // En bonus, on affiche un pop-up pour bien avertir que la personne n'a pas d'ouvrages à rendre actuellement
                            window.onload = function ()
                            {
                                a_popup();
                            };
                        </script>
                        <?php
                        $_SESSION['alerte2'] = false;
                    }
                }
                ?>
            </div>
            
            <div class="contenu-droit">
                <div class="bloc-1">
                    <h2 style="font-size: 40px; text-shadow: 0 0px 35px rgba(0, 0, 0, 0.2);"> Détails </h2>
                </div>

                <?php
                if ($_SESSION['selection'] != null) // Si la variable de session 'selection' n'est pas vide
                {
                    $requete = $objet_PDO->prepare("SELECT * FROM ouvrages WHERE ISBN = ?");
                    $requete->execute([$_SESSION['selection']]);
                    $ouvrage = $requete->fetch();

                    $requete = $objet_PDO->prepare("SELECT date_limite_pret FROM emprunts WHERE ouvrage_ID = ? AND date_retour IS NULL"); // On récupère des informations dans la table emprunts par rapport a l'ID de l'ouvrage sélectionné
                    $requete->execute([$_SESSION['selection']]); // On exécute la requête pour récupérer les données de la table en renseignant ouvrage_ID par la variable de session "selection", ce qui va transferer l'ID de l'ouvrage selectionné actuellement
                    $emprunt = $requete->fetch(); // On récupère le résultat de la requête
                    
                    echo '<div class="bloc-2" style="height: 75%; text-align: left;">';
                        echo '<div class="conteneur-infos">';

                            if ($ouvrage['photo'] != null)
                            {
                                $img = '<img style="width: 18%; height: auto;" src="data:image/jpeg;base64,' . base64_encode($ouvrage['photo']) . '" />';
                                echo $img;
                            }
                            else
                            {
                                echo '<img src="images/default.png" style="width: 18%; height: auto;">';
                            }

                            ?>
                            <div class="infos-ouvrage">
                                <p><strong>Titre :</strong> <?php echo $ouvrage['titre']; ?> </p>
                                <p><strong>Auteur :</strong> <?php echo $ouvrage['auteur']; ?> </p>
                                <p><strong>Type :</strong> <?php echo $ouvrage['type']; ?> </p>
                            </div>

                            <div class="description" style="flex-basis: 100%;">
                                <p><strong>Description :</strong></p>
                                <P><?php echo $ouvrage['description']; ?> </p>
                            </div>
                            <div style="flex-basis: 100%;">
                                <p <?php if (strtotime($emprunt['date_limite_pret']) < time()) {echo 'style="color: rgb(222, 65, 65);"';} ?>><strong>Date limite de rendu :</strong> <?php echo formatDate($emprunt['date_limite_pret']); ?> </p>
                            </div>
                        </div>
                    </div>
                <?php
                }
                else
                {
                    echo '<div class="bloc-2"><p class="selection-vide">Veuillez sélectionner un ouvrage dans la liste.</p></div>';
                }
                ?>

                <div class="bloc-3">
                    <button class="bouton-retour" onclick="window.location.href='choix.php'" style="font-weight: bold; margin-left: 25px;">
                        <img src="images/chevron-left.png" style="filter: invert(1);" class="icone-fleche"> Retour
                    </button>
                </div>
            </div>

            </div>
            <?php $_SESSION['transition'] = false; ?>
        </div>

        <!-- Affiche un message de déconnexion si l'utilisateur est inactif pendant un certain temps -->
        <div id="popup" style ="width: 595px;">
            <img src="images/attention.png" alt="Attention" class="popup-icon">
            <p style="font-size: 19px;">Inactivité détectée ! Déconnexion dans <span id="decompte">10</span> secondes...</p>
        </div>

        <!-- Popup info -->
        <div id="info">
            <div class="couverture" onclick="cacherPopup()"></div> <!-- Arrière plan foncé permettant de sortir du popup si nous cliquons dessus -->
            <div class="boite-infos" style="display: block !important; height: 400px !important;">
            <span class="bouton-fermer" onclick="cacherPopup()">×</span> <!-- Ajout d'une croix en haut à droite pour fermer le popup -->
                <h2 style="text-align: center; margin-bottom: 50px !important; font-size: 37px; margin-top: 20px;"> Vous n'avez pas d'emprunts en cours </h2> <!-- On informe l'utilisateur qu'il a atteint son quota d'emprunts -->
                <div style="display: flex; justify-content: center; align-items: center; height: 250px; text-align: center; flex-direction: column;">
                    <p style="font-size: 22px;"> Si vous souhaitez emprunter des ouvrages, veuillez vous diriger vers <br> la page Emprunter un ouvrage. </p>
                </div>
            </div>
        </div>

        <div class="watermark-fixe">
            <span class="texte-watermark">Bibliothèque Saint Paul de Tartas</span>
            <img src="images/logo.png" alt="Logo" style="height: 22px;">
        </div>

        <script>
            function a_popup()
            {
                const conteneurInfo = document.getElementById('info');
                conteneurInfo.style.transition = 'opacity 0.3s ease, pointer-events 0.3s ease';
                conteneurInfo.style.opacity = 1;
                conteneurInfo.style.pointerEvents = 'auto';
            }

            function cacherPopup()
            {
                const conteneurInfo = document.getElementById('info');
                conteneurInfo.style.opacity = 0;
                conteneurInfo.style.pointerEvents = 'none';
            }
        </script>
    </body>
</html>