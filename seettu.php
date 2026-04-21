<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) { 
    header("Location: index.php"); 
    exit(); 
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'] ?? 'User';

// --- ADD NEW SEETTU ---
if (isset($_POST['add_seettu'])) {
    $name = mysqli_real_escape_string($conn, $_POST['seettu_name']);
    $amt = mysqli_real_escape_string($conn, $_POST['amount']);
    $months = mysqli_real_escape_string($conn, $_POST['total_months']);
    $payout = mysqli_real_escape_string($conn, $_POST['payout_month']);

    $sql = "INSERT INTO factory_seettu (user_id, seettu_name, monthly_amount, total_months, payout_month, status, completed_months) 
            VALUES ('$user_id', '$name', '$amt', '$months', '$payout', 'Active', 0)";
    
    if(mysqli_query($conn, $sql)) {
        header("Location: seettu.php?added=1");
        exit();
    }
}

// --- PAY INSTALLMENT LOGIC ---
if (isset($_GET['pay_id'])) {
    $pay_id = mysqli_real_escape_string($conn, $_GET['pay_id']);
    $res = mysqli_query($conn, "SELECT * FROM factory_seettu WHERE id = '$pay_id' AND user_id = '$user_id'");
    $data = mysqli_fetch_assoc($res);

    if ($data && $data['completed_months'] < $data['total_months']) {
        $new_count = $data['completed_months'] + 1;
        $amt = $data['monthly_amount'];
        $desc = "Seettu Payment: " . $data['seettu_name'];

        mysqli_query($conn, "UPDATE factory_seettu SET completed_months = '$new_count' WHERE id = '$pay_id'");
        mysqli_query($conn, "INSERT INTO expense_records (user_id, category, amount, description, date) 
                             VALUES ('$user_id', 'Savings/Seettu', '$amt', '$desc', CURRENT_DATE())");
        
        header("Location: seettu.php?success=1");
        exit();
    }
}

$seettus = mysqli_query($conn, "SELECT * FROM factory_seettu WHERE user_id = '$user_id' AND status = 'Active'");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seettu Manager | Smart Budget</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap');

        :root {
            --bg: #f1f5f9;
            --sidebar-bg: #1e293b; /* Dark Slate from Dashboard */
            --primary: #6366f1;    /* Indigo from Dashboard */
            --text-main: #0f172a;
            --text-sub: #64748b;
            --border: #e2e8f0;
            --white: #ffffff;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: var(--bg); color: var(--text-main); font-family: 'Inter', sans-serif; display: flex; }

        /* --- DASHBOARD STYLE SIDEBAR --- */
        .sidebar {
            width: 260px; 
            background: var(--sidebar-bg); 
            height: 100vh;
            position: fixed; 
            padding: 30px 20px; 
            display: flex;
            flex-direction: column; 
            color: white;
            z-index: 1000;
        }

        .sidebar-brand { 
            font-size: 1.4rem; 
            font-weight: 800; 
            margin-bottom: 40px; 
            text-align: center; 
            letter-spacing: 1px;
        }

        .nav-links { flex: 1; overflow-y: auto; }

        .nav-group-label { 
            font-size: 0.7rem; 
            text-transform: uppercase; 
            color: #94a3b8; 
            margin: 25px 0 10px 10px; 
            letter-spacing: 1px;
            font-weight: 700;
        }

        .nav-item {
            display: flex; 
            align-items: center; 
            padding: 12px 15px; 
            color: #cbd5e1;
            text-decoration: none; 
            border-radius: 10px; 
            margin-bottom: 4px; 
            transition: 0.3s; 
            font-size: 0.9rem;
        }

        .nav-item i { margin-right: 12px; width: 20px; text-align: center; }

        .nav-item:hover, .nav-item.active { 
            background: rgba(255, 255, 255, 0.1); 
            color: white; 
        }

        .nav-item.active { background: var(--primary); }

        .logout-section {
            margin-top: auto;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 20px;
        }

        .logout-link { 
            padding: 15px; 
            color: #fca5a5; 
            text-decoration: none; 
            font-weight: 600; 
            display: flex; 
            align-items: center; 
            gap: 10px; 
        }

        /* --- CONTENT AREA --- */
        .main-content { margin-left: 260px; flex: 1; padding: 40px; }
        
        .header-section { margin-bottom: 30px; }
        .header-section h1 { font-size: 2rem; font-weight: 800; }
        .header-section p { color: var(--text-sub); }

        .seettu-grid { display: grid; grid-template-columns: 1fr 400px; gap: 30px; align-items: start; }

        .active-cycles-area {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 24px;
            min-height: 400px;
            display: flex;
            flex-direction: column;
            padding: 30px;
        }

        .empty-state { margin: auto; text-align: center; }
        .empty-state img { width: 120px; margin-bottom: 20px; opacity: 0.6; }

        .form-card {
            background: var(--white);
            border: 2px solid var(--primary); 
            border-radius: 24px;
            padding: 30px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
        }

        .form-title { display: flex; align-items: center; gap: 10px; color: var(--primary); font-weight: 700; margin-bottom: 25px; }

        .input-group { margin-bottom: 18px; }
        .input-group label { display: block; font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 8px; }
        .input-group input { 
            width: 100%; padding: 14px; border: 1px solid var(--border); border-radius: 12px; 
            background: #f8fafc; font-size: 0.95rem;
        }

        .btn-start {
            width: 100%; padding: 16px; background: var(--primary); color: white; border: none;
            border-radius: 14px; font-weight: 700; font-size: 1rem; cursor: pointer; margin-top: 10px;
        }

        .cycle-card {
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 20px;
            margin-bottom: 15px;
            background: #fff;
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-brand">SMART BUDGET</div>
    <div class="nav-links">
        <a href="dashboard.php" class="nav-item active">
            <i class="fas fa-home"></i> <span>Dashboard</span>
        </a>
        
        <div class="nav-group-label">Money Management</div>
        <a href="income.php" class="nav-item">
            <i class="fas fa-hand-holding-usd"></i> <span>Live Income</span>
        </a>
        <a href="expenses.php" class="nav-item">
            <i class="fas fa-shopping-cart"></i> <span>Live Expenses</span>
        </a>
        <a href="budget.php" class="nav-item">
            <i class="fas fa-piggy-bank"></i> <span>Budget Planning</span>
        </a>

        <div class="nav-group-label">Factory Specific</div>
        <a href="savings.php" class="nav-item">
            <i class="fas fa-coins"></i> <span>Savings & Goals</span>
        </a>
        <a href="loans.php" class="nav-item">
            <i class="fas fa-shield-virus"></i> <span>Loan Guard</span>
        </a>

        <div class="nav-group-label">Advanced Tools</div>
        <a href="payday.php" class="nav-item">
            <i class="fas fa-calendar-check"></i> <span>Pay Day Planner</span>
        </a>
        <a href="health_score.php" class="nav-item">
            <i class="fas fa-heartbeat"></i> <span>Health Score</span>
        </a>
        <a href="seettu.php" class="nav-item">
            <i class="fas fa-users-cog"></i> <span>Seettu Groups</span>
        </a> 
        <a href="reminders.php" class="nav-item">
            <i class="fas fa-bell"></i> <span>Reminders</span>
        </a>
        <a href="reports.php" class="nav-item">
            <i class="fas fa-chart-pie"></i> <span>Reports & Charts</span>
        </a>
        <a href="profile.php" class="nav-item">
            <i class="fas fa-chart-pie"></i> <span>My Profile</span>
        </a>
    </div>

    <div style="margin-top: auto; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;">
        <a href="logout.php" class="logout-link">
            <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
        </a>
    </div>
</div>
<div class="main-content">
    <div class="header-section">
        <h1>Seettu Manager</h1>
        <p>Organize and track your factory seettu cycles</p>
    </div>

    <div class="seettu-grid">
        <div>
            <h3 style="margin-bottom: 15px;">Active Cycles</h3>
            <div class="active-cycles-area">
                <?php if(mysqli_num_rows($seettus) > 0): ?>
                    <?php while($row = mysqli_fetch_assoc($seettus)): 
                        $total_m = $row['total_months'];
                        $done_m = $row['completed_months'];
                        $progress = ($total_m > 0) ? ($done_m / $total_m) * 100 : 0;
                    ?>
                        <div class="cycle-card">
                            <div style="display:flex; justify-content:space-between;">
                                <strong><?php echo htmlspecialchars($row['seettu_name']); ?></strong>
                                <span style="color: #10b981; font-weight: 700;">Rs. <?php echo number_format($row['monthly_amount']); ?></span>
                            </div>
                            <p style="font-size: 0.8rem; color: var(--text-sub);">Payout Month: <?php echo $row['payout_month']; ?></p>
                            <div style="height:8px; background:#f1f5f9; border-radius:10px; margin: 10px 0;">
                                <div style="width:<?php echo $progress; ?>%; height:100%; background:var(--primary); border-radius:10px;"></div>
                            </div>
                            <a href="seettu.php?pay_id=<?php echo $row['id']; ?>" style="font-size: 0.8rem; color: var(--primary); text-decoration:none; font-weight:600;">Pay Installment →</a>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <img src="https://cdn-icons-png.flaticon.com/512/4076/4076549.png" alt="Empty">
                        <p>No active seettu groups. Start one today!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-card">
            <div class="form-title">
                <i class="fas fa-plus-circle"></i> Add New Cycle
            </div>
            <form method="POST">
                <div class="input-group">
                    <label>Group / Seettu Name</label>
                    <input type="text" name="seettu_name" placeholder="Ex: Friends Office Seettu" required>
                </div>
                <div class="input-group">
                    <label>Monthly Contribution (Rs.)</label>
                    <input type="number" name="amount" value="5000" required>
                </div>
                <div class="input-group">
                    <label>Total Duration (Months)</label>
                    <input type="number" name="total_months" value="10" required>
                </div>
                <div class="input-group">
                    <label>Your Payout Month (1 - 12)</label>
                    <input type="number" name="payout_month" value="5" required>
                </div>
                <button type="submit" name="add_seettu" class="btn-start">Start Seettu Cycle</button>
            </form>
        </div>
    </div>
</div>

</body>
</html>
