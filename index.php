<?php 
require_once 'lang.php';

if(isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $text['title']; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&family=Noto+Sans+Sinhala&family=Noto+Sans+Tamil&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-blue: #2563eb;
            --dark-bg: #0f172a;
            --glass: rgba(255, 255, 255, 0.07);
            --glass-border: rgba(255, 255, 255, 0.15);
            --accent-gold: #fbbf24;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: radial-gradient(circle at top right, #1e293b, #0f172a);
            color: white;
            font-family: 'Inter', 'Noto Sans Sinhala', 'Noto Sans Tamil', sans-serif;
            min-height: 100vh;
            overflow-x: hidden;
        }

        nav {
            display: flex; justify-content: space-between; align-items: center; padding: 20px 8%;
            background: rgba(15, 23, 42, 0.8); backdrop-filter: blur(10px);
            position: sticky; top: 0; z-index: 1000; border-bottom: 1px solid var(--glass-border);
        }
        .logo-badge {
            background: white; color: var(--primary-blue);
            padding: 5px 20px; border-radius: 8px; font-weight: 800; font-size: 1.5rem;
        }
        .lang-switch a { 
            font-size: 0.85rem; 
            color: #94a3b8; 
            text-decoration: none; 
            margin: 0 5px; 
            transition: 0.3s;
        }
        .lang-switch a.active { color: white; font-weight: bold; }

        .hero-container {
            display: flex; align-items: center; justify-content: center;
            padding: 80px 8%; gap: 60px; flex-wrap: wrap;
        }

        .hero-text { flex: 1; max-width: 600px; }
        .hero-text h2 { font-size: 3rem; margin-bottom: 20px; line-height: 1.1; }
        .hero-text p { color: #94a3b8; font-size: 1.15rem; margin-bottom: 35px; }

        .auth-card {
            background: var(--glass); backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border); border-radius: 24px;
            width: 100%; max-width: 450px; padding: 40px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
        }

        .tabs { display: flex; margin-bottom: 25px; border-bottom: 1px solid var(--glass-border); }
        .tab {
            flex: 1; text-align: center; padding: 10px; cursor: pointer;
            color: #94a3b8; transition: 0.3s; font-weight: 600;
        }
        .tab.active { color: white; border-bottom: 2px solid var(--primary-blue); }

        .form-section { display: none; }
        .form-section.active { display: block; }

        input, select {
            width: 100%; padding: 14px; margin-bottom: 15px; border-radius: 12px;
            border: 1px solid var(--glass-border); background: rgba(255,255,255,0.05);
            color: white; outline: none; font-size: 0.95rem; transition: 0.3s;
        }
        input:focus { border-color: var(--primary-blue); background: rgba(255,255,255,0.1); }
        option { background: #1e293b; color: white; }

        .btn-auth {
            width: 100%; padding: 15px; background: var(--primary-blue);
            color: white; border: none; border-radius: 12px;
            font-weight: 600; cursor: pointer; transition: 0.3s; font-size: 1rem;
        }
        .btn-auth:hover { background: #1d4ed8; transform: translateY(-2px); box-shadow: 0 10px 20px rgba(37, 99, 235, 0.3); }

        .stats-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px; padding: 40px 8%; background: rgba(255,255,255,0.02);
            border-top: 1px solid var(--glass-border); border-bottom: 1px solid var(--glass-border);
        }
        .stat-item { text-align: center; padding: 20px; }
        .stat-item h3 { font-size: 2rem; color: var(--accent-gold); margin-bottom: 5px; }
        .stat-item p { color: #94a3b8; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px; }

        .features-section { padding: 80px 8%; text-align: center; }
        .features-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px; margin-top: 50px;
        }
        .feature-card {
            background: var(--glass); padding: 40px; border-radius: 20px;
            border: 1px solid var(--glass-border); transition: 0.3s;
        }
        .feature-card:hover { transform: translateY(-10px); border-color: var(--primary-blue); }
        .feature-card i { font-size: 2.5rem; color: var(--primary-blue); margin-bottom: 20px; }
        .feature-card h4 { margin-bottom: 15px; font-size: 1.3rem; }
        .feature-card p { color: #94a3b8; line-height: 1.6; }

        .feature-tag {
            display: inline-block; padding: 6px 14px; background: rgba(37,99,235,0.15);
            border-radius: 20px; font-size: 0.85rem; margin-bottom: 15px; color: #60a5fa; font-weight: 600;
        }
    </style>
</head>
<body>

<nav>
    <div class="logo-badge">Smart Budget</div>
    <div class="lang-switch">
        <a href="?lang=si" class="<?php echo $current_lang == 'si' ? 'active' : ''; ?>">සිංහල</a> | 
        <a href="?lang=ta" class="<?php echo $current_lang == 'ta' ? 'active' : ''; ?>">தமிழ்</a> | 
        <a href="?lang=en" class="<?php echo $current_lang == 'en' ? 'active' : ''; ?>">English</a>
    </div>
</nav>

<div class="hero-container">
    <div class="hero-text">
        <span class="feature-tag"><i class="fas fa-map-marker-alt"></i> Nuwara Eliya District Factory Portal</span>
        <h2><?php echo $text['hero_h2']; ?></h2>
        <p><?php echo $text['hero_p']; ?></p>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div style="background:var(--glass); padding:20px; border-radius:16px; border: 1px solid var(--glass-border);">
                <i class="fas fa-shield-alt" style="color:var(--primary-blue); font-size: 1.5rem;"></i>
                <h4 style="margin-top:15px"><?php echo $text['loan_guard']; ?></h4>
                <p style="font-size:0.85rem; color:#94a3b8; margin-top: 5px;"><?php echo $text['loan_p']; ?></p>
            </div>
            <div style="background:var(--glass); padding:20px; border-radius:16px; border: 1px solid var(--glass-border);">
                <i class="fas fa-piggy-bank" style="color:var(--primary-blue); font-size: 1.5rem;"></i>
                <h4 style="margin-top:15px"><?php echo $text['seettu']; ?></h4>
                <p style="font-size:0.85rem; color:#94a3b8; margin-top: 5px;"><?php echo $text['seettu_p']; ?></p>
            </div>
        </div>
    </div>

    <div class="auth-card">
        <div class="tabs">
            <div class="tab active" onclick="switchTab('login')"><?php echo $text['tab_login']; ?></div>
            <div class="tab" onclick="switchTab('register')"><?php echo $text['tab_register']; ?></div>
        </div>

        <div id="login" class="form-section active">
            <form action="auth_logic.php" method="POST">
                <input type="text" name="nic" placeholder="<?php echo $text['nic']; ?>" required>
                <input type="password" name="password" placeholder="<?php echo $text['password']; ?>" required>
                <button type="submit" name="login" class="btn-auth"><?php echo $text['btn_login']; ?></button>
            </form>
        </div>

        <div id="register" class="form-section">
            <form action="auth_logic.php" method="POST">
                <input type="text" name="full_name" placeholder="<?php echo $text['full_name']; ?>" required>
                <input type="text" name="nic" placeholder="<?php echo $text['nic']; ?>" required>
                <input type="text" name="factory" placeholder="<?php echo $text['factory']; ?>">
                
                <select name="dept" required>
    <option value="" disabled selected>Select Department</option>
    <option value="Sewigning">Sewigning</option>
    <option value="Design & Sampling">Design & Sampling</option>
    <option value="Production">Production / Sewing</option>
    <option value="Cutting">Cutting</option>
    <option value="Quality Control">Quality Control (QC)</option>
    <option value="Finishing">Finishing</option>
    <option value="Planning">Planning</option>
    <option value="HR">Human Resources (HR)</option>
    <option value="Finance">Finance & Accounting</option>
    <option value="Stores">Stores / Inventory</option>
    <option value="Maintenance">Maintenance</option>
    <option value="Procurement">Procurement / Purchasing</option>
    <option value="Logistics">Shipping / Logistics</option>
</select>

                <input type="email" name="email" placeholder="Enter Email Address" required>
                
                <select name="role" required>
                    <option value="" disabled selected><?php echo $text['role_select']; ?></option>
                    <option value="user"><?php echo $text['role_user']; ?></option>
                    <option value="supervisor"><?php echo $text['role_super']; ?></option>
                    <option value="admin"><?php echo $text['role_admin']; ?></option>
                </select>

                <input type="password" name="password" placeholder="<?php echo $text['password']; ?>" required>
                <button type="submit" name="register" class="btn-auth"><?php echo $text['btn_register']; ?></button>
            </form>
        </div>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-item">
        <h3>14+</h3>
        <p><?php echo $text['stats_tables']; ?></p>
    </div>
    <div class="stat-item">
        <h3>100%</h3>
        <p><?php echo $text['stats_privacy']; ?></p>
    </div>
    <div class="stat-item">
        <h3>3</h3>
        <p><?php echo $text['stats_lang']; ?></p>
    </div>
    <div class="stat-item">
        <h3>24/7</h3>
        <p><?php echo $text['stats_access']; ?></p>
    </div>
</div>

<section class="features-section">
    <span class="feature-tag">Unique Capabilities</span>
    <h3><?php echo $text['feat_h3']; ?></h3>
    
    <div class="features-grid">
        <div class="feature-card">
            <i class="fas fa-calendar-check"></i>
            <h4><?php echo $text['feat1_h4']; ?></h4>
            <p><?php echo $text['feat1_p']; ?></p>
        </div>
        <div class="feature-card">
            <i class="fas fa-chart-pie"></i>
            <h4><?php echo $text['feat2_h4']; ?></h4>
            <p><?php echo $text['feat2_p']; ?></p>
        </div>
        <div class="feature-card">
            <i class="fas fa-lightbulb"></i>
            <h4><?php echo $text['feat3_h4']; ?></h4>
            <p><?php echo $text['feat3_p']; ?></p>
        </div>
    </div>
</section>

<script>
    function switchTab(tabId) {
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.form-section').forEach(s => s.classList.remove('active'));
        event.currentTarget.classList.add('active');
        document.getElementById(tabId).classList.add('active');
    }
</script>

</body>
</html>