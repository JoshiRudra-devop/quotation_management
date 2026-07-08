<?php
require_once __DIR__ . '/config.php';

// Require authentication
require_auth();

$user_id = $_SESSION['user_id'];
$subscription = get_user_subscription($user_id);
$is_premium = is_premium_user($user_id);
$is_trial = is_trial_user($user_id);

// Handle subscription upgrade
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upgrade_plan'])) {
    if (!csrf_validate($_POST['csrf_token'] ?? '')) {
        $error_message = "Invalid request. Please refresh and try again.";
    } else {
        $plan_type = $_POST['plan_type'] ?? '';
        $months = 1;
        
        if ($plan_type === 'yearly') {
            $months = 12;
        } elseif ($plan_type === '3yearly') {
            $months = 36;
        }
        
        if (in_array($plan_type, ['monthly', 'yearly', '3yearly'])) {
            update_subscription($user_id, $plan_type, $months);
            $_SESSION['upgrade_success'] = true;
            header("Location: settings.php?upgrade=1");
            exit();
        } else {
            $error_message = "Invalid plan selected.";
        }
    }
}

$success_message = isset($_GET['success']) ? "Subscription upgraded successfully! You now have premium access." : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<link rel="icon" type="image/png" href="Logo.png">
<link rel="manifest" href="manifest.json">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium Plans - Quotation Management System</title>
    <link rel="stylesheet" href="sidebar.css?v=2.3">
    <link rel="stylesheet" href="home.css?v=1.4">
    <link rel="stylesheet" href="theme.css?v=<?php echo time(); ?>">
    <script>
        (function() {
            const theme = localStorage.getItem('theme') || 'dark';
            if (theme === 'light') {
                document.documentElement.classList.add('light-mode');
            }
        })();
    </script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 50px;
        }

        .header h1 {
            font-size: 2.2rem;
            font-weight: 800;
            margin-bottom: 10px;
            background: linear-gradient(135deg, var(--teal) 0%, #14b8a6 50%, #0d9488 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .header p {
            font-size: 1rem;
            color: var(--text-muted);
        }

        .current-plan {
            background: var(--card-gradient);
            border: 2px solid var(--teal-border);
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 25px;
            text-align: center;
        }

        .current-plan h2 {
            color: var(--teal);
            margin-bottom: 8px;
            font-size: 1.4rem;
        }

        .current-plan .plan-badge {
            display: inline-block;
            background: linear-gradient(135deg, var(--teal) 0%, #14b8a6 100%);
            color: #000;
            padding: 6px 16px;
            border-radius: 15px;
            font-weight: bold;
            margin-top: 8px;
            font-size: 0.9rem;
        }

        .plans-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .plan-card {
            background: var(--card-gradient);
            border: 2px solid var(--teal-border);
            border-radius: 12px;
            padding: 25px 20px;
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .plan-card:hover {
            transform: translateY(-5px);
            border-color: var(--teal);
            box-shadow: 0 15px 40px var(--teal-glow);
        }

        .plan-card.featured {
            border-color: var(--teal);
            border-width: 3px;
        }

        .plan-card.featured::before {
            content: '⭐ MOST POPULAR';
            position: absolute;
            top: 15px;
            right: -35px;
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: #fff;
            padding: 4px 40px;
            font-size: 0.7rem;
            font-weight: bold;
            transform: rotate(45deg);
        }

        .plan-name {
            font-size: 1.4rem;
            font-weight: bold;
            color: var(--teal);
            margin-bottom: 10px;
        }

        .plan-price {
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--text);
            margin-bottom: 8px;
        }

        .plan-price .period {
            font-size: 0.9rem;
            color: var(--text-muted);
            font-weight: normal;
        }

        .plan-features {
            list-style: none;
            margin: 15px 0;
            text-align: left;
            font-size: 13px;
        }

        .plan-features li {
            padding: 8px 0;
            color: var(--text-muted);
            border-bottom: 1px solid var(--teal-border);
        }

        .plan-features li:last-child {
            border-bottom: none;
        }

        .plan-features li::before {
            content: '✓';
            color: var(--teal);
            font-weight: bold;
            margin-right: 8px;
        }

        .plan-button {
            width: 100%;
            padding: 10px 20px;
            background: linear-gradient(135deg, var(--teal) 0%, #14b8a6 100%);
            color: #000;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 15px;
        }

        .plan-button:hover {
            background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px var(--teal-glow);
        }

        .plan-button:disabled {
            background: #555;
            cursor: not-allowed;
            opacity: 0.6;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            text-align: center;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.4);
            color: #10b981;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.4);
            color: #ef4444;
        }

        .back-button {
            display: inline-block;
            margin-top: 30px;
            padding: 12px 24px;
            background: transparent;
            color: var(--teal);
            border: 2px solid var(--teal);
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .back-button:hover {
            background: var(--teal);
            color: #000;
        }

        @media (max-width: 600px) {
            .navbar {
                flex-direction: column;
                gap: 15px;
                text-align: center;
                padding: 20px 15px;
            }
            .nav-links {
                display: flex;
                gap: 15px;
            }
            .header h1 {
                font-size: 2rem;
            }
            .header p {
                font-size: 1rem;
            }
            .plans-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div class="mainBody">
        <div class="main-content">
            <div class="container">
        <div class="header">
            <h1><?php echo icon('diamond', 26); ?> Premium Plans</h1>
            <p>Unlock unlimited access to all features</p>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success">✓ <?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-error">✕ <?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <div class="current-plan">
            <h2>Current Plan</h2>
            <div class="plan-badge">
                <?php 
                if ($is_premium) {
                    echo strtoupper($subscription['subscription_type']) . " - Premium";
                } elseif ($is_trial) {
                    echo "TRIAL - " . get_trial_counts($user_id)['products'] . " products, " . 
                         get_trial_counts($user_id)['companies'] . " companies, " . 
                         get_trial_counts($user_id)['quotations'] . " quotations remaining";
                } else {
                    echo "No Active Plan";
                }
                ?>
            </div>
        </div>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
            <div class="plans-grid">
                <!-- Monthly Plan -->
                <div class="plan-card">
                    <div class="plan-name">Monthly</div>
                    <div class="plan-price">₹999<span class="period">/month</span></div>
                    <ul class="plan-features">
                        <li>Unlimited Instruments</li>
                        <li>Unlimited Companies</li>
                        <li>Unlimited Quotations</li>
                        <li>Priority Support</li>
                        <li>All Premium Features</li>
                    </ul>
                    <button type="submit" name="upgrade_plan" value="monthly" class="plan-button" 
                            <?php echo ($is_premium && $subscription['subscription_type'] === 'monthly') ? 'disabled' : ''; ?>>
                        <?php echo ($is_premium && $subscription['subscription_type'] === 'monthly') ? 'Current Plan' : 'Choose Monthly'; ?>
                    </button>
                    <input type="hidden" name="plan_type" value="monthly">
                </div>

                <!-- Yearly Plan (Featured) -->
                <div class="plan-card featured">
                    <div class="plan-name">Yearly</div>
                    <div class="plan-price">₹9999<span class="period">/year</span></div>
                    <div style="color: #f59e0b; font-weight: bold; margin-bottom: 15px;">Save 17% (₹999/month)</div>
                    <ul class="plan-features">
                        <li>Unlimited Instruments</li>
                        <li>Unlimited Companies</li>
                        <li>Unlimited Quotations</li>
                        <li>Priority Support</li>
                        <li>All Premium Features</li>
                        <li>Best Value</li>
                    </ul>
                    <button type="submit" name="upgrade_plan" value="yearly" class="plan-button" 
                            <?php echo ($is_premium && $subscription['subscription_type'] === 'yearly') ? 'disabled' : ''; ?>>
                        <?php echo ($is_premium && $subscription['subscription_type'] === 'yearly') ? 'Current Plan' : 'Choose Yearly'; ?>
                    </button>
                    <input type="hidden" name="plan_type" value="yearly">
                </div>

                <!-- 3-Yearly Plan -->
                <div class="plan-card">
                    <div class="plan-name">3-Yearly</div>
                    <div class="plan-price">₹24999<span class="period">/3 years</span></div>
                    <div style="color: #f59e0b; font-weight: bold; margin-bottom: 15px;">Save 32% (₹694/month)</div>
                    <ul class="plan-features">
                        <li>Unlimited Instruments</li>
                        <li>Unlimited Companies</li>
                        <li>Unlimited Quotations</li>
                        <li>Priority Support</li>
                        <li>All Premium Features</li>
                        <li>Maximum Savings</li>
                    </ul>
                    <button type="submit" name="upgrade_plan" value="3yearly" class="plan-button" 
                            <?php echo ($is_premium && $subscription['subscription_type'] === '3yearly') ? 'disabled' : ''; ?>>
                        <?php echo ($is_premium && $subscription['subscription_type'] === '3yearly') ? 'Current Plan' : 'Choose 3-Yearly'; ?>
                    </button>
                    <input type="hidden" name="plan_type" value="3yearly">
                </div>
            </div>
        </form>

        <div style="text-align: center;">
            <a href="home.php" class="back-button">← Back to Home</a>
        </div>
    </div>
</div>
</div>
</body>
</html>

