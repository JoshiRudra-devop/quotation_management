<?php
require_once __DIR__ . '/config.php';

// Require auth
require_auth();

$user_id = $_SESSION['user_id'];

// Check if user can add companies
if (!can_add_company($user_id)) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        flash_set('Trial limit reached! Please upgrade to Premium for unlimited access.', 'error');
        header('Location: premium.php');
        exit();
    }
    $trial_counts = get_trial_counts($user_id);
    echo "<script>
        alert('Trial limit reached! You can add up to 2 companies in the trial.\\n\\nRemaining: {$trial_counts['companies']} companies\\nPlease upgrade to Premium for unlimited access.');
        window.location.href='premium.php';
    </script>";
    exit();
}

// Handle form submit — success/failure both redirect, with a flash toast
// shown on the next page (see flash_set()/flash_render() in config.php)
function add_company_fail($message) {
    flash_set($message, 'error');
    header('Location: add-company.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? '')) {
        add_company_fail('Invalid request. Please refresh and try again.');
    }

    // Guards against a double-click or resubmission inserting the same company twice.
    if (!consume_form_token('add_company', $_POST['form_token'] ?? '')) {
        flash_set('This company was already added.', 'info');
        header('Location: home.php');
        exit();
    }

    $companyName   = trim($_POST['company_name'] ?? '');
    $companyAddr   = trim($_POST['company_address'] ?? '');
    $companyContact= trim($_POST['company_contact'] ?? '');
    $companyEmail  = trim($_POST['company_email'] ?? '');
    $companyGst    = strtoupper(trim($_POST['company_gst'] ?? ''));

    if ($companyName === '' || $companyAddr === '' || $companyContact === '' || $companyEmail === '' || $companyGst === '') {
        add_company_fail('All fields are required.');
    }
    if (!filter_var($companyEmail, FILTER_VALIDATE_EMAIL)) {
        add_company_fail('Please enter a valid email address.');
    }
    if (!preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/', $companyGst)) {
        add_company_fail('Please enter a valid 15-character GSTIN.');
    }
    if (!preg_match('/^[0-9+\-()\s]{7,20}$/', $companyContact)) {
        add_company_fail('Please enter a valid contact number.');
    }

    $con = db_connect();

    // Check duplicates for this user (same name or gst)
    $check = $con->prepare("SELECT customer_id FROM customer_companies WHERE company_id = ? AND (customer_company_name = ? OR customer_gstin = ?)");
    $check->bind_param("iss", $_SESSION['company_id'], $companyName, $companyGst);
    $check->execute();
    $dup = $check->get_result();
    if ($dup && $dup->num_rows > 0) {
        $check->close();
        add_company_fail('Company with same name or GST already exists.');
    }
    $check->close();

    $stmt = $con->prepare("INSERT INTO customer_companies (customer_company_name, customer_address, contact, email_id, customer_gstin, company_id) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssi", $companyName, $companyAddr, $companyContact, $companyEmail, $companyGst, $_SESSION['company_id']);
    if (!$stmt->execute()) {
        $stmt->close();
        add_company_fail('Failed to add company. Please try again.');
    }
    $stmt->close();

    if (is_trial_user($user_id)) {
        increment_trial_companies($user_id);
    }

    flash_set('Company added successfully!', 'success');
    header('Location: home.php');
    exit();
}

$formToken = new_form_token('add_company');
?>

<!DOCTYPE html>
<html lang="en">
<head>
<link rel="icon" type="image/png" href="logo-new.png">
<link rel="manifest" href="manifest.json">
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Add Company - Quotation Management System</title>
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
* { margin:0; padding:0; box-sizing:border-box; }

