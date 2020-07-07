<?php

// hide all errors for the user
error_reporting(0);

// prepare server response
$response = [];
$response['status'] = 'error';

// FIXME: set your upload folder name for attachments,
// if this folder does not exists in your project it will be
// created automatically
$upload_folder_name = 'temp_uploads';

// uploaded file size limit
$max_file_size = 24;

// uploaded files count limit
$max_file_count = 5;

// delete uploaded files after upload, recommended option: true
$delete_uploaded_file_after_upload = true;

// this array will store uploaded files path
$temp_files_path = [];

// allowed files extensions
$allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'tif', 'webp', 'svg', 'doc', 'docx', 'zip', 'txt'];

// set default language to English
$lang = 'ru';

// choose to use PHPMailer with SMT or not
$use_smtp = false;

// set if an auto response should be sent
$auto_responder = true;

// array of strings
$info_strings = [];

// recaptcha settings
$use_recaptcha = true;

// FIXME: place here your SECRET KEY
define("RECAPTCHA_V3_SECRET_KEY", 'PLACE_HERE_YOUR_SECRET_KEY');

// English
$info_strings['en'] = [
    'title_success'     => ' Success ',
    'message_success'   => ' Thanks '.$_POST['name'].'!<br> Check your inbox.',
    'title_error'       => ' Error ',
    'message_error'     => ' Something’s wrong! Check form fields for errors and try again. ',
    'file_size_error'   => ' Max file size: ' . $max_file_size . ' MB.',
    'files_count_error' => ' Max files limit: ' . $max_file_count,
    'extension_error'   => ' file extension not allowed ',
    'required'          => ' field is required. ',
    'use_post'          => ' Please use a POST request. ',
    'phone_error'       => ' Please enter a valid phone number. ',
    'email_error'       => ' Please enter a valid e-mail address. ',
    'name_error'        => ' Please enter a valid name <br>(letters and whitespace only). ',
    'date_error'        => ' Please enter valid date. ',
    'url_error'         => ' Please provide a valid url. ',
    'captcha_error'     => ' You are a BOT! '
];

// Русский 
$info_strings['ru'] = [
    'title_success'         => ' Форма отправлена ',
    'message_success'       => ' Спасибо ' . $_POST['name'] .' Проверьте Вашу почту <br>(Вы получил копию на указании email). ',
    'message_success_phone' => ' Спасибо ' . $_POST['name'] .' Мы свяжемся с вами <br>в ближайшее время. ',
    'title_error'           => ' Ошибка ',
    'message_error'         => ' Чтото пошло не так. Попробуйте снова...  ',
    'file_size_error'       => ' Максимальный допустимый размер файла  ' . $max_file_size . ' МБ.',
    'files_count_error'     => ' Допустимое количество файлов: ' . $max_file_count,
    'extension_error'       => ' Файлы не допустимы. ',
    'required'              => ' Поле обязательно для заполнения. ',
    'use_post'              => ' Пожалуйста используйте POST запрос. ',
    'phone_error'           => ' Пожалуйста, введите правильный номер телефона. ',
    'email_error'           => ' Пожалуйста, введите действительный адрес электронной почты. ',
    'name_error'            => ' В поле имени допускаются только буквы и пробелы. ',
    'date_error'            => ' Пожалуйста, введите правильную дату. ',
    'url_error'             => ' Пожалуйста, введите корректный адрес URL. ',
    'captcha_error'         => ' Ты бот! '
];

function secure($var){
    if(!empty($var)) return stripslashes(htmlspecialchars(trim($var)));
    else return false;
}

function bytes_to_mb($size){
    if(!empty($size)) return $fileSize = number_format(round($size / 1024 / 1024,4), 1);
    return 0;
}

function get_domain_url($email){
    if(!empty($email) and filter_var($email, FILTER_VALIDATE_EMAIL))  return '//' .explode('@', $email)[1];
    else return '#';
}

