<?php
require_once __DIR__ . '/config.php';

// If user is already logged in, redirect to home
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: home.php");
    exit();
}

// Handle trial start
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_trial'])) {
    // Redirect to registration
    header("Location: register.php?trial=1");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<link rel="icon" type="image/png" href="Logo.png">
<link rel="manifest" href="manifest.json">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Start Free Trial - Quotation Management System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #000000 0%, #0a0a0a 100%);
            color: #fff;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 40px;
            background: linear-gradient(135deg, #000000 0%, #1a1a1a 100%);
            box-shadow: 0 2px 12px rgba(45, 212, 191, 0.16);
            border-bottom: 1px solid #2dd4bf22;
            z-index: 1000;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-image {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: 2px solid #2dd4bf;
        }

        .company-name {
            font-size: 20px;
            font-weight: bold;
            color: #2dd4bf;
        }

        .nav-links {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .nav-links a {
            color: #fff;
            text-decoration: none;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .nav-links a:hover {
            background-color: rgba(45, 212, 191, 0.1);
            color: #2dd4bf;
        }

        .trial-container {
            max-width: 900px;
            width: 100%;
            margin-top: 100px;
            text-align: center;
            animation: fadeInUp 0.8s ease forwards;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .trial-header h1 {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #2dd4bf 0%, #14b8a6 50%, #0d9488 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1.2;
        }

        .trial-header p {
            font-size: 1.3rem;
            color: #ccc;
            margin-bottom: 40px;
            line-height: 1.6;
        }

        .trial-features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin: 50px 0;
        }

        .feature-box {
            background: linear-gradient(135deg, rgba(26, 26, 26, 0.9) 0%, rgba(15, 31, 31, 0.9) 100%);
            border: 1px solid rgba(45, 212, 191, 0.2);
            border-radius: 16px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .feature-box:hover {
            transform: translateY(-5px);
            border-color: #2dd4bf;
            box-shadow: 0 10px 30px rgba(45, 212, 191, 0.2);
        }

        .feature-icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }

        .feature-box h3 {
            color: #2dd4bf;
            font-size: 1.3rem;
            margin-bottom: 10px;
        }

        .feature-box p {
            color: #aaa;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .trial-limits {
            background: linear-gradient(135deg, rgba(26, 26, 26, 0.9) 0%, rgba(15, 31, 31, 0.9) 100%);
            border: 2px solid rgba(45, 212, 191, 0.3);
            border-radius: 20px;
            padding: 40px;
            margin: 40px 0;
        }

        .trial-limits h2 {
            color: #2dd4bf;
            font-size: 2rem;
            margin-bottom: 30px;
        }

        .limits-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .limit-item {
            background: rgba(10, 10, 10, 0.8);
            border-radius: 12px;
            padding: 20px;
            border: 1px solid rgba(45, 212, 191, 0.2);
        }

        .limit-item .number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #2dd4bf;
            margin-bottom: 10px;
        }

        .limit-item .label {
            color: #ccc;
            font-size: 1rem;
        }

        .cta-button {
            background: linear-gradient(135deg, #2dd4bf 0%, #14b8a6 100%);
            color: #000;
            border: none;
            padding: 18px 50px;
            font-size: 1.3rem;
            font-weight: bold;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 6px 16px rgba(45, 212, 191, 0.2);
            margin: 30px 0;
        }

        .cta-button:hover {
            background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(45, 212, 191, 0.28);
        }

        .cta-button:active {
            transform: translateY(-1px);
        }

        .login-link {
            margin-top: 30px;
            color: #aaa;
            font-size: 1rem;
        }

        .login-link a {
            color: #2dd4bf;
            text-decoration: none;
            font-weight: 600;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .premium-badge {
            display: inline-block;
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: #fff;
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-top: 20px;
        }

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 15px;
                text-align: center;
                padding: 15px;
                position: relative;
            }

            .trial-container {
                margin-top: 20px;
            }

            .trial-header h1 {
                font-size: 2.5rem;
            }

            .trial-header p {
                font-size: 1.1rem;
            }

            .trial-features {
                grid-template-columns: 1fr;
            }

            .limits-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">
            <img src="Logo.png" alt="Logo" class="logo-image">
            <h1 class="company-name">Quotation Management System</h1>
        </div>
        <div class="nav-links">
            <a href="index.html">Home</a>
            <a href="login.php">Login</a>
        </div>
    </nav>

    <div class="trial-container">
        <div class="trial-header">
            <h1><?php echo icon('sparkle', 26); ?> Start Your Free Trial</h1>
            <p>Experience the power of professional quotation management.<br>
            No credit card required. Get started in seconds!</p>
        </div>

        <div class="trial-features">
            <div class="feature-box">
                <div class="feature-icon">📦</div>
                <h3>Add Instruments</h3>
                <p>Add up to 2 instruments to your catalog during trial</p>
            </div>
            <div class="feature-box">
                <div class="feature-icon">🏢</div>
                <h3>Add Companies</h3>
                <p>Manage up to 2 companies in your trial period</p>
            </div>
            <div class="feature-box">
                <div class="feature-icon">📋</div>
                <h3>Generate Quotations</h3>
                <p>Create up to 2 professional quotations for free</p>
            </div>
        </div>

        <div class="trial-limits">
            <h2><?php echo icon('target', 20); ?> Trial Includes</h2>
            <div class="limits-grid">
                <div class="limit-item">
                    <div class="number">2</div>
                    <div class="label">Instruments</div>
                </div>
                <div class="limit-item">
                    <div class="number">2</div>
                    <div class="label">Companies</div>
                </div>
                <div class="limit-item">
                    <div class="number">2</div>
                    <div class="label">Quotations</div>
                </div>
            </div>
            <p style="color: #aaa; margin-top: 25px; font-size: 1rem;">
                Upgrade to Premium for unlimited access to all features!
            </p>
        </div>

        <form method="POST" action="">
            <button type="submit" name="start_trial" class="cta-button">
                Start Free Trial <?php echo icon('arrow-right', 16); ?>
            </button>
        </form>

        <div class="premium-badge">
            <?php echo icon('diamond', 16); ?> Upgrade to Premium for Unlimited Access
        </div>

        <div class="login-link">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </div>
</body>
</html>

