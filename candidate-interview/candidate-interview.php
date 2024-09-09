<?php
/*
Plugin Name: Candidate Interview
Description: A custom plugin to record the interview.
Version: 1.0
Author: Sachin Kumar
*/

  
function create_interview_form_table() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'interview_form';

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL, 
        email varchar(255) NOT NULL, 
        experience int(11) NOT NULL,
        scoreQA int(11) NOT NULL,
        profileLanguage varchar(255) NOT NULL, 
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    dbDelta($sql);
}

function create_interview_questions_table() {
    global $wpdb;
    $table = $wpdb->prefix . 'interview_questions'; 

    $chset_collate = $wpdb->get_charset_collate();

    $sql2 = "CREATE TABLE $table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        technology varchar(255) NOT NULL,
        experience int(11) NOT NULL,
        question text NOT NULL,
        options text NOT NULL,
        correct_answer text NOT NULL,
        correct_option text NOT NULL,
        PRIMARY KEY  (id)
    ) $chset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql2);
}
 

register_activation_hook(__FILE__, 'create_interview_tables');

require_once( plugin_dir_path(__FILE__) . 'admin/questionList.php' );
require_once( plugin_dir_path(__FILE__) . 'admin/tabs/wp-questions.php' );
require_once( plugin_dir_path(__FILE__) . 'admin/tabs/laravel-questions.php' );
require_once( plugin_dir_path(__FILE__) . 'admin/tabs/mern-questions.php' );
require_once( plugin_dir_path(__FILE__) . 'admin/tabs/python-questions.php' ); 
require_once( plugin_dir_path(__FILE__) . 'admin/tabs/angular-questions.php' );

function create_interview_tables() {
    create_interview_form_table();
    create_interview_questions_table();
}


