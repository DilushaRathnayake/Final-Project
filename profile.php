<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$msg = "";

// --- 1. තොරතුරු යාවත්කාලීන කිරීම (Update Details Logic) ---
if (isset($_POST['update_profile'])) {
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $factory = mysqli_real_escape_string($conn, $_POST['factory']);
    $dept = mysqli_real_escape_string($conn, $_POST['dept']);
    $lang = mysqli_real_escape_string($conn, $_POST['lang']);

    $update_query = "UPDATE users SET 
                    full_name = '$full_name', 
                    email = '$email', 
                    phone = '$phone', 
                    factory = '$factory', 
                    department = '$dept', 
                    lang = '$lang' 
                    WHERE user_id = '$user_id'";

    if (mysqli_query($conn, $update_query)) {
        $_SESSION['full_name'] = $full_name; // Session එකත් update කරන්න
        $_SESSION['lang'] = $lang;
        $msg = "<div class='alert success'><i class='fas fa-check-circle'></i> Profile updated successfully!</div>";
    } else {
        $msg = "<div class='alert danger'>Update failed: " . mysqli_error($conn) . "</div>";
    }
}

// --- 2. PROFILE PHOTO UPLOAD ---
if (isset($_POST['upload_photo'])) {
    $target_dir = "uploads/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
    $file_name = time() . "_" . basename($_FILES["profile_img"]["name"]);
    $target_file = $target_dir . $file_name;

    if (move_uploaded_file($_FILES["profile_img"]["tmp_name"], $target_file)) {
        mysqli_query($conn, "UPDATE users SET profile_pic = '$file_name' WHERE user_id = '$user_id'");
        $msg = "<div class='alert success'>Photo updated!</div>";
    }
}

// --- 3. PASSWORD CHANGE ---
if (isset($_POST['change_pass'])) {
    $current_pass = $_POST['current_pass'];
    $new_pass = $_POST['new_pass'];
    $res = mysqli_query($conn, "SELECT password_hash FROM users WHERE user_id = '$user_id'");
    $user_data = mysqli_fetch_assoc($res);
    
    if (password_verify($current_pass, $user_data['password_hash'])) {
        $hashed_new_pass = password_hash($new_pass, PASSWORD_BCRYPT);
        mysqli_query($conn, "UPDATE users SET password_hash = '$hashed_new_pass' WHERE user_id = '$user_id'");
        $msg = "<div class='alert success'>Password changed successfully!</div>";
    } else {
        $msg = "<div class='alert danger'>Current password is incorrect!</div>";
    }
}

// වත්මන් දත්ත ලබා ගැනීම
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE user_id = '$user_id'"));
$back_url = ($user['role'] === 'admin') ? "admin_dashboard.php" : (($user['role'] === 'supervisor') ? "supervisor_dashboard.php" : "dashboard.php");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Profile | Smart Budget</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary: #1e293b; --accent: #6366f1; --bg: #f8fafc; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); margin: 0; padding: 20px; display: flex; justify-content: center; }
        .wrapper { width: 100%; max-width: 1000px; display: grid; grid-template-columns: 320px 1fr; gap: 20px; }
        .card { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        
        .side-profile { text-align: center; }
        .profile-img { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 5px solid #f1f5f9; }
        
        .form-section { margin-bottom: 30px; }
        .section-title { font-size: 1.1rem; font-weight: 700; color: var(--primary); margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #f1f5f9; display: flex; align-items: center; gap: 10px; }
        
        .grid-inputs { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 600; color: #64748b; margin-bottom: 5px; }
        .form-group input, .form-group select { width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 0.9rem; transition: 0.3s; }
        .form-group input:focus { border-color: var(--accent); outline: none; box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1); }
        
        .btn-save { background: var(--accent); color: white; border: none; padding: 12px 25px; border-radius: 10px; font-weight: 700; cursor: pointer; width: 100%; }
        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; text-align: center; font-size: 0.9rem; }
        .success { background: #dcfce7; color: #166534; }
        .danger { background: #fee2e2; color: #b91c1c; }
        
        .readonly-field { background: #f1f5f9; color: #94a3b8; cursor: not-allowed; }
    </style>
</head>
<body>

<div class="wrapper">
    <div class="card side-profile">
        <form action="" method="POST" enctype="multipart/form-data" id="photoForm">
            <?php $pic = (!empty($user['profile_pic']) && $user['profile_pic'] != 'default_avatar.png') ? 'uploads/'.$user['profile_pic'] : 'https://cdn-icons-png.flaticon.com/512/149/149071.png'; ?>
            <img src="<?php echo $pic; ?>" class="profile-img">
            <label for="profile_img" style="display:block; margin-top:10px; color:var(--accent); cursor:pointer; font-size:0.8rem; font-weight:700;"><i class="fas fa-camera"></i> Change Photo</label>
            <input type="file" name="profile_img" id="profile_img" style="display:none;" onchange="this.form.submit()">
            <input type="hidden" name="upload_photo">
        </form>
        
        <h2 style="margin-top:20px;"><?php echo htmlspecialchars($user['full_name']); ?></h2>
        <p style="text-transform:uppercase; font-size:0.75rem; font-weight:800; color:#6366f1; background:#eef2ff; display:inline-block; padding:4px 12px; border-radius:15px;"><?php echo $user['role']; ?></p>
        
        <hr style="margin:25px 0; opacity:0.1;">
        <a href="<?php echo $back_url; ?>" style="text-decoration:none; color:#64748b; font-weight:600;"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>

    <div class="card">
        <?php if($msg) echo $msg; ?>

        <form action="" method="POST">
            <div class="form-section">
                <div class="section-title"><i class="fas fa-user-edit"></i> Edit Personal Information</div>
                <div class="grid-inputs">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>NIC Number (Cannot change)</label>
                        <input type="text" value="<?php echo htmlspecialchars($user['nic']); ?>" class="readonly-field" readonly>
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="section-title"><i class="fas fa-industry"></i> Work & System Settings</div>
                <div class="grid-inputs">
                    <div class="form-group">
                        <label>Factory</label>
                        <input type="text" name="factory" value="<?php echo htmlspecialchars($user['factory']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Department</label>
                        <input type="text" name="dept" value="<?php echo htmlspecialchars($user['department']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Preferred Language</label>
                        <select name="lang">
                            <option value="en" <?php if($user['lang']=='en') echo 'selected'; ?>>English</option>
                            <option value="si" <?php if($user['lang']=='si') echo 'selected'; ?>>Sinhala</option>
                            <option value="ta" <?php if($user['lang']=='ta') echo 'selected'; ?>>Tamil</option>
                        </select>
                    </div>
                </div>
            </div>
            <button type="submit" name="update_profile" class="btn-save">Save All Changes</button>
        </form>

        <form action="" method="POST" style="margin-top: 40px;">
            <div class="section-title"><i class="fas fa-key"></i> Security Update</div>
            <div class="grid-inputs">
                <div class="form-group">
                    <label>Current Password</label>
                    <input type="password" name="current_pass" required>
                </div>
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_pass" required>
                </div>
            </div>
            <button type="submit" name="change_pass" class="btn-save" style="background:#1e293b;">Change Password</button>
        </form>
    </div>
</div>

</body>
</html>