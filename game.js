const LEVELS = {
    easy: {
        digitCount: 4,
        label: "سهل",
        columns: ["آلاف", "مئات", "عشرات", "آحاد"]
    },
    medium: {
        digitCount: 6,
        label: "متوسط",
        columns: ["مئات الآلاف", "عشرات الآلاف", "آلاف", "مئات", "عشرات", "آحاد"]
    },
    hard: {
        digitCount: 8,
        label: "صعب",
        columns: ["عشرات الملايين", "ملايين", "مئات الآلاف", "عشرات الآلاف", "آلاف", "مئات", "عشرات", "آحاد"]
    }
};

const ATTEMPT_PENALTIES = {
    4: 10,
    5: 25,
    6: 45,
    7: 70,
    8: 100,
    9: 135
};

const MAX_ATTEMPTS = 9;

let originalNumber = "";
let selectedLevel = "";
let attemptsCount = 0;
let timer;
let secondsElapsed = 0;
let timerStarted = false;
let gameFinished = false;

const attemptsTable = document.getElementById("attempts");
const checkAnswerButton = document.getElementById("check-answer-btn");
const resultElement = document.getElementById("result");
const attemptsCountElement = document.getElementById("attempts-count");
const timerDisplay = document.getElementById("timer-display");
const penaltyFlashElement = document.getElementById("penalty-flash");

window.addEventListener("DOMContentLoaded", initializeGame);

function initializeGame() {
    selectedLevel = document.body.dataset.level || sessionStorage.getItem("selectedLevel");
    const levelConfig = LEVELS[selectedLevel];
    originalNumber = sessionStorage.getItem("originalNumber") || "";

    if (!levelConfig || !isValidNumberForLevel(originalNumber, levelConfig)) {
        alert("لم يتم العثور على رقم صالح لهذا المستوى. الرجاء بدء لعبة جديدة.");
        window.location.href = "index.html";
        return;
    }

    document.documentElement.dir = "rtl";
    document.getElementById("level-name").textContent = levelConfig.label;
    renderTableHeaders(levelConfig.columns);
    displayConditions(originalNumber, levelConfig.columns);
    generateAttemptRow();
    updateAttemptsCount();
}

function isValidNumberForLevel(number, levelConfig) {
    return /^\d+$/.test(number) && number.length === levelConfig.digitCount && areDigitsDistinct(number);
}

function renderTableHeaders(columns) {
    const headersRow = document.getElementById("digit-headers");
    headersRow.innerHTML = "";

    columns.forEach((column) => {
        const header = document.createElement("th");
        header.textContent = column;
        headersRow.appendChild(header);
    });
}

function displayConditions(number, columns) {
    const conditionsList = document.querySelector("#conditions ul");
    const parityConditions = columns.map((column, index) => ({
        id: `parity-condition-${index}`,
        text: `رقم ${column}`,
        value: isEvenOrOdd(number[index])
    }));

    conditionsList.querySelectorAll(".random-condition").forEach((condition) => condition.remove());

    pickRandomItems(parityConditions, 2).forEach((condition) => {
        const item = document.createElement("li");
        item.classList.add("random-condition");
        item.innerHTML = `${condition.text}: <span id="${condition.id}">${condition.value}</span>`;
        conditionsList.appendChild(item);
    });

    document.getElementById("sum-condition").textContent = sumOfDigits(number);
    document.getElementById("unique-condition").textContent = areDigitsDistinct(number)
        ? "كل الأرقام مختلفة"
        : "بعض الأرقام متشابهة";
}

function pickRandomItems(items, count) {
    return [...items].sort(() => Math.random() - 0.5).slice(0, count);
}

function isEvenOrOdd(digit) {
    return Number(digit) % 2 === 0 ? "زوجي" : "فردي";
}

function sumOfDigits(number) {
    return number.split("").reduce((total, digit) => total + Number(digit), 0);
}

function areDigitsDistinct(number) {
    return new Set(number.split("")).size === number.length;
}

function generateAttemptRow(lastAttempt = null) {
    const levelConfig = LEVELS[selectedLevel];
    const row = document.createElement("tr");

    for (let index = 0; index < levelConfig.digitCount; index++) {
        const cell = document.createElement("td");
        const input = document.createElement("input");

        input.type = "text";
        input.inputMode = "numeric";
        input.maxLength = 1;
        input.autocomplete = "off";
        input.classList.add("digit-input");
        input.setAttribute("aria-label", `${levelConfig.columns[index]} رقم`);

        input.addEventListener("input", handleDigitInput);
        input.addEventListener("keydown", handleDigitKeydown);
        input.addEventListener("focus", startTimer);

        if (lastAttempt && lastAttempt[index].classList.contains("correct")) {
            input.value = lastAttempt[index].value;
            input.classList.add("correct");
            input.disabled = true;
        }

        cell.appendChild(input);
        row.appendChild(cell);
    }

    attemptsTable.appendChild(row);
    if (timerStarted) {
        focusFirstEditableInput(row);
    }
}

function handleDigitInput(event) {
    const input = event.target;
    input.value = input.value.replace(/[^0-9]/g, "").slice(0, 1);
    input.classList.remove("incorrect");
    clearResult();

    if (input.value) {
        focusNextEditableInput(input);
    }
}