function sr_enqueue_scripts() {
    wp_enqueue_style('sr-styles', plugins_url('css/style.css', __FILE__));
    if (file_exists(plugin_dir_path(__FILE__) . 'js/screen-recorder.js')) {
        wp_enqueue_script('sr-screen-recorder', plugins_url('js/screen-recorder.js', __FILE__), array('jquery'), time(), true);
    } else {
        error_log('screen-recorder.js not found!');
    }
    wp_localize_script('sr-screen-recorder', 'sr_ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
}
add_action('wp_enqueue_scripts', 'sr_enqueue_scripts');

function my_enqueue() {  
    wp_enqueue_style('admin_style', plugin_dir_url(__FILE__) . '/css/admin.css');
    wp_enqueue_style('dataTables_style', 'https://cdn.datatables.net/2.1.2/css/dataTables.dataTables.min.css');
    wp_enqueue_script('dataTables', 'https://cdn.datatables.net/2.1.2/js/dataTables.min.js');
    wp_enqueue_script('custom_script', plugin_dir_url(__FILE__) . '/js/custom.js');
}
add_action('admin_enqueue_scripts', 'my_enqueue');
 
 
add_action( 'admin_menu', 'interview_plugin_menu' );

function interview_plugin_menu() {
	add_menu_page( 'Candidate Interview', 'Interview', 'manage_options', 'interview', 'interview_options' );
    add_submenu_page('interview', 'Interview Questions', 'Questions', 'manage_options', 'interview_questions', 'interview_question_page' );
    add_submenu_page( 'interview', 'Questions List', 'Questions List', 'manage_options', 'questions_list', 'getQuestions' );
}

function get_user_ip() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

 function interview_question_submission() {
    global $wpdb;

    if ( ! isset( $_POST['interview_form_nonce'] ) || ! wp_verify_nonce( $_POST['interview_form_nonce'], 'interview_form_action' ) ) {
        wp_die( 'Nonce verification failed' );
    }

    $technology = sanitize_text_field( $_POST['technology'] );
    $experience = sanitize_text_field( $_POST['experience'] );

    $questions_data = array();

    $questions = isset($_POST['questions']) ? $_POST['questions'] : array();
    $correct_answers = isset($_POST['correctAnswers']) ? $_POST['correctAnswers'] : array();
    $correct_options = isset($_POST['correctOptions']) ? $_POST['correctOptions'] : array();

    $num_questions = count($questions);

    if ($num_questions <= 50) {
        //wp_die('The number of questions must be 50 or more.');
        $redirect_url = add_query_arg('submission', 'error', $_SERVER['HTTP_REFERER']);
        wp_redirect($redirect_url);
        exit;
    }

    for ($i = 0; $i < $num_questions; $i++) {
        $options_key = 'options' . ($i + 1);
        $options = isset($_POST[$options_key]) ? array_map('sanitize_text_field', $_POST[$options_key]) : array();
        
        $questions_data[] = array(
            'questions' => isset($questions[$i]) ? sanitize_text_field($questions[$i]) : '',
            'options' => $options,
            'correct_answers' => isset($correct_answers[$i]) ? sanitize_text_field($correct_answers[$i]) : '',
            'correct_options' => isset($correct_options[$i]) ? sanitize_text_field($correct_options[$i]) : '',
        );
    }
 
    $data_to_insert = array(
        'technology' => $technology,
        'experience' => $experience,
        'question' => serialize(array_column($questions_data, 'questions')),
        'options' => serialize(array_column($questions_data, 'options')),
        'correct_answer' => serialize(array_column($questions_data, 'correct_answers')),
        'correct_option' => serialize(array_column($questions_data, 'correct_options')),
    );

    // echo "<pre>"; print_r($data_to_insert); echo "</pre>"; 
    $table_name = $wpdb->prefix . 'interview_questions';  

     // Check if the technology already exists in the database
    // $existing_record = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE technology = %s", $technology));
    $existing_record = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM $table_name WHERE technology = %s AND experience = %s", $technology, $experience )
    );

    if ($existing_record) {
        // Update the existing record if the technology exists
        $wpdb->update(
            $table_name,
            $data_to_insert,
            array('technology' => $technology, 'experience' => $experience), 
            array('%s', '%s', '%s', '%s', '%s', '%s'),
            array('%s', '%s')
        ); 
        wp_redirect(add_query_arg('submission', 'updated', $_SERVER['HTTP_REFERER']));
    } else {
        $sql_query = $wpdb->insert(
                $table_name,
                $data_to_insert,
                array('%s', '%s', '%s', '%s', '%s', '%s')
            );
 
            wp_redirect(add_query_arg('submission', 'success', $_SERVER['HTTP_REFERER']));
    }
        exit; 
}

add_action('admin_post_interview_form_submit', 'interview_question_submission');
add_action('admin_post_nopriv_interview_form_submit', 'interview_question_submission');

function sr_save_recording() {
    $user_ip = get_user_ip();
    if (!empty($_FILES['video_blob']['name'])) {
        $uploaded_file = $_FILES['video_blob'];
        $upload_overrides = array('test_form' => false);
        $movefile = wp_handle_upload($uploaded_file, $upload_overrides);
 
        if ($movefile && !isset($movefile['error'])) {
            // echo $movefile['url'];  for console.log

            $to = 'sachin.itechnolabs@gmail.com';
            $subject = 'Interview Collect '. $_POST['name'];
            $body = 'Candidate is completed the interview, here is dedected some required information for the candidate<br> Name <strong>' . $_POST['name'] .'</strong> <br>Email id <strong> ' .$_POST['email'].'</strong><br> Profile <strong>' . $_POST['profile'].'</strong><br> Score <strong>' . $_POST['score'].'</strong><br> screen/video recoding clips  <strong>'. $movefile['url'].'</strong>  IP Address: <strong>' . $user_ip . '</strong>';
            $headers = array('Content-Type: text/html; charset=UTF-8');

            $mail =  wp_mail( $to, $subject, $body, $headers ); 

            if($_POST['email'] != ''){
                $candidateMail = $_POST['email'];
                $cand_Subject = 'Thanks for the giving interview '. $_POST['name'];
                $messageC = 'You have completed the interview, We will meet shortly !<br> Name <strong>' . $_POST['name'] .'</strong> <br>Email id <strong> ' .$_POST['email'].'</strong><br> Profile <strong>' . $_POST['profile'].'</strong><br> Score <strong>' . $_POST['score'].'</strong><br> ';
                $headers2 = array('Content-Type: text/html; charset=UTF-8');

                $mail2 =  wp_mail( $candidateMail, $cand_Subject, $messageC, $headers2 );   
            } 

        } else {
            echo $movefile['error'];
        }
    }
    wp_die();
}

