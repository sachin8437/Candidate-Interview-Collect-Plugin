jQuery(document).ready(function($) {
    $(window).on('load', function() {
        $('#popupModal').show(); 
    });

    $('.modal .close').on('click', function() {
        $('#popupModal').hide();  
    }); 

    if( /Android|iPhone|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ) {
        alert("Kindly use a desktop device to complete the interview process !");
       }

       
});


/******* working code start  ***************/
let mediaRecorder;
let recordedChunks = [];
let cameraStream;
let screenStream;
let canvasStream;

const startRec = document.getElementById('startRecording');
const stopRec = document.getElementById('stopRecording');
const submitBtn = document.getElementById("submitAnswers");
const cameraView = document.getElementById('cameraView');
const customAlt = document.getElementById('custom_alt');
const recordingForm = document.getElementById('recordingForm');
let tabOrAppChanged = false;

if (startRec) {
    submitBtn.style.display = 'none';
    stopRec.style.display = 'none';
    
    startRec.addEventListener('click', async () => {
        alert('Please allow the permission of camera and entire screen sharing!');
        try {
            jQuery('.removeHead').hide(); 
            const hasScreenCapture = 'getDisplayMedia' in navigator.mediaDevices;
            const hasCameraCapture = 'getUserMedia' in navigator.mediaDevices;

            if (!hasCameraCapture) {
                alert('Camera recording is not supported on your device.'); 
                return;
            }

            if (!hasScreenCapture) {
                console.warn('Screen recording is not supported on your device. Proceeding with camera recording only.'); 
            }  

            [cameraStream, screenStream] = await Promise.all([
                 navigator.mediaDevices.getUserMedia({ video: true }),
                 navigator.mediaDevices.getDisplayMedia({ video: { cursor: "always", displaySurface: "monitor",  } }) // mediaSource: 'browser',
            ]);

            const cameraVideoElement = createVideoElement(cameraStream);
            const screenVideoElement = createVideoElement(screenStream);

            const canvas = document.createElement('canvas');
            const context = canvas.getContext('2d');

            // Wait for the screen video stream to load metadata (dimensions)
            screenVideoElement.onloadedmetadata = () => {
                canvas.width = screenVideoElement.videoWidth;
                canvas.height = screenVideoElement.videoHeight;

                const fragment = document.createDocumentFragment();
                fragment.appendChild(canvas);
                cameraView.appendChild(fragment);

                drawFrame(context, screenVideoElement, cameraVideoElement, canvas);

                canvasStream = canvas.captureStream(30); // Capture the canvas stream at 30fps
                startMediaRecording(canvasStream);
                toggleRecordingUI(true);
            };

            screenVideoElement.play();
            cameraVideoElement.play(); 
           
            /*************************** */


                var visited = 0;
                var $notify = $('#recordingForm');

                $(window).on('blur focus', function(e) {
                var prevType = $(this).data('prevType');
                
                //  reduce double fire issues
                if (prevType != e.type) {
                    switch (e.type) {
                    case 'blur':
                        $notify.append('<div class="alert alert-warning">Don\'t change the tab or switch to another application! (User left this browser tab)</div>');
                        
                        console.log('User left this browser tab.');
                        break;
                        
                    case 'focus':
                        var msg = '<div class="alert alert-success">Please don\'t change tabs, and don\'t open any other applications.</div>';
                        if(visited > 0){
                        var addS = visited == 1 ? '' : 's';
                        msg = '<div class="alert alert-info">You have switched screens or applications, so the interview is closed. This Browser Tab :: ' + visited + ' time' + addS + '</div>';
                        stopMediaRecording();
                       // sendRejectedEmail(); 
                        location.reload();  
                        }
                        
                        $notify.append(msg);
                        visited++;
                        
                        console.log('User is viewing this browser tab.');
                        break;
                    }
                }
                
                $(this).data('prevType', e.type);
                });

            /***************************** */
        } catch (error) {
            console.error('Error accessing media:', error);
            handleMediaAccessError(error);
            customAlt.style.display = 'block';
            setTimeout(() => jQuery(customAlt).hide(), 4000);
        }
    });

    stopRec.addEventListener('click', () => {
        stopMediaRecording();
        cleanUpRecordingUI();
        jQuery("#overlay").show();　
    });
}

function createVideoElement(stream) {
    const videoElement = document.createElement('video');
    videoElement.srcObject = stream;
    videoElement.autoplay = true;
    videoElement.muted = true;
    return videoElement;
}

