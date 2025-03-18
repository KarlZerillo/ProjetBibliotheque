let timer;
let tempsInactivite = 0;
const limiteInactivite = 50;
const popup = document.getElementById("popup");
const decompte = document.getElementById("decompte");
let timerDecompte;

function resetTimer()
{
    tempsInactivite = 0; // Reset du timer d'inactivité
    clearInterval(timer);
    clearInterval(timerDecompte); // Stoppe le compte à rebours du pop-up

    popup.classList.remove("actif"); // Cache le pop-up
    startTimer(); // Relance la détection d'inactivité
}

function startTimer()
{
    timer = setInterval(() =>
    {
        tempsInactivite++;

        if (tempsInactivite >= limiteInactivite)
        {
            clearInterval(timer);
            afficherPopup();
        }
    }, 1000);
}

function afficherPopup()
{
    let compteur = 10; // Début du compte à rebours
    decompte.textContent = compteur; // Affiche le nombre initial
    popup.classList.add("actif"); // Affiche le popup

    timerDecompte = setInterval(() =>
    {
        compteur--; // Décrémente le compteur
        decompte.textContent = compteur; // Met à jour l'affichage
        if (compteur <= 0)
        {
            clearInterval(timerDecompte);
            console.log("Session terminée.");
            window.location.href = 'endsession.php';
        }
    }, 1000);
}

// Détection d'activité utilisateur
document.addEventListener("mousemove", resetTimer);
document.addEventListener("keydown", resetTimer);
document.addEventListener("click", resetTimer);
document.addEventListener("scroll", resetTimer);

// Lancement du chronomètre une fois que la page est chargée
document.addEventListener("DOMContentLoaded", resetTimer);