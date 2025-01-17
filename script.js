//-------------------------------------------Page 1 index-------------------------------------------

// Fonction appelée lorsque l'utilisateur sélectionne un niveau
function selectLevel(level) {
    let digitCount;
    let redirectPage;

    // Déterminer le nombre de chiffres et la page de redirection en fonction du niveau
    switch (level) {
        case 'easy':
            digitCount = 4; // Niveau facile : 4 chiffres
            redirectPage = "verification_easy.html"; // Page facile
            break;
        case 'medium':
            digitCount = 6; // Niveau moyen : 6 chiffres
            redirectPage = "verification_medium.html"; // Page moyenne
            break;
        case 'hard':
            digitCount = 8; // Niveau difficile : 8 chiffres
            redirectPage = "verification_hard.html"; // Page difficile
            break;
        default:
            digitCount = 6; // Valeur par défaut (sécurité)
            redirectPage = "verification_medium.html"; // Page par défaut
    }

    // Générer un nombre unique en fonction du niveau choisi
    let randomNumber = generateUniqueNumber(digitCount);

    // Stocker le nombre et le niveau dans sessionStorage
    sessionStorage.setItem("originalNumber", randomNumber);
    sessionStorage.setItem("selectedLevel", level);

    // Rediriger vers la page appropriée
    window.location.href = redirectPage;
}

// Fonction pour générer un nombre avec des chiffres uniques
function generateUniqueNumber(digitCount) {
    let number;
    let isUnique = false;

    while (!isUnique) {
        // Générer un nombre aléatoire avec le nombre de chiffres spécifié
        number = Math.floor(Math.pow(10, digitCount - 1) + Math.random() * 9 * Math.pow(10, digitCount - 1)).toString();

        // Vérifier si tous les chiffres du nombre sont uniques
        if (areAllDigitsUnique(number)) {
            isUnique = true;
        }
    }

    return number;
}

// Fonction pour vérifier si tous les chiffres d'un nombre sont uniques
function areAllDigitsUnique(number) {
    let digits = number.split('');
    return new Set(digits).size === digits.length;
}
