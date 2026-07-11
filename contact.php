<?php
require_once __DIR__ . '/config.php';
require_auth();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<link rel="icon" type="image/png" href="logo-new.png">
<link rel="manifest" href="manifest.json">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Quotation Management System</title>
    
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
        .contact-card {
            background: var(--card-gradient);
            border: 1px solid var(--teal-border);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            animation: slideInUp 0.5s ease;
        }
        
        html.light-mode .contact-card {
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03) !important;
        }

        .contact-header {
            text-align: center;
            margin-bottom: 20px;
        }

        .contact-header h1 {
            font-size: 1.8rem;
            color: var(--teal);
            margin-bottom: 8px;
        }

        .contact-header p {
            font-size: 13px;
            color: #ccc;
        }
        
        html.light-mode .contact-header p {
            color: #64748b;
        }

        .contact-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .info-card {
            background: rgba(10, 10, 10, 0.4);
            padding: 15px;
            border-radius: 8px;
            border: 1px solid var(--teal-border);
            text-align: center;
            transition: all 0.3s ease;
        }
        
        html.light-mode .info-card {
            background: #ffffff !important;
            border-color: rgba(0, 0, 0, 0.08) !important;
        }

        .info-card:hover {
            border-color: var(--teal);
            transform: translateY(-2px);
        }

        .info-card h3 {
            color: var(--teal);
            margin-bottom: 5px;
            font-size: 13px;
        }

        .info-card p {
            color: #ccc;
            font-size: 12px;
        }
        
        html.light-mode .info-card p {
            color: #475569;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 12px;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
        }

        .form-group {
            margin-bottom: 12px;
        }

        .form-group label {
            display: block;
            margin-bottom: 4px;
            font-weight: 600;
            font-size: 11px;
            color: var(--teal);
        }

        .form-group input, 
        .form-group textarea, 
        .form-group select {
            width: 100%;
            padding: 8px 10px;
            background: rgba(10, 10, 10, 0.6);
            border: 1px solid rgba(45, 212, 191, 0.2);
            border-radius: 6px;
            color: #fff;
            font-size: 12px;
            transition: all 0.3s ease;
        }

        .form-group input:focus, 
        .form-group textarea:focus, 
        .form-group select:focus {
            border-color: var(--teal);
            outline: none;
            box-shadow: 0 0 6px var(--teal-glow);
        }

        .btn-submit {
            background: var(--teal);
            color: #000;
            border: none;
            padding: 8px 18px;
            font-weight: bold;
            font-size: 12px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px var(--teal-glow);
            display: block;
            margin: 15px auto 0;
        }

        .btn-submit:hover {
            background: var(--teal-hover);
            transform: translateY(-1px);
        }

        .success-message,
        .error-message {
            padding: 10px;
            border-radius: 6px;
            margin: 15px 0;
            text-align: center;
            display: none;
            font-size: 12px;
        }

        .success-message {
            background: rgba(45, 212, 191, 0.1);
            border: 1px solid var(--teal);
            color: var(--teal);
        }

        .error-message {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid #ef4444;
            color: #ef4444;
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
                <h1><?php echo icon('mail', 22); ?> Contact Us</h1>
            </header>

            <div class="contact-card">
                <div class="contact-header">
                    <h1>Contact Our Team</h1>
                    <p>We’d love to hear from you. Send us a message and we’ll respond as soon as possible.</p>
                </div>

                <div class="contact-info">
                    <div class="info-card">
                        <h3><?php echo icon('mail', 18); ?> Email</h3>
                        <p>QuotManag111@gmail.com</p>
                    </div>
                    <div class="info-card">
                        <h3><?php echo icon('phone', 18); ?> Phone</h3>
                        <p>+91 9662206964</p>
                    </div>
                    <div class="info-card">
                        <h3><?php echo icon('map-pin', 18); ?> Address</h3>
                        <p>ghodasar , ahmedabad.</p>
                    </div>
                </div>

                <div class="success-message" id="successMessage">
                    ✅ Thank you! Your message has been sent successfully.
                </div>
                <div class="error-message" id="errorMessage">
                    ❌ Please fill in all required fields.
                </div>

                <form id="contactForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="firstName">First Name *</label>
                            <input type="text" id="firstName" name="firstName" required>
                        </div>
                        <div class="form-group">
                            <label for="lastName">Last Name *</label>
                            <input type="text" id="lastName" name="lastName" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Email Address *</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="company">Company/Organization</label>
                        <input type="text" id="company" name="company">
                    </div>

                    <div class="form-group">
                        <label for="subject">Subject *</label>
                        <select id="subject" name="subject" required>
                            <option value="">Select a subject</option>
                            <option value="general">General Inquiry</option>
                            <option value="support">Technical Support</option>
                            <option value="sales">Sales Question</option>
                            <option value="partnership">Partnership</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="message">Message *</label>
                        <textarea id="message" name="message" required rows="4" placeholder="Please describe your inquiry in detail..."></textarea>
                    </div>

                    <button type="submit" class="btn-submit">Send Message</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>

        document.getElementById('contactForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = {
                firstName: document.getElementById('firstName').value,
                lastName: document.getElementById('lastName').value,
                email: document.getElementById('email').value,
                phone: document.getElementById('phone').value,
                company: document.getElementById('company').value,
                subject: document.getElementById('subject').value,
                message: document.getElementById('message').value
            };

            if (!formData.firstName || !formData.lastName || !formData.email || !formData.subject || !formData.message) {
                showError();
                return;
            }

            document.getElementById('successMessage').style.display = 'none';
            document.getElementById('errorMessage').style.display = 'none';

            simulateEmailSend(formData);
        });

        function simulateEmailSend(formData) {
            createMailtoLink(formData);
            const submitBtn = document.querySelector('.btn-submit');
            submitBtn.textContent = 'Opening Email Client...';
            submitBtn.disabled = true;
            setTimeout(() => {
                showSuccess();
                document.getElementById('contactForm').reset();
                submitBtn.textContent = 'Send Message';
                submitBtn.disabled = false;
            }, 1500);
        }

        function showSuccess() {
            const successMsg = document.getElementById('successMessage');
            successMsg.style.display = 'block';
            successMsg.scrollIntoView({ behavior: 'smooth' });
        }

        function showError() {
            const errorMsg = document.getElementById('errorMessage');
            errorMsg.style.display = 'block';
            errorMsg.scrollIntoView({ behavior: 'smooth' });
        }

        function createMailtoLink(formData) {
            const subject = `Contact Form: ${formData.subject}`;
            const body = `
Name: ${formData.firstName} ${formData.lastName}
Email: ${formData.email}
Phone: ${formData.phone}
Company: ${formData.company}
Subject: ${formData.subject}

Message:
${formData.message}
            `.trim();

            const mailtoLink = `mailto:QuotManag111@gmail.com?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
            window.location.href = mailtoLink;
        }
    </script>
</body>
</html>