function drawFrame(context, screenVideo, cameraVideo, canvas) {
    const draw = () => {
        context.clearRect(0, 0, canvas.width, canvas.height);
        context.drawImage(screenVideo, 0, 0, canvas.width, canvas.height);
        context.drawImage(cameraVideo, 0, 0, 320, 240); // Position and size for the camera feed
        requestAnimationFrame(draw);
    };
    draw();
}

function startMediaRecording(stream) {
    mediaRecorder = new MediaRecorder(stream);
    if (mediaRecorder) {
        recordingForm.style.display = 'block';
        startRec.style.display = 'none';
    }
    mediaRecorder.ondataavailable = (event) => {
        if (event.data.size > 0) {
            recordedChunks.push(event.data);
        }
    };

    mediaRecorder.onstop = () => {
        const blob = new Blob(recordedChunks, { type: 'video/webm' });
        const url = URL.createObjectURL(blob);
        const video = document.getElementById('recordedVideo');
        video.src = url;
        video.style.display = 'block';
        recordedChunks = []; 
        saveRecording(blob);
    };

    mediaRecorder.start();
}

function stopMediaRecording() {
    if (mediaRecorder && mediaRecorder.state !== 'inactive') {
        mediaRecorder.stop();
    }

    [cameraStream, screenStream, canvasStream].forEach(stream => {
        if (stream && stream.active) {
            stream.getTracks().forEach(track => track.stop());
        }
    });
}

function cleanUpRecordingUI() {
    document.querySelectorAll('#cameraView video, #cameraView canvas').forEach(element => {
        if (element.tagName === 'VIDEO') {
            element.pause();
            element.srcObject = null;
        }
        element.remove();
    }); 
    toggleRecordingUI(false);
}

function toggleRecordingUI(isRecording) {
    if (isRecording) {
        startRec.classList.add('hidden');
        stopRec.classList.remove('hidden');
        jQuery(stopRec).addClass('activeSRec');
    } else {
        startRec.classList.remove('hidden');
        stopRec.classList.add('hidden');
    }
}
/*
 
function sendRejectedEmail() {
    const nameS = document.getElementById('name').value;
    const emailS = document.getElementById('email').value;
    const profileS = document.getElementById('selectedValue').value;

    const formData = new FormData();
    formData.append('action', 'sr_send_rejected_email');
    formData.append('name', nameS);
    formData.append('email', emailS);
    formData.append('profile', profileS);

    jQuery.ajax({
        url: sr_ajax_object.ajax_url,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
            console.log('Rejected email sent successfully.');
        },
        error: function (response) {
            console.error('Error sending rejected email: ', response);
        }
    });
}
*/
function saveRecording(blob) {
    const nameS = document.getElementById('name').value;
    const emailS = document.getElementById('email').value;
    const scoreS = document.getElementById('scoreVal').value;
    const totalQst = document.getElementById('totalQuestion').value;
    const profileS = document.getElementById('selectedValue').value;

    const formData = new FormData();
    formData.append('action', 'sr_save_recording');
    formData.append('video_blob', blob, 'recording.webm');
    formData.append('name', nameS);
    formData.append('email', emailS);
    formData.append('score', scoreS);
    formData.append('totalQst', totalQst);
    formData.append('profile', profileS);

    jQuery.ajax({
        url: sr_ajax_object.ajax_url,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
            submitBtn.disabled = true;
            cameraView.style.display = 'none';
             
            setTimeout(() => {
                jQuery("#overlay").hide();
                alert('Thank you for the interview. HR Team will reach you soon!');
                location.reload();  
            }, 3000);  
        },
        error: function (response) {
            console.error('Error saving video: ', response);
        }
    });
}

