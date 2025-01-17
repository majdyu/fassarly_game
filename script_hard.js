let originalNumber = "";
let attemptsTable = document.getElementById("attempts");
let attemptsCount = 0;
let timer;
let secondsElapsed = 0;
let timerStarted = false; // Nouveau flag pour vérifier si le chronomètre a déjà démarré

// Récupérer le nombre depuis sessionStorage
window.onload = function() {
    originalNumber = sessionStorage.getItem("originalNumber");
    
    if (originalNumber) {
        generateAttemptRow();

        // Calculer et afficher les conditions
        displayConditions(originalNumber);
    } else {
        alert("لم يتم العثور على رقم، العودة إلى الصفحة السابقة.");
        window.location.href = "webfass.html"; // Si aucun nombre trouvé, retour à la première page
    }
}

// Fonction pour afficher les conditions
function displayConditions(number) {
    // Récupérer les éléments HTML pour afficher les conditions
    let unitConditionElement = document.getElementById("unit-condition");
    let tenMillionConditionElement = document.getElementById("ten-millions-condition");
    let sumConditionElement = document.getElementById("sum-condition");

    // Afficher si le chiffre des unités et des dizaines de millions est pair ou impair
    unitConditionElement.textContent = isEvenOrOdd(number[7]);
    tenMillionConditionElement.textContent = isEvenOrOdd(number[0]);

    // Afficher la somme des chiffres
    sumConditionElement.textContent = sumOfDigits(number);
}

// Fonction pour vérifier si un chiffre est pair ou impair
function isEvenOrOdd(digit) {
    return digit % 2 === 0 ? "زوجي" : "فردي";
}

// Fonction pour calculer la somme des chiffres d'un nombre
function sumOfDigits(number) {
    return number.split('').reduce((acc, digit) => acc + parseInt(digit), 0);
}


// Fonction pour générer une nouvelle ligne de tentative avec les valeurs correctes
function generateAttemptRow(lastAttempt = null) {
    let row = document.createElement("tr");

    for (let i = 0; i < 8; i++) { // 8 colonnes pour chaque chiffre
        let cell = document.createElement("td");
        let input = document.createElement("input");
        
        // Configurer le champ pour accepter uniquement des chiffres
        input.type = "text"; // Utiliser "text" pour éviter les flèches
        input.inputMode = "numeric"; // Activer le clavier numérique sur mobile
        input.maxLength = 1; // Limiter à un seul caractère
        input.classList.add("digit-input");

        // Ajouter un événement pour filtrer les caractères non numériques
        input.addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/[^0-9]/g, ''); // Supprimer tout caractère non numérique
        });

        // Ajouter l'événement "focus" pour démarrer le chronomètre
        input.addEventListener('focus', startTimer);

        // Si c'est une nouvelle ligne et qu'on a une tentative précédente, remplir avec les valeurs correctes
        if (lastAttempt && lastAttempt[i].classList.contains("correct")) {
            input.value = lastAttempt[i].value; // Remplir avec le chiffre correct
            input.classList.add("correct"); // Marquer en vert dans la nouvelle ligne
            input.disabled = true; // Désactiver les champs corrects pour éviter qu'ils soient modifiés
        }

        cell.appendChild(input);
        row.appendChild(cell);
    }

    attemptsTable.appendChild(row);
    attemptsCount++;
}


// Fonction pour démarrer le chronomètre
function startTimer() {
    // Vérifier si le chronomètre n'a pas déjà démarré
    if (!timerStarted) {
        timerStarted = true; // Empêche de redémarrer le chronomètre plusieurs fois
        document.getElementById("check-answer-btn").disabled = false; // Activer le bouton "Vérifier"

        // Démarrer le chronomètre
        timer = setInterval(() => {
            secondsElapsed++;

            // Calculer les minutes et les secondes
            let minutes = Math.floor(secondsElapsed / 60);
            let seconds = secondsElapsed % 60;

            // Afficher le temps écoulé en format "minutes:secondes"
            document.getElementById("timer-display").innerText = `الوقت المنقضي: ${minutes} دقيقة و ${seconds} ثانية`;
        }, 1000); // Mise à jour toutes les secondes
    }
}

// Fonction pour vérifier la réponse
function checkAnswer() {
    let attemptRows = attemptsTable.querySelectorAll("tr");
    let lastAttempt = attemptRows[attemptRows.length - 1].querySelectorAll(".digit-input");
    let isCorrect = true;

    lastAttempt.forEach((input, index) => {
        if (input.value === originalNumber[index]) {
            input.classList.add("correct");
            input.classList.remove("incorrect"); // Retirer la classe "incorrect" si elle était déjà appliquée
        } else {
            isCorrect = false;
            input.classList.add("incorrect"); // Ajouter la classe "incorrect" pour les valeurs fausses
            input.classList.remove("correct"); // Retirer la classe "correct" si elle était déjà appliquée
        }
    });

    if (isCorrect) {
        clearInterval(timer);

        let minutes = Math.floor(secondsElapsed / 60);
        let seconds = secondsElapsed % 60;
        document.getElementById("result").innerText = `تهانينا! لقد وجدت العدد في ${minutes} دقيقة و ${seconds} ثانية.`;

        // Affiche le nombre réel et applique la classe "revealed"
        let revealedNumberElement = document.getElementById("revealed-number");
        revealedNumberElement.innerText = originalNumber;
        document.getElementById("hidden-number-box").classList.add("revealed");
    } else {
        document.getElementById("result").innerText = "حاول مرة أخرى.";
        generateAttemptRow(lastAttempt);
    }
}