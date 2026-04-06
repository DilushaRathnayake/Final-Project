<?php
session_start();
require_once 'config.php';

// 1. ආරක්ෂක පියවර: Supervisor කෙනෙක් දැයි පරීක්ෂා කිරීම
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'supervisor') {
    header("Location: index.php");
    exit();
}

$supervisor_id = $_SESSION['user_id'];

// Session එකේ department එක නැතිනම් Database එකෙන් ලබා ගැනීම (Safety Check)
if (!isset($_SESSION['department'])) {
    $u_res = mysqli_query($conn, "SELECT department FROM users WHERE user_id = '$supervisor_id'");
    $u_row = mysqli_fetch_assoc($u_res);
    $_SESSION['department'] = $u_row['department'];
}

$dept = $_SESSION['department'];

// 2. තමන්ගේ අංශයේ සේවකයින් පමණක් ලබා ගැනීම
// මෙහිදී 'user' role එක ඇති අය පමණක් තෝරා ගනී
$query = "SELECT user_id, full_name, nic, phone, lang FROM users WHERE department = '$dept' AND role = 'user' ORDER BY full_name ASC";
$result = mysqli_query($conn, $query);

// 3. සාරාංශ දත්ත (Stats) ලබා ගැනීම
$seettu_res = mysqli_query($conn, "SELECT COUNT(*) as total FROM factory_seettu WHERE status = 'active'");
$seettu_data = mysqli_fetch_assoc($seettu_res);

// අංශයේ මුළු සේවකයින් ගණන
$total_emp = mysqli_num_rows($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supervisor Dashboard | Smart Budget</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root { --primary: #0f172a; --accent: #6366f1; --bg: #f8fafc; --sidebar-w: 260px; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: #1e293b; display: flex; }
        
        /* Sidebar Styles */
        .sidebar { 
            width: var(--sidebar-w); background: var(--primary); height: 100vh; 
            color: white; padding: 30px 20px; position: fixed; left: 0; top: 0; 
        }
        .sidebar h2 { font-size: 1.4rem; font-weight: 800; margin-bottom: 40px; display: flex; align-items: center; gap: 10px; }
        .nav-item { 
            display: flex; align-items: center; gap: 12px; color: #94a3b8; 
            text-decoration: none; padding: 14px; border-radius: 12px; 
            margin-bottom: 8px; transition: 0.3s; font-weight: 500;
        }
        .nav-item:hover, .nav-item.active { background: rgba(255,255,255,0.1); color: white; }
        .nav-item i { font-size: 1.1rem; }

        /* Main Content */
        .main-content { margin-left: var(--sidebar-w); padding: 40px; width: 100%; min-height: 100vh; }
        
        .welcome-box { margin-bottom: 40px; }
        .welcome-box h1 { font-size: 1.8rem; color: var(--primary); }
        .dept-tag { 
            display: inline-block; background: #e0e7ff; color: #4338ca; 
            padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 700; margin-top: 8px;
        }

        /* Stats Cards */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 25px; margin-bottom: 40px; }
        .stat-card { 
            background: white; padding: 25px; border-radius: 20px; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.03); display: flex; align-items: center; gap: 20px;
        }
        .stat-icon { 
            width: 50px; height: 50px; border-radius: 12px; 
            display: flex; align-items: center; justify-content: center; font-size: 1.5rem;
        }
        .stat-info h3 { font-size: 0.85rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-info p { font-size: 1.5rem; font-weight: 800; color: var(--primary); margin-top: 4px; }

        /* Table Card */
        .table-card { 
            background: white; border-radius: 20px; padding: 30px; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.03); border: 1px solid #eef2ff;
        }
        .table-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; color: #64748b; font-weight: 600; border-bottom: 2px solid #f1f5f9; font-size: 0.85rem; }
        td { padding: 18px 15px; border-bottom: 1px solid #f1f5f9; font-size: 0.95rem; }
        tr:hover { background: #f8fafc; }

        .btn-view { 
            background: #f1f5f9; color: var(--accent); padding: 8px 16px; 
            border-radius: 10px; text-decoration: none; font-weight: 700; 
            font-size: 0.85rem; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px;
        }
        .btn-view:hover { background: var(--accent); color: white; transform: translateY(-2px); }

        .logout-btn { margin-top: auto; color: #f87171 !important; }
    </style>
</head>
<body>

<aside class="sidebar">
    <h2><i class="fas fa-wallet text-accent"></i> Smart Budget</h2>
    <a href="supervisor_dashboard.php" class="nav-item active"><i class="fas fa-th-large"></i> Dashboard</a>
    <a href="profile.php" class="nav-item"><i class="fas fa-user-circle"></i> My Profile</a>
    
    
    <div style="margin-top: 100px;">
        <hr style="opacity: 0.1; margin-bottom: 20px;">
        <a href="logout.php" class="nav-item logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</aside>

<main class="main-content">
    <div class="welcome-box">
        <h1>Hello, Supervisor! 👋</h1>
        <span class="dept-tag"><i class="fas fa-industry"></i> <?php echo htmlspecialchars($dept); ?> Department</span>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: #eef2ff; color: #6366f1;"><i class="fas fa-users"></i></div>
            <div class="stat-info">
                <h3>Total Employees</h3>
                <p><?php echo $total_emp; ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: #f0fdf4; color: #22c55e;"><i class="fas fa-piggy-bank"></i></div>
            <div class="stat-info">
                <h3>Active Seettu</h3>
                <p><?php echo $seettu_data['total']; ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: #fffbeb; color: #f59e0b;"><i class="fas fa-check-circle"></i></div>
            <div class="stat-info">
                <h3>Dept. Status</h3>
                <p>Stable</p>
            </div>
        </div>
    </div>

    <div class="table-card">
        <div class="table-header">
            <h2 style="font-size: 1.2rem;"><i class="fas fa-list-ul" style="color:var(--accent)"></i> Employee Overview</h2>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>EMPLOYEE NAME</th>
                    <th>NIC NUMBER</th>
                    <th>PHONE</th>
                    <th>LANGUAGE</th>
                    <th style="text-align: right;">ACTION</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td>
                        <div style="font-weight: 700; color: var(--primary);">
                            <?php echo htmlspecialchars($row['full_name']); ?>
                        </div>
                    </td>
                    <td><code style="background:#f1f5f9; padding:2px 6px; border-radius:4px;"><?php echo htmlspecialchars($row['nic']); ?></code></td>
                    <td><?php echo htmlspecialchars($row['phone'] ?: '---'); ?></td>
                    <td><span style="text-transform: uppercase; font-size: 0.8rem; font-weight: 600;"><?php echo $row['lang']; ?></span></td>
                    <td style="text-align: right;">
                        <a href="view_employee_summary.php?id=<?php echo $row['user_id']; ?>" class="btn-view">
                            <i class="fas fa-chart-line"></i> View Report
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
                
                <?php if($total_emp == 0): ?>
                <tr>
                    <td colspan="5" style="text-align: center; color: #94a3b8; padding: 60px;">
                        <i class="fas fa-folder-open" style="font-size: 2rem; display: block; margin-bottom: 10px;"></i>
                        No employees found in the <strong><?php echo htmlspecialchars($dept); ?></strong> department.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

</body>
</html>