/* Uses the app's shared card variables so this respects light/dark mode like every other page */
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
.form-header { text-align:center; margin-bottom:40px; }
.form-header h1 { font-family: var(--font-head); color: var(--teal); font-size:28px; font-weight:700; margin-bottom:10px; }
.form-header p { color: var(--text-muted, #aaa); }
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
.btn { padding:12px 24px; border:none; border-radius:25px; font-size:16px; font-weight:600; cursor:pointer; transition:all 0.3s ease; }
.btn-primary { background: var(--teal); color:#000; box-shadow:0 0 10px var(--teal-glow); }
.btn-primary:hover { background: var(--teal-hover); }
.btn-primary:disabled { opacity:0.6; cursor:not-allowed; }
.btn-secondary { background:transparent; color: var(--teal); border:2px solid var(--teal); }
.btn-secondary:hover { background: var(--teal); color:#000; }
.actions { display:flex; gap:12px; justify-content:flex-end; margin-top:10px; }
.error { color:#ff6b6b; background:rgba(255,107,107,0.1); border:1px solid rgba(255,107,107,0.4); padding:10px 12px; border-radius:8px; margin-bottom:15px; }

.btn-text-back {
  background: transparent;
  border: none;
  color: var(--teal);
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  margin-top: 20px;
  margin-bottom: -5px;
  transition: opacity 0.2s;
}
.btn-text-back:hover { opacity: 0.8; text-decoration: underline; }

@media (max-width: 600px) {
  .navbar { flex-direction: column; gap: 12px; text-align: center; padding: 1.5rem 1rem; }
  .nav-links { flex-direction: column; gap: 10px; width: 100%; }
  .user-info { width: 100%; text-align: center; }
}

@media (max-width: 480px) {
  .setup-container {
    margin: 15px auto;
    padding: 15px;
    border-radius: 10px;
    width: 95%;
  }
  .header h1 {
    font-size: 20px;
  }
  .actions {
    flex-direction: column;
    gap: 10px;
  }
  .actions .btn {
    width: 100%;
    text-align: center;
  }
}
</style>
</head>
<body>
<?php include __DIR__ . '/sidebar.php'; ?>
<div class="mainBody">
  <div class="main-content">

<div style="max-width: 600px; margin: 0 auto; padding: 0 15px;">
  <button type="button" class="btn-text-back" onclick="window.location.href='home.php'">← Back to Dashboard</button>
</div>
<div class="setup-container form-container" style="margin-top: 15px;">
  <div class="form-header">
    <h1>Add Company</h1>
    <p>Provide your client/company details</p>
  </div>

  <form method="POST" action="" id="companyForm">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
    <input type="hidden" name="form_token" value="<?php echo htmlspecialchars($formToken); ?>">
    <div class="form-group">
      <label for="company_name">Company Name</label>
      <input type="text" id="company_name" name="company_name" required placeholder="Enter company name">
    </div>
    <div class="form-group">
      <label for="company_address">Address</label>
      <textarea id="company_address" name="company_address" rows="3" required placeholder="Enter address"></textarea>
    </div>
    <div class="form-group">
      <label for="company_contact">Contact</label>
      <input type="text" id="company_contact" name="company_contact" required placeholder="e.g. +91 98765 43210">
    </div>
    <div class="form-group">
      <label for="company_email">Email</label>
      <input type="email" id="company_email" name="company_email" required placeholder="e.g. billing@example.com">
    </div>
    <div class="form-group">
      <label for="company_gst">GSTIN</label>
      <input type="text" id="company_gst" name="company_gst" required placeholder="22AAAAA0000A1Z5" maxlength="15">
    </div>

    <div class="actions">
      <button type="button" class="btn btn-secondary" onclick="window.location.href='home.php'">Cancel</button>
      <button type="submit" class="btn btn-primary" id="companySubmitBtn">Save Company</button>
    </div>
  </form>
</div>
</div>
</div>

<script>
document.getElementById('company_gst').addEventListener('input', function(e) { e.target.value = e.target.value.toUpperCase(); });

// Guard against double-click/double-submit (belt-and-suspenders with the server-side
// one-time form_token check). On success the page redirects to home.php, which shows
// the flash toast set by the server.
let companySubmitting = false;
document.getElementById('companyForm').addEventListener('submit', function(e){
  if (companySubmitting) { e.preventDefault(); return; }
  companySubmitting = true;
  document.getElementById('companySubmitBtn').disabled = true;
  document.getElementById('companySubmitBtn').textContent = 'Saving...';
});
</script>

</body>
</html>

