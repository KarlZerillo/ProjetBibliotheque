<?php
    session_start();

    //On vide la variable de session SCAN RFID
    $_SESSION['SCAN'] = null;

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

    // Si les variables de sessions suivantes ne sont pas définies:
    $_SESSION['panier'] = $_SESSION['panier'] ?? [];
    $_SESSION['recap'] = $_SESSION['recap'] ?? false;
    $_SESSION['erreur'] = $_SESSION['erreur'] ?? "Erreur inconnue.";

    if (isset($_POST['valider'])) // Si on appuie sur le bouton valider le panier
    {
        $_SESSION['recap'] = true; // On active le recap via la variable de session
        header("Location: emprunter.php"); // On recharge la page
        exit();
    }

    $panier = count($_SESSION['panier']); // On compte le nombre d'éléments dans le tableau de session panier
    $bloqué = false; // Variable pour bloquer le scan des ouvrages
    $retirer = false; // Variable qui gère l'activation de la suppression d'un ouvrage si le scan a été refusé
    $montrerErreur = false; // Variable qui sert à montrer une erreur si un ouvrage scanné n'a pas pu être ajouté au panier

    if ($panier > 0) // Si la variable panier est supérieure a 0, on vérifie le contenu du tableau panier
    {
        foreach (array_reverse($_SESSION['panier']) as $index => $codebarre) // Pour chaque éléments dans le tableau, on inverse la lecture du tableau pour que les derniers éléments scannés apparaissent en haut de la liste
        {
            $requete = $objet_PDO->prepare("SELECT * FROM ouvrages WHERE ISBN = ?"); // On récupère des informations dans la table administration selon la valeur de l'ISBN
            $requete->execute([$codebarre]); // On exécute la requête préparée pour récupérer les données de la table en renseignant l'ID du code barre qui viendra compléter la valeur de l'ISBN
            $ouvrage = $requete->fetch(); // On récupère le résultat de la requête

            if (!$ouvrage) // Si l'ouvrage n'est pas trouvé dans la table
            {
                $retirer = true; // On active la suppression de l'ouvrage
                $_SESSION['erreur'] = "Ouvrage non reconnu."; // On précise l'erreur
            }
            else // Sinon si l'ouvrage est reconnu
            {
                if ($ouvrage['statut'] == "Emprunté") // Si il est en cours d'emprunt
                {
                    $retirer = true; // On active la suppression de l'ouvrage
                    $_SESSION['erreur'] = "Ouvrage en cours d'emprunt."; // On précise l'erreur
                }
                else if ($ouvrage['statut'] == "Endommagé") // Sinon si il est endommagé
                {
                    $retirer = true; // On active la suppression de l'ouvrage
                    $_SESSION['erreur'] = "Ouvrage endommagé."; // On précise l'erreur
                }
            }
        }
        
        if ($retirer) // Si un ouvrage est inconnu, emprunté ou endommagé
        {
            array_pop($_SESSION['panier']); // On supprime la dernière entrée du tableau panier
            $_SESSION['panier'] = array_values($_SESSION['panier']); // On réindexe le tabeau
            $panier--; // On retire 1 à la variable panier
            $montrerErreur = true; // On montre l'erreur
        }
    }

    $requete = $objet_PDO->prepare("SELECT limite_emprunts, delai_rappel, delai_pret FROM administration WHERE id = 1"); // On récupère des informations dans la table administration où l'ID est égal à 1
    $requete->execute(); // On exécute la requête préparée pour récupérer les données de la table
    $systeme = $requete->fetch(); // On récupère le résultat de la requête

    $limite_emprunts = $systeme['limite_emprunts']; // On définit la variable limite_emprunts selon la valeur qu'on a trouvé dans la table
    $_SESSION['delai_rappel'] = $systeme['delai_rappel']; // On définit la variable de session delai_rappel selon la valeur qu'on a trouvé dans la table
    $_SESSION['delai'] = $systeme['delai_pret']; // On définit la variable de session delai pour le nombre de jours d'emprunts autorisés selon la valeur qu'on a trouvé dans la table

    $requete = $objet_PDO->prepare("SELECT emprunts_en_cours FROM utilisateurs WHERE badge_RFID = ?"); // On récupère des informations dans la table utilisateurs par rapport au badge_RFID
    $requete->execute([$_SESSION['ID']]); // On exécute la requête pour récupérer les données de la table en renseignant badge_RFID par la variable de session ID, ce qui va transferer l'ID de l'utilisateur connecté actuellement
    $utilisateur = $requete->fetch(); // On récupère le résultat de la requête

    $emprunts_utilisateur = $utilisateur['emprunts_en_cours']; // On définit la variable emprunts_utilisateur selon la valeur qu'on a trouvé dans la table
    $limite_emprunts = $limite_emprunts - $emprunts_utilisateur - $panier; // On définit la variable limite_emprunts selon la limite d'emprunts autorisé, le nombre d'emprunts en cours de l'utilisateur, et le nombre d'ouvrages dans le panier actuellement