function handleDigitKeydown(event) {
    if (event.key === "Enter") {
        event.preventDefault();
        if (!checkAnswerButton.disabled) {
            checkAnswer();
        }
        return;
    }

    if (event.key === "ArrowRight" || event.key === "ArrowDown") {
        event.preventDefault();
        focusNextEditableInput(event.target);
        return;
    }

    if (event.key === "ArrowLeft" || event.key === "ArrowUp") {
        event.preventDefault();
        focusPreviousEditableInput(event.target);
        return;
    }

    if (event.key === "Backspace" && !event.target.value) {
        event.preventDefault();
        focusPreviousEditableInput(event.target);
    }
}

function focusFirstEditableInput(row) {
    const firstEditableInput = row.querySelector(".digit-input:not(:disabled)");
    if (firstEditableInput) {
        firstEditableInput.focus();
        firstEditableInput.select();
    }
}

function focusNextEditableInput(input) {
    const inputs = getCurrentAttemptInputs();
    const currentIndex = inputs.indexOf(input);
    const nextInput = inputs.slice(currentIndex + 1).find((item) => !item.disabled);

    if (nextInput) {
        nextInput.focus();
        nextInput.select();
    }
}

function focusPreviousEditableInput(input) {
    const inputs = getCurrentAttemptInputs();
    const currentIndex = inputs.indexOf(input);
    const previousInput = inputs.slice(0, currentIndex).reverse().find((item) => !item.disabled);

    if (previousInput) {
        previousInput.focus();
        previousInput.select();
    }
}

function getCurrentAttemptInputs() {
    const rows = attemptsTable.querySelectorAll("tr");
    const currentRow = rows[rows.length - 1];
    return Array.from(currentRow.querySelectorAll(".digit-input"));
}

function startTimer() {
    if (timerStarted || gameFinished) {
        return;
    }

    timerStarted = true;
    checkAnswerButton.disabled = false;
    timer = setInterval(() => {
        secondsElapsed++;
        updateTimerDisplay();
    }, 1000);
}

function updateTimerDisplay() {
    const minutes = Math.floor(secondsElapsed / 60);
    const seconds = secondsElapsed % 60;
    timerDisplay.textContent = `الوقت المنقضي: ${minutes} دقيقة و ${seconds} ثانية`;
}

function checkAnswer() {
    if (gameFinished) {
        return;
    }

    const lastAttempt = getCurrentAttemptInputs();

    if (!isAttemptComplete(lastAttempt)) {
        showError("الرجاء ملء جميع الخانات قبل اتخاذ القرار النهائي.");
        return;
    }

    attemptsCount++;
    updateAttemptsCount();
    const penaltySeconds = applyAttemptPenalty();

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
        finishGame();
        return;
    }

    if (attemptsCount >= MAX_ATTEMPTS) {
        endGameWithLoss();
        return;
    }

    resultElement.className = "message error-message";
    resultElement.textContent = penaltySeconds
        ? `حاول مرة أخرى. تمت إضافة ${penaltySeconds} ثانية بسبب الوصول إلى المحاولة رقم ${attemptsCount}.`
        : "حاول مرة أخرى.";
    generateAttemptRow(lastAttempt);
}

function isAttemptComplete(inputs) {
    return inputs.every((input) => input.disabled || input.value.length === 1);
}

function finishGame() {
    gameFinished = true;
    clearInterval(timer);
    checkAnswerButton.disabled = true;
    attemptsTable.querySelectorAll(".digit-input").forEach((input) => {
        input.disabled = true;
    });

    const minutes = Math.floor(secondsElapsed / 60);
    const seconds = secondsElapsed % 60;
    resultElement.className = "message success-message";
    resultElement.textContent = `تهانينا! لقد وجدت العدد في ${minutes} دقيقة و ${seconds} ثانية بعد ${attemptsCount} محاولة.`;

    document.getElementById("revealed-number").textContent = originalNumber;
    document.getElementById("hidden-number-box").classList.add("revealed");
}

function applyAttemptPenalty() {
    const penaltySeconds = ATTEMPT_PENALTIES[attemptsCount];

    if (!penaltySeconds) {
        return 0;
    }

    secondsElapsed += penaltySeconds;
    updateTimerDisplay();
    showPenaltyFlash(penaltySeconds);
    return penaltySeconds;
}

function showPenaltyFlash(penaltySeconds) {
    if (!penaltyFlashElement) {
        return;
    }

    penaltyFlashElement.textContent = `+${penaltySeconds} ثانية`;
    penaltyFlashElement.classList.remove("show");
    void penaltyFlashElement.offsetWidth;
    penaltyFlashElement.classList.add("show");
}

function endGameWithLoss() {
    gameFinished = true;
    clearInterval(timer);
    checkAnswerButton.disabled = true;
    attemptsTable.querySelectorAll(".digit-input").forEach((input) => {
        input.disabled = true;
    });

    document.getElementById("revealed-number").textContent = originalNumber;
    document.getElementById("hidden-number-box").classList.add("revealed");
    resultElement.className = "message error-message";
    resultElement.textContent = `انتهت اللعبة. لقد استعملت ${MAX_ATTEMPTS} محاولات، والعدد الصحيح هو ${originalNumber}.`;
}

function updateAttemptsCount() {
    attemptsCountElement.textContent = attemptsCount;
}

function showError(message) {
    resultElement.className = "message error-message";
    resultElement.textContent = message;
}

function clearResult() {
    if (!resultElement.classList.contains("success-message")) {
        resultElement.textContent = "";
        resultElement.className = "message";
    }
}

function startNewGame() {
    sessionStorage.removeItem("originalNumber");
    sessionStorage.removeItem("selectedLevel");
    window.location.href = "index.html";
}
