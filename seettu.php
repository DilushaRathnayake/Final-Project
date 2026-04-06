<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }
$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'] ?? 'User';

// --- 1. ADD NEW SEETTU ---
if (isset($_POST['add_seettu'])) {
    $name = mysqli_real_escape_string($conn, $_POST['group_name']);
    $amt = mysqli_real_escape_string($conn, $_POST['amount']);
    $months = mysqli_real_escape_string($conn, $_POST['total_months']);
    $payout = mysqli_real_escape_string($conn, $_POST['payout_month']);

    mysqli_query($conn, "INSERT INTO factory_seettu (id, group_name, monthly_amount, total_months, payout_month, status) 
                         VALUES ('$user_id', '$name', '$amt', '$months', '$payout', 'Active')");
    $msg = "New Seettu Group Added!";
}

// --- 2. PAY INSTALLMENT (Auto-Expense Logic) ---
if (isset($_GET['pay_id'])) {
    $id = $_GET['pay_id'];
    $res = mysqli_query($conn, "SELECT * FROM factory_seettu WHERE id = '$id' AND user_id = '$user_id'");
    $data = mysqli_fetch_assoc($res);

    if ($data && $data['completed_months'] < $data['total_months']) {
        $new_count = $data['completed_months'] + 1;
        $amt = $data['monthly_amount'];
        $name = "Seettu: " . $data['group_name'];

        // Update Seettu Table
        mysqli_query($conn, "UPDATE factory_seettu SET completed_months = '$new_count' WHERE id = '$id'");
        
        // Auto-Insert into Expenses Table
        mysqli_query($conn, "INSERT INTO expense_records (user_id, category, amount, description, date) 
                             VALUES ('$user_id', 'Savings/Seettu', '$amt', '$name', CURRENT_DATE())");
        
        header("Location: seettu.php?success=1");
        exit();
    }
}

$seettus = mysqli_query($conn, "SELECT * FROM factory_seettu WHERE id = '$user_id' AND status = 'Active'");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Budget | Seettu Manager</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap');

        :root {
            --bg: #f1f5f9;
            --sidebar-bg: #1e293b;
            --card-bg: #ffffff;
            --border: #e2e8f0;
            --primary: #6366f1;
            --text-main: #0f172a;
            --text-sub: #64748b;
            --success: #10b981;
            --warning: #f59e0b;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: var(--bg); color: var(--text-main); font-family: 'Inter', sans-serif; display: flex; }

        /* SIDEBAR */
        .sidebar {
            width: 260px; background: var(--sidebar-bg); height: 100vh; position: fixed;
            padding: 30px 20px; display: flex; flex-direction: column; color: white; z-index: 1000;
        }
        .sidebar-brand { font-size: 1.4rem; font-weight: 800; margin-bottom: 40px; text-align: center; }
        .nav-links { flex: 1; overflow-y: auto; }
        .nav-group-label { font-size: 0.7rem; text-transform: uppercase; color: #94a3b8; margin: 25px 0 10px 10px; letter-spacing: 1px; }
        .nav-item { display: flex; align-items: center; padding: 12px 15px; color: #cbd5e1; text-decoration: none; border-radius: 10px; margin-bottom: 4px; transition: 0.3s; font-size: 0.9rem; }
        .nav-item i { margin-right: 12px; width: 20px; text-align: center; }
        .nav-item:hover, .nav-item.active { background: rgba(255, 255, 255, 0.1); color: white; }
        .nav-item.active { background: var(--primary); }
        .logout-link { padding: 15px; color: #fca5a5; text-decoration: none; font-weight: 600; display: flex; align-items: center; gap: 10px; border-top: 1px solid rgba(255,255,255,0.1); margin-top: auto; }

        /* MAIN CONTENT */
        .main-content { margin-left: 260px; width: calc(100% - 260px); padding: 40px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .grid { display: grid; grid-template-columns: 1fr 350px; gap: 30px; }
        
        .card { 
            background: var(--card-bg); border: 1px solid var(--border); padding: 25px; 
            border-radius: 20px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); margin-bottom: 20px;
        }

        /* PROGRESS BAR */
        .progress-container { height: 10px; background: #f1f5f9; border-radius: 10px; margin: 15px 0; overflow: hidden; border: 1px solid var(--border); }
        .progress-fill { height: 100%; background: linear-gradient(90deg, var(--primary), #818cf8); transition: 0.5s; }

        /* FORM STYLES */
        .input-group { margin-bottom: 18px; }
        .input-group label { display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-sub); margin-bottom: 8px; }
        .input-group input { width: 100%; padding: 12px; background: #f8fafc; border: 1px solid var(--border); border-radius: 12px; font-family: inherit; }
        
        .btn-pay { 
            background: var(--success); color: white; text-decoration: none; 
            padding: 8px 18px; border-radius: 10px; font-size: 0.8rem; font-weight: 700;
            display: inline-flex; align-items: center; gap: 8px; transition: 0.2s;
        }
        .btn-pay:hover { opacity: 0.9; transform: translateY(-1px); }
        
        .badge-info { background: #eef2ff; color: var(--primary); padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-brand">SMART BUDGET</div>
    <div class="nav-links">
        <a href="dashboard.php" class="nav-item ">
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
        <a href="seettu.php" class="nav-item active">
            <i class="fas fa-users-cog"></i> <span>Seettu Groups</span>
        </a> 
        <a href="reminders.php" class="nav-item">
            <i class="fas fa-bell"></i> <span>Reminders</span>
        </a>
        <a href="reports.php" class="nav-item">
            <i class="fas fa-chart-pie"></i> <span>Reports & Charts</span>
        </a>
    </div>

    <div style="margin-top: auto; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;">
        <a href="logout.php" class="logout-link">
            <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
        </a>
    </div>
</div>


<div class="main-content">
    <div class="header">
        <div>
            <h2 style="font-weight: 700;">Seettu Manager</h2>
            <p style="color: var(--text-sub); font-size: 0.9rem;">Track your traditional savings groups</p>
        </div>
        <?php if(isset($_GET['success'])): ?>
            <div style="color: var(--success); font-weight: 600;"><i class="fas fa-check-circle"></i> Installment Paid!</div>
        <?php endif; ?>
    </div>

    <div class="grid">
        <div>
            <h3 style="font-size: 1.1rem; margin-bottom: 20px; font-weight: 700;">Active Groups</h3>
            <?php while($row = mysqli_fetch_assoc($seettus)): 
                $progress = ($row['completed_months'] / $row['total_months']) * 100;
            ?>
                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div>
                            <h4 style="font-size: 1.1rem; font-weight: 700;"><?php echo htmlspecialchars($row['seettu_name']); ?></h4>
                            <span class="badge-info">Payout Month: <?php echo $row['payout_month']; ?></span>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-weight: 800; color: var(--success); font-size: 1.1rem;">LKR <?php echo number_format($row['monthly_amount']); ?></div>
                            <small style="color: var(--text-sub);">per month</small>
                        </div>
                    </div>
                    
                    <div class="progress-container">
                        <div class="progress-fill" style="width: <?php echo $progress; ?>%;"></div>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 0.85rem; font-weight: 600; color: var(--text-sub);">
                            Paid: <span style="color: var(--text-main);"><?php echo $row['completed_months']; ?>/<?php echo $row['total_months']; ?></span> Months
                        </span>
                        
                        <?php if($row['completed_months'] < $row['total_months']): ?>
                            <a href="seettu.php?pay_id=<?php echo $row['id']; ?>" class="btn-pay" onclick="return confirm('Mark this month as paid?')">
                                <i class="fas fa-check"></i> Pay Installment
                            </a>
                        <?php else: ?>
                            <span style="color: var(--success); font-weight: 700;"><i class="fas fa-award"></i> Completed</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>

            <?php if(mysqli_num_rows($seettus) == 0): ?>
                <div class="card" style="text-align: center; padding: 40px; color: var(--text-sub);">
                    <i class="fas fa-users-slash" style="font-size: 3rem; opacity: 0.2; margin-bottom: 15px;"></i>
                    <p>No active Seettu groups found. Add one to start tracking!</p>
                </div>
            <?php endif; ?>
        </div>

        <div>
            <div class="card">
                <h3 style="font-size: 1.1rem; margin-bottom: 20px; font-weight: 700;"><i class="fas fa-plus-circle" style="color: var(--primary);"></i> New Seettu</h3>
                <form method="POST">
                    <div class="input-group">
                        <label>Group Name</label>
                        <input type="text" name="seettu_name" placeholder="e.g. Office Seettu A" required>
                    </div>
                    <div class="input-group">
                        <label>Monthly Amount (LKR)</label>
                        <input type="number" name="amount" placeholder="5000" required>
                    </div>
                    <div class="input-group">
                        <label>Total Months</label>
                        <input type="number" name="total_months" placeholder="10" required>
                    </div>
                    <div class="input-group">
                        <label>Your Payout Month</label>
                        <input type="number" name="payout_month" placeholder="e.g. 5" required>
                    </div>
                    <button type="submit" name="add_seettu" style="width:100%; padding:14px; background:var(--primary); color:white; border:none; border-radius:12px; cursor:pointer; font-weight:700; transition: 0.2s;">
                        Create Seettu Group
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

</body>
</html>