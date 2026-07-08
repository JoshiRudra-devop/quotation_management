<?php
require_once __DIR__ . '/config.php';

$con = db_connect();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // CSRF validation
    if (!csrf_validate($_POST['csrf_token'] ?? '')) {
        toast_script('Invalid request. Please refresh and try again.', 'error');
    } else {
        $name = trim($_POST['name']);
        $contact_no = trim($_POST['contact_no']);
        $password = trim($_POST['password']);
        $confirmPassword = trim($_POST['confirmPassword']);

        if ($password !== $confirmPassword) {
            toast_script('Passwords do not match!', 'error');
        } elseif (strlen($password) < 8) {
            toast_script('Password must be at least 8 characters long!', 'error');
        } else {
            // Check if user exists
            $stmt = $con->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->bind_param("s", $contact_no);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                toast_script('Contact number already exists!', 'error');
            } else {
                // --- Hash password and start transaction ---
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $subscription_type = 'trial';

                $con->begin_transaction();
                try {
                    // 1. Insert User First (Storing contact_no in the email column)
                    $stmt_insert = $con->prepare("INSERT INTO users(email, user_name, password, subscription_type, trial_products_count, trial_companies_count, trial_quotations_count) VALUES (?, ?, ?, ?, 0, 0, 0)");
                    $stmt_insert->bind_param("ssss", $contact_no, $name, $passwordHash, $subscription_type);
                    if (!$stmt_insert->execute()) {
                        throw new Exception("Error creating user account.");
                    }
                    $new_user_id = $con->insert_id;
                    $stmt_insert->close();

                    // 2. Insert Blank Company
                    $dummy_company = $name . "'s Company";
                    $stmt_comp = $con->prepare("INSERT INTO companies(user_id, company_name) VALUES (?, ?)");
                    $stmt_comp->bind_param("is", $new_user_id, $dummy_company);
                    if (!$stmt_comp->execute()) {
                        throw new Exception("Error saving company details.");
                    }
                    $new_company_id = $con->insert_id;
                    $stmt_comp->close();

                    // 3. Insert Dummy Customer
                    $stmt_cust = $con->prepare("INSERT INTO customer_companies(company_id, customer_company_name, customer_address, contact) VALUES (?, 'Sample Client Ltd', '123 Dummy Street', '9876543210')");
                    $stmt_cust->bind_param("i", $new_company_id);
                    $stmt_cust->execute();
                    $stmt_cust->close();

                    // 4. Insert Dummy Instrument
                    $stmt_inst = $con->prepare("INSERT INTO instruments(company_id, instrument_name, description, price, hsn_code) VALUES (?, 'Sample Calibration Instrument', 'Calibration and Testing Services', 1500.00, '998346')");
                    $stmt_inst->bind_param("i", $new_company_id);
                    $stmt_inst->execute();
                    $stmt_inst->close();

                    $con->commit();
                    header('Location: login.php?registered=1');
                    exit();
                } catch (Exception $e) {
                    $con->rollback();
                    toast_script($e->getMessage() . ' Please try again.', 'error');
                }
            }
            $stmt->close();
        }
    }
}

