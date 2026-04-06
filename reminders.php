<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }
$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'] ?? 'User';

// --- 1. ADD NEW REMINDER ---
if (isset($_POST['add_reminder'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $amt = mysqli_real_escape_string($conn, $_POST['amount']);
    $date = mysqli_real_escape_string($conn, $_POST['due_date']);
    $cat = mysqli_real_escape_string($conn, $_POST['category']);

    mysqli_query($conn, "INSERT INTO reminders (user_id, title, amount, due_date, category, status) 
                         VALUES ('$user_id', '$title', '$amt', '$date', '$cat', 'Pending')");
    header("Location: reminders.php?added=1");
    exit();
}

// --- 2. MARK AS PAID (Auto-Expense Logic) ---
if (isset($_GET['pay_id'])) {
    $id = $_GET['pay_id'];
    $res = mysqli_query($conn, "SELECT * FROM reminders WHERE id = '$id' AND user_id = '$user_id'");
    $data = mysqli_fetch_assoc($res);

    if ($data) {
        $amt = $data['amount'];
        $title = "Paid: " . $data['title'];
        $cat = $data['category'];
        
        // Update Reminder Status
        mysqli_query($conn, "UPDATE reminders SET status = 'Paid' WHERE id = '$id'");
        
        // Auto-Add to Expenses
        mysqli_query($conn, "INSERT INTO expense_records (user_id, category, amount, description, date) 
                             VALUES ('$user_id', '$cat', '$amt', '$title', CURRENT_DATE())");
        
        header("Location: reminders.php?success=1");
        exit();
    }
}

$reminders = mysqli_query($conn, "SELECT * FROM reminders WHERE user_id = '$user_id' AND status = 'Pending' ORDER BY due_date ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Budget | Reminders</title>
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
            --danger: #ef4444;
            --warning: #f59e0b;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: var(--bg); color: var(--text-main); font-family: 'Inter', sans-serif; display: flex; }

        /* SIDEBAR */
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
        .main-grid { display: grid; grid-template-columns: 1fr 350px; gap: 30px; }
        .card { background: var(--card-bg); border: 1px solid var(--border); padding: 25px; border-radius: 20px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }

        /* REMINDER ITEMS */
        .reminder-item { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            padding: 18px 0; 
            border-bottom: 1px solid #f1f5f9; 
        }
        .reminder-item:last-child { border-bottom: none; }
        
        .date-badge { 
            padding: 6px 12px; 
            border-radius: 8px; 
            font-size: 0.75rem; 
            font-weight: 700; 
            display: inline-flex; 
            align-items: center; 
            gap: 6px; 
        }

        /* FORM STYLES */
        .input-group { margin-bottom: 18px; }
        .input-group label { display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-sub); margin-bottom: 8px; }
        .input-group input, .input-group select { 
            width: 100%; padding: 12px; 
            background: #f8fafc; 
            border: 1px solid var(--border); 
            border-radius: 12px; 
            font-family: inherit;
        }

        .btn-pay { 
            background: var(--primary); 
            color: white; 
            padding: 8px 16px; 
            border-radius: 8px; 
            text-decoration: none; 
            font-size: 0.75rem; 
            font-weight: 600;
            transition: 0.2s;
        }
        .btn-pay:hover { opacity: 0.9; transform: translateY(-1px); }

        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
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
        <a href="seettu.php" class="nav-item">
            <i class="fas fa-users-cog"></i> <span>Seettu Groups</span>
        </a> 
        <a href="reminders.php" class="nav-item active">
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
            <h2 style="font-weight: 700;">Reminders & Alerts</h2>
            <p style="color: var(--text-sub); font-size: 0.9rem;">Manage your upcoming bills and payments</p>
        </div>
        <?php if(isset($_GET['success'])): ?>
            <div style="color: var(--success); font-weight: 600; font-size: 0.85rem;"><i class="fas fa-check-circle"></i> Payment Recorded!</div>
        <?php endif; ?>
    </div>

    <div class="main-grid">
        <div>
            <div class="card">
                <h3 style="font-size: 1.1rem; margin-bottom: 20px;"><i class="fas fa-clock" style="color: var(--warning);"></i> Pending Tasks</h3>
                
                <?php if(mysqli_num_rows($reminders) > 0): ?>
                    <?php while($row = mysqli_fetch_assoc($reminders)): 
                        $today = date('Y-m-d');
                        $is_overdue = ($row['due_date'] < $today);
                    ?>
                    <div class="reminder-item">
                        <div>
                            <h4 style="font-weight: 700; font-size: 1rem;"><?php echo htmlspecialchars($row['title']); ?></h4>
                            <p style="color: var(--text-sub); font-size: 0.8rem; margin-top: 4px;">
                                <span style="color: var(--primary); font-weight: 600;"><?php echo $row['category']; ?></span> • 
                                <span style="font-weight: 700; color: var(--text-main);">LKR <?php echo number_format($row['amount']); ?></span>
                            </p>
                        </div>
                        <div style="text-align: right;">
                            <div class="date-badge" style="<?php echo $is_overdue ? 'background:#fee2e2; color:var(--danger);' : 'background:#fef3c7; color:var(--warning);'; ?>">
                                <i class="far fa-calendar-alt"></i> Due: <?php echo $row['due_date']; ?>
                            </div>
                            <div style="margin-top: 12px;">
                                <a href="reminders.php?pay_id=<?php echo $row['id']; ?>" class="btn-pay">Mark as Paid</a>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px 0; color: var(--text-sub);">
                        <i class="fas fa-calendar-check" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.2;"></i>
                        <p>No pending payments. You're all caught up!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div>
            <div class="card">
                <h3 style="font-size: 1.1rem; margin-bottom: 20px;"><i class="fas fa-plus-circle" style="color: var(--primary);"></i> New Reminder</h3>
                <form method="POST">
                    <div class="input-group">
                        <label>Title</label>
                        <input type="text" name="title" required placeholder="e.g. Boarding Fee">
                    </div>
                    <div class="input-group">
                        <label>Amount (LKR)</label>
                        <input type="number" name="amount" required placeholder="0.00">
                    </div>
                    <div class="input-group">
                        <label>Due Date</label>
                        <input type="date" name="due_date" required>
                    </div>
                    <div class="input-group">
                        <label>Category</label>
                        <select name="category">
                            <option value="Bill">Utility Bill</option>
                            <option value="Boarding">Boarding/Rent</option>
                            <option value="Loan">Loan EMI</option>
                            <option value="Seettu">Seettu Payment</option>
                            <option value="Other">Other Payment</option>
                        </select>
                    </div>
                    <button type="submit" name="add_reminder" style="width:100%; padding:14px; background:var(--primary); color:white; border:none; border-radius:12px; cursor:pointer; font-weight:700; transition: 0.2s;">
                        Set Reminder
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

</body>
</html>