<?php
session_start();
require_once 'config.php'; // Ensure this file defines $conn

// --- REGISTRATION LOGIC ---
if (isset($_POST['register'])) {
    // Sanitize inputs
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $nic = mysqli_real_escape_string($conn, $_POST['nic']);
    $email = mysqli_real_escape_string($conn, $_POST['email']); // Added email
    $factory = mysqli_real_escape_string($conn, $_POST['factory']);
    $dept = mysqli_real_escape_string($conn, $_POST['dept']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    
    // Hash the password
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    
    // Capture the current language the user is viewing
    $user_lang = $_SESSION['lang'] ?? 'en';

    // Check if NIC or Email already exists
    $check_user = "SELECT user_id FROM users WHERE nic = '$nic' OR email = '$email'";
    $result = mysqli_query($conn, $check_user);

    if (mysqli_num_rows($result) > 0) {
        header("Location: index.php?error=user_exists");
    } else {
        // Updated INSERT query with email
        $query = "INSERT INTO users (full_name, nic, email, factory, department, role, password_hash, lang) 
                  VALUES ('$full_name', '$nic', '$email', '$factory', '$dept', '$role', '$password', '$user_lang')";
        
        if (mysqli_query($conn, $query)) {
            header("Location: index.php?success=registered");
        } else {
            header("Location: index.php?error=registration_failed");
        }
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
            
            // 1. SET SESSION DATA
            $_SESSION['user_id'] = $user['user_id']; 
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['department'] = $user['department']; // IMPORTANT for Supervisor filtering
            
            // 2. AUTO-LOAD LANGUAGE
            $_SESSION['lang'] = $user['lang']; 

            // 3. ROLE-BASED REDIRECTION
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