<?php 

function interview_options() { 
    global $wpdb;
	echo '<div class="wrap">';
	    echo '<h3>Candidate Records</h3>';
        ?>
            <table id="listOfCandidate">
                <thead>
                    <tr>
                        <th>S.N</th>
                        <th>Name</th>
                        <th>Email</th> 
                        <th>Experience</th>
                        <th>Profile</th>
                        <th>Score</th> 
                    </tr>
                </thead>
                <tbody>
                 <?php  
                 $snum = 1;
                    $tableName = $wpdb->prefix . 'interview_form';
                    $results = $wpdb->get_results( "SELECT * FROM $tableName ORDER BY id DESC"); 
                    if(!empty($results)) {  
                        foreach($results as $row){ 
                         //   echo "<pre>"; print_r($row);
                            ?>
                    <tr>
                        <td><?php echo $snum; ?></td>
                        <td><?php echo $row->name; ?></td>
                        <td><?php echo $row->email; ?></td> 
                        <td><?php echo $row->experience; ?></td>
                        <td><?php echo $row->profileLanguage; ?></td>
                        <td><?php echo $row->scoreQA; ?></td> 
                    </tr> 
                    <?php
                    $snum++;
                    } } ?>
                </tbody>
            </table>
        <?php 
	echo '</div>';
}


/*******************************
 * 
 *  Qustion callback function
 * 
 ****************/

 function interview_question_page(){
    ?>
 <div class="interQuestion">
        <h1>Interview Form</h1>
        <?php if (isset($_GET['submission']) && $_GET['submission'] === 'success') : ?>
            <div class="success-message">Your submission was successful!</div>
        <?php endif; ?>

        <form id="interviewForm" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
            <input type="hidden" name="action" value="interview_form_submit">
            <?php wp_nonce_field('interview_form_action', 'interview_form_nonce'); ?>
            <div class="formFcont">
                <div class="form-section">
                    <label for="technology">Technology:</label> 
                    <select id="technology" name="technology">
                        <option value="">Select Profile</option>
                        <option value="wordpress">Wordpress</option>
                        <option value="laravel">Laravel</option>
                        <option value="mern-stack">Mern Stack</option>
                        <option value="python">Python</option> 
                        <option value="angular">Angular</option> 
                    </select>
                </div>
                <div class="form-section">
                    <label for="experience">Experience:</label>
                    <select id="experience" name="experience" onchange="loadQuestions()">
                        <option value="">Select Experience</option>
                        <option value="1">1 Year</option>
                        <option value="2">2 Years</option>
                        <option value="3">3 Years</option>
                        <option value="4">4 Years</option>
                        <option value="5">5 Years</option>
                        <option value="6">6 Years</option>
                    </select>
                </div>
            </div>
            <div id="questionsContainer">
                <div class="form-section">
                    <label for="question1">Question 1:</label>
                    <textarea id="question1" name="questions[]" rows="4" cols="100" required></textarea>
                    <div class="question-options">
                        <input type="text" name="options1[]" placeholder="Option 1" class="question-option" required>
                        <input type="text" name="options1[]" placeholder="Option 2" class="question-option" required>
                        <input type="text" name="options1[]" placeholder="Option 3" class="question-option" required>
                        <input type="text" name="options1[]" placeholder="Option 4" class="question-option" required>
                    </div>
                    <label for="correctAnswer1">Correct Answer:</label>
                    <input type="text" id="correctAnswer1" name="correctAnswers[]" required>
                    <label for="correctOption1">Correct Option:</label>
                    <select id="correctOption1" name="correctOptions[]" required>
                        <option value="">Select Option</option>
                        <option value="1">Option 1</option>
                        <option value="2">Option 2</option>
                        <option value="3">Option 3</option>
                        <option value="4">Option 4</option>
                    </select>
                    <span class="remove-btn" onclick="removeQuestion(this)">Remove</span>
                </div>
            </div>

            <button type="button" class="add_qust" onclick="addQuestion()">Add More Questions</button>

            <div class="form-section">
                <button type="submit">Submit</button>
            </div>

            <?php
            if ( isset($_GET['submission']) && $_GET['submission'] == 'error' ) {
                echo '<div class="error-message" style="color:red; margin-top:10px;">The number of questions must be 50 or more.</div>';
            }
            ?>

        </form>
    </div>


    <?php
 } 

function technolgyTab(){
    ?>
        <div class="tabs listingOfTech">
            <header class="tab-buttons">
                <button class="tab-button" data-tab="1">
                    <span class="line"></span>
                    <span>WordPress</span>
                </button>
                <button class="tab-button" data-tab="2">
                    <span class="line"></span>
                    <span>Laravel</span>
                </button>
                <button class="tab-button" data-tab="3">
                    <span class="line"></span>
                    <span>Mern Stack</span>
                </button>
                <button class="tab-button" data-tab="4">
                    <span class="line"></span>
                    <span>Python</span>
                </button>
                <button class="tab-button" data-tab="5">
                    <span class="line"></span>
                    <span>Angular</span>
                </button>
            </header>
            <div class="tab-contents">
                <div class="tab-content" data-tab="1">
                    <?php get_WP_Questions(); ?>
                </div>
                <div class="tab-content" data-tab="2">
                    <?php get_laravel_Questions(); ?>
                </div>
                <div class="tab-content" data-tab="3">
                    <?php get_mernStack_Questions(); ?>
                </div>
                <div class="tab-content" data-tab="4">
                    <?php get_python_Questions(); ?>
                </div>
                <div class="tab-content" data-tab="5">
                    <?php get_angular_Questions(); ?>
                </div>
            </div>
        </div>

    <?php
}


function getQuestions() { 
    technolgyTab(); 
}
  
function handleUpdate() {
    if (isset($_POST['submit_update'])) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'interview_questions';

        $question_id = intval($_POST['question_id']);
        $question_index = intval($_POST['question_index']);
        $update_question = sanitize_text_field($_POST['update_question']);
        $update_options = array_map('sanitize_text_field', explode(',', $_POST['update_options']));
        $update_answer = sanitize_text_field($_POST['update_answer']);
        $update_correct_option = intval($_POST['update_correct_option']);

        // Get current data
        $current_data = $wpdb->get_row("SELECT * FROM $table_name WHERE id = $question_id");
        $questions = unserialize($current_data->question);
        $options = unserialize($current_data->options);
        $correct_answers = unserialize($current_data->correct_answer);
        $correct_options = unserialize($current_data->correct_option);

        // Update data
        $questions[$question_index] = $update_question;
        $options[$question_index] = $update_options;
        $correct_answers[$question_index] = $update_answer;
        $correct_options[$question_index] = $update_correct_option;

        // Serialize data
        $questions_serialized = serialize($questions);
        $options_serialized = serialize($options);
        $correct_answers_serialized = serialize($correct_answers);
        $correct_options_serialized = serialize($correct_options);

        // Update database
        $wpdb->update(
            $table_name,
            array(
                'question' => $questions_serialized,
                'options' => $options_serialized,
                'correct_answer' => $correct_answers_serialized,
                'correct_option' => $correct_options_serialized,
            ),
            array('id' => $question_id)
        );

        echo "<div class='success'> Record updated successfully!</div>";
    }
}
add_action('init', 'handleUpdate');