?>
<html lang="fr">
    <head>
        <meta charset="utf-8"/>
        <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
        <title>Emprunter</title>
        <link href="css/style.css" rel="stylesheet"/>
        <link href="css/style-emprunt.css" rel="stylesheet"/>
        <script defer src="js/inactivite.js"></script> <!-- script d'inactivité -->
    </head>
    <body>
        <div class="conteneur" <?php if($_SESSION['transition']){echo 'style="animation: fadeIn 1s ease-in-out"';}?>> <!-- Si la variable de session "transition" est sur vrai, on ajoute une entrée sur la page fluide -->
            
            <!-- Section Scan -->
            <div class="section-scan">
                <?php
                if ($limite_emprunts <= 0) // Si le nombre d'emprunts autorisé pour l'utilisateur est inférieur ou égal à 0
                {
                    $bloqué = true; // On bloque le scan d'ouvrages
                    $limite_emprunts = 0; // Pour éviter des potentiels dépassements de valeurs, on force l'affichage des emprunts restants à 0;
                    
                    if ($panier > 0) // Si il y a au moins un ouvrage dans le panier
                    {
                        ?> <!-- On affiche le message d'erreur 1 -->
                        <h2 style="font-size: 40px; margin-bottom: 0px;">Vous avez <br> atteint la limite <br> d'emprunts</h2>
                        <div class="message-erreur">
                            <p style="font-size: 20px; margin-top: 35px;"> Vous ne pouvez pas ajouter plus <br> d'ouvrages. </p>
                            <br>
                            <p style="font-size: 20px; margin-top: 35px;"> Veuillez valider ou supprimer certains <br> de vos choix.</p>
                        </div>
                        <?php
                    }
                    else // Sinon si le panier est vide (ce qui signifie que l'utilisateur arrive pour la premiere fois sur la page et est bloqué dès son arrivée)
                    {
                        ?> <!-- On affiche le message d'erreur 2 -->
                        <h2 style="font-size: 40px; margin-bottom: 0px;">Vous avez <br> atteint votre <br> quota d'emprunts</h2>
                        <div class="message-erreur">
                            <p style="font-size: 20px; margin-top: 35px;"> Nous vous prions de bien <br> vouloir retourner vos ouvrages <br> en cours d'emprunt. </p>
                            <p style="font-size: 20px; margin-top: 35px;"> Emprunts en cours: <?php echo $emprunts_utilisateur ?></p>
                        </div>
                        <script>
                            // En bonus, on affiche un pop-up pour bien avertir que la personne ne peut pas emprunter plus d'ouvrages dû à son quota d'emprunts atteint
                            window.onload = function ()
                            {
                                a_popup(0); // Étant donné qu'il y a deux types de pop-up, le fait de renvoyer 0 dans la fonction permet de dire que nous souhaitons afficher le premier type
                            };
                        </script>
                        <?php
                    }
                }
                else // Sinon si le nombre d'emprunts autorisé pour l'utilisateur est supérieur à 0
                {
                    ?>
                    <h2 style="font-size: 40px; margin-bottom: 0px;">Scannez <br> les ouvrages</h2> <!-- Titre de la section scan -->
                    <p style="font-size: 20px; margin-top: 15px;">pour les ajouter au panier</p>
                    <div class="conteneur-video"> <!-- Ajout d'une animation vidéo -->
                        <video autoplay loop muted playsinline width="100%" poster="images/placeholder.png" id="maVideo">
                            <source src="images/scan.mp4" type="video/mp4">
                        </video>
                    </div>
                    <?php
                }
                ?>
            </div>

            <!-- Section Panier -->
            <div class="section-panier" style="padding-right: 40px; padding-bottom: 30px;">
                <div class="conteneur-panier">
                    <h2 style="font-size: 26px;"> VOTRE PANIER </h2> <!-- Titre de la section panier -->
                    <?php 
                        if ($panier > 0) // Si le nombre d'ouvrages dans le panier est superieur à 0
                        {
                            ?>
                            <div class="entete-panier"> 
                                <span> Auteur </span>
                                <span> Type </span>
                                <span> </span>
                                <span> Supprimer </span>
                            </div>
                            <?php
                        }
                    ?>
                </div>
                <hr class="separateur" style="margin-top: 15px; margin-bottom: 3px !important;">
                <div class="liste-panier"> <!-- Liste des ouvrages ajoutés -->
                    <?php
                        if ($panier != 0)  // Si la variable panier est supérieure a 0, on vérifie le contenu du tableau panier
                        {
                            foreach (array_reverse($_SESSION['panier']) as $index => $codebarre) // Pour chaque éléments dans le tableau, on inverse la lecture du tableau pour que les derniers éléments scannés apparaissent en haut de la liste
                            {
                                $requete = $objet_PDO->prepare("SELECT * FROM ouvrages WHERE ISBN = ?"); // On récupère des informations dans la table ouvrages selon la valeur de l'ISBN
                                $requete->execute([$codebarre]); // On exécute la requête préparée pour récupérer les données de la table en renseignant l'ID du code barre qui viendra compléter la valeur de l'ISBN
                                $ouvrage = $requete->fetch(); // On récupère le résultat de la requête

                                if ($ouvrage && $ouvrage['statut'] == "Disponible") // Si l'ouvrage est trouvé et que son statut est réglé sur "Disponible"
                                {
                                    ?>
                                    <div class="articles-panier">
                                        <?php
                                            if ($ouvrage['photo'] != null) // Si l'image n'est pas manquante
                                            {
                                                // Définir la variable $img et décoder l'image à partir d'un blob
                                                $img = '<img style="width: 50px; cursor: pointer;" src="data:image/jpeg;base64,'. base64_encode($ouvrage['photo']) . '" onclick="a_popup(' . $ouvrage['ISBN'] . ')" />';
                                                echo $img; // On affiche l'image
                                            }
                                            else // Sinon on affiche une image de base
                                            {
                                                echo '<img src="images/default.png" style="width: 50px; cursor: pointer;" onclick="a_popup(' . $ouvrage['ISBN'] . ')">';
                                            }
                                        ?>
                                        <span style="font-size: 20px; text-align: left !important; cursor: pointer;" onclick="a_popup(<?php echo $ouvrage['ISBN']; ?>)"><?php echo $ouvrage['titre']; ?></span> <!-- Affichage du titre de l'ouvrage -->
                                        <span><?php echo $ouvrage['auteur']; ?></span> <!-- Affichage de l'auteur de l'ouvrage -->
                                        <span><?php echo $ouvrage['type']; ?></span> <!-- Affichage du type de l'ouvrage -->
                                        <span></span>
                                        <a href="supprimer_panier.php?index=<?php echo array_search($codebarre, $_SESSION['panier']); ?>"> <!-- Affichage du bouton retirer du panier -->
                                            <img src="images/trashcan.png" style="width: 35px; height: 35px;">
                                        </a>
                                    </div>
                                    <?php
                                }
                            }
                        }
                        else
                        {
                            echo '<p class="panier-vide">Votre panier est vide.</p>'; // Affichage d'un message pour indiquer que le panier est vide
                        }
                    ?>
                </div>

                <div class="contenu-inferieur">
                    <hr class="separateur">
                
                    <div class="infos-panier"> <!-- Affichage des emprunts restants et du nombre d'ouvrages présents dans le panier -->
                        <span>Emprunts restants : <?php echo $limite_emprunts ?></span>
                        <span>Emprunts aujourd'hui : <?php echo $panier ?></span>
                    </div>
                
                    <hr class="separateur">
                
                    <div class="boutons-panier"> <!-- Affichage des boutons d'annulation et de validation -->
                        <?php
                            if ($panier == 0) // Si le panier est vide
                            {
                                ?>
                                <button class="bouton-retour" onclick="window.location.href='choix.php?id=<?php echo $_SESSION['ID']; ?>'" style="font-weight: bold;"> <!-- Bouton retour qui nous ramène vers la page de choix -->
                                    <img src="images/chevron-left.png" style="filter: invert(1);" class="icone-fleche"> Retour
                                </button>
                                <?php
                            }
                            else // Sinon si il y a des ouvrages dans le panier
                            {
                                ?>
                                <button class="bouton-retour" onclick="popupChoix()" style="font-weight: bold;">
                                    <img src="images/chevron-left.png" style="filter: invert(1);" class="icone-fleche"> Abandonner <!-- Bouton retour qui fait apparaitre un pop up de confirmation d'abandon du panier avant de revenir vers la page de choix -->
                                </button>
                                <?php
                            }
                        ?>
                        </button>
                        <form method="POST" action="" style="margin-bottom: 0px !important;"> <!-- Bouton de validation -->
                            <button type="submit" name="valider" class="bouton-valider" <?php if ($panier == 0 || ($bloqué && $panier == 0)) {echo 'style="opacity: 0.6;" disabled';} ?>> <!-- On grise le bouton si le panier est vide ou si nous sommes bloqués et que le panier est vide -->
                                <img src="images/panier.png" class="icone-panier">
                                <span style="font-size: 19px; text-align: left; line-height: 1.2;">
                                    Valider les <br> <strong style="font-size: 22px;"> choix </strong>
                                </span>
                                <img src="images/chevron-right.png" style="margin-left: 47px;" class="icone-fleche">
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <?php
            if ($montrerErreur) // Si la variable "montrer-erreur" est réglée sur vrai, alors on affiche l'erreur.
            {
                ?>
                <script>
                    document.addEventListener("DOMContentLoaded", function ()
                    {
                        const popupErreur = document.getElementById("erreur"); // Récupère l'élément HTML ayant l'ID "erreur" et le stocke dans la variable "popupErreur"
                        if (popupErreur)
                        {
                            setTimeout(() =>
                            {
                                popupErreur.classList.add("erreur-active"); // On ajoute la classe "erreur-active" au popup "erreur" pour qu'il puisse apparaître
                                setTimeout(() =>
                                {
                                    popupErreur.classList.remove("erreur-active"); // On retire la classe "erreur-active" du popup "erreur" pour qu'il puisse réapparaitre
                                }, 3000); // Exécution après 3 secondes
                            }, 10);
                        }
                    });
                </script>
                <?php
                $montrerErreur = false; // On règle la variable "montrer-erreur" sur faux pour signaler la fin de l'execution du script 'erreur' et éviter que le message réaparaisse lors du raffraîchissement de la page
            }

            if (!$bloqué && !$_SESSION['recap']) // Si nous ne sommes pas bloqués et que nous ne sommes pas en recap
            {
                ?>
                <form id="formulaireCodebarre" action="ajouter_panier.php" method="POST"> <!-- On active le formulaire caché pour pouvoir scanner des ouvrages  -->
                    <input type="hidden" name="codebarre" id="entreeCodebarre">
                </form>
                <?php
            }
        ?>

        <!-- Affiche un message de déconnexion si l'utilisateur est inactif pendant un certain temps -->
        <div id="popup" style ="width: 595px;">
            <img src="images/attention.png" alt="Attention" class="popup-icon">
            <p style="font-size: 19px;">Inactivité détectée ! Déconnexion dans <span id="decompte">10</span> secondes... <br> Votre panier sera abandonné.</p>
        </div>

        <!-- Popup info -->
        <div id="info">
            <div class="couverture" onclick="cacherPopup()"></div> <!-- Arrière plan foncé permettant de sortir du popup si nous cliquons dessus -->
            <?php
                if ($limite_emprunts <= 0 && $panier <= 0) // Si la limite d'emprunt est inférieure ou égale à 0 et que le nombre d'ouvrages dans le panier est inférieur ou égal à 0 -> situation lors de l'arrivée sur la page
                {
                    ?>
                    <div class="boite-infos" style="display: block !important; height: 420px !important;">
                    <span class="bouton-fermer" onclick="cacherPopup()">×</span> <!-- Ajout d'une croix en haut à droite pour fermer le popup -->
                        <h2 style="text-align: center; margin-bottom: 50px !important; font-size: 37px; "> Vous avez atteint votre quota d'emprunts </h2> <!-- On informe l'utilisateur qu'il a atteint son quota d'emprunts -->
                        <div style="display: flex; justify-content: center; align-items: center; height: 270px; text-align: center; flex-direction: column;">
                            <p style="font-size: 20px;"> Veuillez bien vouloir retourner les ouvrages en cours d’emprunt <br> afin de pouvoir recommencer à emprunter. </p>
                            <p style="font-size: 20px; margin-top: 35px;"> Emprunts en cours: <?php echo $emprunts_utilisateur ?> </p>
                        </div>
                    </div>
                    <?php
                }
                else // Sinon si les conditions ne sont pas remplies -> autre utilisation du popup
                {
                    ?>
                    <div class="boite-infos"> <!-- Popup qui affiche les informations d'un ouvrage (selon sur lequel nous avons cliqué) -->
                    <span class="bouton-fermer" onclick="cacherPopup()">×</span> <!-- Ajout d'une croix en haut à droite pour fermer le popup -->
                        <div style="display: flex; align-items: center;">
                            <img id="imageOuvrage" style="width: 280px; margin-right: 20px; box-shadow: 4px 4px 15px rgba(0, 0, 0, 0.3);" />
                        </div>
                        <div style="display: grid; grid-template-rows: minmax(25px, auto) 50px 50px auto; margin-top: 52px; margin-bottom: 51px;">
                            <span id="titreOuvrage" style="font-size: 20px; font-weight: 600;"></span>
                            <p>de <span id="auteurOuvrage" style="font-weight: 600;"></span></p>
                            <p><strong>Type :</strong> <span id="typeOuvrage"></span></p>
                            <p><strong>Description :</strong> <span id="descriptionOuvrage" style="display: block; max-height: 196px; overflow-y: auto; padding-top: 6px; padding-right: 10px;"></span></p>
                        </div>
                    </div>
                    <?php
                }
            ?>
        </div>
        
        <!-- Popup choix (lors de l'appui sur abandonner le panier) -->
        <div id="choix">
            <div class="couverture" onclick="cacherPopupChoix()"></div> <!-- Arrière plan foncé permettant de sortir du popup si nous cliquons dessus -->
                <div class="boite-infos" style="display: block !important; height: 350px !important; width: 700px !important;">
                    <h2 style="text-align: center; margin-bottom: 50px !important; margin-top: 38px !important; font-size: 37px; "> Votre panier sera vidé si vous l'abandonnez. <br> Êtes-vous sûr de vouloir continuer ? </h2>
                    <div class="bouton-conteneur" style="margin-top: 75px;">
                        <button class="bouton-retour-recap" onclick="cacherPopupChoix()"> Non </button> <!-- Le popup disparait si on choisit non -->
                        <button class="bouton-valider-recap" onclick="window.location.href='choix.php?id=<?php echo $_SESSION['ID']; ?>'"> Oui </button> <!-- Si on choisit oui, nous sommes renvoyés sur la page de choix et notre panier est vidé -->
                    </div>
                </div>
            </div>
        </div>
        
        <?php
        if ($_SESSION['recap']) // Si la variable de session "recap" est réglée sur true
        {
            $_SESSION['recap'] = false; // On désactive la variable pour éviter que le popup réaparaisse sur les prochains rachraîchissements de la page
            ?>
            <div id="popup-recap"> <!-- Popup récapitulatif lors de l'appui sur le bouton de validation du panier -->
                <div class="couverture"></div> <!-- Arrière plan foncé -->
                <div class="boite-infos">
                    <h2 style="text-align: center; margin-bottom: 50px !important; font-size: 32px;"> Récapitulatif de votre emprunt </h2>
                    <div class="box-recap">
                        <?php
                            foreach ($_SESSION['panier'] as $index => $codebarre) // Pour chaque éléments dans le tableau
                            {
                                $requete = $objet_PDO->prepare("SELECT * FROM ouvrages WHERE ISBN = ?"); // On récupère des informations dans la table administration selon la valeur de l'ISBN
                                $requete->execute([$codebarre]); // On exécute la requête préparée pour récupérer les données de la table en renseignant l'ID du code barre qui viendra compléter la valeur de l'ISBN
                                $ouvrage = $requete->fetch(); // On récupère le résultat de la requête

                                if ($ouvrage) // Si l'ouvrage est trouvé
                                {  
                                    ?>
                                    <div class="recap-liste-panier">
                                        <?php
                                            if ($ouvrage['photo'] != null) // Si l'image n'est pas manquante
                                            {
                                                // Définir la variable $img et décoder l'image à partir d'un blob
                                                $img = '<img style="width: 50px; height: auto;" src="data:image/jpeg;base64,' . base64_encode($ouvrage['photo']) . '" />';
                                                echo $img; // On affiche l'image
                                            }
                                            else // Sinon on affiche une image de base
                                            {
                                                echo '<img src="images/default.png" style="width: 50px; height: auto;">';
                                            }
                                        ?>
                                        <div class="infos-ouvrages">
                                            <span style="font-size: 16px; text-align: left;"><?php echo $ouvrage['titre']; ?></span> <!-- Affichage du titre de l'ouvrage -->
                                            <span style="font-size: 14px; color: #555;"><?php echo $ouvrage['auteur']; ?></span> <!-- Affichage de l'auteur de l'ouvrage -->
                                        </div>
                                    </div>
                                    <?php
                                }
                            }
                        ?>
                    </div>
                    <div class="bouton-conteneur"> <!-- Affichage des boutons d'annulation et de validation -->
                        <button class="bouton-retour-recap" onclick="window.location.href='emprunter.php'"> Retour </button> <!-- On sort du pop up -->
                        <button onclick="window.location.href='confirmer_emprunt.php'" class="bouton-valider-recap"> Confirmer l'emprunt </button> <!-- On valide l'emprunt -->
                    </div>
                </div>
            </div>
            <?php
        }
        ?>

        <!-- Popup erreur -->
        <?php
            if (!$_SESSION['transition'])
            {
                ?>
                <div id="erreur">
                    <p style="font-size: 24px;">Objet non ajouté au panier. <br> <?php echo $_SESSION['erreur'] ?></p>  <!-- Affichage de l'alerte de non ajout de l'ouvrage accompagné de sa raison -->
                </div>
                <?php
            }
            $_SESSION['transition'] = false;
        ?>

        <div class="watermark-fixe">
            <span class="texte-watermark">Bibliothèque Saint Paul de Tartas</span>
            <img src="images/logo.png" alt="Logo" style="height: 22px;">
        </div>
        
        <script>
            // Script pour faire en sorte que la vidéo ne reprenne à 0 lors du rechargement de la page
            document.addEventListener("DOMContentLoaded", function ()
            {
                let video = document.querySelector("video");

                // Reprendre la vidéo à la position sauvegardée si elle existe
                let tempsSauvegardé = localStorage.getItem("tempsVideo");
                if (tempsSauvegardé !== null)
                {
                    video.currentTime = parseFloat(tempsSauvegardé);
                }

                // Sauvegarder uniquement quand l'utilisateur quitte ou recharge la page
                window.addEventListener("beforeunload", function ()
                {
                    localStorage.setItem("tempsVideo", video.currentTime);
                });
            });

            // Script pour lire l'ouvrage scanné
            document.addEventListener("DOMContentLoaded", function ()
            {
                let entreeCodebarre = document.getElementById("entreeCodebarre");
                let codeScanne = "";

                document.addEventListener("keypress", function (event)
                {
                    if (event.key === "Enter")
                    {
                        event.preventDefault();

                        if (codeScanne.trim() !== "")
                        {
                            entreeCodebarre.value = codeScanne; // Insère la valeur dans le formulaire
                            document.getElementById("formulaireCodebarre").submit(); // Envoie le formulaire
                            codeScanne = ""; // Réinitialise la variable après l'envoi
                        }
                    }
                    else
                    {
                        codeScanne += event.key; // Construit le code-barres caractère par caractère
                    }
                });
            });

            // Script pour afficher le popup informatif
            function a_popup(isbn)
            {
                const conteneurInfo = document.getElementById('info');
                conteneurInfo.style.transition = 'opacity 0.3s ease, pointer-events 0.3s ease';
                conteneurInfo.style.opacity = 1;
                conteneurInfo.style.pointerEvents = 'auto';

                if (isbn != 0)
                {
                    recupInfoOuvrage(isbn); // Appel AJAX pour récupérer les infos de l'ouvrage à partir de l'ISBN
                }
            }

            // Script pour afficher le popup de confirmation d'abandon
            function popupChoix()
            {
                const conteneurInfo = document.getElementById('choix');
                conteneurInfo.style.transition = 'opacity 0.3s ease, pointer-events 0.3s ease';
                conteneurInfo.style.opacity = 1;
                conteneurInfo.style.pointerEvents = 'auto';
            }
            
            // Script pour cacher le popup informatif
            function cacherPopup()
            {
                const conteneurInfo = document.getElementById('info');
                conteneurInfo.style.opacity = 0;
                conteneurInfo.style.pointerEvents = 'none';
            }

            // Script pour cacher le popup de confirmation d'abandon
            function cacherPopupChoix()
            {
                const conteneurInfo = document.getElementById('choix');
                conteneurInfo.style.opacity = 0;
                conteneurInfo.style.pointerEvents = 'none';
            }

            // Fonction AJAX pour récupérer les informations de l'ouvrage et les insérer dans le popup informatif
            function recupInfoOuvrage(isbn)
            {
                const xhr = new XMLHttpRequest();
                xhr.open('GET', 'infos_ouvrage.php?isbn=' + isbn, true);
                xhr.onload = function()
                {
                    if (xhr.status === 200)
                    {
                        // Met à jour le contenu du pop-up avec les données reçues
                        const infoOuvrage = JSON.parse(xhr.responseText);
                        document.getElementById('titreOuvrage').textContent = infoOuvrage.titre;
                        document.getElementById('auteurOuvrage').textContent = infoOuvrage.auteur;
                        document.getElementById('typeOuvrage').textContent = infoOuvrage.type;
                        document.getElementById('descriptionOuvrage').textContent = infoOuvrage.description;
                        if (infoOuvrage.photo)
                        {
                            document.getElementById('imageOuvrage').src = 'data:image/jpeg;base64,' + infoOuvrage.photo;
                        }
                        else
                        {
                            document.getElementById('imageOuvrage').src = 'images/default.png';
                        }
                    }
                };
                xhr.send();
            }

            // Script vérifiant si un tag RFID a été scanné
            setInterval(() =>
            {
                fetch("rfid-check-ouvrages.php")
                    .then(response => response.json())
                    .then(data =>
                    {
                        if (data.changement)
                        {
                            window.location.href = "ajouter_panier.php";
                        }
                    })
            }, 1000);
        </script>
    </body>
</html>