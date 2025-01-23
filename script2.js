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
        window.location.href = "index.html"; // Si aucun nombre trouvé, retour à la première page
    }
}

// Fonction pour afficher les conditions
function displayConditions(number) {
    // Récupérer les éléments HTML pour afficher les conditions
    let unitConditionElement = document.getElementById("unit-condition");
    let hundredThousandConditionElement = document.getElementById("hundred-thousand-condition");
    let sumConditionElement = document.getElementById("sum-condition");

    // Afficher si le chiffre des unités et des centaines de milliers est pair ou impair
    unitConditionElement.textContent = isEvenOrOdd(number[5]);
    hundredThousandConditionElement.textContent = isEvenOrOdd(number[0]);

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

    for (let i = 0; i < 6; i++) { // 6 columns for each digit
        let cell = document.createElement("td");
        let input = document.createElement("input");

        // Configure the input field
        input.type = "text";
        input.inputMode = "numeric";
        input.maxLength = 1;
        input.classList.add("digit-input");

        // Restrict input to numbers only
        input.addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/[^0-9]/g, '');
        });

        input.addEventListener('focus', startTimer);

        if (lastAttempt && lastAttempt[i].classList.contains("correct")) {
            input.value = lastAttempt[i].value;
            input.classList.add("correct");
            input.disabled = true;
        }

        cell.appendChild(input);
        row.appendChild(cell);
    }

    attemptsTable.appendChild(row);
    
    // Increment attempts count
    attemptsCount++;

    // Update the display in the conditions box
    document.getElementById("attempts-count").textContent = attemptsCount;
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
            input.classList.remove("incorrect");
        } else {
            isCorrect = false;
            input.classList.add("incorrect");
            input.classList.remove("correct");
        }
    });

    if (isCorrect) {
        clearInterval(timer);

        let minutes = Math.floor(secondsElapsed / 60);
        let seconds = secondsElapsed % 60;
        document.getElementById("result").innerText = `تهانينا! لقد وجدت العدد في ${minutes} دقيقة و ${seconds} ثانية بعد ${attemptsCount} محاولة.`;

        let revealedNumberElement = document.getElementById("revealed-number");
        revealedNumberElement.innerText = originalNumber;
        document.getElementById("hidden-number-box").classList.add("revealed");
    } else {
        document.getElementById("result").innerText = "حاول مرة أخرى.";
        generateAttemptRow(lastAttempt);
    }
}


