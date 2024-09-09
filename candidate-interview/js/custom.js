 
jQuery(document).ready(function($) {
    $('#listOfCandidate').dataTable();
});

/**************
 *  
 * Questions 
 * 
 */
let questionCounter = 1;

function addQuestion() {
    questionCounter++;
    const questionsContainer = document.getElementById('questionsContainer');
    const questionDiv = document.createElement('div');
    questionDiv.classList.add('form-section');

    questionDiv.innerHTML = `
        <label for="question${questionCounter}">Question ${questionCounter}:</label>
        <textarea id="question${questionCounter}" name="questions[]" rows="4" cols="100" required></textarea>
        <div class="question-options">
            <input type="text" name="options${questionCounter}[]" placeholder="Option 1" class="question-option" required>
            <input type="text" name="options${questionCounter}[]" placeholder="Option 2" class="question-option" required>
            <input type="text" name="options${questionCounter}[]" placeholder="Option 3" class="question-option" required>
            <input type="text" name="options${questionCounter}[]" placeholder="Option 4" class="question-option" required>
        </div>
        <label for="correctAnswer${questionCounter}">Correct Answer:</label>
        <input type="text" id="correctAnswer${questionCounter}" name="correctAnswers[]" required>
        <label for="correctOption${questionCounter}">Correct Option:</label>
        <select id="correctOption${questionCounter}" name="correctOptions[]" required>
            <option value="">Select Option</option>
            <option value="1">Option 1</option>
            <option value="2">Option 2</option>
            <option value="3">Option 3</option>
            <option value="4">Option 4</option>
        </select>
        <span class="remove-btn" onclick="removeQuestion(this)">Remove</span>
    `;

    questionsContainer.appendChild(questionDiv);
}

function removeQuestion(element) {
    element.parentElement.remove();
}

function showUpdateForm(questionId, questionIndex) {
    var formId = 'update-form-' + questionId + '-' + questionIndex;
    document.getElementById(formId).style.display = 'block';
}

jQuery(document).ready(function($) {
    $('.acc-container .acc:nth-child(1) .acc-head').addClass('active');
    $('.acc-container .acc:nth-child(1) .acc-content').slideDown();
    $('.acc-head').on('click', function() {
        if($(this).hasClass('active')) {
          $(this).siblings('.acc-content').slideUp();
          $(this).removeClass('active');
        }
        else {
          $('.acc-content').slideUp();
          $('.acc-head').removeClass('active');
          $(this).siblings('.acc-content').slideToggle();
          $(this).toggleClass('active');
        }
    });     
});

jQuery(document).ready(function($) {
    $('.load-more').click(function() {
        var techId = $(this).data('tech-id');
        var $hiddenQuestions = $(this).siblings('.all-questions').find('.hidden-question');

        $hiddenQuestions.slice(0, 4).removeClass('hidden-question');

        if ($hiddenQuestions.length <= 4) {
            $(this).hide();
        }
    });
});


/*********************
 * 
 *  Add Tabs for interview Questions 
 * 
 */
document.addEventListener('DOMContentLoaded', function () {
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');
    const tabButtonsContainer = document.querySelector('.tab-buttons');

    tabButtons.forEach(button => {
        button.addEventListener('click', function () {
            const tab = this.getAttribute('data-tab');

            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));

            this.classList.add('active');
            document.querySelector(`.tab-content[data-tab="${tab}"]`).classList.add('active');

            document.querySelector('.tab-contents').scrollTo({ top: 0, behavior: 'smooth' });
        });
    });

    tabButtons[0].classList.add('active');
    tabContents[0].classList.add('active');
});
