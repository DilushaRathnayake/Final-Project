<?php
session_start();
require_once 'config.php';

// සීරට්ටුවක් අලුතින් ඇතුළත් කිරීම
if (isset($_POST['add_seettu'])) {
    $name = mysqli_real_escape_string($conn, $_POST['seettu_name']);
    $amount = $_POST['amount'];
    $months = $_POST['months'];
    $date = $_POST['start_date'];

    $sql = "INSERT INTO factory_seettu (group_name, monthly_amount, start_date, total_members, status) 
            VALUES ('$name', '$amount', '$date', 0, 'active')";
    
    if (mysqli_query($conn, $sql)) {
        header("Location: factory_seettu.php?success=1");
        exit();
    } else {
        die("Error: " . mysqli_error($conn));
    }
}

// සීරට්ටු සමූහයක් Delete කිරීම (Foreign Key ගැටලුව විසඳා ඇත)
if (isset($_GET['delete_id'])) {
    $delete_id = mysqli_real_escape_string($conn, $_GET['delete_id']);
    
    // 1. මුලින්ම seettu_winners table එකේ දත්ත මකා දමන්න
    mysqli_query($conn, "DELETE FROM seettu_winners WHERE seettu_id = '$delete_id'");
    
    // 2. දෙවනුව seettu_members table එකේ දත්ත මකා දමන්න
    mysqli_query($conn, "DELETE FROM seettu_members WHERE seettu_id = '$delete_id'");
    
    // 3. අවසානයට ප්‍රධාන factory_seettu table එකෙන් group එක මකා දමන්න
    if (mysqli_query($conn, "DELETE FROM factory_seettu WHERE id = '$delete_id'")) {
        header("Location: factory_seettu.php?deleted=1");
        exit();
    } else {
        die("Error deleting record: " . mysqli_error($conn));
    }
}

// දත්ත ලබා ගැනීම (Seettu Groups)
$seettu_list = mysqli_query($conn, "SELECT * FROM factory_seettu ORDER BY id DESC");

// දත්ත ලබා ගැනීම (All Winners History)
$all_winners_query = "SELECT sw.*, u.full_name, fs.group_name 
                      FROM seettu_winners sw 
                      JOIN users u ON sw.user_id = u.user_id 
                      JOIN factory_seettu fs ON sw.seettu_id = fs.id 
                      ORDER BY sw.draw_date DESC";

$all_winners = mysqli_query($conn, $all_winners_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Factory Seettu Management | Smart Budget</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1; --bg: #f8fafc; --white: #ffffff;
            --sidebar-bg: #0f172a; --accent: #10b981; --warning: #f59e0b; --danger: #ef4444;
        }
        * { box-sizing: border-box; font-family: 'Inter', sans-serif; margin:0; padding:0; }
        body { background: var(--bg); display: flex; min-height: 100vh; }
        
         /* --- SIDEBAR STYLE --- */
        .sidebar {
            width: 260px;
            background-color: var(--sidebar-bg);
            color: var(--white);
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100%;
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

        .logout-section {
            padding: 20px;
            border-top: 1px solid rgba(255,255,255,0.05);
        }
        .content { margin-left: 260px; padding: 40px; width: calc(100% - 260px); }

        .glass-card {
            background: var(--white);
            border: 1px solid #e2e8f0;
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }

        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; align-items: end; }
        input { width: 100%; padding: 12px; border-radius: 12px; border: 1px solid #e2e8f0; outline: none; transition: 0.3s; }
        
        .btn-main { background: var(--primary); color: white; border: none; padding: 12px 25px; border-radius: 12px; cursor: pointer; font-weight: 600; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { text-align: left; padding: 15px; color: #64748b; font-size: 0.75rem; text-transform: uppercase; border-bottom: 2px solid #f1f5f9; }
        td { padding: 15px; border-bottom: 1px solid #f1f5f9; font-size: 0.85rem; color: #1e293b; }
        
        .status-active { background: #dcfce7; color: #166534; padding: 4px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: 700; }
        .winner-badge { background: #fef3c7; color: #92400e; padding: 4px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: 700; }
        
        .grid-layout { display: grid; grid-template-columns: 1.2fr 1fr; gap: 30px; }
        .btn-delete { color: var(--danger); text-decoration: none; margin-left: 15px; transition: 0.2s; }
    </style>
</head>
<body>

<div class="sidebar">
        <div class="sidebar-header">
            <h2>SMART<span style="color:white">BUDGET</span></h2>
        </div>
        
        <div class="nav-links">
            <a href="admin_dashboard.php" class="nav-item active">
                <i class="fas fa-th-large"></i> <span>Dashboard</span>
            </a>
            <a href="manage_employees.php" class="nav-item">
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
    <div class="header" style="margin-bottom:30px;">
        <h1 style="font-size: 1.8rem; color: #1e293b;">Factory Seettu Management</h1>
        <p style="color: #64748b;">Community savings and automated winner tracking.</p>
    </div>

    <div class="glass-card">
        <h3 style="margin-bottom:15px; font-size:0.9rem; color:var(--primary); text-transform:uppercase;">Create New Seettu Group</h3>
        <form method="POST" class="form-grid">
            <div><label>GROUP NAME</label><input type="text" name="seettu_name" required></div>
            <div><label>MONTHLY AMT</label><input type="number" name="amount" required></div>
            <div><label>DURATION</label><input type="number" name="months" required></div>
            <div><label>START DATE</label><input type="date" name="start_date" required></div>
            <button type="submit" name="add_seettu" class="btn-main">Create Group</button>
        </form>
    </div>

    <div class="grid-layout">
        <div class="glass-card">
            <h3 style="margin-bottom:15px; font-size:1rem;">Active Seettu Groups</h3>
            <table>
                <thead>
                    <tr><th>Name</th><th>Monthly</th><th>Status</th><th style="text-align:right;">Actions</th></tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($seettu_list)): ?>
                    <tr>
                        <td style="font-weight:600;"><?php echo htmlspecialchars($row['group_name']); ?></td>
                        <td>Rs. <?php echo number_format($row['monthly_amount'], 0); ?></td>
                        <td><span class="status-active"><?php echo ucfirst($row['status']); ?></span></td>
                        <td style="text-align:right;">
                            <a href="view_seettu_members.php?id=<?php echo $row['id']; ?>" style="color:var(--primary); text-decoration:none; font-weight:700; font-size:0.75rem;">MANAGE <i class="fas fa-arrow-right"></i></a>
                            <a href="factory_seettu.php?delete_id=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('Deleting this group will also remove all its members and winning history. Proceed?');">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="glass-card">
            <h3 style="margin-bottom:15px; font-size:1rem;">Recent Winners</h3>
            <table>
                <thead>
                    <tr><th>Winner</th><th>Month</th><th style="text-align:right;">Payout</th></tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($all_winners) > 0): ?>
                        <?php while($win = mysqli_fetch_assoc($all_winners)): ?>
                        <tr>
                            <td>
                                <div style="font-weight:600; font-size:0.85rem;"><?php echo htmlspecialchars($win['full_name']); ?></div>
                                <div style="font-size:0.7rem; color:#94a3b8;"><?php echo htmlspecialchars($win['group_name']); ?></div>
                            </td>
                            <td><span class="winner-badge"><?php echo htmlspecialchars($win['draw_month']); ?></span></td>
                            <td style="text-align:right; font-weight:700; color:var(--accent);">Rs. <?php echo number_format($win['payout_amount'], 0); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="3" style="text-align:center; color:#94a3b8; padding:30px;">No winners yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
