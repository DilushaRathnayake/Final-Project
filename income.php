<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }
$user_id = $_SESSION['user_id'];

// --- DELETE LOGIC ---
if (isset($_GET['delete_id'])) {
    $delete_id = mysqli_real_escape_string($conn, $_GET['delete_id']);
    $del_query = "DELETE FROM income_records WHERE id = '$delete_id' AND user_id = '$user_id' AND added_by = 'User'";
    mysqli_query($conn, $del_query);
    header("Location: income.php");
    exit();
}

// --- CALCULATION FOR SUMMARY CARDS ---
$sum_query = "SELECT 
    SUM(CASE WHEN added_by = 'Admin' THEN amount ELSE 0 END) as salary_total,
    SUM(CASE WHEN added_by = 'User' THEN amount ELSE 0 END) as other_total
    FROM income_records WHERE user_id = '$user_id' AND MONTH(date) = MONTH(CURRENT_DATE())";
$sum_res = mysqli_query($conn, $sum_query);
$sums = mysqli_fetch_assoc($sum_res);

// --- FORM SUBMISSION ---
if (isset($_POST['add_income'])) {
    $amount = mysqli_real_escape_string($conn, $_POST['amount']);
    $source = mysqli_real_escape_string($conn, $_POST['source']); 
    $date = $_POST['date'];
    $query = "INSERT INTO income_records (user_id, amount, source, date, type, added_by) 
              VALUES ('$user_id', '$amount', '$source', '$date', 'Other', 'User')";
    mysqli_query($conn, $query);
    header("Location: income.php?success=1");
    exit();
}

