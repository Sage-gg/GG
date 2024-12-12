// Define lessons for each language
const lessonsFrench = [
    { question: "What is 'hello' in French?", options: ["Bonjour", "Hola", "Ciao", "안녕하세요"], answer: "Bonjour" },
    { question: "What is 'apple' in French?", options: ["Pomme", "Manzana", "Apfel", "사과"], answer: "Pomme" },
    { question: "What is 'thank you' in French?", options: ["Merci", "Gracias", "Danke", "감사합니다"], answer: "Merci" },
    { question: "What is 'dog' in French?", options: ["Chien", "Perro", "Hund", "개"], answer: "Chien" },
    // More questions...
];

const lessonsKorean = [
    { question: "What is 'hello' in Korean?", options: ["안녕하세요", "Hola", "Ciao", "Bonjour"], answer: "안녕하세요" },
    { question: "What is 'apple' in Korean?", options: ["사과", "Pomme", "Apfel", "Manzana"], answer: "사과" },
    { question: "What is 'thank you' in Korean?", options: ["감사합니다", "Gracias", "Danke", "Merci"], answer: "감사합니다" },
    { question: "What is 'dog' in Korean?", options: ["개", "Perro", "Hund", "Chien"], answer: "개" },
    // More questions...
];

const lessonsJapanese = [
    { question: "What is 'hello' in Japanese?", options: ["こんにちは", "Hola", "Bonjour", "안녕하세요"], answer: "こんにちは" },
    { question: "What is 'apple' in Japanese?", options: ["りんご", "Pomme", "Manzana", "Apfel"], answer: "りんご" },
    { question: "What is 'thank you' in Japanese?", options: ["ありがとうございます", "Gracias", "Danke", "Merci"], answer: "ありがとうございます" },
    { question: "What is 'dog' in Japanese?", options: ["犬", "Perro", "Hund", "Chien"], answer: "犬" },
    // More questions...
];

// Shuffle function to randomize lessons
function shuffleLessons(lessons) {
    for (let i = lessons.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [lessons[i], lessons[j]] = [lessons[j], lessons[i]]; // Swap
    }
    return lessons;
}

// Function to render language options with flags using Bootstrap
function renderLanguageOptions() {
    const container = document.getElementById("lesson-container");
    container.innerHTML = `
        <h3>Select a language:</h3>
        <div class="row text-center">
            <div class="col-4">
                <button class="btn btn-lg btn-outline-primary" onclick="renderLessons('french')">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/c/c3/Flag_of_France.svg" alt="French Flag" class="img-fluid" style="width: 40px; height: auto;" />
                    <br/>French
                </button>
            </div>
            <div class="col-4">
                <button class="btn btn-lg btn-outline-success" onclick="renderLessons('korean')">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/0/09/Flag_of_South_Korea.svg" alt="Korean Flag" class="img-fluid" style="width: 40px; height: auto;" />
                    <br/>Korean
                </button>
            </div>
            <div class="col-4">
                <button class="btn btn-lg btn-outline-danger" onclick="renderLessons('japanese')">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/9/9e/Flag_of_Japan.svg" alt="Japanese Flag" class="img-fluid" style="width: 40px; height: auto;" />
                    <br/>Japanese
                </button>
            </div>
        </div>
    `;
}

// Function to render the lessons based on language choice
function renderLessons(language) {
    let lessons;
    if (language === 'french') {
        lessons = lessonsFrench;
    } else if (language === 'korean') {
        lessons = lessonsKorean;
    } else if (language === 'japanese') {
        lessons = lessonsJapanese;
    }

    const shuffledLessons = shuffleLessons(lessons);
    const container = document.getElementById("lesson-container");
    container.innerHTML = ''; // Clear current content

    const gameElement = document.createElement("div");
    gameElement.classList.add("lesson-game");
    gameElement.innerHTML = `
        <h3>Choose your activity for ${language.charAt(0).toUpperCase() + language.slice(1)}:</h3>
        <button class="btn btn-outline-info" onclick="renderAnswerQuestions('${language}')">Answer Questions</button>  
        <button class="btn btn-outline-success" onclick="renderFillInTheBlank('${language}')">Fill in the Blank</button>
        <button class="btn btn-outline-danger" onclick="renderWordScramble('${language}')">Word Scramble</button>
    `;
    container.appendChild(gameElement);
}

