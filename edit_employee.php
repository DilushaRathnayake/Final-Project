<?php
session_start();
require_once 'config.php';

if (isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    $res = mysqli_query($conn, "SELECT * FROM users WHERE user_id = '$id'");
    $user = mysqli_fetch_assoc($res);
}

// Update Logic
if (isset($_POST['update_user'])) {
    $u_id = $_POST['user_id'];
    $name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $dept = $_POST['department'];
    $role = $_POST['role'];

    $update_query = "UPDATE users SET full_name='$name', email='$email', department='$dept', role='$role' WHERE user_id='$u_id'";
    
    if (mysqli_query($conn, $update_query)) {
        header("Location: manage_employees.php?msg=updated");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Employee | Smart Budget</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; display: flex; justify-content: center; padding: 50px; }
        .edit-card { background: white; padding: 30px; border-radius: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); width: 400px; border: 1px solid #e2e8f0; }
        h2 { color: #1e293b; margin-bottom: 20px; font-size: 1.2rem; }
        label { display: block; font-size: 0.8rem; font-weight: 600; color: #64748b; margin-bottom: 5px; }
        input, select { width: 100%; padding: 12px; margin-bottom: 20px; border: 1px solid #e2e8f0; border-radius: 10px; outline: none; }
        .btn-update { background: #6366f1; color: white; border: none; padding: 12px; border-radius: 10px; width: 100%; font-weight: 700; cursor: pointer; }
        .btn-cancel { display: block; text-align: center; margin-top: 15px; color: #64748b; text-decoration: none; font-size: 0.9rem; }
    </style>
</head>
<body>

<div class="edit-card">
    <h2><i class="fas fa-user-edit"></i> Edit Member Details</h2>
    <form method="POST">
        <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
        
        <label>Full Name</label>
        <input type="text" name="full_name" value="<?php echo $user['full_name']; ?>" required>
        
        <label>Email Address</label>
        <input type="email" name="email" value="<?php echo $user['email']; ?>" required>
        
        <label>Department</label>
        <select name="department">
            <option value="Sewing" <?php if($user['department'] == 'Sewing') echo 'selected'; ?>>Sewing Section</option>
            <option value="Cutting" <?php if($user['department'] == 'Cutting') echo 'selected'; ?>>Cutting Section</option>
            <option value="Packing" <?php if($user['department'] == 'Packing') echo 'selected'; ?>>Packing & Quality</option>
            <option value="Office" <?php if($user['department'] == 'Office') echo 'selected'; ?>>Office Staff</option>
        </select>
        
        <label>Access Level</label>
        <select name="role">
            <option value="user" <?php if($user['role'] == 'user') echo 'selected'; ?>>Employee</option>
            <option value="supervisor" <?php if($user['role'] == 'supervisor') echo 'selected'; ?>>Supervisor</option>
        </select>
        
        <button type="submit" name="update_user" class="btn-update">Save Changes</button>
        <a href="manage_employees.php" class="btn-cancel">Cancel</a>
    </form>
</div>

</body>
</html>