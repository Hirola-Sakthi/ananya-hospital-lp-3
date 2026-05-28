<?php
ob_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
file_put_contents('debug_log.txt', "Reached PHP script at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

include('database.inc.php');

session_start();
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json');

$response = [
    'status' => 'error',
    'message' => '',
];

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $Name        = trim(mysqli_real_escape_string($con, $_POST['name']    ?? ''));
    $Email       = trim(mysqli_real_escape_string($con, $_POST['email']   ?? ''));
    $Phonenumber = trim(mysqli_real_escape_string($con, $_POST['phone']   ?? ''));
    $Concern     = trim(mysqli_real_escape_string($con, $_POST['concern'] ?? ''));
    $Message     = trim(mysqli_real_escape_string($con, $_POST['message'] ?? ''));

    $error_msg = "";
    $phone_err = "";

    if (empty($Name)) {
        $error_msg .= 'Name is required ';
    }
    if (empty($Phonenumber)) {
        $phone_err .= 'Phone number is required ';
    }
    if (empty($Email)) {
        $error_msg .= 'Email is required ';
    }
    if ($Concern === 'Choose a concern' || empty($Concern)) {
        $error_msg .= 'Please select a valid concern ';
    }

    $cleanedPhone = preg_replace('/[^0-9]/', '', $Phonenumber);
    if (strlen($cleanedPhone) < 10 || strlen($cleanedPhone) > 15) {
        $phone_err .= 'Enter a valid Mobile Number ';
    } else {
        $Phonenumber = $cleanedPhone;
    }

    $email_exp = '/^[A-Za-z0-9._%-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,4}$/';
    if (!preg_match($email_exp, $Email)) {
        $error_msg .= 'Please Enter a valid Email Address ';
    } else {
        $cleanedEmail = str_replace(' ', '', $Email);
        if (!filter_var($cleanedEmail, FILTER_VALIDATE_EMAIL)) {
            $error_msg .= 'Invalid email address ';
        } else {
            $Email = $cleanedEmail;
        }
    }

    if (empty($error_msg) && empty($phone_err)) {

        // --- Save to DB ---
        $query = "INSERT INTO ananya_hospital_contact_form (Name, PhoneNumber, Email, Concern, Message) 
                  VALUES ('$Name', '$Phonenumber', '$Email', '$Concern', '$Message')";

        if (mysqli_query($con, $query)) {
            mysqli_close($con);

            $fromEmail = 'queries@ananyahospitals.online';
            $fromName  = 'Ananya Hospital';

            $commonHeaders  = "MIME-Version: 1.0\r\n";
            $commonHeaders .= "Content-Type: text/html; charset=UTF-8\r\n";
            $commonHeaders .= "From: $fromName <$fromEmail>\r\n";

            // --- Mail to hospital ---
            $adminBody = "
                <b>Name:</b> $Name <br>
                <b>Phone Number:</b> $Phonenumber <br>
                <b>Email:</b> $Email <br>
                <b>Concern:</b> $Concern <br>
                <b>Message:</b> $Message <br>
            ";
            $adminHeaders  = $commonHeaders;
            $adminHeaders .= "Reply-To: $Email\r\n";

            $adminSent  = mail($fromEmail, 'New Ananya Hospital Orthopaedic Inquiry', $adminBody, $adminHeaders);
            $adminSent2 = mail('ravimaddur06@gmail.com', 'New Ananya Hospital Orthopaedic Inquiry', $adminBody, $adminHeaders);
            $adminSent3 = mail('social@ananyahospitals.in', 'New Ananya Hospital Orthopaedic Inquiry', $adminBody, $adminHeaders);
            // --- Confirmation mail to user ---
            $userBody = "
                Hi <b>$Name</b>,<br><br>
                Thank you for reaching out to <b>Ananya Hospital</b>.<br>
                We have received your inquiry and will get back to you shortly.<br><br>
                Regards,<br>
                Ananya Hospital Team
            ";
            $userHeaders  = $commonHeaders;

            mail($Email, 'Thank You for Contacting Us', $userBody, $userHeaders);

            if ($adminSent || $adminSent2 || $adminSent3) {
               http_response_code(200);
               $response['status']  = 'success';
               $response['message'] = 'Form Submitted Successfully';
            } else {
               http_response_code(200);
               $response['status']  = 'success';
               $response['message'] = 'Form Submitted. Email notification may be delayed.';
            }

            ob_clean();
            echo json_encode($response);
            exit();

        } else {
            http_response_code(500);
            $response['message'] = 'Database error: ' . mysqli_error($con);
            ob_clean();
            echo json_encode($response);
            exit();
        }

    } else {
        http_response_code(400);
        $response['errors'] = ['name' => $error_msg, 'tel' => $phone_err];
        ob_clean();
        echo json_encode($response);
        exit();
    }

} else {
    http_response_code(405);
    $response['message'] = 'Invalid Request Method';
    ob_clean();
    echo json_encode($response);
    exit();
}