// Function to render the Answer Questions game
function renderAnswerQuestions(language) {
    let lessons;
    if (language === 'french') {
        lessons = lessonsFrench;
    } else if (language === 'korean') {
        lessons = lessonsKorean;
    } else if (language === 'japanese') {
        lessons = lessonsJapanese;
    }

    const shuffledLessons = shuffleLessons(lessons);
    const container = document.getElementById("lesson-container");
    container.innerHTML = ''; // Clear current content

    const gameElement = document.createElement("div");
    gameElement.classList.add("answer-questions-game");
    
    gameElement.innerHTML = `
        <h3>Answer the following questions:</h3>
        <div class="questions">
            ${shuffledLessons.map((lesson, index) => `
                <div class="question-item">
                    <p>${lesson.question}</p>
                    <div class="options">
                        ${lesson.options.map(option => `
                            <label>
                                <input type="radio" name="question-${index}" value="${option}" />
                                ${option}
                            </label>
                        `).join('')}
                    </div>
                </div>
            `).join('')}
        </div>
        <button class="btn btn-primary" onclick="checkAnswerQuestions('${language}')">Submit Answers</button>
        <button class="btn btn-secondary" onclick="renderLessons('${language}')">Return to Lessons</button>
    `;
    
    container.appendChild(gameElement);
}

// Function to check the answers in the Answer Questions game
function checkAnswerQuestions(language) {
    let lessons;
    if (language === 'french') {
        lessons = lessonsFrench;
    } else if (language === 'korean') {
        lessons = lessonsKorean;
    } else if (language === 'japanese') {
        lessons = lessonsJapanese;
    }

    const container = document.getElementById("lesson-container");
    const questionItems = document.querySelectorAll('.question-item');
    let score = 0;

    questionItems.forEach((item, index) => {
        const selectedOption = item.querySelector('input[type="radio"]:checked');
        const correctAnswer = lessons[index].answer;
        
        if (selectedOption) {
            const userAnswer = selectedOption.value;
            if (userAnswer === correctAnswer) {
                score++;
                item.style.backgroundColor = "#c3ffcc"; // Green background for correct answers
            } else {
                item.style.backgroundColor = "#ffcccc"; // Red background for incorrect answers
            }
        } else {
            item.style.backgroundColor = "#ffcccc"; // Red background for unanswered questions
        }
    });

    // Show score and feedback
    container.innerHTML = `
        <p>Your score: ${score} out of ${lessons.length}</p>
        <p>${score === lessons.length ? 'Correct! Well done!' : 'Some answers were incorrect. Try again!'}</p>
        <button class="btn btn-secondary" onclick="renderLessons('${language}')">Return to Lessons</button>
    `;
}

// Fill in the Blank Game
function renderFillInTheBlank(language) {
    let lessons;
    if (language === 'french') {
        lessons = lessonsFrench;
    } else if (language === 'korean') {
        lessons = lessonsKorean;
    } else if (language === 'japanese') {
        lessons = lessonsJapanese;
    }

    const container = document.getElementById("lesson-container");
    container.innerHTML = ''; // Clear current content

    const lesson = lessons[0]; // Get the first lesson
    const fillInTheBlankElement = document.createElement("div");
    fillInTheBlankElement.classList.add("fill-in-the-blank");
    fillInTheBlankElement.innerHTML = `
        <h3>${lesson.question.replace(lesson.answer, "____")}</h3>
        <input type="text" id="user-answer" placeholder="Type your answer" />
        <button class="btn btn-primary" onclick="checkFillInTheBlankAnswer('${lesson.answer}', '${language}')">Submit</button>
    `;
    container.appendChild(fillInTheBlankElement);
}

