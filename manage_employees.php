<?php
session_start();
require_once 'config.php';

// --- Error Reporting ---
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- Delete Logic ---
if (isset($_GET['delete_id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['delete_id']);
    mysqli_query($conn, "DELETE FROM users WHERE user_id = '$id'");
    header("Location: manage_employees.php?msg=deleted");
    exit();
}

// --- Add Member Logic ---
if (isset($_POST['add_user'])) {
    $name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $role = $_POST['role'];
    $dept = $_POST['department']; 
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $checkEmail = mysqli_query($conn, "SELECT user_id FROM users WHERE email='$email'");
    if(mysqli_num_rows($checkEmail) > 0) {
        $error = "Email already exists!";
    } else {
        mysqli_query($conn, "INSERT INTO users (full_name, email, password, role, department) VALUES ('$name', '$email', '$pass', '$role', '$dept')");
        $success = "New Member Added to $dept Department!";
    }
}

// --- LIVE TOTAL EMPLOYEES COUNT ---
$count_query = mysqli_query($conn, "SELECT COUNT(user_id) as total FROM users WHERE role != 'admin'");
$count_data = mysqli_fetch_assoc($count_query);
$total_members = $count_data['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Employee Management | Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1; 
            --secondary: #64748b; 
            --success: #10b981;
            --danger: #ef4444; 
            --bg: #f8fafc; 
            --white: #ffffff;
            --sidebar-bg: #0f172a;
            --sidebar-hover: #1e293b;
            --accent: #10b981;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { background: var(--bg); display: flex; min-height: 100vh; }

        .sidebar {
            width: 260px;
            background-color: var(--sidebar-bg);
            color: var(--white);
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .sidebar-header {
            padding: 30px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }

        .sidebar-header h2 {
            font-size: 1.4rem;
            font-weight: 700;
            letter-spacing: 1px;
            color: var(--accent);
        }

        .nav-links { padding: 20px 10px; flex-grow: 1; }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: #94a3b8;
            text-decoration: none;
            border-radius: 10px;
            margin-bottom: 8px;
            transition: 0.3s;
        }

        .nav-item i { width: 25px; font-size: 1.1rem; margin-right: 15px; }

        .nav-item:hover, .nav-item.active {
            background-color: var(--sidebar-hover);
            color: var(--white);
        }

        .nav-item.active { border-left: 4px solid var(--primary); }

        .content { margin-left: 260px; width: calc(100% - 260px); padding: 40px; }
        
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .welcome-text h1 { font-size: 1.8rem; color: #1e293b; }
        
        .stats-badge { 
            background: var(--white); 
            padding: 10px 20px; 
            border-radius: 12px; 
            border: 1px solid #e2e8f0; 
            display: flex; 
            align-items: center; 
            gap: 10px; 
        }
        .stats-badge span { font-weight: 700; color: var(--primary); font-size: 1.1rem; }

        .logout-section {
            padding: 20px;
            border-top: 1px solid rgba(255,255,255,0.05);
        }

        .card { 
            background: var(--white); 
            padding: 25px; 
            border-radius: 16px; 
            border: 1px solid #e2e8f0; 
            margin-bottom: 30px; 
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); 
        }
        .form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; align-items: end; }
        
        label { display: block; font-size: 0.75rem; font-weight: 600; color: var(--secondary); margin-bottom: 8px; }
        input, select { width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px; outline: none; font-size: 0.85rem; }
        
        .btn-add { 
            background: var(--primary); 
            color: white; 
            border: none; 
            padding: 12px; 
            border-radius: 10px; 
            cursor: pointer; 
            font-weight: 600; 
            transition: 0.3s;
        }
        .btn-add:hover { opacity: 0.9; }

        .table-container { background: var(--white); border-radius: 16px; overflow: hidden; border: 1px solid #e2e8f0; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f8fafc; padding: 15px; text-align: left; font-size: 0.75rem; color: var(--secondary); text-transform: uppercase; border-bottom: 1px solid #e2e8f0; }
        td { padding: 15px; border-bottom: 1px solid #f1f5f9; font-size: 0.9rem; }
        
        .role-badge { padding: 4px 10px; border-radius: 6px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; }
        .user-bg { background: #e0e7ff; color: #4338ca; }
        .sup-bg { background: #fef3c7; color: #92400e; }
        .dept-text { font-size: 0.75rem; color: var(--secondary); font-weight: 400; }

        .actions { display: flex; justify-content: flex-end; gap: 15px; align-items: center; }
        .actions a { color: #94a3b8; font-size: 1.1rem; transition: 0.2s; text-decoration: none; display: flex; align-items: center; gap: 5px;}
        .actions .btn-view { color: var(--primary); font-size: 0.8rem; font-weight: 600; background: #eef2ff; padding: 5px 10px; border-radius: 6px; }
        .actions .btn-view:hover { background: var(--primary); color: white; }
        .actions .btn-edit:hover { color: var(--primary); }
        .actions .btn-del:hover { color: var(--danger); }

        .alert { padding: 15px; border-radius: 10px; margin-bottom: 25px; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-info { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-header">
            <h2>SMART<span style="color:white">BUDGET</span></h2>
        </div>
        
        <div class="nav-links">
            <a href="admin_dashboard.php" class="nav-item">
                <i class="fas fa-th-large"></i> <span>Dashboard</span>
            </a>
            <a href="manage_employees.php" class="nav-item active">
                <i class="fas fa-users"></i> <span>Manage Employees</span>
            </a>
            <a href="salary_process.php" class="nav-item">
                <i class="fas fa-file-invoice-dollar"></i> <span>Payroll Center</span>
            </a>
            <a href="factory_seettu.php" class="nav-item">
                <i class="fas fa-layer-group"></i> <span>Factory Seettu</span>
            </a>
        </div>
        <div class="logout-section">
            <a href="logout.php" class="nav-item" style="color: #fca5a5;">
                <i class="fas fa-sign-out-alt"></i> <span>Log Out</span>
            </a>
        </div>
    </div>

    <div class="content">
        <div class="header">
            <div class="welcome-text">
                <h1>Member Management</h1>
                <p style="color: var(--secondary);">Garment factory employee registration portal.</p>
            </div>
            <div class="stats-badge">
                <i class="fas fa-user-friends"></i> Total Members: <span><?php echo $total_members; ?></span>
            </div>
        </div>

        <?php 
        if(isset($success)) echo "<div class='alert alert-success'><i class='fas fa-check-circle'></i> $success</div>"; 
        if(isset($_GET['msg']) && $_GET['msg'] == 'updated') echo "<div class='alert alert-info'><i class='fas fa-info-circle'></i> Employee details updated successfully!</div>";
        if(isset($_GET['msg']) && $_GET['msg'] == 'deleted') echo "<div class='alert' style='background:#fee2e2; color:#b91c1c;'><i class='fas fa-trash'></i> Member removed from system.</div>";
        ?>

        <div class="card">
            <form method="POST" class="form-row">
                <div>
                    <label>Full Name</label>
                    <input type="text" name="full_name" placeholder="John Doe" required>
                </div>
                <div>
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="email@factory.com" required>
                </div>
                <div>
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <div>
                    <label>Section / Department</label>
                    <select name="department">
                        <option value="Sewing">Sewing Section</option>
                        <option value="Cutting">Cutting Section</option>
                        <option value="Packing">Packing & Quality</option>
                        <option value="Office">Office Staff</option>
                    </select>
                </div>
                <div>
                    <label>Access Level</label>
                    <select name="role">
                        <option value="user">Employee</option>
                        <option value="supervisor">Supervisor</option>
                    </select>
                </div>
                <button type="submit" name="add_user" class="btn-add">Register</button>
            </form>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Member Details</th>
                        <th>Email Address</th>
                        <th>Section</th>
                        <th>Role</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $res = mysqli_query($conn, "SELECT * FROM users WHERE role != 'admin' ORDER BY user_id DESC");
                    while($row = mysqli_fetch_assoc($res)) {
                        $roleClass = ($row['role'] == 'supervisor') ? 'sup-bg' : 'user-bg';
                        ?>
                        <tr>
                            <td>
                                <div style="font-weight: 600;"><?php echo $row['full_name']; ?></div>
                                <div class="dept-text">ID: #<?php echo $row['user_id']; ?></div>
                            </td>
                            <td><?php echo $row['email']; ?></td>
                            <td style="font-weight:500;"><?php echo $row['department']; ?></td>
                            <td><span class="role-badge <?php echo $roleClass; ?>"><?php echo $row['role']; ?></span></td>
                            <td class="actions">
                                <a href="view_user_details.php?id=<?php echo $row['user_id']; ?>" class="btn-view" title="View Financials">
                                    <i class="fas fa-eye"></i> View Details
                                </a>

                                <a href="edit_employee.php?id=<?php echo $row['user_id']; ?>" class="btn-edit" title="Edit Profile">
                                    <i class="fas fa-user-edit"></i>
                                </a>
                                
                                <a href="manage_employees.php?delete_id=<?php echo $row['user_id']; ?>" 
                                   onclick="return confirm('Are you sure you want to remove this member?')" 
                                   class="btn-del" title="Remove Member">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>