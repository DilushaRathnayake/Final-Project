<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }
$user_id = $_SESSION['user_id'];

// --- 1. LIVE INCOME CALCULATION ---
$inc_query = "SELECT SUM(amount) as total_inc FROM income_records 
              WHERE user_id = '$user_id' 
              AND MONTH(date) = MONTH(CURRENT_DATE()) 
              AND YEAR(date) = YEAR(CURRENT_DATE())";
$inc_res = mysqli_query($conn, $inc_query);
$total_income = mysqli_fetch_assoc($inc_res)['total_inc'] ?? 0;

// --- 2. LIVE EXPENSE CALCULATION ---
$exp_query = "SELECT SUM(amount) as total_exp FROM expense_records 
              WHERE user_id = '$user_id' 
              AND MONTH(date) = MONTH(CURRENT_DATE()) 
              AND YEAR(date) = YEAR(CURRENT_DATE())";
$exp_res = mysqli_query($conn, $exp_query);
$total_spent = mysqli_fetch_assoc($exp_res)['total_exp'] ?? 0;

// --- 3. REMAINING BALANCE & DANGER ZONE ---
$remaining_balance = $total_income - $total_spent;
$usage_percent = ($total_income > 0) ? round(($total_spent / $total_income) * 100) : 0;
$is_danger = ($usage_percent >= 50 || $remaining_balance < 0);

// --- HANDLE DELETE ---
if (isset($_GET['delete_id'])) {
    $delete_id = mysqli_real_escape_string($conn, $_GET['delete_id']);
    mysqli_query($conn, "DELETE FROM expense_records WHERE id = '$delete_id' AND user_id = '$user_id'");
    header("Location: expenses.php");
    exit();
}

