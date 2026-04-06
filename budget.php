<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }
$user_id = $_SESSION['user_id'];
$current_month = date('Y-m');

// --- 1. DELETE LOGIC ---
if (isset($_GET['delete_id'])) {
    $del_id = mysqli_real_escape_string($conn, $_GET['delete_id']);
    mysqli_query($conn, "DELETE FROM budgets WHERE id = '$del_id' AND user_id = '$user_id'");
    header("Location: budget.php?msg=deleted");
    exit();
}

// --- 2. FETCH TOTAL INCOME ---
$inc_res = mysqli_query($conn, "SELECT SUM(amount) as total FROM income_records WHERE user_id = '$user_id' AND DATE_FORMAT(date, '%Y-%m') = '$current_month'");
$total_income = mysqli_fetch_assoc($inc_res)['total'] ?? 0;

// --- 3. HANDLE BUDGET FORM ---
if (isset($_POST['save_budget'])) {
    $cat = mysqli_real_escape_string($conn, $_POST['category']);
    $amt = mysqli_real_escape_string($conn, $_POST['amount']);
    $pri = mysqli_real_escape_string($conn, $_POST['priority']);

    $check_q = mysqli_query($conn, "SELECT SUM(allocated_amount) as total_bud FROM budgets WHERE user_id = '$user_id' AND month_year = '$current_month'");
    $existing_total = mysqli_fetch_assoc($check_q)['total_bud'] ?? 0;

    if (($existing_total + $amt) > $total_income) {
        $error = "Warning: Total Budget (LKR " . number_format($existing_total + $amt) . ") exceeds your Monthly Income!";
    } else {
        mysqli_query($conn, "INSERT INTO budgets (user_id, category, allocated_amount, priority, month_year) VALUES ('$user_id', '$cat', '$amt', '$pri', '$current_month')");
        header("Location: budget.php?success=1");
        exit();
    }
}

