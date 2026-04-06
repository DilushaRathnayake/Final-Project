<?php
session_start();
require_once 'config.php';

// Security: Admin ද කියලා පරීක්ෂා කිරීම (මෙය පසුව activate කරගන්න)
// if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { header("Location: login.php"); exit(); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Smart Budget</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        :root {
            --sidebar-bg: #0f172a; /* Dark Navy */
            --sidebar-hover: #1e293b;
            --primary: #6366f1; /* Indigo */
            --accent: #10b981; /* Emerald */
            --bg-light: #f8fafc;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --white: #ffffff;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }

        body { background-color: var(--bg-light); display: flex; min-height: 100vh; }

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

        /* --- MAIN CONTENT STYLE --- */
        .main-content {
            flex-grow: 1;
            margin-left: 260px; /* Sidebar width */
            padding: 30px;
        }

        .header-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .welcome-text h1 { font-size: 1.6rem; color: var(--text-main); }
        .welcome-text p { color: var(--text-muted); font-size: 0.9rem; }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: var(--white);
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
            display: flex;
            align-items: center;
            border: 1px solid #edf2f7;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            margin-right: 15px;
        }

        .icon-blue { background: #eef2ff; color: #6366f1; }
        .icon-green { background: #ecfdf5; color: #10b981; }

        .stat-info h3 { font-size: 1.4rem; color: var(--text-main); }
        .stat-info p { font-size: 0.85rem; color: var(--text-muted); }

        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .sidebar-header h2, .nav-item span { display: none; }
            .main-content { margin-left: 70px; }
        }
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
            <a href="profile.php" class="nav-item">
    <i class="fas fa-user-circle"></i> <span>My Profile</span>
</a>
        </div>
        

        <div class="logout-section">
            <a href="logout.php" class="nav-item" style="color: #fca5a5;">
                <i class="fas fa-sign-out-alt"></i> <span>Log Out</span>
            </a>
        </div>
    </div>

    <div class="main-content">
        <div class="header-bar">
            <div class="welcome-text">
                <h1>Admin Overview</h1>
                <p>Welcome back! Here's what's happening today.</p>
            </div>
            <div class="admin-profile">
                <img src="https://ui-avatars.com/api/?name=Admin&background=6366f1&color=fff" alt="Admin" style="width: 45px; border-radius: 50%;">
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon icon-blue">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3>51</h3>
                    <p>Total Employees</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon icon-green">
                    <i class="fas fa-wallet"></i>
                </div>
                <div class="stat-info">
                    <h3>LKR 0.00</h3>
                    <p>Total Salary Paid</p>
                </div>
            </div>
        </div>

        <div style="background: white; padding: 50px; border-radius: 20px; border: 2px dashed #e2e8f0; text-align: center; color: #94a3b8;">
            <i class="fas fa-plus-circle" style="font-size: 3rem; margin-bottom: 15px;"></i>
            <h3>Ready to add Salary or Seettu Modules</h3>
            <p>Click on the sidebar links to start managing your data.</p>
        </div>
    </div>

</body>
</html>