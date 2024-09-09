<?php  
/*****************
 * 
 * Get all wordpress questions based on experience
 * 
 */

function get_WP_Questions(){
    
    global $wpdb; 
    $table_name = $wpdb->prefix . 'interview_questions'; 
    $results = $wpdb->get_results("SELECT * FROM $table_name");
    
    ?>

    <div class='main-questionlist acc-container'>  
    <?php
    if($results > 0 ){ 
        
        foreach ($results as $data) {
             if($data->technology == 'wordpress'){
                $questions = unserialize($data->question);
                $options = unserialize($data->options);
                $correct_answers = unserialize($data->correct_answer);
                $correct_options = unserialize($data->correct_option);
                ?>
                <div class='lists-QA acc'> 
                    <div class="acc-head">
                        <?php echo '<h2><span class="head-text">Technology</span> : ' . esc_html($data->technology) . ' ( <span class="cnd-exp">Experience: ' . esc_html($data->experience) . '</span> )</h2>'; ?>
                    </div>

                    <div class='items acc-content'>
                        <div class="all-questions">
                        <?php     
                        $count = 1;
                        foreach ($questions as $index => $question) { 
                            $class = $count > 4 ? 'hidden-question' : '';
                            echo "<div class='item-list {$class}'>";
                                echo '<p><strong>Question: ' .$count. '</strong>  '. esc_html($question) . '</p>';
                                
                                // Display options 
                                echo '<p><strong>Options:</strong></p>';
                                echo '<ul>';
                                foreach ($options[$index] as $option) {
                                    echo '<li>' . esc_html($option) . '</li>';
                                }
                                echo '</ul>'; 
        
                                echo '<p><strong>Answer:</strong> ' . esc_html($correct_answers[$index]) . '</p>';
                                    
                                echo '<p><strong>Correct Option:</strong> Option ' . esc_html($correct_options[$index]) . '</p>'; 
        
                                echo '<button onclick="showUpdateForm(' . $data->id . ',' . $index . ')">Update</button>';
                                
                                echo '<div id="update-form-' . $data->id . '-' . $index . '" class="update-form" style="display:none;">';
                                echo '<form method="post" action="">';
                                echo '<input type="hidden" name="question_id" value="' . $data->id . '">';
                                echo '<input type="hidden" name="question_index" value="' . $index . '">';
                                echo '<label for="update_question">Question:</label>';
                                echo '<input type="text" name="update_question" value="' . esc_html($question) . '">';
                                echo '<label for="update_options">Options (comma separated):</label>';
                                echo '<input type="text" name="update_options" value="' . esc_html(implode(',', $options[$index])) . '">';
                                echo '<label for="update_answer">Answer:</label>';
                                echo '<input type="text" name="update_answer" value="' . esc_html($correct_answers[$index]) . '">';
                                echo '<label for="update_correct_option">Correct Option:</label>';
                                echo '<input type="text" name="update_correct_option" value="' . esc_html($correct_options[$index]) . '">';
                                echo '<input type="submit" name="submit_update" value="Update">';
                                echo '</form>';
                                echo '</div>';
                            echo "</div>";
                            $count++;
                        } 
                        ?>
                        </div>
                        <?php if (count($questions) > 4) : ?>
                            <button class="load-more" data-tech-id="<?php echo esc_attr($data->id); ?>">Load More</button>
                        <?php endif; ?>
                    </div> 
                </div> 
                <?php
            }
        }
    } else {
        echo "<div class='error'> Record not found !</div>";
    }
    ?>
    </div> 
    <?php
}

?>