function is_valid_date($entry_date){
    if( !empty($entry_date) ){
        if( preg_match('/\d{4}\.\d{2}.\d{2}/', $entry_date)    ||
            preg_match('/\d{4}\-\d{2}-\d{2}/', $entry_date)    ||
            preg_match('/\d{4}\\/\d{2}\\/\d{2}/', $entry_date) ||

            preg_match('/\d{2}\.\d{2}.\d{4}/', $entry_date)    ||
            preg_match('/\d{2}\-\d{2}-\d{4}/', $entry_date)    ||
            preg_match('/\d{2}\\/\d{2}\\/\d{4}/', $entry_date)
        ){
            return true;
        }
        else return false;
    }
    return false;
}

function is_valid_url($url){
    if(substr($url, 0, 7) == 'http://' || substr($url, 0, 8) == 'https://'){
        if(preg_match('%^(?:(?:https?|ftp)://)(?:\S+(?::\S*)?@|\d{1,3}(?:\.\d{1,3}){3}|(?:(?:[a-z\d\x{00a1}-\x{ffff}]+-?)*[a-z\d\x{00a1}-\x{ffff}]+)(?:\.(?:[a-z\d\x{00a1}-\x{ffff}]+-?)*[a-z\d\x{00a1}-\x{ffff}]+)*(?:\.[a-z\x{00a1}-\x{ffff}]{2,6}))(?::\d+)?(?:[^\s]*)?$%iu', $url)){
            return true;
        }
    }
    else{
        $url = 'http://' . $url;
        if(preg_match('%^(?:(?:https?|ftp)://)(?:\S+(?::\S*)?@|\d{1,3}(?:\.\d{1,3}){3}|(?:(?:[a-z\d\x{00a1}-\x{ffff}]+-?)*[a-z\d\x{00a1}-\x{ffff}]+)(?:\.(?:[a-z\d\x{00a1}-\x{ffff}]+-?)*[a-z\d\x{00a1}-\x{ffff}]+)*(?:\.[a-z\x{00a1}-\x{ffff}]{2,6}))(?::\d+)?(?:[^\s]*)?$%iu', $url)){
            return true;
        }
    }

    return false;
}

function recaptcha(){
    $token = $_POST['g-recaptcha-response'];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,"https://www.google.com/recaptcha/api/siteverify");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('secret' => RECAPTCHA_V3_SECRET_KEY, 'response' => $token)));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $rs = curl_exec($ch);
    curl_close($ch);
    $arrResponse = json_decode($rs, true);

    global $response;
    if( count($arrResponse["error-codes"]) > 0 ){
        $response['errors'][] = 'reCaptcha: '. $arrResponse["error-codes"][0];
    }

    if($arrResponse["success"] == '1' && $arrResponse["score"] >= 0.5)  return true;
    else return false;
}