// --- 4. FETCH DATA ---
$budget_list = mysqli_query($conn, "SELECT b.*, 
    (SELECT SUM(amount) FROM expense_records e WHERE e.user_id = b.user_id AND e.category = b.category AND DATE_FORMAT(e.date, '%Y-%m') = b.month_year) as spent 
    FROM budgets b WHERE b.user_id = '$user_id' AND b.month_year = '$current_month' ORDER BY priority DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Budget | Strategy Planning</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap');

        :root {
            /* Sidebar Styles (Matching Dashboard) */
            --sidebar-bg: #1e293b;
            --sidebar-active: #3b82f6;
            --sidebar-text: #cbd5e1;
            
            /* Main Content (Light) */
            --bg-light: #f1f5f9;
            --card-white: #ffffff;
            --text-dark: #0f172a;
            --text-muted: #64748b;
            --border-light: #e2e8f0;
            
            /* UI Colors */
            --primary: #6366f1;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--bg-light); color: var(--text-dark); display: flex; min-height: 100vh; }

        /* --- SIDEBAR (DASHBOARD STYLE) --- */
        .sidebar {
            width: 260px;
            background: var(--sidebar-bg);
            height: 100vh;
            position: fixed;
            padding: 30px 20px;
            display: flex;
            flex-direction: column;
            z-index: 1000;
        }

        .sidebar-brand { 
            font-size: 1.4rem; font-weight: 800; margin-bottom: 40px; color: #fff; 
            text-align: center; letter-spacing: 1px;
        }

        .nav-links { flex: 1; overflow-y: auto; }
        .nav-links::-webkit-scrollbar { width: 4px; }
        .nav-links::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); }

        .nav-group-label { 
            font-size: 0.7rem; text-transform: uppercase; color: #94a3b8; 
            margin: 25px 0 10px 10px; letter-spacing: 1px;
        }

        .nav-item {
            display: flex; align-items: center; padding: 12px 15px; color: var(--sidebar-text);
            text-decoration: none; border-radius: 10px; margin-bottom: 4px; transition: 0.3s;
            font-size: 0.9rem;
        }

        .nav-item i { margin-right: 12px; width: 20px; text-align: center; }
        .nav-item:hover, .nav-item.active { background: rgba(255, 255, 255, 0.1); color: white; }
        .nav-item.active { background: var(--sidebar-active); color: white; }

        .logout-link { 
            color: #fca5a5; text-decoration: none; display: flex; align-items: center; 
            padding: 15px; font-weight: 600; gap: 10px; border-top: 1px solid rgba(255,255,255,0.1);
            margin-top: auto; padding-top: 20px;
        }
        .logout-link:hover { color: #ef4444; }

        /* --- MAIN CONTENT (LIGHT) --- */
        .main-wrapper { margin-left: 260px; width: calc(100% - 260px); padding: 40px; }

        .info-banner { 
            background: var(--card-white); border: 1px solid var(--border-light); 
            padding: 25px; border-radius: 20px; display: flex; 
            justify-content: space-between; align-items: center; margin-bottom: 30px; 
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        .layout-grid { display: grid; grid-template-columns: 360px 1fr; gap: 25px; }
        .card { 
            background: var(--card-white); border: 1px solid var(--border-light); 
            padding: 25px; border-radius: 20px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); 
        }

        /* LIGHT FORMS */
        label { display: block; font-size: 0.75rem; color: var(--text-muted); margin-bottom: 8px; text-transform: uppercase; font-weight: 700; }
        input, select { 
            width: 100%; padding: 12px; margin-bottom: 20px; background: #f8fafc; 
            border: 1px solid var(--border-light); color: var(--text-dark); border-radius: 12px; outline: none;
        }
        input:focus { border-color: var(--sidebar-active); background: #fff; }

        .btn-main { 
            width: 100%; padding: 14px; border: none; background: #6366f1; 
            color: white; font-weight: 700; border-radius: 12px; cursor: pointer; transition: 0.3s; 
        }
        .btn-main:hover { background: #4f46e5; transform: translateY(-2px); }

        /* LIST ITEMS */
        .budget-item { background: #f8fafc; border: 1px solid var(--border-light); padding: 20px; border-radius: 18px; margin-bottom: 15px; position: relative; }
        .progress-container { width: 100%; height: 8px; background: #e2e8f0; border-radius: 10px; margin: 15px 0; overflow: hidden; }
        .progress-bar { height: 100%; transition: 0.8s ease; }

        .badge { padding: 4px 10px; border-radius: 6px; font-size: 0.65rem; font-weight: 700; text-transform: uppercase; margin-left: 10px; }
        .badge-need { background: #dbeafe; color: #1e40af; }
        .badge-want { background: #fef9c3; color: #854d0e; }

        .delete-btn { position: absolute; top: 20px; right: 20px; color: #94a3b8; transition: 0.3s; }
        .delete-btn:hover { color: var(--danger); }

        .alert { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; padding: 15px; border-radius: 12px; margin-bottom: 20px; font-size: 0.9rem; }
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
        <a href="expenses.php" class="nav-item">
            <i class="fas fa-shopping-cart"></i> <span>Live Expenses</span>
        </a>
        <a href="budget.php" class="nav-item active">
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
    </div>

    <div style="margin-top: auto; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;">
        <a href="logout.php" class="logout-link">
            <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
        </a>
    </div>
</div>


    <a href="logout.php" class="logout-link">
        <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
    </a>
</div>

<div class="main-wrapper">
    <div class="info-banner">
        <div>
            <h2 style="font-weight: 700;">Budget Strategy</h2>
            <p style="color: var(--text-sub); font-size: 0.9rem;">Nuwara Eliya Regional Model Active</p>
        </div>
        <div style="text-align: right;">
            <p style="color: var(--text-muted); font-size: 0.75rem; font-weight: 700;">MONTHLY INCOME LIMIT</p>
            <h3 style="color: var(--success); margin: 0; font-size: 1.6rem; font-weight: 800;">LKR <?php echo number_format($total_income); ?></h3>
        </div>
    </div>

    <?php if(isset($error)): ?>
        <div class="alert"><i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?></div>
    <?php endif; ?>

    <div class="layout-grid">
        <div class="card">
            <h3 style="font-size: 1rem; margin-bottom: 20px;">Create Envelope</h3>
            <form method="POST">
                <label>Category</label>
                <select name="category">
                    <option value="Food">Food & Grocery</option>
                    <option value="Seettu">Seettu Payment</option>
                    <option value="Transport">Transport</option>
                    <option value="Loan">Debt/Loans</option>
                    <option value="Family">Family Support</option>
                    <option value="Other">Miscellaneous</option>
                </select>

                <label>Allocated Amount (LKR)</label>
                <input type="number" name="amount" placeholder="e.g. 15000" required>

                <label>Priority Level</label>
                <select name="priority">
                    <option value="Need">Need (Essential)</option>
                    <option value="Want">Want (Optional)</option>
                </select>

                <button type="submit" name="save_budget" class="btn-main">Save Envelope</button>
            </form>
        </div>

        <div class="card">
            <h3 style="font-size: 1rem; margin-bottom: 20px; display: flex; justify-content: space-between;">
                Monthly Plan <span><?php echo date('F Y'); ?></span>
            </h3>
            
            <?php while($row = mysqli_fetch_assoc($budget_list)): 
                $spent = $row['spent'] ?? 0;
                $limit = $row['allocated_amount'];
                $percent = ($limit > 0) ? ($spent / $limit) * 100 : 0;
                
                $bar_color = "var(--success)";
                if($percent > 75) $bar_color = "var(--warning)";
                if($percent > 100) $bar_color = "var(--danger)";
            ?>
            <div class="budget-item">
                <a href="budget.php?delete_id=<?php echo $row['id']; ?>" class="delete-btn" onclick="return confirm('Remove this plan?')">
                    <i class="fas fa-trash-alt"></i>
                </a>
                
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <span style="font-weight: 700; color: var(--text-dark);"><?php echo $row['category']; ?></span>
                        <span class="badge <?php echo ($row['priority'] == 'Need') ? 'badge-need' : 'badge-want'; ?>">
                            <?php echo $row['priority']; ?>
                        </span>
                    </div>
                    <div style="text-align: right;">
                        <span style="font-weight: 800; color: <?php echo ($percent > 100) ? 'var(--danger)' : 'var(--text-dark)'; ?>">
                            LKR <?php echo number_format($spent, 0); ?>
                        </span>
                        <small style="color: var(--text-muted);"> / <?php echo number_format($limit, 0); ?></small>
                    </div>
                </div>

                <div class="progress-container">
                    <div class="progress-bar" style="width: <?php echo min($percent, 100); ?>%; background: <?php echo $bar_color; ?>;"></div>
                </div>

                <div style="display: flex; justify-content: space-between; font-size: 0.7rem; color: var(--text-muted); font-weight: 600;">
                    <span><?php echo round($percent); ?>% used</span>
                    <span style="color: <?php echo ($limit-$spent < 0) ? 'var(--danger)' : 'var(--success)'; ?>">
                        Balance: LKR <?php echo number_format($limit - $spent, 0); ?>
                    </span>
                </div>
            </div>
            <?php endwhile; ?>
            
            <?php if(mysqli_num_rows($budget_list) == 0): ?>
                <div style="text-align: center; color: var(--text-muted); padding: 40px;">
                    <i class="fas fa-piggy-bank fa-2x" style="margin-bottom: 10px; opacity: 0.2;"></i>
                    <p>No budget plans created.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>