// Check Fill-in-the-Blank answer
function checkFillInTheBlankAnswer(correctAnswer, language) {
    const userAnswer = document.getElementById("user-answer").value.trim();
    const container = document.getElementById("lesson-container");
    if (userAnswer.toLowerCase() === correctAnswer.toLowerCase()) {
        container.innerHTML = "<p>Correct! Well done!</p>";
    } else {
        container.innerHTML = "<p>Incorrect. Try again!</p>";
    }
    
    // Add the "Return to Lessons" button
    const returnButton = document.createElement("button");
    returnButton.innerText = "Return to Lessons";
    returnButton.classList.add("btn", "btn-secondary");
    returnButton.onclick = () => renderLessons(language);
    container.appendChild(returnButton);
}

// Word Scramble Game with Hints and Attempts
function renderWordScramble(language) {
    let lessons;
    if (language === 'french') {
        lessons = lessonsFrench;
    } else if (language === 'korean') {
        lessons = lessonsKorean;
    } else if (language === 'japanese') {
        lessons = lessonsJapanese;
    }

    const container = document.getElementById("lesson-container");
    container.innerHTML = ''; // Clear current content

    const lesson = lessons[0]; // Get the first lesson
    const scrambledWord = scrambleWord(lesson.answer);

    const wordScrambleElement = document.createElement("div");
    wordScrambleElement.classList.add("word-scramble");
    wordScrambleElement.innerHTML = `
        <h3>Unscramble the word: <span id="scrambled-word">${scrambledWord}</span></h3>
        <input type="text" id="scramble-answer" placeholder="Unscramble the word" />
        <button class="btn btn-primary" onclick="checkWordScrambleAnswer('${lesson.answer}', '${language}')">Submit</button>
        <button class="btn btn-warning" onclick="showHint('${lesson.answer}')">Show Hint</button>
        <p id="hint-message" style="display:none;"></p>
        <p>Attempts: <span id="attempts-left">3</span></p>
    `;
    container.appendChild(wordScrambleElement);
}

// Show the first letter of the word as a hint
function showHint(correctAnswer) {
    const hintMessage = document.getElementById('hint-message');
    hintMessage.innerHTML = `Hint: The first letter is '${correctAnswer.charAt(0)}'`;
    hintMessage.style.display = 'block'; // Show the hint
}

// Function to scramble the word
function scrambleWord(word) {
    const scrambled = word.split('').sort(() => Math.random() - 0.5).join('');
    return scrambled;
}

// Check Word Scramble answer with attempt tracking
function checkWordScrambleAnswer(correctAnswer, language) {
    const userAnswer = document.getElementById("scramble-answer").value.trim();
    const container = document.getElementById("lesson-container");
    const attemptsLeft = document.getElementById("attempts-left");
    let remainingAttempts = parseInt(attemptsLeft.innerHTML);

    if (userAnswer.toLowerCase() === correctAnswer.toLowerCase()) {
        container.innerHTML = "<p>Correct! Well done!</p>";
        // Optionally add scoring here based on attempts
        if (remainingAttempts === 3) {
            container.innerHTML += "<p>Excellent! You solved it on the first try.</p>";
        } else if (remainingAttempts === 2) {
            container.innerHTML += "<p>Good job! You solved it in two attempts.</p>";
        } else {
            container.innerHTML += "<p>Well done! You solved it.</p>";
        }
    } else {
        remainingAttempts -= 1;
        attemptsLeft.innerHTML = remainingAttempts;
        if (remainingAttempts <= 0) {
            container.innerHTML = `
                <p>Out of attempts! The correct word was: ${correctAnswer}</p>
                <button class="btn btn-secondary" onclick="renderLessons('${language}')">Return to Lessons</button>
            `;
        } else {
            container.innerHTML = `
                <p>Incorrect. Try again! You have ${remainingAttempts} attempts left.</p>
                <button class="btn btn-primary" onclick="checkWordScrambleAnswer('${correctAnswer}', '${language}')">Submit</button>
                <button class="btn btn-warning" onclick="showHint('${correctAnswer}')">Show Hint</button>
            `;
        }
    }

    // Add the "Return to Lessons" button after game ends
    const returnButton = document.createElement("button");
    returnButton.innerText = "Return to Lessons";
    returnButton.classList.add("btn", "btn-secondary");
    returnButton.onclick = () => renderLessons(language);
    container.appendChild(returnButton);
}


// Initial language selection screen
renderLanguageOptions();