add_action('wp_ajax_sr_save_recording', 'sr_save_recording');
add_action('wp_ajax_nopriv_sr_save_recording', 'sr_save_recording');


function sr_send_rejected_email() {
    $user_ip = get_user_ip();
    if (!empty($_POST['email'])) {
        $to = 'sachin.itechnolabs@gmail.com'; 
        $subject = 'Interview Rejected for ' . $_POST['name'];
        $body = 'The interview was closed because the user switched tabs or opened another application. Here is the candidate\'s information:<br> Name: <strong>' . $_POST['name'] . '</strong><br>Email: <strong>' . $_POST['email'] . '</strong><br>Profile: <strong>' . $_POST['profile'] . '</strong> <br> User IP address: <strong>'.$user_ip.'</strong>';
        $headers = array('Content-Type: text/html; charset=UTF-8');

        wp_mail($to, $subject, $body, $headers);
    }

    wp_die();
}

add_action('wp_ajax_sr_send_rejected_email', 'sr_send_rejected_email');
add_action('wp_ajax_nopriv_sr_send_rejected_email', 'sr_send_rejected_email'); 


function interviewForm(){
    ob_start();
    ?>
     <div class="custom-alert" id="custom_alt" style="display:none;">
        <div class="alter-in">
            <h4> Please give the permission entire screen sharing and turn on the camera ! </h4>
        </div>
     </div>  
     <div id="popupModal" class="modal termsandcondition" style="display: none;">
        <div class="modal-content">
            <span class="close">&times;</span>
            <?php 
                $post = get_post(23); 
                $content = apply_filters('the_content', $post->post_content); 
                echo $content;  
            ?> 
        </div>
    </div> 
     <div class="intervewMain">
        <div class="interSection">
            <div id="overlay">
                <div class="cv-spinner">
                    <span class="spinner"></span>
                </div>
            </div>
          
        <button id="startRecording" class="animatedButton main-btn">Click Here to Start Interview</button> 
        <button id="stopRecording" class="animatedButton" style="display:none;">Click on Interview Completed</button>
            <div class="interviewInner">
                <div id="recordingForm" style="display:none;">
                
                    <form id="dynamicForm" onsubmit="return validateForm()">
                        <div class="step" id="step1">
                            <div style="display: flex; gap: 20px;">
                                <div style="width: 50%;">
                                    <label for="name" class="cmn-label">Name:</label>
                                    <input type="text" id="name" name="name" class="cmn-field">
                                    <div id="nameError" class="error"></div>
                                </div>

                                <div style="width: 50%">
                                    <label for="email" class="cmn-label">Email:</label>
                                    <input type="email" id="email" name="email" class="cmn-field">
                                    <div id="emailError" class="error"></div> 
                                </div>
                            </div>

                            <div style="display: flex; gap: 20px;">  
                                <div style="width: 50%;">
                                <label for="experience" class="cmn-label">Experience:</label>         
                                    <select id="experience" name="experience" class="cmn-field">
                                        <option>Select</option>
                                        <option value="1">1</option>
                                        <option value="2">2</option>
                                        <option value="3">3</option>
                                        <!-- <option value="4">4</option>
                                        <option value="5">5</option>
                                        <option value="6">6</option>  -->
                                    </select>
                                    <div id="experienceError" class="error"></div>
                                </div>
                            
                                <div style="width: 50%;"> 
                                    <label for="profileLng" class="cmn-label">Profile:</label>
                                    <select id="profileLng" name="profileLng" class="cmn-field">
                                        <option>Select Profile</option>
                                        <option value="wordpress">Wordpress </option>
                                        <option value="laravel">Laravel</option>
                                        <option value="mern-stack">Mern Stack</option>
                                        <option value="python">Python</option> 
                                        <!-- <option value="data-scientists">Data Scientists</option>
                                        <option value="angular">Angular JS</option> 
                                        <option value="graphics">Graphics Design</option> -->
                                    </select>
                                    <input type="hidden" id="selectedValue" name="profileLngSl">
                                    <div id="profileError" class="error"></div> 
                                    <button type="button" class="next-btn" onclick="nextStep(2)">Next</button>
                                </div>
                            </div>
                        </div>

                        <div class="step" id="step2" style="display:none;">    
                            <div id="formattedOutput"></div>
                            <!-- <div class="sachin"></div> -->
                            <div class="errorMSG"></div>
                            <div class="score_btn"> 
                                <button type="button" class="next-btn" onclick="nextStep(3)">Next</button> 
                            </div>  
                        </div> 
                        
                        <div class="step saveSubmit-btn" id="step3" style="display:none;">
                            <input type="hidden" id="scoreVal" name="scoreVal">
                            <input type="hidden" id="totalQuestion" name="totalQuestion">
                            <div class="saveANS"><span id="saveAns">Save Answers</span></div> 
                            <input type="submit" value="Submit" id="submitAnswers" class="submit-button"> 
                        </div>

                        
                    </form>
                    <div class="successMSG"></div>
                </div> 
            </div>
        </div>
        <div class="stickySidBar">
            <div id="scoreContainer" style="display:none;">
                <p id="scoreMessage"></p>
            </div>
            <div id="scoreDisplay" class="score-display"></div>

            <video id="recordedVideo" controls style="display:none;"></video>
            <div id="cameraView"></div>
        </div>
    </div>   
    <?php
   // echo '<video id="recordedVideo" controls style="display:none;"></video>';

    return ob_get_clean();
}

