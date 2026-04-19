<?php
session_start();
require_once 'config.php'; 

// --- REGISTRATION LOGIC ---
if (isset($_POST['register'])) {
    $full_name = mysqli_real_escape_string($conn, $_POST['name']);
    $nic = mysqli_real_escape_string($conn, $_POST['nic']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $dept = mysqli_real_escape_string($conn, $_POST['dept']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        header("Location: index.php?error=password_mismatch");
        exit();
    }

    $check_user = "SELECT user_id FROM users WHERE nic = '$nic' OR email = '$email'";
    $result = mysqli_query($conn, $check_user);

    if (mysqli_num_rows($result) > 0) {
        header("Location: index.php?error=user_exists");
    } else {
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        $user_lang = $_SESSION['lang'] ?? 'en';

        $query = "INSERT INTO users (full_name, nic, email, department, role, password_hash, lang) 
                  VALUES ('$full_name', '$nic', '$email', '$dept', '$role', '$password_hash', '$user_lang')";
        
        if (mysqli_query($conn, $query)) {
            // --- SIMULATED OTP LOGIC ---
            $otp_code = rand(100000, 999999);
            $_SESSION['temp_otp'] = $otp_code;
            $_SESSION['temp_email'] = $email;

            // Email ekak yawana simulation ekak widiyata alert ekak pennala redirect karanawa
            echo "<script>
                alert('SECURITY ALERT: A verification code has been sent to $email.\\n\\nYour Demo OTP Code is: $otp_code');
                window.location.href = 'otp_verify.php?email=" . urlencode($email) . "';
            </script>";
            exit(); 
        } else {
            header("Location: index.php?error=registration_failed");
        }
    }
    exit();
}

// --- OTP VERIFICATION LOGIC ---
if (isset($_POST['verify_otp'])) {
    $entered_otp = implode('', $_POST['otp']); 
    $stored_otp = $_SESSION['temp_otp'] ?? '';

    if ($entered_otp == $stored_otp) {
        unset($_SESSION['temp_otp']);
        unset($_SESSION['temp_email']);
        header("Location: index.php?success=registered");
    } else {
        header("Location: otp_verify.php?error=invalid_otp");
    }
    exit();
}

// --- LOGIN LOGIC ---
if (isset($_POST['login'])) {
    $nic = mysqli_real_escape_string($conn, $_POST['nic']);
    $password_input = $_POST['password'];

    $query = "SELECT * FROM users WHERE nic = '$nic'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);
        if (password_verify($password_input, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['user_id']; 
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['department'] = $user['department']; 
            $_SESSION['lang'] = $user['lang']; 

            if ($user['role'] === 'admin') {
                header("Location: admin_dashboard.php");
            } elseif ($user['role'] === 'supervisor') {
                header("Location: supervisor_dashboard.php");
            } else {
                header("Location: dashboard.php");
            }
            exit();
        } else {
            header("Location: index.php?error=wrong_password");
        }
    } else {
        header("Location: index.php?error=user_not_found");
    }
    exit();
}
?>