// process only post requests
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // required fields
    $required_fields = ['name', 'email', 'phone', 'message'];

    // FIXME: set your fields names
    $phone_fields    = ['phone', 'tel', 'telephone'];                                         // phone number fields
    $email_fields    = ['email', 'mail', 'e-mail'];                                           // e-mail fields
    $name_fields     = ['name', 'first-name', 'last-name', 'username', 'login'];              // name fields
    $date_fields     = ['date', 'start-date', 'end-date'];                                    // date fields
    $url_fields      = ['url', 'link', 'website', 'profile', 'profile-url', 'web', 'social']; // url fields

    // excluded fields will not appear on received email
    $excluded_fields = ['agree', 'accept_terms', 'g-recaptcha-response'];

    // FIXME: validation patterns
    $name_pattern    = '/^[a-zA-Z ]*$/'; // only letters and white space allowed
    $email_pattern   = '/^[_a-z0-9-]+(.[_a-z0-9-]+)*@[a-z0-9-]+(.[a-z0-9-]+)*(.[a-z]{2,})$/i';

    // validate post data
    foreach ($_POST as $key => $value) {
        $value = secure($value);

        // check if required fields are not empty
        if( empty($value) and in_array($key, $required_fields) ){
            $response['errors'][] = strtolower($key . $info_strings[$lang]['required']);
        }

        if( !empty($value) ){
            // validate phone fields
            if( in_array($key, $phone_fields) ){
                $valid_number = filter_var($value, FILTER_SANITIZE_NUMBER_INT);

                if (strlen($valid_number) < 9 || strlen($valid_number) > 14) {
                    $response['errors'][] = strtolower($info_strings[$lang]['phone_error']);
                }
            }

            // validate email fields
            if( in_array($key, $email_fields) and !preg_match($email_pattern, $value) ){
                $response['errors'][] = strtolower($info_strings[$lang]['email_error']);
            }

            // validate name fields
            if( in_array($key, $name_fields) and !preg_match($name_pattern, $value) ){
                $response['errors'][] = strtolower($info_strings[$lang]['name_error']);
            }

            // validate date fields
            if( in_array($key, $date_fields) and !is_valid_date($value)){
                $response['errors'][] = strtolower($info_strings[$lang]['date_error']);
            }

            // validate url fields
            if( in_array($key, $url_fields) and !is_valid_url($value)){
                $response['errors'][] = strtolower($info_strings[$lang]['url_error']);
            }
        }
    }

    // recaptcha status check
    if( isset($_POST['g-recaptcha-response']) and $use_recaptcha and !recaptcha() ) {
        $response['status']        = 'error';
        $response['info']['title'] = $info_strings[$lang]['title_error'];
        $response['errors'][]      = strtolower($info_strings[$lang]['captcha_error']);
        print json_encode($response, JSON_FORCE_OBJECT);
        exit;
    }

    // if errors found return errors info
    if( isset($response['errors']) and count($response['errors']) > 0 ){
        $response['status']        = 'error';
        $response['info']['title'] = $info_strings[$lang]['title_error'];
        print json_encode($response, JSON_FORCE_OBJECT);
        exit;
    }
    else{
        require './PHPMailerAutoload.php';

        $mail = new PHPMailer;

        if($use_smtp){
            require './smpt-config.php';

            $mail->isSMTP();
            $mail->Host       = host;
            $mail->SMTPAuth   = true;
            $mail->Username   = email;
            $mail->Password   = password;
            $mail->SMTPSecure = encryption;
            $mail->Port       = port;
        }
        else{
            require './config.php';
        }

        $mail->setFrom(email, company_name);
        $mail->addAddress(recipient_email);
        $mail->addCC('romanvaskevich@gmail.com');
        $mail->isHTML(true);
        $mail->Subject    = subject;
        $mail->CharSet    = 'UTF-8';

        // prepare form data
        $form_data = '';

        // write form data
        foreach ($_POST as $key => $value){
            $value = secure($value);

            if( !in_array($key, $excluded_fields) and !empty($value) ){
                $form_data .= "<p><span>$value</span></p>";
                //$form_data .= "<p><strong>".ucfirst($key).":</strong> <span>$value</span></p>";
            }
        }

        //ADD 29/05/2020
        // set additional form information
        $_date    = date('d M Y');
        $_time    = date('H:i');
        $_user_ip = $_SERVER['REMOTE_ADDR'];


        $form_extended_info .= "<p><strong>Отправлено:</strong> <span>$_date | $_time</span></p>";
        $form_extended_info .= "<p><strong>IP клиента:</strong> <span>$_user_ip</span></p>";
        //ADD 29/05/2020

        // validate uploaded files
        if(count($_FILES) > 0){
            foreach ($_FILES as $key => $value) {
                if(!empty($value['name'][0])){
                    if( count($value['name']) > $max_file_count ){
                        $response['status']        = 'error';
                        $response['info']['title'] = $info_strings[$lang]['title_error'];
                        $response['errors'][]      = strtolower($info_strings[$lang]['files_count_error']);
                    }

                    foreach ($value['size'] as $size_key => $item_size) {
                        $file_size = bytes_to_mb($item_size);

                        if( $file_size > $max_file_size) {
                            $response['status']        = 'error';
                            $response['info']['title'] = $info_strings[$lang]['title_error'];
                            $response['errors'][]      = "<strong>".$value['name'][$size_key]."</strong> | <strong>$file_size MB</strong>. <br>" . $info_strings[$lang]['file_size_error'];
                        }
                    }

                    foreach ($value['name'] as $name_key => $item_name) {
                        $file_extension = pathinfo($item_name, PATHINFO_EXTENSION);

                        if( empty($item_name) or !in_array($file_extension, $allowed_extensions)){
                            $response['status']        = 'error';
                            $response['info']['title'] = $info_strings[$lang]['title_error'];
                            $response['errors'][]      = $file_extension . " " . strtolower($info_strings[$lang]['extension_error']);
                        }
                    }
                }
            }
        }

        // if errors found return errors info
        if( isset($response['errors']) and count($response['errors']) > 0 ){
            $response['status']        = 'error';
            $response['info']['title'] = $info_strings[$lang]['title_error'];
            print json_encode($response, JSON_FORCE_OBJECT);
            exit;
        }
        else{
            if(count($_FILES) > 0){
                foreach ($_FILES as $key => $value) {
                    foreach ($value['name'] as $name_key => $item_name) {
                        $path = $upload_folder_name . '/' . uniqid() . '-' .date("yy-m-d") . '-' . $item_name;

                        if (!file_exists($upload_folder_name)) {
                            mkdir($upload_folder_name, 0777, true);
                        }

                        if(move_uploaded_file($value['tmp_name'][$name_key], $path)){
                            $mail->addAttachment($path);
                            $temp_files_path[] = $path;
                        }
                    }
                }
            }
        }

        // add form data to email template
        $content    = file_get_contents(__DIR__.'/email-template.html');
        $content    = str_replace('{%SUBJECT%}', subject, $content);
        $content    = str_replace('{%FORM_DATA%}', $form_data, $content);
        $content    = str_replace('{%FORM_EXTENDED_INFO%}', $form_extended_info, $content);
        $content    = str_replace('{%IP%}', $_SERVER['REMOTE_ADDR'], $content);
        $mail->Body = $content;

        if(!$mail->send()) {
            $response['status']          = 'error';
            $response['info']['title']   = $info_strings[$lang]['title_error'];
            $response['errors'][]        = 'Mailer Error: ' . $mail->ErrorInfo;
            print json_encode($response, JSON_FORCE_OBJECT);
        }
        else {
            if($auto_responder and filter_var(secure($_POST['email']), FILTER_VALIDATE_EMAIL)){
                $autoRespond = new PHPMailer();

                if($use_smtp){
                    $autoRespond->IsSMTP();
                    $autoRespond->CharSet    = 'UTF-8';
                    $autoRespond->SMTPDebug  = 0;
                    $autoRespond->SMTPAuth   = true;
                    $autoRespond->SMTPSecure = encryption;
                    $autoRespond->Port       = port;
                    $autoRespond->Username   = email;
                    $autoRespond->Password   = password;
                    $autoRespond->Host       = host;
                }

                $autoRespond->CharSet  = 'UTF-8';
                $autoRespond->isHTML(true);
                $autoRespond->setFrom(email, company_name);
                $autoRespond->addAddress(secure($_POST['email']));
                $autoRespond->Subject = auto_responder_subject;
                $autoRespond->Body    = file_get_contents(__DIR__.'/autoresponder-email-template.html');

                $autoRespond->send();

                $autoRespond->clearAttachments();
                $autoRespond->clearAddresses();
            }

            $response['status']          = 'success';
            $response['info']['title']   = $info_strings[$lang]['title_success'];
            $response['info']['message'] = $info_strings[$lang]['message_success_phone'];
            print json_encode($response, JSON_FORCE_OBJECT);
        }

        $mail->clearAddresses();
        $mail->clearAttachments();

        // delete uploaded files after submit if $delete_uploaded_file_after_upload = true
       if( count($temp_files_path) > 0 and $delete_uploaded_file_after_upload ) {
           foreach ($temp_files_path as $file_path) {
               if( file_exists($file_path) ) unlink($file_path);
           }
       }
    }
}
else {
    // direct access of this file will trow an use POST error
    $response['status']         = 'error';
    $response['info']['title']  = $info_strings[$lang]['title_error'];
    $response['errors']         = $info_strings[$lang]['use_post'];
    print json_encode($response, JSON_FORCE_OBJECT);
}

















