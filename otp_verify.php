<?php
session_start();
require_once 'lang.php';

// OTP එක යවපු email එක session එකෙන් ගන්නවා. නැත්නම් redirect කරනවා.
if (!isset($_SESSION['temp_email'])) {
    header("Location: index.php");
    exit();
}

$target_email = $_SESSION['temp_email'];
$error_msg = "";

if (isset($_GET['error']) && $_GET['error'] == 'invalid_otp') {
    $error_msg = "The code you entered is incorrect. Please try again.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Identity | Smart Budget</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #3b82f6;
            --bg-dark: #0f172a;
            --card-glass: rgba(30, 41, 59, 0.75);
            --border-glass: rgba(255, 255, 255, 0.1);
            --text-main: #f8fafc;
            --text-dim: #94a3b8;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-dark); color: var(--text-main); min-height: 100vh; display: flex; align-items: center; justify-content: center; }

        /* Background Glow */
        .glow { position: fixed; width: 450px; height: 450px; border-radius: 50%; filter: blur(120px); z-index: -1; opacity: 0.2; }
        .glow-1 { top: -100px; left: -100px; background: var(--primary); }

        .verify-card {
            background: var(--card-glass); backdrop-filter: blur(30px);
            border: 1px solid var(--border-glass); border-radius: 32px;
            padding: 45px; width: 100%; max-width: 440px; text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.6);
            animation: slideIn 0.6s ease-out;
        }

        @keyframes slideIn { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }

        .icon-circle {
            width: 75px; height: 75px; background: rgba(59, 130, 246, 0.1);
            border-radius: 22px; display: flex; align-items: center; justify-content: center;
            margin: 0 auto 25px; font-size: 2rem; color: var(--primary);
            border: 1px solid rgba(59, 130, 246, 0.2);
        }

        h2 { font-size: 1.8rem; margin-bottom: 12px; font-weight: 800; letter-spacing: -0.5px; }
        p { color: var(--text-dim); font-size: 0.95rem; line-height: 1.6; margin-bottom: 35px; }

        /* OTP Inputs */
        .otp-wrapper { display: flex; gap: 12px; justify-content: center; margin-bottom: 25px; }
        .otp-input {
            width: 50px; height: 62px; border-radius: 14px; border: 1px solid var(--border-glass);
            background: rgba(255, 255, 255, 0.04); color: white; text-align: center;
            font-size: 1.6rem; font-weight: 700; outline: none; transition: all 0.3s ease;
        }
        .otp-input:focus { border-color: var(--primary); background: rgba(255, 255, 255, 0.08); box-shadow: 0 0 15px rgba(59, 130, 246, 0.3); transform: scale(1.05); }

        .btn-verify {
            width: 100%; padding: 18px; background: var(--primary); color: white; border: none;
            border-radius: 16px; font-weight: 700; cursor: pointer; transition: 0.3s;
            text-transform: uppercase; letter-spacing: 1px; margin-top: 10px;
        }
        .btn-verify:hover { transform: translateY(-3px); box-shadow: 0 15px 30px rgba(59, 130, 246, 0.4); }

        .error-alert {
            background: rgba(239, 68, 68, 0.1); color: #f87171; padding: 14px;
            border-radius: 14px; border: 1px solid rgba(239, 68, 68, 0.2);
            margin-bottom: 25px; font-size: 0.85rem; display: flex; align-items: center; gap: 10px;
        }

        .footer-links { margin-top: 30px; font-size: 0.85rem; color: var(--text-dim); }
        .footer-links a { color: var(--primary); text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>

<div class="glow glow-1"></div>

<div class="verify-card">
    <div class="icon-circle">
        <i class="fas fa-user-shield"></i>
    </div>
    <h2>Authentication Required</h2>
    <p>Please enter the 6-digit verification code sent to <br> <b style="color: white;"><?php echo htmlspecialchars($target_email); ?></b></p>

    <?php if ($error_msg): ?>
        <div class="error-alert">
            <i class="fas fa-circle-exclamation"></i> <?php echo $error_msg; ?>
        </div>
    <?php endif; ?>

    <form action="auth_logic.php" method="POST" id="otpForm">
        <div class="otp-wrapper">
            <input type="text" name="otp[]" class="otp-input" maxlength="1" pattern="\d*" inputmode="numeric" required autofocus>
            <input type="text" name="otp[]" class="otp-input" maxlength="1" pattern="\d*" inputmode="numeric" required>
            <input type="text" name="otp[]" class="otp-input" maxlength="1" pattern="\d*" inputmode="numeric" required>
            <input type="text" name="otp[]" class="otp-input" maxlength="1" pattern="\d*" inputmode="numeric" required>
            <input type="text" name="otp[]" class="otp-input" maxlength="1" pattern="\d*" inputmode="numeric" required>
            <input type="text" name="otp[]" class="otp-input" maxlength="1" pattern="\d*" inputmode="numeric" required>
        </div>
        
        <button type="submit" name="verify_otp" class="btn-verify">Complete Registration</button>
    </form>

    <div class="footer-links">
        Didn't get the email? <a href="#">Resend Code</a><br><br>
        <a href="index.php" style="color: var(--text-dim); font-weight: 400;"><i class="fas fa-arrow-left"></i> Back to Login</a>
    </div>
</div>

<script>
    const inputs = document.querySelectorAll('.otp-input');

    inputs.forEach((input, index) => {
        // Typing handler
        input.addEventListener('input', (e) => {
            if (e.target.value.length > 1) {
                e.target.value = e.target.value.slice(0, 1);
            }
            if (e.target.value !== "" && index < inputs.length - 1) {
                inputs[index + 1].focus();
            }
        });

        // Backspace handler
        input.addEventListener('keydown', (e) => {
            if (e.key === "Backspace" && e.target.value === "" && index > 0) {
                inputs[index - 1].focus();
            }
        });
    });
</script>

</body>
</html>