$con->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
<link rel="icon" type="image/png" href="Logo.png">
<link rel="manifest" href="manifest.json">
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Account - Quotation Management System</title>
  <link rel="stylesheet" href="sidebar.css?v=2.3">
  <link rel="stylesheet" href="components.css">
  <script src="utils.js" defer></script>
  <style>
    * {
      box-sizing: border-box;
    }

    body {
      font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(135deg, #000000 0%, #0a0a0a 100%);
      color: #e2e8f0;
      margin: 0;
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 20px;
    }

    .form-container {
      background: linear-gradient(135deg, #1a1a1a 0%, #0f1f1f 100%);
      padding: 40px;
      border-radius: 20px;
      box-shadow: 0 0 16px rgba(45, 212, 191, 0.16);
      width: 100%;
      max-width: 900px;
      border: 1px solid #2dd4bf22;
      animation: fadeIn 0.6s ease-in-out;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: scale(0.95); }
      to { opacity: 1; transform: scale(1); }
    }

    h1 {
      text-align: center;
      margin-bottom: 25px;
      font-size: 28px;
      font-weight: bold;
      background: linear-gradient(45deg, #1aa89f, #00ffc3);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .input-group {
      margin-bottom: 20px;
    }

    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
      margin-bottom: 20px;
    }

    .form-row-single {
      margin-bottom: 20px;
    }

    label {
      display: block;
      margin-bottom: 6px;
      font-size: 14px;
      color: #94a3b8;
    }

    input[type="text"],
    input[type="email"],
    input[type="password"],
    input[type="tel"] {
      width: 100%;
      padding: 12px 15px;
      border: 2px solid #334155;
      border-radius: 10px;
      font-size: 15px;
      background: #1e293b;
      color: #f8fafc;
      transition: all 0.3s ease;
    }

    input:focus {
      border-color: #1aa89f;
      outline: none;
      box-shadow: 0 0 8px #1aa89f;
    }

    /* File Upload Styling */
    .file-upload-wrapper {
      position: relative;
      margin-bottom: 20px;
    }

    input[type="file"] {
      width: 100%;
      padding: 12px 15px;
      border: 2px dashed #334155;
      border-radius: 10px;
      background: #1e293b;
      color: #94a3b8;
      cursor: pointer;
      transition: all 0.3s ease;
      font-size: 14px;
    }

    input[type="file"]:hover {
      border-color: #1aa89f;
      background: #243447;
    }

    input[type="file"]:focus {
      outline: none;
      border-color: #1aa89f;
      box-shadow: 0 0 8px #1aa89f;
    }

    /* Image Preview */
    .image-preview {
      margin-top: 10px;
      display: none;
      border-radius: 10px;
      overflow: hidden;
      border: 2px solid #334155;
      background: #0f172a;
      position: relative;
    }

    .image-preview.show {
      display: block;
    }

    .preview-container {
      position: relative;
      max-width: 100%;
      height: 200px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .preview-img {
      max-width: 100%;
      max-height: 200px;
      object-fit: contain;
    }

    .remove-image {
      position: absolute;
      top: 10px;
      right: 10px;
      background: #ef4444;
      color: white;
      border: none;
      border-radius: 50%;
      width: 30px;
      height: 30px;
      cursor: pointer;
      font-size: 18px;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: background 0.3s;
    }

    .remove-image:hover {
      background: #dc2626;
    }

    .file-info {
      padding: 10px;
      background: #1e293b;
      color: #94a3b8;
      font-size: 12px;
      text-align: center;
    }

    /* Error Message */
    .error-message {
      color: #ef4444;
      font-size: 12px;
      margin-top: 5px;
      display: none;
    }

    .error-message.show {
      display: block;
    }

    /* Password Strength Indicator */
    .password-strength {
      height: 4px;
      margin-top: 8px;
      border-radius: 2px;
      background: #334155;
      overflow: hidden;
    }

    .password-strength-bar {
      height: 100%;
      width: 0;
      transition: all 0.3s ease;
      background: #ef4444;
    }

    .password-strength-bar.weak { width: 33%; background: #ef4444; }
    .password-strength-bar.medium { width: 66%; background: #f59e0b; }
    .password-strength-bar.strong { width: 100%; background: #10b981; }

    .password-hint {
      font-size: 11px;
      color: #64748b;
      margin-top: 4px;
    }

    .btn {
      width: 100%;
      padding: 14px;
      background: #1aa89f;
      color: white;
      border: none;
      border-radius: 10px;
      font-size: 16px;
      font-weight: bold;
      cursor: pointer;
      transition: transform 0.2s, box-shadow 0.2s;
      position: relative;
      overflow: hidden;
    }

    .btn:hover:not(:disabled) {
      transform: translateY(-2px);
      box-shadow: 0 5px 14px rgba(45, 212, 191, 0.24);
    }

    .btn:disabled {
      background: #475569;
      cursor: not-allowed;
      opacity: 0.6;
    }

    .btn-secondary {
      background: #475569;
    }

    .btn-container {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 15px;
      margin-top: 10px;
    }

    /* Loading Spinner */
    .spinner {
      display: none;
      width: 20px;
      height: 20px;
      border: 3px solid #ffffff33;
      border-top-color: #ffffff;
      border-radius: 50%;
      animation: spin 0.8s linear infinite;
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
    }

    @keyframes spin {
      to { transform: translate(-50%, -50%) rotate(360deg); }
    }

    .btn.loading .spinner {
      display: block;
    }

    .btn.loading .btn-text {
      opacity: 0;
    }

    .text-center {
      text-align: center;
      margin-top: 20px;
    }

    .link {
      color: #1aa89f;
      text-decoration: none;
      font-weight: bold;
    }

    .link:hover {
      text-decoration: underline;
    }

    .form-container:hover {
      box-shadow: 0 0 20px rgba(45, 212, 191, 0.24);
      border-color: #2dd4bf44;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
      .form-row {
        grid-template-columns: 1fr;
        gap: 0;
      }

      .btn-container {
        grid-template-columns: 1fr;
      }

      .form-container {
        padding: 30px 20px;
        width: 95%;
      }
    }
  </style>
</head>
<body>
  <div class="form-container">
    <h1>📝 Create Account</h1>

    <form method="POST" action="" id="registrationForm">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
      
      <div class="input-group">
        <label for="name">👤 Full Name</label>
        <input type="text" id="name" name="name" required placeholder="Enter your full name">
      </div>
      <div class="input-group">
        <label for="contact_no">📞 Contact Number</label>
        <input type="tel" id="contact_no" name="contact_no" required placeholder="Enter your contact number">
      </div>
      <div class="input-group">
        <label for="password">🔒 Password</label>
        <input type="password" id="password" name="password" required placeholder="Min 8 characters" minlength="8">
        <div class="password-strength">
          <div class="password-strength-bar" id="strengthBar"></div>
        </div>
        <div class="password-hint">Use letters, numbers &amp; symbols</div>
      </div>
      <div class="input-group" style="margin-bottom: 25px;">
        <label for="confirmPassword">🔒 Confirm Password</label>
        <input type="password" id="confirmPassword" name="confirmPassword" required placeholder="Re-enter your password">
      </div>

      <button type="submit" class="btn" id="submitBtn">
        <span class="btn-text">🚀 Create Account</span>
        <div class="spinner"></div>
      </button>

      <div style="text-align: center; margin-top: 15px; font-size: 13px; color: #94a3b8;">
        Already have an account? <a href="login.php" class="link">Login here</a>
      </div>
    </form>

    <div class="text-center">
      <a href="index.html" class="link">← Back to Home</a>
    </div>
  </div>

  <script>
    // ── Form submit loading state ──
    document.getElementById('registrationForm').addEventListener('submit', function() {
      var btn = document.getElementById('submitBtn');
      btn.disabled = true;
      btn.classList.add('loading');
    });
  </script>
</body>
</html>