function handleMediaAccessError(error) {
    let errorMessage = 'Error accessing media: ';
    if (error.name === 'NotFoundError' || error.name === 'DevicesNotFoundError') {
        errorMessage += 'No camera or screen found on your device. Please check your settings and try again.';
    } else if (error.name === 'NotAllowedError' || error.name === 'PermissionDeniedError') {
        errorMessage += 'Permission to access media was denied. Please allow access and try again.';
    } else if (error.name === 'NotReadableError' || error.name === 'TrackStartError') {
        errorMessage += 'Media device is not available or is already in use. Please check if another application is using the device.';
    } else {
        errorMessage += error.message;
    }
    alert(errorMessage);
     jQuery('.removeHead').show();
}

    /******* working code end ***************/
    
    function nextStep(stepNumber) {
        if (!validateCurrentStep()) {
            return; // Stop if validation fails
        }

        var currentStep = document.querySelector('.step:not([style*="display: none"])');
        var nextStep = document.getElementById('step' + stepNumber);

        if (currentStep) {
            currentStep.style.display = 'none';
        }

        if (nextStep) {
            nextStep.style.display = 'block';
        }
    }

    function validateCurrentStep() {
        var currentStep = document.querySelector('.step:not([style*="display: none"])');
        let isValid = true;
        
        if (currentStep.id === 'step1') {
            // Validate step 1 fields
            document.querySelectorAll('#step1 .error').forEach(error => error.textContent = '');

            const name = document.getElementById('name').value;
            if (!name) {
                document.getElementById('nameError').textContent = 'Name is required';
                isValid = false;
            } 

            const email = document.getElementById('email').value;
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!email) {
                document.getElementById('emailError').textContent = 'Email is required';
                isValid = false;
            } else if (!emailPattern.test(email)) {
                document.getElementById('emailError').textContent = 'Please enter a valid email address';
                isValid = false;
            }
 
            const experience = document.getElementById('experience').value;
            if (experience === 'Select') {
                document.getElementById('experienceError').textContent = 'Experience is required';
                isValid = false;
            }

            const profile = document.getElementById('profileLng').value;
            if (profile === 'Select Profile') {
                document.getElementById('profileError').textContent = 'Please select your profile.';
                isValid = false;
            }
        }  
        
        return isValid;
    }
 
    function validateForm() {
        let isValid = true; 
        document.querySelectorAll('.error').forEach(error => error.textContent = '');
        if (!validateCurrentStep()) {
            isValid = false;
        } 
        return isValid;
    } 

    var interviewForm =  document.getElementById('dynamicForm');
    if(interviewForm){
        interviewForm.addEventListener('submit', function(event) {
            event.preventDefault();
            const isValid = validateForm();
            if (isValid) {
                const candidateData = new FormData(this);
                candidateData.append('action', 'submit_interview_form');
               // console.log(candidateData);

                jQuery.ajax({
                    url: sr_ajax_object.ajax_url,
                    type: 'POST',
                    data: candidateData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        //console.log('form saved successfully: ', response);
                        jQuery('.successMSG').html(response.data);
                        submitBtn.style.display = 'none';
                         jQuery('.saveANS').hide();
                         document.getElementById('stopRecording').style.display = 'block !important';  
                        jQuery('#stopRecording').removeClass('activeSRec');
                        jQuery(stopRec).addClass('activeSR');
                        jQuery('#stopRecording').show();
                    },
                    error: function(xhr, status, error) {
                        console.error('Error saving form: ', error);
                    }
                });
            }
        }); 
    }


 /*************************
  * 
  *  Check the question and answer score accoding the candidate answers !
  * 
  ************************/
 document.getElementById('profileLng').addEventListener('change', function() { 
    const selectElement = document.getElementById('profileLng');
    const selectedValue = selectElement.value;
    document.getElementById('selectedValue').value = selectedValue;
});
// document.getElementById("saveAns").disabled = true; 
jQuery(document).ready(function($) {
   // console.clear();
 
    jQuery('#profileLng, #experience').on('change', function() {
       // var currentVal = jQuery(this).val();
        var currentTechnology = jQuery('#profileLng').val();
        var currentExperience = jQuery('#experience').val();

      if (currentTechnology && currentExperience) { 
        jQuery.ajax({
            url: sr_ajax_object.ajax_url,
            type: 'POST',
            data: { action: 'frontend_Questions', 'technologyFind': currentTechnology, 'experienceFind': currentExperience },
            success: function(response) {
                // if(data.error == 'Questions are not found!'){
                //     jQuery('.errorMSG').html(data.error);
                //     setTimeout(() => jQuery('.errorMSG').hide(), 4000); 
                // }
                try {
                    var data = JSON.parse(response);
                    if (data.error == 'Questions are not found!') {
                        jQuery('.errorMSG').html(data.error).show();
                        setTimeout(() => jQuery('.errorMSG').hide(), 4000); 
                        return; // Exit the function as there is no need to process further
                    } else {

                    jQuery('#formattedOutput').empty();

                    correctAnswers = {}; 
                    var questionCounter = 1;
                    var questionHtml = '';
                    var totalQuestions = 0;
                     
                    data.forEach(function(item) {
                        totalQuestions += item.question.length;
                        
                    });

                    data.forEach(function(item) {
                        item.question.forEach(function(question, index) {
                            let formattedOutput = '<div class="question-item question-' + questionCounter + '">' +
                                '<label>Question ' + questionCounter + ':</label> ' + question + '</div>' +
                                '<div class="Options-' + questionCounter + '">';

                            item.options[index].forEach(function(option, optIndex) {
                                formattedOutput += '<div class="qst-option"><input type="radio" name="question' + questionCounter + '" class="opt-' + optIndex + '" id="question' + questionCounter + '_option' + optIndex + '" value="' + option + '" />\n' +
                                    '<label for="question' + questionCounter + '_option' + optIndex + '">' + option + '</label><br></div>';
                            });

                            formattedOutput += '</div>';
                            /*
                            formattedOutput += 'Answer: ' + item.correct_answer[index] + '\n\n' +
                                'Correct Option: Option ' + item.correct_option[index] + '\n';*/
                            
                            if (questionCounter < item.question.length) {
                                formattedOutput += '<button type="button" class="next-question" data-question="' + questionCounter + '">Next Question</button>'; 
                            }

                            correctAnswers[questionCounter] = item.correct_answer[index];
                             
                            questionHtml += '<div class="question-items question-' + questionCounter + '" style="display:none;">' + formattedOutput + '</div>';
                            questionCounter++;
                        });
                    });

                    jQuery('.sachin').append(questionCounter);
                    jQuery('#formattedOutput').append(questionHtml);

                    jQuery('.question-items.question-1').show();

                    jQuery('#formattedOutput').on('click', '.next-question', function() {
                        var currentQuestion = jQuery(this).data('question');
                        var nextQuestion = currentQuestion + 1;

                        if (!$('input[name="question' + currentQuestion + '"]:checked').length) { 
                            jQuery('.errorMSG').html('Please select an option before proceeding to the next question.'); 
                            return;
                        }

                        jQuery('.question-items.question-' + currentQuestion).hide();

                        jQuery('.question-items.question-' + nextQuestion).show();

                        if (nextQuestion === totalQuestions) {
                            jQuery('#totalQuestion').val(totalQuestions);
                            jQuery('.score_btn').show();
                            jQuery('#step3').show();
                            jQuery('.next-btn').hide();
                        }
                    });
                    }
                } catch (e) {
                    console.error('Error parsing response:', e);
                    jQuery('.errorMSG').html('Questions Blank !!!');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error saving form:', error); 
            }
        });
      }else {
        jQuery('.errorMSG').html('All fields are requied !');
      }
    });
});
 
function calculateScoreNew() {
    let score = 0;
    let totalQuestions = 0;
  
    // Iterate through each question item
    var count =1;
    jQuery('#formattedOutput').find('.question-items').each(function(index) {
       
        const selectedOption = jQuery('input[name="question' + count + '"]:checked').val();
        
        if (selectedOption !== undefined) {
            const correctOptionValue = correctAnswers[count];
            
            if (selectedOption === correctOptionValue) {
                score++;
            }
            totalQuestions++;
        }
        count++;
    });
    document.getElementById('scoreVal').value = score;
   // alert('Score is: ' + score );
}

jQuery(document).ready(function($) {
    jQuery('.saveANS').on('click', function() {
        calculateScoreNew();
        jQuery('.score_btn').show();
        submitBtn.style.display = 'block';
        
        document.getElementById("saveAns").style.color = 'green';
        jQuery('.saveSubmit-btn').addClass('savedSubmit');
    });
});

function unserialize(data) {
    if (typeof data !== 'string') {
      //  console.error('Data is not a string:', data);
        return data;
    }
    try {
        return JSON.parse(data.replace(/a:\d+:{/g, '[')
                              .replace(/i:\d+;/g, '')
                              .replace(/s:\d+:"/g, '"')
                              .replace(/";/g, '",')
                              .replace(/};/g, '],')
                              .replace(/}/g, ']')
                              .replace(/{/g, '[')
                              .replace(/,]/g, ']'));
    } catch (e) {
        console.error('Error unserializing data:', e);
        return null;
    }
}
 