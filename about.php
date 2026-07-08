<?php
require_once __DIR__ . '/config.php';
require_auth();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<link rel="icon" type="image/png" href="Logo.png">
<link rel="manifest" href="manifest.json">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Quotation Management System</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Central Styles -->
    <link rel="stylesheet" href="sidebar.css?v=2.3">
    <link rel="stylesheet" href="home.css?v=1.4">
    <link rel="stylesheet" href="theme.css?v=<?php echo time(); ?>">
    
    <!-- Theme Loader Script -->
    <script>
        (function() {
            const theme = localStorage.getItem('theme') || 'dark';
            if (theme === 'light') {
                document.documentElement.classList.add('light-mode');
            }
        })();
    </script>
    
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        
        .stat-item {
            text-align: center;
            padding: 15px;
            background: rgba(10, 10, 10, 0.4);
            border-radius: 8px;
            border: 1px solid var(--teal-border);
            transition: all 0.3s ease;
        }
        
        html.light-mode .stat-item {
            background: #ffffff !important;
            border-color: rgba(0, 0, 0, 0.08) !important;
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
        }

        .stat-item:hover {
            border-color: var(--teal);
            transform: translateY(-2px);
        }

        .stat-number {
            font-size: 1.8rem;
            color: var(--teal);
            font-weight: bold;
            display: block;
        }
        
        .stat-label {
            color: #ccc;
            margin-top: 5px;
            font-size: 12px;
        }
        
        html.light-mode .stat-label {
            color: #64748b;
        }
        
        .hero-section {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .hero-section h1 {
            font-size: 2.2rem;
            color: var(--teal);
            margin-bottom: 10px;
            font-weight: bold;
            background: linear-gradient(45deg, var(--teal), #00ffc3);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .hero-section p {
            color: #ccc;
            font-size: 13px;
        }
        
        html.light-mode .hero-section p {
            color: #475569;
        }
        
        .section-card {
            background: var(--card-gradient);
            border: 1px solid var(--teal-border);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
        }
        
        html.light-mode .section-card {
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03) !important;
        }
        
        .section-card:hover {
            border-color: var(--teal);
            transform: translateY(-2px);
        }

        .section-card h2 {
            font-size: 15px;
            color: var(--teal);
            margin-bottom: 10px;
            font-weight: bold;
        }
        
        .section-card p, .section-card li {
            font-size: 12px;
            color: #ccc;
            line-height: 1.6;
        }
        
        html.light-mode .section-card p, html.light-mode .section-card li {
            color: #475569;
        }
        
        .section-card ul {
            list-style: none;
        }
        
        .section-card li {
            margin-bottom: 6px;
            padding-left: 15px;
            position: relative;
        }
        
        .section-card li::before {
            content: "▶";
            color: var(--teal);
            position: absolute;
            left: 0;
            font-size: 10px;
        }
        
        .cta-section {
            text-align: center;
            background: var(--card-gradient);
            border: 1px solid var(--teal-border);
            border-radius: 8px;
            padding: 20px;
        }
        
        .cta-section h2 {
            font-size: 1.4rem;
            color: var(--teal);
            margin-bottom: 10px;
        }
        
        .cta-button {
            display: inline-block;
            padding: 8px 18px;
            background-color: var(--teal);
            color: #000;
            text-decoration: none;
            border-radius: 20px;
            font-weight: bold;
            font-size: 12px;
            transition: all 0.4s ease;
            box-shadow: 0 4px 15px var(--teal-glow);
            margin-top: 10px;
        }
        
        .cta-button:hover {
            background-color: var(--teal-hover);
            transform: translateY(-2px);
        }

        /* Adjust page spacing */
        .main-header h1 {
            font-size: 1.5rem !important;
        }
        .main-content {
            padding: 20px 25px !important;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/sidebar.php'; ?>

    <div class="mainBody">

        <!-- Main Content -->
        <div class="main-content">
            <header class="main-header">
                <h1><?php echo icon('info', 22); ?> About Us</h1>
            </header>

            <section class="hero-section">
                <h1>About Quotation Management System</h1>
                <p>Now Making Quotation Is Not A Headache.</p>
                <p>Create, manage, and track professional quotations with ease. Boost your sales efficiency and close deals faster.</p>
            </section>

            <div class="section-card">
                <h2>Key Features</h2>
                <ul>
                    <li>Smart Quote Generation - Create professional quotations instantly</li>
                    <li>Real-time Analytics - Track quote performance and conversion rates</li>
                    <li>Workflow Automation - Streamline approval processes</li>
                    <li>Team Collaboration - Multiple team members can work together</li>
                    <li>Mobile Responsive - Access from any device</li>
                    <li>Enterprise Security - Bank-level security protocols</li>
                </ul>
            </div>

            <div class="section-card">
                <h2>Our Results</h2>
                <div class="stats-grid">
                    <div class="stat-item">
                        <span class="stat-number">50+</span>
                        <div class="stat-label">Businesses Served</div>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">95%</span>
                        <div class="stat-label">Customer Satisfaction</div>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">50+</span>
                        <div class="stat-label">Quotes Processed</div>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">24/7</span>
                        <div class="stat-label">Support Available</div>
                    </div>
                </div>
            </div>

            <section class="cta-section">
                <h2>Ready to Transform Your Quotation Process?</h2>
                <p>Join hundreds of businesses that have already streamlined their quotation management with our powerful system.</p>
                <a href="form2.php" class="cta-button">Generate Your First Quotation</a>
            </section>
        </div>
    </div>


</body>
</html>