add_shortcode('interview', 'interviewForm');


 
add_action('wp_ajax_submit_interview_form', 'submit_interview_form');
add_action('wp_ajax_nopriv_submit_interview_form', 'submit_interview_form');

function submit_interview_form() {
    global $wpdb;
    $name = sanitize_text_field($_POST['name']);
    $email = sanitize_email($_POST['email']);
    $experience = intval($_POST['experience']);
    $profileLng = sanitize_text_field($_POST['profileLngSl']);

    $scoreNmb = sanitize_text_field($_POST['scoreVal']);
    $tableName = $wpdb->prefix . 'interview_form';
    $query = $wpdb->insert(
        $tableName,
        array(
            'name' => $name,
            'email' => $email,
            'experience' => $experience,
            'profileLanguage' => $profileLng,
            'scoreQA' => $scoreNmb,
        )
    );
    if ($query) {
        wp_send_json_success('Thanks for submission, HR Team reach you soon !');
    } else {
        wp_send_json_error('Form submission failed.');
    }
 
    wp_die();
}
  

add_action('wp_ajax_frontend_Questions', 'frontend_Questions');
add_action('wp_ajax_nopriv_frontend_Questions', 'frontend_Questions');
function frontend_Questions(){
    global $wpdb; 
    $table_name = $wpdb->prefix . 'interview_questions'; 
    $searchVal = $_POST['technologyFind']; 
    $experience = $_POST['experienceFind'];

     //$query =  "SELECT * FROM $table_name WHERE technology LIKE '%".$searchVal."%'";
     $query = $wpdb->prepare("SELECT * FROM $table_name WHERE technology LIKE %s AND experience = %d", '%' . $wpdb->esc_like($searchVal) . '%', $experience);
    $results = $wpdb->get_results($query);
    $output = [];

    if($results) {
        foreach ($results as $data) {
            $data->question = unserialize($data->question);
            $data->options = unserialize($data->options);
            $data->correct_answer = unserialize($data->correct_answer);
            $data->correct_option = unserialize($data->correct_option);
            $output[] = $data;
        }
        echo json_encode($output);
    } else {
        echo json_encode(['error' => 'Questions are not found!']);
    }
    
    wp_die();
}
 
 