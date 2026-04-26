<?php
session_start();
require_once 'config.php';

// Security: Admin ද කියලා පරීක්ෂා කිරීම (මෙය අවසානයේ activate කරගන්න)
// if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { header("Location: login.php"); exit(); }

// --- 1. සක්‍රීය සේවකයින් ගණන (Active Staff) ---
$emp_query = mysqli_query($conn, "SELECT COUNT(user_id) as total_emp FROM users WHERE role != 'admin'");
$emp_data = mysqli_fetch_assoc($emp_query);
$total_employees = $emp_data['total_emp'] ?? 0;

// --- 2. මෙම මාසයේ ගෙවූ මුළු පඩි මුදල (Current Month Payroll) ---
$current_month = date('m');
$current_year = date('Y');
$pay_query = mysqli_query($conn, "SELECT SUM(net_salary) as total_paid FROM salary_records 
                                  WHERE MONTH(pay_date) = '$current_month' AND YEAR(pay_date) = '$current_year'");
$pay_data = mysqli_fetch_assoc($pay_query);
$total_payroll = $pay_data['total_paid'] ?? 0;

// --- 3. සක්‍රීය සීරට්ටු කණ්ඩායම් ගණන (Active Seettu Groups) ---
$seettu_query = mysqli_query($conn, "SELECT COUNT(id) as active_groups FROM factory_seettu WHERE status = 'active'");
$seettu_data = mysqli_fetch_assoc($seettu_query);
$total_seettu = $seettu_data['active_groups'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Smart Budget</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap');

        :root {
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --sidebar-bg: #0f172a;
            --bg-body: #f8fafc;
            --white: #ffffff;
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --success: #10b981;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }

        body { background-color: var(--bg-body); display: flex; min-height: 100vh; color: var(--text-dark); }

        /* --- SIDEBAR --- */
        .sidebar {
            width: 280px;
            background: var(--sidebar-bg);
            color: var(--white);
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            z-index: 1000;
            transition: all 0.3s;
        }

        .sidebar-header {
            padding: 40px 25px;
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: -1px;
            color: var(--white);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-header span { color: #818cf8; }

        .nav-links { padding: 10px 15px; flex-grow: 1; }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 14px 18px;
            color: #94a3b8;
            text-decoration: none;
            border-radius: 12px;
            margin-bottom: 5px;
            transition: all 0.3s;
            font-weight: 500;
        }

        .nav-item i { width: 25px; font-size: 1.2rem; margin-right: 10px; }

        .nav-item:hover, .nav-item.active {
            background: rgba(99, 102, 241, 0.1);
            color: var(--white);
        }

        .nav-item.active { background: var(--primary); color: white; }

        .logout-section { padding: 20px 15px; border-top: 1px solid rgba(255,255,255,0.05); }

        /* --- MAIN CONTENT --- */
        .main-content {
            margin-left: 280px;
            width: calc(100% - 280px);
            padding: 40px;
        }

        .top-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 15px;
            background: var(--white);
            padding: 8px 20px 8px 8px;
            border-radius: 50px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.03);
            border: 1px solid #e2e8f0;
        }

        .user-profile img { width: 38px; height: 38px; border-radius: 50%; }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: var(--white);
            padding: 25px;
            border-radius: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.02);
            transition: transform 0.3s ease;
        }

        .stat-card:hover { transform: translateY(-5px); }

        .stat-card h3 { font-size: 1.6rem; margin: 5px 0; font-weight: 700; color: var(--text-dark); }
        .stat-card p { color: var(--text-muted); font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }

        .stat-icon {
            width: 55px; height: 55px;
            border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem;
        }

        /* Action Hub */
        .section-title { margin-bottom: 20px; font-size: 1.1rem; font-weight: 700; color: var(--text-dark); }

        .action-hub {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
        }

        .action-card {
            background: linear-gradient(135deg, #6366f1 0%, #4338ca 100%);
            color: white;
            padding: 35px;
            border-radius: 24px;
            position: relative;
            overflow: hidden;
            cursor: pointer;
            text-decoration: none;
            transition: 0.3s;
        }

        .action-card:hover { box-shadow: 0 20px 25px -5px rgba(99, 102, 241, 0.4); }

        .action-card h2 { font-size: 1.4rem; margin-bottom: 12px; font-weight: 700; }
        .action-card p { font-size: 0.9rem; opacity: 0.9; line-height: 1.5; max-width: 80%; }

        .action-card i.bg-icon {
            position: absolute;
            right: -15px;
            bottom: -15px;
            font-size: 7rem;
            opacity: 0.15;
            transform: rotate(-15deg);
        }

        @media (max-width: 1024px) {
            .sidebar { width: 85px; }
            .sidebar-header, .nav-item span { display: none; }
            .main-content { margin-left: 85px; width: calc(100% - 85px); }
        }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-bolt"></i> SMART<span>BUDGET</span>
        </div>
        
        <nav class="nav-links">
            <a href="admin_dashboard.php" class="nav-item active">
                <i class="fas fa-house"></i> <span>Dashboard</span>
            </a>
            <a href="manage_employees.php" class="nav-item">
                <i class="fas fa-user-group"></i> <span>Employees</span>
            </a>
            <a href="salary_process.php" class="nav-item">
                <i class="fas fa-wallet"></i> <span>Payroll Hub</span>
            </a>
            <a href="factory_seettu.php" class="nav-item">
                <i class="fas fa-layer-group"></i> <span>Seettu System</span>
            </a>
            <a href="profile.php" class="nav-item">
                <i class="fas fa-circle-user"></i> <span>Admin Profile</span>
            </a>
        </nav>

        <div class="logout-section">
            <a href="logout.php" class="nav-item" style="color: #fb7185;">
                <i class="fas fa-power-off"></i> <span>Sign Out</span>
            </a>
        </div>
    </aside>

    <main class="main-content">
        <header class="top-nav">
            <div>
                <h1 style="font-size: 1.7rem; font-weight: 800; color: var(--text-dark);">Admin Overview</h1>
                <p style="color: var(--text-muted); margin-top: 4px;">Track and manage factory financial operations.</p>
            </div>
            <div class="user-profile">
                <img src="https://ui-avatars.com/api/?name=Admin&background=6366f1&color=fff" alt="Profile">
                <span style="font-weight: 700; font-size: 0.85rem; color: var(--text-dark);">SYSTEM ADMIN</span>
            </div>
        </header>

        <section class="stats-grid">
            <div class="stat-card">
                <div>
                    <p>Total Employees</p>
                    <h3><?php echo number_format($total_employees); ?></h3>
                </div>
                <div class="stat-icon" style="background: #eef2ff; color: #6366f1;">
                    <i class="fas fa-users-viewfinder"></i>
                </div>
            </div>

            <div class="stat-card">
                <div>
                    <p>Monthly Payroll</p>
                    <h3>LKR <?php echo number_format($total_payroll / 1000, 1); ?>k</h3>
                    <span style="font-size: 0.7rem; color: var(--text-muted);">Total: <?php echo number_format($total_payroll, 0); ?></span>
                </div>
                <div class="stat-icon" style="background: #ecfdf5; color: #10b981;">
                    <i class="fas fa-hand-holding-dollar"></i>
                </div>
            </div>

            <div class="stat-card">
                <div>
                    <p>Active Seettu</p>
                    <h3><?php echo $total_seettu; ?> Groups</h3>
                </div>
                <div class="stat-icon" style="background: #fff7ed; color: #f59e0b;">
                    <i class="fas fa-boxes-stacked"></i>
                </div>
            </div>
        </section>

        <h3 class="section-title">Management Hub</h3>
        <div class="action-hub">
            <a href="salary_process.php" class="action-card">
                <h2>Payroll Center</h2>
                <p>Process monthly wages, EPF/ETF contributions, and print employee pay slips.</p>
                <i class="fas fa-file-invoice-dollar bg-icon"></i>
            </a>

            <a href="factory_seettu.php" class="action-card" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                <h2>Seettu Portal</h2>
                <p>Monitor community savings, manage groups, and track monthly lucky winners.</p>
                <i class="fas fa-users-rays bg-icon"></i>
            </a>
        </div>

        <div style="margin-top: 40px; background: white; padding: 25px; border-radius: 20px; border: 1px solid #e2e8f0; display: flex; align-items: center; gap: 15px;">
            <div style="width: 10px; height: 10px; background: #10b981; border-radius: 50%; box-shadow: 0 0 10px #10b981;"></div>
            <p style="font-size: 0.85rem; color: var(--text-muted); font-weight: 500;">
                Database Synced: <strong><?php echo date('Y-m-d H:i'); ?></strong>. All payroll systems operational.
            </p>
        </div>
    </main>

</body>
</html>