// --- HANDLE FORM SUBMISSION ---
if (isset($_POST['add_expense'])) {
    $amount   = mysqli_real_escape_string($conn, $_POST['amount']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $note      = mysqli_real_escape_string($conn, $_POST['note']);
    $type      = mysqli_real_escape_string($conn, $_POST['type']);
    $date      = $_POST['date'];

    $sql = "INSERT INTO expense_records (user_id, amount, category, note, type, date) 
            VALUES ('$user_id', '$amount', '$category', '$note', '$type', '$date')";
    
    if(mysqli_query($conn, $sql)) {
        header("Location: expenses.php?success=1");
        exit();
    }
}

$fetch_expenses = mysqli_query($conn, "SELECT * FROM expense_records WHERE user_id = '$user_id' ORDER BY date DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Budget | Expense Command Center</title>
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
            --warning: #f59e0b;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        
        body { 
            background: var(--bg); 
            color: var(--text-main); 
            display: flex; 
            min-height: 100vh; 
        }

        /* Danger Mode Background Effect */
        <?php if($is_danger): ?>
        body { background: linear-gradient(135deg, #f8fafc 0%, #fee2e2 100%); }
        <?php endif; ?>

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
            margin-left: 260px;
            width: calc(100% - 260px);
            padding: 40px;
        }

        .header-section { margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; }
        .header-section h2 { font-size: 1.8rem; font-weight: 700; letter-spacing: -0.5px; }
        
        .danger-msg { 
            background: #fff1f2; 
            border: 1px solid #fecaca; 
            padding: 18px; 
            border-radius: 15px; 
            color: var(--danger); 
            margin-bottom: 25px; 
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
            animation: blink 2s infinite; 
        }
        @keyframes blink { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.01); } }

        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { 
            background: var(--card-bg); 
            border: 1px solid var(--border); 
            padding: 25px; 
            border-radius: 20px; 
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            position: relative;
            overflow: hidden;
        }
        .stat-card::after { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px; }
        .card-primary::after { background: var(--primary); }
        .card-danger::after { background: var(--danger); }
        .card-success::after { background: var(--success); }

        .layout-grid { display: grid; grid-template-columns: 380px 1fr; gap: 30px; }
        .glass-card { 
            background: var(--card-bg); 
            border: 1px solid var(--border); 
            padding: 30px; 
            border-radius: 24px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.03);
        }

        label { display: block; font-size: 0.75rem; color: var(--text-sub); margin-bottom: 8px; text-transform: uppercase; font-weight: 700; }
        input, select { 
            width: 100%; padding: 12px; margin-bottom: 20px; background: #f8fafc; 
            border: 1px solid var(--border); color: var(--text-main); border-radius: 12px; outline: none; transition: 0.3s;
        }
        input:focus, select:focus { border-color: var(--primary); background: #fff; box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1); }
        
        .btn-submit { 
            width: 100%; padding: 15px; background: var(--primary); color: white; 
            border: none; border-radius: 12px; font-weight: 700; cursor: pointer; transition: 0.3s;
        }
        .btn-submit:hover { background: #4f46e5; transform: translateY(-2px); }

        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; color: var(--text-sub); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; border-bottom: 2px solid var(--bg); }
        td { padding: 18px 15px; border-bottom: 1px solid #f1f5f9; font-size: 0.9rem; }
        
        .tag { padding: 5px 10px; border-radius: 8px; font-size: 0.65rem; font-weight: 800; }
        .tag-fixed { background: rgba(99, 102, 241, 0.1); color: var(--primary); }
        .tag-var { background: rgba(245, 158, 11, 0.1); color: var(--warning); }

        .action-icon { color: var(--text-sub); transition: 0.2s; text-decoration: none; }
        .action-icon:hover { color: var(--danger); }

        @media (max-width: 1200px) {
            .layout-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-brand">SMART BUDGET</div>
    <div class="nav-links">
        <a href="dashboard.php" class="nav-item">
            <i class="fas fa-home"></i> <span>Dashboard</span>
        </a>
        
        <div class="nav-group-label">Money Management</div>
        <a href="income.php" class="nav-item">
            <i class="fas fa-hand-holding-usd"></i> <span>Live Income</span>
        </a>
        <a href="expenses.php" class="nav-item active">
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
            <i class="fas fa-chart-pie"></i> <span>Reports</span>
        </a>
    </div>

    <a href="logout.php" class="logout-link">
        <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
    </a>
</div>

<div class="main-wrapper">
    <div class="header-section">
        <div>
            <h2><i class="fas fa-bolt" style="color: var(--primary);"></i> Live Expense Control</h2>
            <p style="color: var(--text-sub);">Factory Employee Budget Monitoring Panel</p>
        </div>
    </div>

    <?php if($is_danger): ?>
    <div class="danger-msg">
        <i class="fas fa-exclamation-triangle" style="font-size: 1.2rem;"></i> 
        <span>CRITICAL LIMIT: You have spent <?php echo $usage_percent; ?>% of your current income.</span>
    </div>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card card-primary">
            <h4 style="color: var(--text-sub); font-size: 0.7rem; text-transform: uppercase; font-weight: 700;">Total Income</h4>
            <div style="font-size: 1.6rem; font-weight: 800; margin-top: 5px;">LKR <?php echo number_format($total_income, 2); ?></div>
        </div>
        <div class="stat-card card-danger">
            <h4 style="color: var(--text-sub); font-size: 0.7rem; text-transform: uppercase; font-weight: 700;">Total Spent</h4>
            <div style="font-size: 1.6rem; font-weight: 800; color: var(--danger); margin-top: 5px;">LKR <?php echo number_format($total_spent, 2); ?></div>
        </div>
        <div class="stat-card card-success">
            <h4 style="color: var(--text-sub); font-size: 0.7rem; text-transform: uppercase; font-weight: 700;">Remaining Balance</h4>
            <div style="font-size: 1.6rem; font-weight: 800; color: <?php echo ($remaining_balance < 0) ? 'var(--danger)' : 'var(--success)'; ?>; margin-top: 5px;">
                LKR <?php echo number_format($remaining_balance, 2); ?>
            </div>
        </div>
    </div>

    <div class="layout-grid">
        <div class="glass-card">
            <h3 style="margin-bottom: 25px; font-size: 1.1rem;"><i class="fas fa-plus-circle" style="color: var(--primary);"></i> Log Expense</h3>
            <form method="POST">
                <label>Amount (LKR)</label>
                <input type="number" name="amount" required step="0.01" placeholder="0.00">

                <label>Category</label>
                <select name="category">
                    <option value="Food">Food & Grocery</option>
                    <option value="Transport">Transport</option>
                    <option value="Education">Education</option>
                    <option value="Family">Family</option>
                    <option value="Health">Health</option>
                    <option value="Entertainment">Entertainment</option>
                    <option value="Seettu">Seettu Payment</option>
                    <option value="Loan">Loan Interest</option>
                    <option value="Other">Other</option>
                </select>

                <label>Type</label>
                <select name="type">
                    <option value="Variable">Variable</option>
                    <option value="Fixed">Fixed Cost</option>
                </select>

                <label>Note</label>
                <input type="text" name="note" placeholder="What was this for?">

                <label>Date</label>
                <input type="date" name="date" value="<?php echo date('Y-m-d'); ?>">

                <button type="submit" name="add_expense" class="btn-submit">Save Transaction</button>
            </form>
        </div>

        <div class="glass-card">
            <h3 style="margin-bottom: 25px; font-size: 1.1rem;"><i class="fas fa-history" style="color: var(--primary);"></i> Recent Transactions</h3>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Category</th>
                            <th>Note</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($fetch_expenses) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($fetch_expenses)): ?>
                            <tr>
                                <td style="color: var(--text-sub);"><?php echo date('M d', strtotime($row['date'])); ?></td>
                                <td style="font-weight: 700; color: var(--text-main);"><?php echo $row['category']; ?></td>
                                <td style="color: var(--text-sub);"><?php echo htmlspecialchars($row['note']); ?></td>
                                <td>
                                    <span class="tag <?php echo ($row['type'] == 'Fixed') ? 'tag-fixed' : 'tag-var'; ?>">
                                        <?php echo $row['type']; ?>
                                    </span>
                                </td>
                                <td style="color: var(--danger); font-weight: 800;">- <?php echo number_format($row['amount'], 2); ?></td>
                                <td>
                                    <a href="expenses.php?delete_id=<?php echo $row['id']; ?>" class="action-icon" onclick="return confirm('Delete this record?')">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-sub);">No expense records found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>