$fetch_income = mysqli_query($conn, "SELECT * FROM income_records WHERE user_id = '$user_id' ORDER BY date DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Budget | Income Tracker</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap');

        :root {
            --bg: #f8fafc;
            --sidebar-bg: #0f172a;
            --card-bg: #ffffff;
            --border: #e2e8f0;
            --primary: #6366f1;
            --text-main: #0f172a;
            --text-sub: #64748b;
            --success: #10b981;
            --danger: #ef4444;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--bg); color: var(--text-main); display: flex; }

        /* SIDEBAR STYLING */
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
            color: #fff;
        }

        .nav-links { flex: 1; overflow-y: auto; }
        .nav-group-label {
            font-size: 0.65rem;
            text-transform: uppercase;
            color: #475569;
            margin: 25px 0 10px 10px;
            letter-spacing: 1.2px;
            font-weight: 700;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: #94a3b8;
            text-decoration: none;
            border-radius: 12px;
            margin-bottom: 5px;
            transition: 0.3s;
            font-size: 0.9rem;
        }

        .nav-item i { margin-right: 12px; width: 20px; text-align: center; }
        .nav-item:hover, .nav-item.active {
            background: rgba(255, 255, 255, 0.05);
            color: white;
        }
        .nav-item.active { background: var(--primary); color: white; box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3); }

        .logout-link {
            padding: 15px;
            color: #f87171;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        /* MAIN CONTENT AREA */
        .main-wrapper {
            margin-left: 260px; /* Same as sidebar width */
            width: calc(100% - 260px);
            padding: 40px;
        }

        .header-section { margin-bottom: 35px; display: flex; justify-content: space-between; align-items: center; }
        .header-section h2 { font-size: 1.8rem; font-weight: 700; letter-spacing: -0.5px; }
        
        .summary-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 25px; margin-bottom: 30px; }
        .mini-card { 
            background: var(--card-bg); border: 1px solid var(--border); 
            padding: 25px; border-radius: 20px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.03);
            display: flex; align-items: center; gap: 20px;
        }
        .icon-box { width: 55px; height: 55px; border-radius: 15px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; }

        .content-grid { display: grid; grid-template-columns: 380px 1fr; gap: 30px; }
        .glass-card { 
            background: var(--card-bg); border: 1px solid var(--border); 
            padding: 30px; border-radius: 24px; box-shadow: 0 10px 25px rgba(0,0,0,0.02);
        }

        label { display: block; font-size: 0.75rem; color: var(--text-sub); margin-bottom: 8px; text-transform: uppercase; font-weight: 700; }
        input { 
            width: 100%; padding: 14px; margin-bottom: 20px; background: #f8fafc; 
            border: 1px solid var(--border); border-radius: 12px; outline: none; transition: 0.3s;
        }
        input:focus { border-color: var(--primary); background: #fff; box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1); }
        
        .submit-btn { 
            width: 100%; padding: 15px; border: none; border-radius: 12px; font-weight: 700;
            background: var(--primary); color: white; cursor: pointer; transition: 0.3s;
        }
        .submit-btn:hover { background: #4f46e5; transform: translateY(-2px); }

        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; color: var(--text-sub); font-size: 0.7rem; text-transform: uppercase; border-bottom: 2px solid #f1f5f9; }
        td { padding: 18px 15px; border-bottom: 1px solid #f1f5f9; font-size: 0.9rem; }
        
        .badge { padding: 5px 10px; border-radius: 10px; font-size: 0.7rem; font-weight: 700; }
        .badge-salary { background: #eef2ff; color: var(--primary); }
        .badge-other { background: #ecfdf5; color: var(--success); }

        .action-icon { color: var(--danger); background: #fff1f2; width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; text-decoration: none; }
        
        @media (max-width: 1200px) {
            .content-grid { grid-template-columns: 1fr; }
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
<div class="main-wrapper">
    <div class="header-section">
        <div>
            <h2><i class="fas fa-wallet" style="color: var(--primary); margin-right: 10px;"></i> Income Tracker</h2>
            <p>Manage your factory wages and secondary income sources.</p>
        </div>
    </div>

    <div class="summary-grid">
        <div class="mini-card">
            <div class="icon-box" style="background: #eef2ff; color: var(--primary);"><i class="fas fa-university"></i></div>
            <div>
                <p style="font-size: 0.7rem; color: var(--text-sub); font-weight: 700; text-transform: uppercase;">Factory Salary</p>
                <p style="font-size: 1.4rem; font-weight: 800;">LKR <?php echo number_format($sums['salary_total'] ?? 0, 2); ?></p>
            </div>
        </div>
        <div class="mini-card">
            <div class="icon-box" style="background: #ecfdf5; color: var(--success);"><i class="fas fa-plus-circle"></i></div>
            <div>
                <p style="font-size: 0.7rem; color: var(--text-sub); font-weight: 700; text-transform: uppercase;">Other Income</p>
                <p style="font-size: 1.4rem; font-weight: 800;">LKR <?php echo number_format($sums['other_total'] ?? 0, 2); ?></p>
            </div>
        </div>
    </div>

    <div class="content-grid">
        <div class="glass-card">
            <h3 style="margin-bottom:20px;"><i class="fas fa-plus-circle"></i> Add New Entry</h3>
            <form method="POST">
                <label>Amount (LKR)</label>
                <input type="number" name="amount" placeholder="Enter amount" required>
                
                <label>Source / Description</label>
                <input type="text" name="source" placeholder="e.g., Overtime, Bonus" required>
                
                <label>Date</label>
                <input type="date" name="date" value="<?php echo date('Y-m-d'); ?>" required>
                
                <button type="submit" name="add_income" class="submit-btn">Save Transaction</button>
            </form>
        </div>

        <div class="glass-card">
            <h3 style="margin-bottom:20px;"><i class="fas fa-history"></i> Income History</h3>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Category</th>
                            <th>Amount</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($fetch_income)): ?>
                        <tr>
                            <td style="color: var(--text-sub);"><?php echo date('M d', strtotime($row['date'])); ?></td>
                            <td style="font-weight: 600;"><?php echo htmlspecialchars($row['source']); ?></td>
                            <td>
                                <span class="badge <?php echo ($row['added_by'] == 'Admin') ? 'badge-salary' : 'badge-other'; ?>">
                                    <?php echo ($row['added_by'] == 'Admin') ? 'Salary' : 'Other'; ?>
                                </span>
                            </td>
                            <td style="color: var(--success); font-weight: 700;">+ <?php echo number_format($row['amount'], 2); ?></td>
                            <td>
                                <?php if($row['added_by'] == 'User'): ?>
                                    <a href="income.php?delete_id=<?php echo $row['id']; ?>" class="action-icon" onclick="return confirm('Delete?')">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                <?php else: ?>
                                    <i class="fas fa-lock" style="color: #cbd5e1;"></i>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</body>
</html>
