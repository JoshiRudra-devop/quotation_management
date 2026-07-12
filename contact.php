<?php
require_once __DIR__ . '/config.php';
require_auth();

// Just for sidebar breadcrumbs
$_meta_title = 'Quote Management';
$_meta_crumb = 'Contact Support';
$_show_back_btn = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');

    // === EDIT THIS TO YOUR ORIGINAL EMAIL ===
    $admin_email = 'your-real-email@example.com'; 
    // ========================================

    $subject = "New Contact Support Request from: $name";
    $body = "Name: $name\nEmail: $email\n\nMessage:\n$message";
    $headers = "From: noreply@quotemanagement.com\r\n";
    $headers .= "Reply-To: $email\r\n";

    // Send the email (Requires server to be configured to send mail)
    @mail($admin_email, $subject, $body, $headers);

    flash_set('Your message has been sent to our support team! We will get back to you shortly.', 'success');
    header('Location: contact.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Contact Us - Quote Management</title>
<link rel="stylesheet" href="sidebar.css?v=2.3">
<link rel="stylesheet" href="home.css?v=1.4">
<link rel="stylesheet" href="theme.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="components.css">
<script src="utils.js" defer></script>
<script>
    (function() {
        const theme = localStorage.getItem('theme') || 'dark';
        if (theme === 'light') {
            document.documentElement.classList.add('light-mode');
        }
    })();
</script>
<style>
/* Same card styling from add-company.php for consistency */
.setup-container {
  background: var(--card-gradient);
  border: 1px solid var(--teal-border);
  border-radius: var(--radius);
  width:100%;
  max-width:700px;
  padding:40px;
  margin:40px auto;
  color: var(--text, #ccc);
  box-shadow: var(--shadow-card, 0 4px 20px rgba(45,212,191,0.12));
}
/* .form-header styles are now global */

.contact-info {
    display: flex;
    justify-content: space-around;
    margin-bottom: 30px;
    flex-wrap: wrap;
    gap: 15px;
}
.info-card {
    text-align: center;
    padding: 15px;
    background: var(--surface2, #2a2a2a);
    border-radius: 12px;
    flex: 1;
    min-width: 150px;
    border: 1px solid var(--teal-border);
    text-decoration: none; /* For links */
    color: inherit;
    transition: all 0.3s ease;
    display: block; /* To behave like a card */
}
.info-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 12px var(--teal-glow);
    border-color: var(--teal);
}
.info-card svg {
    color: var(--teal);
    width: 32px; height: 32px;
    margin-bottom: 10px;
}
.info-card h3 { font-size: 16px; margin-bottom: 5px; color: var(--text); }
.info-card p { font-size: 14px; color: var(--text-muted); }

.form-group { margin-bottom:20px; }
.form-group label { display:block; margin-bottom:8px; color: var(--teal); font-weight:600; }
.form-group input, .form-group textarea {
  width:100%;
  padding:14px;
  border:1px solid var(--teal-border);
  border-radius:8px;
  font-size:15px;
  background: var(--bg-surface, #1a1a1a);
  color: var(--text, #e5f7f4);
}
.form-group input:focus, .form-group textarea:focus { outline:none; border-color: var(--teal); box-shadow:0 0 8px var(--teal-glow); }
.btn { padding:12px 24px; border:none; border-radius:25px; font-size:16px; font-weight:600; cursor:pointer; transition:all 0.3s ease; width: 100%; }
.btn-primary { background: var(--teal); color:#000; box-shadow:0 0 10px var(--teal-glow); }
.btn-primary:hover { background: var(--teal-hover); }

@media(max-width: 600px) {
  .setup-container { padding: 20px; margin: 20px auto; width: 95%; }
  /* .form-header h1 responsive styles inherited globally */
  .contact-info { flex-direction: column; }
}
</style>
</head>
<body>
<?php include __DIR__ . '/sidebar.php'; ?>

<div class="mainBody">
  <div class="main-content">

    <div class="setup-container">
      <div class="form-header">
        <h1>Contact Support</h1>
        <p>Need help with Quote Management? We're here for you.</p>
      </div>

      <div class="contact-info">
          <!-- REPLACE WITH YOUR REAL EMAIL -->
          <a href="mailto:your-real-email@example.com" class="info-card">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
              <h3>Email Us</h3>
              <p>your-real-email@example.com</p>
          </a>
          <!-- REPLACE WITH YOUR REAL PHONE NUMBER -->
          <a href="tel:+18001234567" class="info-card">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
              <h3>Call Us</h3>
              <p>+1 (800) 123-4567</p>
          </a>
      </div>

      <form method="POST">
        <div class="form-group">
          <label>Your Name</label>
          <input type="text" name="name" placeholder="John Doe" required>
        </div>
        <div class="form-group">
          <label>Your Email</label>
          <input type="email" name="email" placeholder="john@example.com" required>
        </div>
        <div class="form-group">
          <label>How can we help?</label>
          <textarea name="message" rows="5" placeholder="Describe your issue or question..." required></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Send Message</button>
      </form>

    </div>

  </div>
</div>

</body>
</html>
