<?php
require_once __DIR__ . '/config.php';

// Check if user is logged in
require_auth();

$user_id = $_SESSION['user_id'];

// Database connection using config
$conn = db_connect();

// AJAX Upload handler for Payment QR Code & Details Image
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'upload_payment_qr') {
    // CSRF validation
    if (!csrf_validate($_POST['csrf_token'] ?? '')) {
        json_response(['error' => 'Invalid CSRF token.'], 400);
    }
    
    // Cloudinary details
    $cloud_name = "div48nrko";
    $upload_preset = "quatation_managment";
    
    if (isset($_FILES['payment_qr']) && $_FILES['payment_qr']['error'] === 0) {
        $tmpFilePath = $_FILES['payment_qr']['tmp_name'];
        $fileSize = $_FILES['payment_qr']['size'];
        $fileType = $_FILES['payment_qr']['type'];
        
        if ($fileSize > 5 * 1024 * 1024) {
            json_response(['error' => 'File size must be less than 5MB'], 400);
        }
        
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($fileType, $allowed_types)) {
            json_response(['error' => 'Only JPG, PNG, GIF, and WEBP images are allowed'], 400);
        }
        
        $url = "https://api.cloudinary.com/v1_1/$cloud_name/image/upload";
        global $user_id;
        $postData = [
            "file" => new CURLFile($tmpFilePath),
            "upload_preset" => $upload_preset,
            "folder" => "user_" . $user_id . "/profile"
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Allow local SSL connections
        $response = curl_exec($ch);
        if ($response === false) {
            $err = curl_error($ch);
            curl_close($ch);
            json_response(['error' => 'cURL error: ' . $err], 500);
        }
        curl_close($ch);
        
        $result = json_decode($response, true);
        if (isset($result['error'])) {
            json_response(['error' => 'Cloudinary error: ' . ($result['error']['message'] ?? 'Unknown error')], 500);
        }
        
        if (isset($result['secure_url'])) {
            // Also update the company profile's payment_qr_image persistently!
            $stmt = $conn->prepare("UPDATE companies SET payment_qr_image = ? WHERE company_id = ?");
            $stmt->bind_param("si", $result['secure_url'], $_SESSION['company_id']);
            $stmt->execute();
            $stmt->close();
            
            json_response(['url' => $result['secure_url']]);
        } else {
            json_response(['error' => 'Failed to obtain secure URL from Cloudinary'], 500);
        }
    } else {
        json_response(['error' => 'No file uploaded or upload error'], 400);
    }
}

// ✅ FIXED: Handle form submission for adding a new company
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_company'])) {
    // Check if user can add companies
    if (!can_add_company($user_id)) {
        $trial_counts = get_trial_counts($user_id);
        $error_message = "Trial limit reached! You can add up to 2 companies. Remaining: {$trial_counts['companies']}. Please upgrade to Premium.";
    } else {
        // CSRF validation
        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            $error_message = "Invalid request. Please refresh and try again.";
        } else {
            $party_name = sanitize_input($_POST['party_name'] ?? '');
            $party_contact = sanitize_input($_POST['party_contact'] ?? '');
            $party_email = sanitize_input($_POST['party_email'] ?? '');
            $party_add = sanitize_input($_POST['party_add'] ?? '');
            $gst_no = strtoupper(sanitize_input($_POST['gst_no'] ?? ''));
            
            if (empty($party_name) || empty($party_contact) || empty($party_email) || empty($party_add) || empty($gst_no)) {
                $error_message = "All fields are required.";
            } elseif (!validate_email($party_email)) {
                $error_message = "Invalid email address.";
            } else {
                $stmt = $conn->prepare("INSERT INTO customer_companies (customer_company_name, customer_address, contact, email_id, customer_gstin, company_id) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssi", $party_name, $party_add, $party_contact, $party_email, $gst_no, $_SESSION['company_id']);
                
                if ($stmt->execute()) {
                    // Increment trial company count if on trial
                    if (is_trial_user($user_id)) {
                        increment_trial_companies($user_id);
                    }
                    $success_message = "Company added successfully!";
                } else {
                    $error_message = "Error: " . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
}

// Fetch all customer companies from database
$companies = [];
$sql = "SELECT customer_id AS company_id, customer_company_name AS party_name, customer_address AS party_add, contact AS party_contact, email_id AS party_email, customer_gstin AS gst_no FROM customer_companies WHERE company_id = ? ORDER BY customer_company_name";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['company_id']);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $companies[] = $row;
    }
}
$stmt->close();

// Fetch all products (instruments) from database
$products = [];
$sql = "SELECT instrument_id AS product_id, instrument_name AS name, price, description, image, hsn_code AS hsn FROM instruments WHERE company_id = ? ORDER BY instrument_id ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['company_id']);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}
$stmt->close();

// Fetch user company profile details
$user_company = [
    'company_name' => '',
    'company_address' => '',
    'gstin_number' => '',
    'header_image' => '',
    'footer_image' => '',
    'logo_image' => '',
    'sign_image' => '',
    'payment_qr_image' => '',
    'bank_name' => '',
    'account_no' => '',
    'ifsc_code' => '',
    'account_holder' => '',
    'default_expiration' => '1 Days',
    'default_due_date' => '',
    'default_payment_terms' => 'To confirm your booking, an advance payment of 50% of the total cost is required.',
    'default_notes' => '',
    'default_highlights_title' => 'Additional Free Services Included (Highlights)',
    'default_highlights_text' => "* Tournament Setup: Complete match setup...\n* Match Scheduling...",
    'include_highlights' => 1
];
$sql = "SELECT company_name, company_address, gstin_number, header_image, footer_image, logo_image, sign_image, payment_qr_image, bank_name, account_no, ifsc_code, account_holder, default_expiration, default_due_date, default_payment_terms, default_notes, default_highlights_title, default_highlights_text, include_highlights FROM companies WHERE company_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['company_id']);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    $user_company = $result->fetch_assoc();
}
$stmt->close();

// Fetch current user format preference
$stmt_pref = $conn->prepare("SELECT format_preference, product_ui_preference FROM users WHERE user_id = ?");
$stmt_pref->bind_param("i", $_SESSION['user_id']);
$stmt_pref->execute();
$pref_res = $stmt_pref->get_result();
$format_pref = null;
$product_ui_pref = 'list'; // Default
if ($pref_res && $pref_res->num_rows > 0) {
    $row = $pref_res->fetch_assoc();
    $format_pref = $row['format_preference'];
    $product_ui_pref = $row['product_ui_preference'] ?: 'list';
}
$stmt_pref->close();

// Fetch auto-generated quotation number
$next_quote_no = 'QT-0001';
$sql_q = "SELECT quotation_no FROM quotations WHERE company_id = ? ORDER BY quotation_id DESC LIMIT 1";
$stmt_q = $conn->prepare($sql_q);
$stmt_q->bind_param("i", $_SESSION['company_id']);
$stmt_q->execute();
$res_q = $stmt_q->get_result();
if ($res_q && $res_q->num_rows === 1) {
    $last_no = $res_q->fetch_assoc()['quotation_no'];
    if (preg_match('/^(.*?)([0-9]+)$/', $last_no, $matches)) {
        $prefix = $matches[1];
        $num_str = $matches[2];
        $num_val = intval($num_str) + 1;
        $next_quote_no = $prefix . str_pad($num_val, strlen($num_str), '0', STR_PAD_LEFT);
    } else {
        $next_quote_no = $last_no . '-1';
    }
}
$stmt_q->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
<link rel="icon" type="image/png" href="logo-new.png">
<link rel="manifest" href="manifest.json">
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Quotation Management System</title>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
  <link rel="stylesheet" href="sidebar.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="home.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="theme.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="components.css?v=<?php echo time(); ?>">
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
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: var(--font-body);
      /* background and padding handled by sidebar.css global body rule */
    }

    .mainBody {
      display: block;
      min-height: 100vh;
    }
     .user-info-bar {
            position: fixed;
            top: 0;
            right: 0;
            background: linear-gradient(135deg, #1a1a1a 0%, #000000 100%);
            padding: 10px 20px;
            border-bottom-left-radius: 12px;
            border-bottom: 1px solid #2dd4bf22;
            border-left: 1px solid #2dd4bf22;
            z-index: 1001;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 2px 10px rgba(45, 212, 191, 0.1);
        }

        .user-welcome {
            color: #2dd4bf;
            font-size: 14px;
            font-weight: 500;
        }

        .user-email {
            color: #ccc;
            font-size: 12px;
        }

        .logout-btn {
            background: #ff4757;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: bold;
            transition: all 0.3s;
        }

        .logout-btn:hover {
            background: #ff3838;
            transform: translateY(-1px);
        }
    /* Local Main Content Layout Override */
    .main-content {
      min-height: calc(100vh - var(--topbar-h) - var(--bnav-h));
    }

    h1 {
      font-size: 1.8rem;
      color: #2dd4bf;
      font-weight: 800;
      margin-bottom: 25px;
      text-align: center;
      background: linear-gradient(135deg, #2dd4bf 0%, #14b8a6 50%, #0d9488 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      letter-spacing: -0.5px;
      text-shadow: 0 0 30px rgba(45, 212, 191, 0.3);
    }

    h2 {
      font-size: 1.3rem;
      color: #2dd4bf;
      margin: 20px 0 15px 0;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    h2::before {
      content: '';
      width: 4px;
      height: 28px;
      background: linear-gradient(180deg, #2dd4bf, #14b8a6);
      border-radius: 2px;
    }

    .alert {
      padding: 16px 20px;
      margin-bottom: 25px;
      border-radius: 12px;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 12px;
      animation: slideIn 0.4s ease;
    }

    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateY(-10px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
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

    /* Form Styles */
    .product-form {
      position: relative;
      background: linear-gradient(135deg, rgba(26, 26, 26, 0.95) 0%, rgba(15, 31, 31, 0.98) 100%);
      border-radius: 16px;
      padding: 40px;
      margin-bottom: 40px;
      box-shadow: 0 20px 60px rgba(45, 212, 191, 0.15), inset 0 0 0 1px rgba(255,255,255,0.05);
      backdrop-filter: blur(20px);
      overflow: hidden;
      transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275), box-shadow 0.4s ease;
      z-index: 1;
    }

    .product-form::before {
      content: '';
      position: absolute;
      top: -50%; left: -50%; width: 200%; height: 200%;
      background: conic-gradient(from 0deg, transparent 0%, rgba(45, 212, 191, 0.2) 25%, transparent 50%, rgba(45, 212, 191, 0.2) 75%, transparent 100%);
      animation: rotate-gradient 10s linear infinite;
      z-index: -1;
      opacity: 0.5;
    }

    .product-form::after {
      content: '';
      position: absolute;
      inset: 2px;
      background: inherit;
      border-radius: 14px;
      z-index: -1;
    }

    .product-form:hover {
      transform: translateY(-5px) scale(1.01);
      box-shadow: 0 25px 70px rgba(45, 212, 191, 0.25), inset 0 0 0 1px rgba(255,255,255,0.1);
    }
    
    @keyframes rotate-gradient {
      from { transform: rotate(0deg); }
      to { transform: rotate(360deg); }
    }

    .form-group {
      margin-bottom: 15px;
    }

    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 30px;
    }

    .form-group label {
      display: block;
      margin-bottom: 12px;
      color: #2dd4bf;
      font-weight: 700;
      font-size: 17px;
      letter-spacing: 0.5px;
    }

    .form-group input,
    .form-group select,
    .form-group textarea,
    #invoiceTable input,
    #modalProductSearch {
      width: 100%;
      padding: 18px 22px;
      border: 2px solid rgba(45, 212, 191, 0.2);
      border-radius: 14px;
      background: rgba(10, 10, 10, 0.7) linear-gradient(120deg, transparent 0%, rgba(45,212,191,0.08) 50%, transparent 100%);
      background-size: 200% 100%;
      color: white;
      font-size: 17px;
      font-weight: 500;
      transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
      font-family: inherit;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }

    .form-group input:hover,
    .form-group select:hover,
    .form-group textarea:hover,
    #invoiceTable input:hover,
    #modalProductSearch:hover {
      background-position: 100% 0;
      border-color: rgba(45, 212, 191, 0.6);
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(45, 212, 191, 0.15);
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus,
    #invoiceTable input:focus,
    #modalProductSearch:focus {
      outline: none;
      border-color: #2dd4bf;
      box-shadow: 0 0 0 4px rgba(45, 212, 191, 0.2), 0 15px 30px rgba(45, 212, 191, 0.2);
      background: rgba(20, 20, 20, 0.95);
      transform: translateY(-4px) scale(1.02);
    }

    /* Custom Select Styling */
    .form-group select {
      appearance: none;
      -webkit-appearance: none;
      -moz-appearance: none;
      background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%232dd4bf' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
      background-repeat: no-repeat;
      background-position: right 16px center;
      background-size: 18px;
      padding-right: 45px;
      cursor: pointer;
    }

    .form-group select option {
      background: #1a1a1a;
      color: #fff;
      padding: 12px;
    }
    
    .form-group select:hover {
      border-color: rgba(45, 212, 191, 0.5);
    }

    .form-group input[readonly],
    .form-group textarea[readonly] {
      background: rgba(26, 26, 26, 0.6);
      color: #999;
      cursor: not-allowed;
      border-color: rgba(45, 212, 191, 0.1);
    }

    .add-company-btn {
      background: linear-gradient(135deg, #2dd4bf 0%, #14b8a6 100%);
      color: #000;
      border: none;
      padding: 10px 15px;
      border-radius: 8px;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.3s ease;
      white-space: nowrap;
      min-width: 130px;
      font-size: 13px;
    }

    .add-company-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(45, 212, 191, 0.2);
      background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
    }

    /* Modal Styles */
    .modal {
      display: none;
      position: fixed;
      z-index: 2000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.85);
      backdrop-filter: blur(8px);
      animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    .modal-content {
      background: linear-gradient(135deg, #1a1a1a 0%, #0a0a0a 100%);
      margin: 3% auto;
      padding: 35px;
      border-radius: 20px;
      width: 90%;
      max-width: 700px;
      box-shadow: 0 12px 30px rgba(45, 212, 191, 0.16);
      border: 1px solid rgba(45, 212, 191, 0.2);
      position: relative;
      max-height: 90vh;
      overflow-y: auto;
      animation: slideUp 0.4s ease;
    }

    @keyframes slideUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .close {
      color: #999;
      float: right;
      font-size: 32px;
      font-weight: bold;
      cursor: pointer;
      transition: all 0.3s;
      position: absolute;
      right: 25px;
      top: 20px;
      width: 35px;
      height: 35px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
    }

    .close:hover {
      color: #2dd4bf;
      background: rgba(45, 212, 191, 0.1);
      transform: rotate(90deg);
    }

    .modal h2 {
      color: #2dd4bf;
      margin-bottom: 30px;
      font-size: 26px;
    }

    .modal-buttons {
      display: flex;
      gap: 15px;
      justify-content: flex-end;
      margin-top: 35px;
    }

    .save-btn,
    .cancel-btn {
      padding: 14px 28px;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      font-size: 15px;
      font-weight: 600;
      transition: all 0.3s ease;
    }

    .save-btn {
      background: linear-gradient(135deg, #2dd4bf 0%, #14b8a6 100%);
      color: #000;
    }

    .save-btn:hover {
      background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
      transform: translateY(-2px);
      box-shadow: 0 4px 14px rgba(45, 212, 191, 0.2);
    }

    .cancel-btn {
      background: rgba(51, 51, 51, 0.8);
      color: white;
      border: 1px solid rgba(85, 85, 85, 0.6);
    }

    .cancel-btn:hover {
      background: rgba(68, 68, 68, 0.9);
      border-color: rgba(102, 102, 102, 0.8);
      transform: translateY(-2px);
    }

    /* Instrument Container */
    .instrument-container {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 15px;
      margin-top: 20px;
    }

    .instrument-card {
      background: linear-gradient(135deg, rgba(26, 26, 26, 0.9) 0%, rgba(15, 31, 31, 0.9) 100%);
      border: 1px solid rgba(45, 212, 191, 0.15);
      border-radius: 12px;
      padding: 15px;
      box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
      cursor: pointer;
      position: relative;
      overflow: hidden;
      opacity: 0;
      animation: slideInUp 0.6s ease forwards;
      user-select: none;
    }

    .instrument-card.selected {
      border-color: #2dd4bf;
      background: linear-gradient(135deg, rgba(26, 26, 26, 1) 0%, rgba(15, 47, 47, 1) 100%);
      box-shadow: 0 8px 24px rgba(45, 212, 191, 0.16);
      transform: translateY(-5px);
    }

    .instrument-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(45, 212, 191, 0.08), transparent);
      transition: left 0.6s;
    }

    .instrument-card:hover::before {
      left: 100%;
    }

    .instrument-card:hover {
      transform: translateY(-8px);
      border-color: rgba(45, 212, 191, 0.4);
      box-shadow: 0 16px 50px rgba(45, 212, 191, 0.2);
    }

    .instrument-card:nth-child(2) {
      animation-delay: 0.1s;
    }

    .instrument-card:nth-child(3) {
      animation-delay: 0.2s;
    }

    .instrument-card:nth-child(4) {
      animation-delay: 0.3s;
    }

    .instrument-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      font-weight: 700;
      margin-bottom: 22px;
      font-size: 1.2rem;
      color: #2dd4bf;
    }

    .instrument-header input[type="checkbox"] {
      width: 22px;
      height: 22px;
      accent-color: #2dd4bf;
      cursor: pointer;
      transform: scale(1.1);
    }

    .instrument-img {
      width: 100%;
      height: 140px;
      object-fit: cover;
      margin-bottom: 12px;
      border-radius: 8px;
      border: 2px solid rgba(45, 212, 191, 0.15);
      transition: all 0.4s ease;
      background: rgba(10, 10, 10, 0.8);
    }

    .instrument-card:hover .instrument-img {
      border-color: rgba(45, 212, 191, 0.4);
      transform: scale(1.03);
      box-shadow: 0 8px 25px rgba(45, 212, 191, 0.2);
    }

    .instrument-details {
      font-size: 15px;
      margin-bottom: 20px;
      color: rgba(255, 255, 255, 0.8);
      line-height: 1.7;
    }

    .instrument-details p {
      margin-bottom: 10px;
    }

    .instrument-details b {
      color: #2dd4bf;
      font-weight: 600;
    }

    .price-input,
    .qty-input {
      width: 100%;
      padding: 8px 10px;
      border-radius: 8px;
      border: 1px solid rgba(45, 212, 191, 0.2);
      background: rgba(10, 10, 10, 0.9);
      color: white;
      font-size: 13px;
      transition: all 0.3s ease;
      margin-top: 4px;
      font-weight: 500;
    }

    .price-input:focus,
    .qty-input:focus {
      outline: none;
      border-color: #2dd4bf;
      box-shadow: 0 0 0 3px rgba(45, 212, 191, 0.1);
    }

    .instrument-card label {
      color: #2dd4bf;
      font-weight: 600;
      display: block;
      margin-bottom: 6px;
      margin-top: 16px;
      font-size: 14px;
    }

    .instrument-card>div {
      margin-bottom: 16px;
    }

    /* Scrollbar */
    ::-webkit-scrollbar {
      width: 10px;
    }

    ::-webkit-scrollbar-track {
      background: #0a0a0a;
    }

    ::-webkit-scrollbar-thumb {
      background: linear-gradient(180deg, #2dd4bf 0%, #14b8a6 100%);
      border-radius: 5px;
    }

    ::-webkit-scrollbar-thumb:hover {
      background: linear-gradient(180deg, #14b8a6 0%, #0d9488 100%);
    }

    /* Animation keyframes */
    @keyframes slideInUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* Mobile Header and Menu Buttons */
    .mobile-header {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      height: 55px;
      background: linear-gradient(135deg, #000000 0%, #111111 100%);
      border-bottom: 1px solid rgba(45, 212, 191, 0.2);
      z-index: 1001;
      align-items: center;
      padding: 0 15px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.5);
    }

    .menu-toggle-btn {
      background: transparent;
      border: none;
      color: #2dd4bf;
      font-size: 24px;
      cursor: pointer;
      padding: 5px 10px;
      transition: color 0.3s;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .menu-toggle-btn:hover {
      color: white;
    }

    .mobile-logo-text {
      color: #2dd4bf;
      font-size: 16px;
      font-weight: bold;
      margin-left: 15px;
      letter-spacing: 0.5px;
    }

    .close-sidebar-btn {
      display: none;
      position: absolute;
      top: 15px;
      right: 15px;
      background: transparent;
      border: none;
      color: #ff4757;
      font-size: 22px;
      cursor: pointer;
      padding: 5px;
      z-index: 1101;
      transition: color 0.3s;
    }

    .close-sidebar-btn:hover {
      color: white;
    }

    /* Responsive Design */
    @media (max-width: 1200px) {
      .instrument-container {
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      }
    }

    @media (max-width: 768px) {
      h1 {
        font-size: 1.8rem;
      }

      .instrument-container {
        grid-template-columns: 1fr;
      }

      .form-row {
        grid-template-columns: 1fr;
      }
    }

    /* Table styles for Quotation Items */
    #invoiceTable {
      width: 100%;
      border-collapse: collapse;
      margin-top: 15px;
      font-size: 14px;
    }
    #invoiceTable th, #invoiceTable td {
      border-bottom: 1px solid rgba(45, 212, 191, 0.15);
      padding: 12px 10px;
      vertical-align: middle;
    }
    #invoiceTable th {
      border-bottom: 2px solid rgba(45, 212, 191, 0.3);
      text-transform: uppercase;
      font-size: 11px;
      letter-spacing: 0.5px;
    }
    #invoiceTable tr:hover {
      background: rgba(45, 212, 191, 0.03);
    }
    #invoiceTable input {
      width: 100%;
      background: transparent;
      border: 1px solid transparent;
      color: var(--text);
      padding: 8px;
      border-radius: 6px;
      font-size: 14px;
      transition: all 0.2s ease;
    }
    #invoiceTable input:focus {
      background: var(--surface);
      border-color: #2dd4bf;
      outline: none;
      box-shadow: 0 0 0 2px rgba(45, 212, 191, 0.15);
    }
    #invoiceTable input[readonly] {
      background: transparent;
      color: var(--text-muted);
      cursor: not-allowed;
      border-color: transparent;
    }
    .delete-row-btn {
      background: none;
      border: none;
      color: #ff4757;
      cursor: pointer;
      font-size: 16px;
      transition: all 0.2s ease;
    }
    .delete-row-btn:hover {
      color: #ff3838;
      transform: scale(1.15);
    }

    /* Modal for Product Picker */
    #productPickerModal .modal-content {
      background: linear-gradient(135deg, #161616 0%, #0a0a0a 100%);
      border: 1px solid rgba(45, 212, 191, 0.3);
      box-shadow: 0 10px 30px rgba(45, 212, 191, 0.14);
    }
    #modalProductSearch {
      width: 100%;
      padding: 12px;
      background: #0d0d0d;
      border: 1px solid rgba(45, 212, 191, 0.3);
      border-radius: 8px;
      color: white;
      font-size: 15px;
    }
    #modalProductSearch:focus {
      border-color: #2dd4bf;
      outline: none;
      box-shadow: 0 0 6px rgba(45, 212, 191, 0.14);
    }
    #modalProductsTable tr:hover {
      background: rgba(45, 212, 191, 0.1);
    }
    #modalProductsTable th {
      font-weight: 600;
    }

    /* form2-actions-bar removed — bottom nav handles these actions now */
  </style>
</head>

<body>
  <?php include __DIR__ . '/sidebar.php'; ?>

  <div class="mainBody">

    <!-- Main Content -->
    <div class="main-content">
      <nav class="breadcrumb"><a href="home.php">🏠 Home</a><span class="breadcrumb-sep">›</span><span>New Quotation</span></nav>
      <h1>Quotation Management System</h1>

      <?php if (isset($success_message)): ?>
        <div class="alert alert-success">✓ <?php echo $success_message; ?></div>
      <?php endif; ?>
      <?php if (isset($error_message)): ?>
        <div class="alert alert-error">✕ <?php echo $error_message; ?></div>
      <?php endif; ?>

      <?php
      if (is_trial_user($user_id)) {
          $trial_counts = get_trial_counts($user_id);
          echo '<div class="trial-banner">⚠️ <span><strong>Trial Mode:</strong> ' . $trial_counts['products'] . ' instruments, ' . $trial_counts['companies'] . ' companies, ' . $trial_counts['quotations'] . ' quotations remaining</span><a href="premium.php">Upgrade ›</a></div>';
      }
      ?>

      <form id="productForm" class="product-form" style="padding: 20px; margin-bottom: 20px; border-radius: 12px; background: var(--surface2); border: 1px solid var(--teal-border); box-shadow: var(--shadow-card);">
        
        <!-- Unified Top Row -->
        <div class="mobile-responsive-grid">
          
          <!-- Company Selection Block -->
          <div class="form-group">
            <label>Client Company</label>
            <div style="display: flex; gap: 10px; align-items: center; margin-bottom: 5px;">
              <select id="companySelect" name="company" required onchange="fillCompanyDetails()">
                <option value="">-- Select Company --</option>
                <?php foreach ($companies as $company): ?>
                  <option value="<?php echo $company['company_id']; ?>"
                          data-name="<?php echo htmlspecialchars($company['party_name']); ?>"
                          data-address="<?php echo htmlspecialchars($company['party_add']); ?>"
                          data-contact="<?php echo htmlspecialchars($company['party_contact']); ?>"
                          data-email="<?php echo htmlspecialchars($company['party_email']); ?>"
                          data-gst="<?php echo htmlspecialchars($company['gst_no']); ?>">
                    <?php echo htmlspecialchars($company['party_name']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <button type="button" onclick="openAddCompanyModal()" class="add-company-btn">+ Add</button>
            </div>

            <div id="companySummaryBlock" style="display: none; margin-top: 15px; align-items: flex-start; justify-content: space-between; background: rgba(45, 212, 191, 0.05); padding: 12px; border-radius: 8px; border: 1px solid rgba(45, 212, 191, 0.2);">
              <div id="companyInfoText" style="font-size: 12.5px; color: var(--text-muted); line-height: 1.5;"></div>
              <button type="button" onclick="toggleCompanyEdit()" style="padding: 4px 10px; font-size: 11px; background: transparent; border: 1px solid var(--teal); color: var(--teal); border-radius: 4px; cursor: pointer; white-space: nowrap; margin-left: 10px;">Edit</button>
            </div>

            <!-- Editable fields for Client details -->
            <div id="companyEditableDetails" style="display: none; margin-top: 15px; border-top: 1px dashed rgba(45, 212, 191, 0.2); padding-top: 15px;">
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <span style="font-size: 11px; font-weight: 600; color: var(--teal); text-transform: uppercase;">Edit Company Details</span>
                <button type="button" onclick="toggleCompanyEdit()" style="padding: 4px 10px; font-size: 11px; background: transparent; border: 1px solid var(--text-muted); color: var(--text-muted); border-radius: 4px; cursor: pointer;">Done</button>
              </div>
              <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                <div class="form-group" style="margin-bottom:0;">
                  <label style="font-size: 12px; margin-bottom: 4px;">Contact Number</label>
                  <input type="text" id="companyContact" name="companyContact" />
                </div>
                <div class="form-group" style="margin-bottom:0;">
                  <label style="font-size: 12px; margin-bottom: 4px;">Email ID</label>
                  <input type="text" id="companyEmail" name="companyEmail" />
                </div>
              </div>
              <div class="form-group">
                <label style="font-size: 12px; margin-bottom: 4px;">GSTIN</label>
                <input type="text" id="gst_no" name="gst_no" />
              </div>
              <div class="form-group">
                <label style="font-size: 12px; margin-bottom: 4px;">Address</label>
                <textarea id="companyAddress" name="companyAddress" rows="2" style="resize: vertical;"></textarea>
              </div>
            </div>
          </div>

          <!-- Quote No Block -->
          <div class="form-group">
            <label for="quotationNo">Quotation No.</label>
            <input type="text" id="quotationNo" name="quotationNo" required value="<?php echo htmlspecialchars($next_quote_no); ?>" readonly />
          </div>

          <!-- Date Block -->
          <div class="form-group">
            <label for="quotationDate">Date</label>
            <input type="date" id="quotationDate" name="quotationDate" required value="<?php echo date('Y-m-d'); ?>" />
          </div>
        </div>

        <!-- Order By Person Block -->
        <div class="mobile-responsive-grid-2">
          <div class="form-group">
            <label for="orderByPerson">Order By (Person Name)</label>
            <input type="text" id="orderByPerson" name="orderByPerson" required placeholder="Enter name..." />
          </div>
          <div class="form-group">
            <label for="ordererContact">Orderer Contact No.</label>
            <input type="text" id="ordererContact" name="ordererContact" placeholder="Enter contact no..." />
          </div>
        </div>

        <!-- Advanced Options Removed per User Request (Now configured in Settings) -->
        <input type="hidden" id="quotationFormat" value="<?php echo htmlspecialchars($format_pref ?: 'format1'); ?>" />
        <input type="hidden" id="expirationDate" value="<?php echo htmlspecialchars($user_company['default_expiration'] ?? ''); ?>" />
        <input type="hidden" id="paymentDueDate" value="<?php echo htmlspecialchars($user_company['default_due_date'] ?? ''); ?>" />
        <textarea id="paymentTerms" style="display:none;"><?php echo htmlspecialchars($user_company['default_payment_terms'] ?? ''); ?></textarea>
        <textarea id="quotationNotes" style="display:none;"><?php echo htmlspecialchars($user_company['default_notes'] ?? ''); ?></textarea>
        <input type="checkbox" id="includeHighlights" <?php echo (!empty($user_company['include_highlights']) ? 'checked' : ''); ?> style="display:none;" />
        <input type="hidden" id="highlightsTitle" value="<?php echo htmlspecialchars($user_company['default_highlights_title'] ?? ''); ?>" />
        <textarea id="highlightsText" style="display:none;"><?php echo htmlspecialchars($user_company['default_highlights_text'] ?? ''); ?></textarea>
        
        <input type="hidden" id="accHolder" value="<?php echo htmlspecialchars($user_company['account_holder'] ?? ''); ?>" />
        <input type="hidden" id="bankName" value="<?php echo htmlspecialchars($user_company['bank_name'] ?? ''); ?>" />
        <input type="hidden" id="accNo" value="<?php echo htmlspecialchars($user_company['account_no'] ?? ''); ?>" />
        <input type="hidden" id="ifscCode" value="<?php echo htmlspecialchars($user_company['ifsc_code'] ?? ''); ?>" />
        
        <!-- Pass format_pref securely to JS for logic flow -->
        <script>
            const defaultFormatPref = "<?php echo htmlspecialchars($format_pref ?: 'format1'); ?>";
            // Set the value internally if toggleFormatFields is called
            function toggleFormatFields() {} 
        </script>
      </form>

      <?php if ($product_ui_pref === 'card'): ?>
      <h2 style="font-size: 1.5rem; margin-bottom: 15px;">Invoice Items (Card Style)</h2>
      <div id="productCardGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 15px; margin-bottom: 20px;">
        <?php foreach ($products as $index => $prod): ?>
        <div class="product-card" data-index="<?php echo $index; ?>" style="background: var(--surface2); border: 1px solid var(--teal-border); border-radius: 12px; padding: 15px; text-align: center; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <?php if (!empty($prod['image'])): ?>
                <img src="<?php echo htmlspecialchars($prod['image']); ?>" alt="Product" style="width: 100%; height: 120px; object-fit: contain; border-radius: 8px; margin-bottom: 10px; background: rgba(0,0,0,0.1);">
                <?php else: ?>
                <div style="width: 100%; height: 120px; background: rgba(45, 212, 191, 0.1); border-radius: 8px; margin-bottom: 10px; display: flex; align-items: center; justify-content: center; color: var(--teal);"><?php echo icon('image', 32); ?></div>
                <?php endif; ?>
                <h3 style="font-size: 16px; margin-bottom: 5px; color: var(--text);"><?php echo htmlspecialchars($prod['name']); ?></h3>
                <p style="font-size: 12px; color: var(--text-muted); margin-bottom: 10px; height: 36px; overflow: hidden;"><?php echo htmlspecialchars($prod['description'] ?: 'No description'); ?></p>
            </div>
            
            <div style="margin-top: auto;">
                <div style="margin-bottom: 10px; text-align: left;">
                    <label style="font-size: 12px; color: var(--text-muted);">Rate (₹)</label>
                    <input type="number" class="card-rate" value="<?php echo htmlspecialchars($prod['price']); ?>" oninput="syncCardsToTable()" style="width: 100%; padding: 8px; background: var(--background); border: 1px solid var(--border); color: var(--text); border-radius: 6px;">
                </div>
                
                <div style="display: flex; align-items: center; justify-content: center; gap: 10px;">
                    <button type="button" onclick="updateCardQty(this, -1)" style="width: 32px; height: 32px; border-radius: 50%; background: var(--surface); border: 1px solid var(--teal-border); color: var(--teal); cursor: pointer; font-size: 16px; display: flex; align-items: center; justify-content: center;">-</button>
                    <input type="number" class="card-qty" value="0" min="0" oninput="syncCardsToTable()" style="width: 60px; text-align: center; padding: 6px; background: var(--background); border: 1px solid var(--border); color: var(--text); border-radius: 6px; font-weight: bold;">
                    <button type="button" onclick="updateCardQty(this, 1)" style="width: 32px; height: 32px; border-radius: 50%; background: var(--surface); border: 1px solid var(--teal-border); color: var(--teal); cursor: pointer; font-size: 16px; display: flex; align-items: center; justify-content: center;">+</button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
      </div>
      
      <!-- Hidden table for compatibility with generate PDF logic -->
      <div style="display: none;">
      <?php else: ?>
      <h2 style="font-size: 1.5rem; margin-bottom: 15px;">Invoice Items</h2>
      <div class="table-responsive-container" style="overflow-x: auto; border: 1px solid var(--teal-border); border-radius: 12px; background: var(--surface2); padding: 15px; margin-bottom: 20px;">
      <?php endif; ?>

        <table id="invoiceTable">
          <thead>
            <tr>
              <th style="padding: 12px 10px; width: 60px; color: var(--teal);">#</th>
              <th style="padding: 12px 10px; color: var(--teal);">Item Description</th>
              <th style="padding: 12px 10px; width: 120px; color: var(--teal);">HSN</th>
              <th style="padding: 12px 10px; width: 100px; color: var(--teal);">Qty</th>
              <th style="padding: 12px 10px; width: 150px; color: var(--teal);">Rate (₹)</th>
              <th style="padding: 12px 10px; width: 150px; color: var(--teal);">Amount (₹)</th>
              <th style="padding: 12px 10px; width: 60px; color: var(--teal); text-align: center;">🗑</th>
            </tr>
          </thead>
          <tbody>
            <!-- Dynamic rows will be loaded here by JavaScript -->
          </tbody>
        </table>
        
        <?php if ($product_ui_pref !== 'card'): ?>
        <button type="button" class="dock-button" onclick="addInvoiceRow()" style="margin-top: 15px; max-width: 150px; background: transparent; color: #2dd4bf; border: 1px solid #2dd4bf; box-shadow: none;">
          ➕ Add Row
        </button>
        <?php endif; ?>
      </div>
      
      <?php if ($product_ui_pref === 'card'): ?>
      <script>
        function updateCardQty(btn, change) {
            const container = btn.parentElement;
            const input = container.querySelector('.card-qty');
            let val = parseInt(input.value) || 0;
            val += change;
            if (val < 0) val = 0;
            input.value = val;
            
            const card = container.closest('.product-card');
            if (val > 0) {
                card.style.borderColor = 'var(--teal)';
                card.style.boxShadow = 'var(--shadow-teal)';
            } else {
                card.style.borderColor = 'var(--teal-border)';
                card.style.boxShadow = 'none';
            }
            
            syncCardsToTable();
        }

        function syncCardsToTable() {
            // This function syncs the card inputs to the hidden table so PDF generation works seamlessly.
            const tbody = document.querySelector('#invoiceTable tbody');
            tbody.innerHTML = ''; // Clear all existing rows
            
            let itemCounter = 0;
            
            document.querySelectorAll('.product-card').forEach(card => {
                const qtyInput = card.querySelector('.card-qty');
                const rateInput = card.querySelector('.card-rate');
                const qty = parseInt(qtyInput.value) || 0;
                const rate = parseFloat(rateInput.value) || 0;
                
                if (qty > 0) {
                    const idx = card.getAttribute('data-index');
                    addInvoiceRowWithData(idx, qty, rate);
                    itemCounter++;
                }
            });
            
            // Re-run totals calculation which runs when addInvoiceRowWithData fires, 
            // but just to be sure we also run computeTotals manually
            computeTotals();
        }
      </script>
      <?php endif; ?>

      <!-- Actions moved to form action bar -->
      <div class="action-bar-spacer" style="height: 100px;"></div> <!-- Spacer so content isn't covered -->
    </div>
  </div>

  <!-- Dedicated Quotation Action Bar -->
  <div class="form-action-bar-new">
    <div class="action-bar-left">
      <span class="status-dot"></span>
      <span id="instrumentCountText">0 items selected</span>
    </div>
    <div class="action-bar-right">
      <button type="button" class="btn-action btn-preview" onclick="previewSameTab()">
        <span class="icon">👁️</span> Preview Combined PDF
      </button>
      <button type="button" class="btn-action btn-print" onclick="printPDF()">
        <span class="icon">🖨️</span> Print Combined PDF
      </button>
      <button type="button" class="btn-action btn-share" onclick="shareWhatsApp()">
        <span class="icon">🔗</span> Share Combined PDF
      </button>
      <button type="button" id="enableEditBtn" class="btn-action btn-edit" style="display: none; background: #3b82f6;" onclick="setReadOnlyMode(false)">
        <span class="icon">✏️</span> Enable Editing
      </button>
      <button type="button" class="btn-action btn-save" onclick="saveQuotation()">
        <span class="icon">💾</span> Save All to DB & Folder
      </button>
    </div>
  </div>

  <!-- Add Company Modal -->
  <div id="addCompanyModal" class="modal">
    <div class="modal-content">
      <span class="close" onclick="closeAddCompanyModal()">&times;</span>
      <h2>Add New Company</h2>
      <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
        <div class="form-group">
          <label for="newCompanyName">Company Name: *</label>
          <input type="text" name="party_name" id="newCompanyName" required placeholder="Enter company name">
        </div>

        <div class="form-group">
          <label for="newCompanyContact">Contact Number: *</label>
          <input type="tel" name="party_contact" id="newCompanyContact" required placeholder="Enter contact number">
        </div>

        <div class="form-group">
          <label for="newCompanyEmail">Email Address: *</label>
          <input type="email" name="party_email" id="newCompanyEmail" required placeholder="Enter email address">
        </div>
        
        <div class="form-group">
          <label for="newgst_no">GSTIN NO: *</label>
          <input type="text" name="gst_no" id="newgst_no" required placeholder="Enter GSTIN No">
        </div>

        <div class="form-group">
          <label for="newCompanyAddress">Address: *</label>
          <textarea name="party_add" id="newCompanyAddress" rows="4" required placeholder="Enter complete address"></textarea>
        </div>

        <div class="modal-buttons">
          <button type="button" class="cancel-btn" onclick="closeAddCompanyModal()">Cancel</button>
          <button type="submit" name="add_company" class="save-btn">Add Company</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Instruments Selector Popup Modal -->
  <div id="productPickerModal" class="modal">
    <div class="modal-content" style="max-width: 800px; width: 95%;">
      <span class="close" onclick="closeProductPickerModal()">&times;</span>
      <h2>🔍 Select Instrument</h2>
      
      <!-- Search Bar -->
      <div class="form-group" style="margin-bottom: 20px;">
        <input type="text" id="modalProductSearch" placeholder="Type to search instruments..." style="font-size: 16px; padding: 12px;" />
      </div>
      
      <!-- Instruments List Table -->
      <div style="max-height: 50vh; overflow-y: auto; border: 1px solid rgba(45, 212, 191, 0.2); border-radius: 8px;">
        <table id="modalProductsTable" style="width: 100%; border-collapse: collapse; text-align: left;">
          <thead>
            <tr style="background: rgba(45, 212, 191, 0.1); border-bottom: 1px solid rgba(45, 212, 191, 0.3);">
              <th style="padding: 12px; color: #2dd4bf;">Product Name</th>
              <th style="padding: 12px; color: #2dd4bf;">Stock Qty</th>
              <th style="padding: 12px; color: #2dd4bf; text-align: right;">Fixed Price</th>
            </tr>
          </thead>
          <tbody>
            <!-- Populated via JS -->
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- PDF Preview Modal -->
  <div id="pdfPreviewModal" class="modal">
    <div class="modal-content" style="max-width: 95%; width: 1000px; height: 90vh; padding: 20px; display: flex; flex-direction: column;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <h2 style="margin: 0; color: #045c4a;">📄 Document Preview</h2>
        <span class="close" onclick="closePdfPreviewModal()" style="position: static; font-size: 28px;">&times;</span>
      </div>
      <iframe id="pdfPreviewFrame" src="" style="width: 100%; flex-grow: 1; border: 1px solid var(--teal-border); border-radius: 8px; background: #fff;"></iframe>
    </div>
  </div>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <script>
      

    const { jsPDF } = window.jspdf;

    // Global variable to store instrument data from PHP
    const instrumentData = <?php echo json_encode($products); ?>;

    // User company details from PHP
    const userCompany = <?php echo json_encode($user_company); ?>;
    const userImages = {
      header_image: userCompany.header_image,
      footer_image: userCompany.footer_image,
      sign_image: userCompany.sign_image,
      payment_qr_image: userCompany.payment_qr_image
    };
    const currentUserName = <?php echo json_encode($_SESSION['username']); ?>;
    const userEmail = <?php echo json_encode($_SESSION['email']); ?>;

    // Fill company details on selection
    function fillCompanyDetails() {
      const select = document.getElementById('companySelect');
      const option = select.options[select.selectedIndex];
      
      const detailsDiv = document.getElementById('companyEditableDetails');
      const summaryDiv = document.getElementById('companySummaryBlock');
      
      if (option.value) {
        document.getElementById('companyContact').value = option.dataset.contact || '';
        document.getElementById('companyEmail').value = option.dataset.email || '';
        document.getElementById('companyAddress').value = option.dataset.address || '';
        document.getElementById('gst_no').value = option.dataset.gst || '';
        
        updateCompanySummaryText();
        
        if(summaryDiv) summaryDiv.style.display = 'flex';
        if(detailsDiv) detailsDiv.style.display = 'none';
      } else {
        document.getElementById('companyContact').value = '';
        document.getElementById('companyEmail').value = '';
        document.getElementById('companyAddress').value = '';
        document.getElementById('gst_no').value = '';
        if(summaryDiv) summaryDiv.style.display = 'none';
        if(detailsDiv) detailsDiv.style.display = 'none';
      }
    }

    function toggleCompanyEdit() {
      const summaryDiv = document.getElementById('companySummaryBlock');
      const detailsDiv = document.getElementById('companyEditableDetails');
      if (detailsDiv.style.display === 'none') {
        detailsDiv.style.display = 'block';
        summaryDiv.style.display = 'none';
      } else {
        detailsDiv.style.display = 'none';
        summaryDiv.style.display = 'flex';
        updateCompanySummaryText();
      }
    }

    function updateCompanySummaryText() {
        const gst = document.getElementById('gst_no').value;
        const contact = document.getElementById('companyContact').value;
        const email = document.getElementById('companyEmail').value;
        const address = document.getElementById('companyAddress').value;
        
        let infoHtml = `<strong>GSTIN:</strong> ${gst || 'N/A'}<br/>`;
        infoHtml += `<strong>Contact:</strong> ${contact || 'N/A'} &nbsp;|&nbsp; <strong>Email:</strong> ${email || 'N/A'}<br/>`;
        infoHtml += `<strong>Address:</strong> ${address || 'N/A'}`;
        document.getElementById('companyInfoText').innerHTML = infoHtml;
    }

    // Toggle template format fields visibility
    function toggleFormatFields() {
      const formatEl = document.getElementById('quotationFormat');
      const format = formatEl ? formatEl.value : 'format1';
      const f2Options = document.getElementById('format2Options');
      if (f2Options) {
        if (format === 'format2' || format === 'format3') {
          f2Options.style.display = 'block';
        } else {
          f2Options.style.display = 'none';
        }
      }
    }

    // Handle AJAX QR Image Upload
    async function uploadQrImageAjax() {
      const fileInput = document.getElementById('qrUploadFile');
      const file = fileInput.files[0];
      if (!file) return;

      const formData = new FormData();
      formData.append('payment_qr', file);
      const csrf = '<?php echo csrf_token(); ?>';
      formData.append('csrf_token', csrf);

      QT.toastInfo('Uploading QR Code... Please wait.');

      try {
        const res = await fetch('form2.php?action=upload_payment_qr', {
          method: 'POST',
          body: formData
        });
        const data = await res.json();
        if (!res.ok) {
          QT.toastError(data.error || 'Failed to upload QR Code.');
          fileInput.value = '';
          return;
        }

        // Set URL and display preview
        document.getElementById('paymentQrUrl').value = data.url;
        userImages.payment_qr_image = data.url; // Update dynamically
        
        const previewImg = document.getElementById('qrPreviewImg');
        previewImg.src = data.url;
        document.getElementById('qrPreviewDiv').style.display = 'block';
        QT.toastSuccess('QR Code uploaded successfully!');
      } catch (err) {
        console.error('QR upload failed', err);
        QT.toastError('An error occurred during QR upload.');
        fileInput.value = '';
      }
    }

    function removeQrImage() {
      const qrUploadFile = document.getElementById('qrUploadFile');
      if (qrUploadFile) qrUploadFile.value = '';
      
      const paymentQrUrl = document.getElementById('paymentQrUrl');
      if (paymentQrUrl) paymentQrUrl.value = '';
      
      userImages.payment_qr_image = null;
      
      const qrPreviewDiv = document.getElementById('qrPreviewDiv');
      if (qrPreviewDiv) qrPreviewDiv.style.display = 'none';
      
      QT.toastInfo('QR Code removed.');
    }

    // Populate bank details on load
    function initBankDetails() {
      const accHolder = document.getElementById('accHolder');
      if (accHolder) accHolder.value = userCompany.account_holder || currentUserName || '';
      
      const bankName = document.getElementById('bankName');
      if (bankName) bankName.value = userCompany.bank_name || '';
      
      const accNo = document.getElementById('accNo');
      if (accNo) accNo.value = userCompany.account_no || '';
      
      const ifscCode = document.getElementById('ifscCode');
      if (ifscCode) ifscCode.value = userCompany.ifsc_code || '';
      
      if (userCompany.payment_qr_image) {
        const paymentQrUrl = document.getElementById('paymentQrUrl');
        if (paymentQrUrl) paymentQrUrl.value = userCompany.payment_qr_image;
        
        const qrPreviewImg = document.getElementById('qrPreviewImg');
        if (qrPreviewImg) qrPreviewImg.src = userCompany.payment_qr_image;
        
        const qrPreviewDiv = document.getElementById('qrPreviewDiv');
        if (qrPreviewDiv) qrPreviewDiv.style.display = 'block';
      }
    }

    function updateDueDateFromQuoteDate() {
      const quoteDateEl = document.getElementById('quotationDate');
      if (!quoteDateEl) return;
      const quoteDateVal = quoteDateEl.value;
      if (!quoteDateVal) return;
      
      const d = new Date(quoteDateVal);
      d.setDate(d.getDate() + 1); // 24 hours later
      
      const months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
      const day = d.getDate();
      const month = months[d.getMonth()];
      const year = d.getFullYear();
      
      let suffix = "th";
      if (day === 1 || day === 21 || day === 31) suffix = "st";
      else if (day === 2 || day === 22) suffix = "nd";
      else if (day === 3 || day === 23) suffix = "rd";
      
      const formattedDueDate = `${day}${suffix}- ${month} -${year}`;
      const paymentDueDateEl = document.getElementById('paymentDueDate');
      if (paymentDueDateEl) {
        paymentDueDateEl.value = formattedDueDate;
      }
    }

    // Run bank details initializer when DOM ready
    document.addEventListener('DOMContentLoaded', function() {
      initBankDetails();
      toggleFormatFields();
      updateDueDateFromQuoteDate();
      
      const quoteDateEl = document.getElementById('quotationDate');
      if (quoteDateEl) {
        quoteDateEl.addEventListener('change', updateDueDateFromQuoteDate);
      }
      
      const includeH = document.getElementById('includeHighlights');
      if (includeH) {
        includeH.addEventListener('change', function() {
          const group = document.getElementById('highlightsContentGroup');
          group.style.display = this.checked ? 'block' : 'none';
        });
      }
    });

    // Utility: get URL param
    function getParam(name) {
      const url = new URL(window.location.href);
      return url.searchParams.get(name);
    }

    // Load quotation if qno provided
    async function loadQuotationIfAny() {
      const qno = getParam('qno');
      if (!qno) return;
      try {
        const res = await fetch(`home.php?action=get_quotation&qno=${encodeURIComponent(qno)}`);
        const data = await res.json();
        if (!res.ok) {
          QT.toastError(data.error || 'Failed to load quotation');
          return;
        }
        // Fill company selection by matching company_id
        const companySelect = document.getElementById('companySelect');
        if (data.company_id) {
          for (let i = 0; i < companySelect.options.length; i++) {
            if (String(companySelect.options[i].value) === String(data.company_id)) {
              companySelect.selectedIndex = i;
              break;
            }
          }
          fillCompanyDetails();
        }
        // Set read-only company fields from data in case of mismatch
        document.getElementById('companyAddress').value = data.company_address || document.getElementById('companyAddress').value;
        document.getElementById('companyContact').value = data.company_contact || document.getElementById('companyContact').value;
        document.getElementById('companyEmail').value = data.company_email || document.getElementById('companyEmail').value;
        document.getElementById('gst_no').value = data.gst_no || document.getElementById('gst_no').value;

        // Fill top fields
        document.getElementById('quotationNo').value = data.quotation_no || '';
        document.getElementById('quotationDate').value = data.quotation_date || '';
        document.getElementById('orderByPerson').value = data.order_by_person || '';

        // Fill template format selection
        if (data.template_format) {
          const quotationFormat = document.getElementById('quotationFormat');
          if (quotationFormat) {
            for (let i = 0; i < quotationFormat.options.length; i++) {
              if (String(quotationFormat.options[i].value) === String(data.template_format)) {
                quotationFormat.selectedIndex = i;
                break;
              }
            }
            toggleFormatFields();
          }
        }

        // Fill items
        try {
          const items = JSON.parse(data.items_json || '[]');
          if (Array.isArray(items)) {
            items.forEach((it) => {
              // find product index by product_id match in instrumentData
              const idx = instrumentData.findIndex(p => String(p.product_id || p.id) === String(it.product_id || it.id));
              if (idx >= 0) {
                const checkbox = document.getElementById(`check-${idx}`);
                const qtyInput = document.getElementById(`qty-${idx}`);
                const priceInput = document.getElementById(`price-${idx}`);
                if (checkbox) {
                  checkbox.checked = true;
                }
                if (qtyInput) qtyInput.value = it.qty || it.quantity || 1;
                if (priceInput) priceInput.value = (it.price != null ? it.price : instrumentData[idx].price) || 0;
              }
            });
          }
        } catch (e) {}

        // Sync visible table from loaded hidden inputs
        syncTableFromHidden();
        
        // Enable read-only mode for past quotations
        setReadOnlyMode(true);
      } catch (e) {
        console.error('Failed to load quotation', e);
      }
    }

    // Toggle read-only mode
    function setReadOnlyMode(isReadOnly) {
      const inputs = document.querySelectorAll('#quotationForm input, #quotationForm select, #quotationForm textarea');
      inputs.forEach(el => {
        if (el.id === 'quotationNo') return; // already readonly
        el.disabled = isReadOnly;
      });

      const addRowBtn = document.querySelector('button[onclick="addInvoiceRow()"]');
      if (addRowBtn) addRowBtn.style.display = isReadOnly ? 'none' : 'inline-block';
      
      const deleteBtns = document.querySelectorAll('.delete-row-btn');
      deleteBtns.forEach(btn => btn.style.display = isReadOnly ? 'none' : 'inline-block');
      
      const saveBtn = document.querySelector('button[onclick="saveQuotation()"]');
      if (saveBtn) saveBtn.style.display = isReadOnly ? 'none' : 'inline-block';
      
      const editBtn = document.getElementById('enableEditBtn');
      if (editBtn) editBtn.style.display = isReadOnly ? 'inline-flex' : 'none';
      
      // Some inputs need to remain readonly even when editing is enabled
      if (!isReadOnly) {
        document.querySelectorAll('.row-total, .row-hsn, .row-product-name').forEach(el => {
          el.readOnly = true;
          el.disabled = false;
        });
      }
    }

    function disableEditing() {
      const inputs = document.querySelectorAll('input, select, textarea, button');
      inputs.forEach(el => {
        // allow navigation buttons and preview/pdf
        if (el.classList.contains('dock-button')) {
          if (el.textContent.includes('Save Quotation') || el.textContent.includes('Add Row')) {
            el.disabled = true;
            el.style.opacity = 0.6;
            el.style.cursor = 'not-allowed';
          }
          return;
        }
        if (el.id === 'quotationNo' || el.id === 'quotationDate' || el.id === 'orderByPerson' || el.id.startsWith('qty-') || el.id.startsWith('price-')) {
          el.setAttribute('readonly', 'readonly');
          el.disabled = true;
        }
        if (el.classList.contains('row-product-name') || el.classList.contains('row-hsn') || el.classList.contains('row-qty') || el.classList.contains('row-price')) {
          el.setAttribute('readonly', 'readonly');
          el.disabled = true;
          el.style.pointerEvents = 'none';
        }
        if (el.classList.contains('delete-row-btn')) {
          el.disabled = true;
          el.style.opacity = 0.5;
          el.style.pointerEvents = 'none';
        }
        if (el.tagName.toLowerCase() === 'select') {
          el.disabled = true;
        }
        if (el.type === 'checkbox') {
          el.disabled = true;
        }
      });
    }

    function collectSelectedItems() {
      const items = [];
      instrumentData.forEach((p, index) => {
        const checkbox = document.getElementById(`check-${index}`);
        if (checkbox && checkbox.checked) {
          const qty = parseFloat(document.getElementById(`qty-${index}`).value) || 0;
          const price = parseFloat(document.getElementById(`price-${index}`).value) || 0;
          if (qty > 0) {
            items.push({
              product_id: p.product_id || p.id,
              name: p.name,
              description: p.description || null,
              qty: qty,
              price: price,
              hsn: p.hsn || ''
            });
          }
        }
      });
      return items;
    }

    function computeTotals(items) {
      const subTotal = items.reduce((acc, it) => acc + (it.qty * it.price), 0);
      const gstInput = document.getElementById('gst_no');
      const hasGST = (gstInput && gstInput.value.trim() !== '');
      const gstAmount = hasGST ? subTotal * 0.18 : 0;
      const grandTotal = subTotal + gstAmount;
      return { subTotal, gstAmount, grandTotal };
    }

    async function saveQuotation() {
      const select = document.getElementById('companySelect');
      const option = select.options[select.selectedIndex];
      const companyId = select.value;
      if (!companyId) { QT.toastError('Please select a company'); return; }
      const quotationNo = document.getElementById('quotationNo').value.trim();
      if (!quotationNo) { QT.toastError('Please enter a quotation number'); return; }
      const quotationDate = document.getElementById('quotationDate').value;
      const orderByPerson = document.getElementById('orderByPerson').value.trim();
      if (!orderByPerson) { QT.toastError('Please enter order by person'); return; }
      
      // Ensure hidden inputs are updated from visible table inputs before collecting items
      syncHiddenFromTable();
      
      const items = collectSelectedItems();
      if (items.length === 0) { QT.toastError('Please select at least one instrument with quantity'); return; }
      const totals = computeTotals(items);

      const templateFormat = document.getElementById('quotationFormat') ? document.getElementById('quotationFormat').value : 'format1';

      QT.toastInfo('Preparing quotation...');
      let pdfBase64 = '';
      try {
        const doc = await generatePDF();
        pdfBase64 = doc.output('datauristring'); // Format: data:application/pdf;filename=generated.pdf;base64,...
      } catch (e) {
        console.error('Error generating PDF for save', e);
        // Continue even if PDF fails, but backend won't save the PDF
      }

      const payload = {
        quotationNo: quotationNo,
        quotationDate: quotationDate,
        companyId: parseInt(companyId),
        companyName: option.dataset.name || '',
        companyAddress: document.getElementById('companyAddress').value || '',
        companyContact: document.getElementById('companyContact').value || '',
        companyEmail: document.getElementById('companyEmail').value || '',
        gst_no: document.getElementById('gst_no').value || '',
        orderByPerson: orderByPerson,
        items: items,
        subTotal: totals.subTotal,
        gstAmount: totals.gstAmount,
        grandTotal: totals.grandTotal,
        templateFormat: templateFormat,
        pdfBase64: pdfBase64
      };

      try {
        const res = await fetch('home.php?action=create_quotation', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (!res.ok) {
          if (data.upgrade_required) {
            QT.confirm(data.error + ' Upgrade to Premium now?', function() {
              window.location.href = 'premium.php';
            });
          } else {
            QT.toastError(data.error || 'Failed to save quotation');
          }
          return;
        }
        QT.toastSuccess('Quotation saved successfully');
        setTimeout(function() { window.location.href = 'home.php'; }, 1200);
      } catch (e) {
        console.error('Save quotation failed', e);
        QT.toastError('Failed to save quotation. Please try again.');
      }
    }

    // Add company modal functions
    function openAddCompanyModal() {
      document.getElementById('addCompanyModal').style.display = 'block';
    }

    function closeAddCompanyModal() {
      document.getElementById('addCompanyModal').style.display = 'none';
    }

    // Global row picker reference
    let activeRowForPicker = null;
    let selectedModalItemIndex = -1;
    let currentFilteredProducts = [];

    // Initialize hidden fields
    function initHiddenSyncFields() {
      let container = document.getElementById('hiddenSyncContainer');
      if (!container) {
        container = document.createElement('div');
        container.id = 'hiddenSyncContainer';
        container.style.display = 'none';
        document.body.appendChild(container);
      }
      
      let html = '';
      instrumentData.forEach((p, idx) => {
        html += `
          <input type="checkbox" id="check-${idx}" data-index="${idx}" />
          <input type="number" id="qty-${idx}" value="0" />
          <input type="number" id="price-${idx}" value="${p.price != null ? p.price : 0}" />
        `;
      });
      container.innerHTML = html;
    }

    // Sync table data to hidden inputs for PDF/saving
    function updateInstrumentCount() {
      const table = document.getElementById('invoiceTable');
      if (!table) return;
      const rows = table.querySelectorAll('tbody tr.invoice-row');
      let count = 0;
      rows.forEach(row => {
        const prodName = row.querySelector('.row-product-name');
        if (prodName && prodName.value && prodName.value.trim() !== '') {
          count++;
        }
      });
      const countText = document.getElementById('instrumentCountText');
      if (countText) {
        countText.innerText = count + ' items selected';
      }
      const dot = document.querySelector('.status-dot');
      if (dot) {
        dot.style.backgroundColor = count > 0 ? '#10b981' : '#cbd5e1';
      }
    }

    function syncHiddenFromTable() {
      instrumentData.forEach((p, idx) => {
        const chk = document.getElementById(`check-${idx}`);
        const qtyInput = document.getElementById(`qty-${idx}`);
        const priceInput = document.getElementById(`price-${idx}`);
        if (chk) chk.checked = false;
        if (qtyInput) qtyInput.value = 0;
        if (priceInput) priceInput.value = (p.price != null ? p.price : 0);
      });

      const rows = document.querySelectorAll('#invoiceTable tbody tr');
      rows.forEach(row => {
        const selectIdxVal = row.getAttribute('data-product-index');
        if (selectIdxVal !== null && selectIdxVal !== '') {
          const idx = parseInt(selectIdxVal);
          const qty = parseFloat(row.querySelector('.row-qty').value) || 0;
          const price = parseFloat(row.querySelector('.row-price').value) || 0;

          const chk = document.getElementById(`check-${idx}`);
          const qtyInput = document.getElementById(`qty-${idx}`);
          const priceInput = document.getElementById(`price-${idx}`);
          if (chk) {
            chk.checked = (qty > 0);
          }
          if (qtyInput) {
            qtyInput.value = qty;
          }
          if (priceInput) {
            priceInput.value = price;
          }
        }
      });
    }

    // Sync table from loaded hidden inputs
    function syncTableFromHidden() {
      const tbody = document.querySelector('#invoiceTable tbody');
      tbody.innerHTML = '';
      
      let rowCount = 0;
      instrumentData.forEach((p, idx) => {
        const chk = document.getElementById(`check-${idx}`);
        if (chk && chk.checked) {
          const qty = parseFloat(document.getElementById(`qty-${idx}`).value) || 0;
          const price = parseFloat(document.getElementById(`price-${idx}`).value) || 0;
          addInvoiceRowWithData(idx, qty, price);
          rowCount++;
        }
      });
      
      while (rowCount < 5) {
        addInvoiceRow();
        rowCount++;
      }
    }

    // Add invoice rows
    function addInvoiceRow() {
      const tbody = document.querySelector('#invoiceTable tbody');
      const row = document.createElement('tr');
      row.className = 'invoice-row';
      
      row.innerHTML = `
        <td class="row-sr-no" style="text-align: center; color: #2dd4bf; font-weight: bold;"></td>
        <td>
          <input type="text" class="row-product-name" placeholder="Click to select instrument..." onfocus="openProductPickerModal(this)" readonly style="cursor: pointer;" />
        </td>
        <td>
          <input type="text" class="row-hsn" placeholder="-" readonly />
        </td>
        <td>
          <input type="number" class="row-qty" min="0" step="any" value="0" oninput="updateRowTotal(this)" onkeydown="handleRowKeyDown(event, this, 'qty')" />
        </td>
        <td>
          <input type="number" class="row-price" min="0" step="any" value="0.00" oninput="updateRowTotal(this)" onkeydown="handleRowKeyDown(event, this, 'price')" />
        </td>
        <td>
          <input type="text" class="row-total" value="0.00" readonly style="text-align: right;" />
        </td>
        <td style="text-align: center;">
          <button type="button" class="delete-row-btn" onclick="deleteInvoiceRow(this)">🗑️</button>
        </td>
      `;
      
      tbody.appendChild(row);
      reindexRows();
    }

    function addInvoiceRowWithData(productIdx, qty, price) {
      const tbody = document.querySelector('#invoiceTable tbody');
      const row = document.createElement('tr');
      row.className = 'invoice-row';
      row.setAttribute('data-product-index', productIdx);
      
      const product = instrumentData[productIdx];
      const total = (qty * price).toFixed(2);
      
      row.innerHTML = `
        <td class="row-sr-no" style="text-align: center; color: #2dd4bf; font-weight: bold;"></td>
        <td>
          <input type="text" class="row-product-name" value="${escapeHtml(product.name)}" placeholder="Click to select instrument..." onfocus="openProductPickerModal(this)" readonly style="cursor: pointer;" />
        </td>
        <td>
          <input type="text" class="row-hsn" value="${escapeHtml(product.hsn || '-')}" placeholder="-" readonly />
        </td>
        <td>
          <input type="number" class="row-qty" min="0" step="any" value="${qty}" oninput="updateRowTotal(this)" onkeydown="handleRowKeyDown(event, this, 'qty')" />
        </td>
        <td>
          <input type="number" class="row-price" min="0" step="any" value="${price.toFixed(2)}" oninput="updateRowTotal(this)" onkeydown="handleRowKeyDown(event, this, 'price')" />
        </td>
        <td>
          <input type="text" class="row-total" value="${total}" readonly style="text-align: right;" />
        </td>
        <td style="text-align: center;">
          <button type="button" class="delete-row-btn" onclick="deleteInvoiceRow(this)">🗑️</button>
        </td>
      `;
      
      tbody.appendChild(row);
      reindexRows();
    }

    function escapeHtml(text) {
      if (!text) return '';
      return text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
    }

    function reindexRows() {
      const rows = document.querySelectorAll('#invoiceTable tbody tr');
      rows.forEach((row, i) => {
        row.querySelector('.row-sr-no').textContent = i + 1;
      });
    }

    function deleteInvoiceRow(btn) {
      const row = btn.closest('tr');
      row.remove();
      reindexRows();
      syncHiddenFromTable();
    }

    function updateRowTotal(input) {
      const row = input.closest('tr');
      const qty = parseFloat(row.querySelector('.row-qty').value) || 0;
      const price = parseFloat(row.querySelector('.row-price').value) || 0;
      row.querySelector('.row-total').value = (qty * price).toFixed(2);
      syncHiddenFromTable();
    }

    function handleRowKeyDown(event, input, field) {
      if (event.key === 'Enter') {
        event.preventDefault();
        const row = input.closest('tr');
        if (field === 'qty') {
          row.querySelector('.row-price').focus();
        } else if (field === 'price') {
          const nextRow = row.nextElementSibling;
          if (nextRow) {
            nextRow.querySelector('.row-product-name').focus();
          } else {
            addInvoiceRow();
            const newRow = row.nextElementSibling;
            if (newRow) {
              newRow.querySelector('.row-product-name').focus();
            }
          }
        }
      }
    }

    // Modal Picker controls
    function openProductPickerModal(input) {
      activeRowForPicker = input.closest('tr');
      const modal = document.getElementById('productPickerModal');
      modal.style.display = 'block';
      
      const searchInput = document.getElementById('modalProductSearch');
      searchInput.value = '';
      
      renderModalProducts(instrumentData);
      
      setTimeout(() => {
        searchInput.focus();
      }, 50);
    }

    function closeProductPickerModal() {
      document.getElementById('productPickerModal').style.display = 'none';
      selectedModalItemIndex = -1;
    }

    function renderModalProducts(productsList) {
      currentFilteredProducts = productsList;
      const tbody = document.querySelector('#modalProductsTable tbody');
      tbody.innerHTML = '';
      selectedModalItemIndex = -1;
      
      if (productsList.length === 0) {
        tbody.innerHTML = `<tr><td colspan="3" style="text-align: center; color: #999; padding: 15px;">No instruments found</td></tr>`;
        return;
      }
      
      productsList.forEach((p, idx) => {
        const prodId = p.product_id || p.id || 0;
        const stockQty = ((prodId * 7 + 13) % 35) + 5; 
        const priceVal = parseFloat(p.price || 0).toFixed(2);
        const globalIdx = instrumentData.findIndex(item => (item.product_id || item.id) === (p.product_id || p.id));
        
        const tr = document.createElement('tr');
        tr.style.cursor = 'pointer';
        tr.style.borderBottom = '1px solid rgba(45, 212, 191, 0.1)';
        tr.setAttribute('data-global-index', globalIdx);
        tr.setAttribute('data-filtered-index', idx);
        
        tr.innerHTML = `
          <td style="padding: 12px; color: white;">
            <span style="font-weight: 500;">${escapeHtml(p.name)}</span>
            ${p.description ? `<br/><small style="color: #888;">${escapeHtml(p.description)}</small>` : ''}
          </td>
          <td style="padding: 12px; color: #a5f3fc;">${stockQty} units</td>
          <td style="padding: 12px; color: #2dd4bf; text-align: right; font-weight: bold;">₹${priceVal}</td>
        `;
        
        tr.onclick = function() {
          selectProductFromModal(globalIdx);
        };
        
        tbody.appendChild(tr);
      });
      
      updateInstrumentCount(); // Update count when hidden fields sync
    }

    function filterModalProducts() {
      const query = document.getElementById('modalProductSearch').value.toLowerCase().trim();
      if (!query) {
        renderModalProducts(instrumentData);
        return;
      }
      
      const filtered = instrumentData.filter(p => {
        const nameMatch = p.name && p.name.toLowerCase().includes(query);
        const descMatch = p.description && p.description.toLowerCase().includes(query);
        const hsnMatch = p.hsn && p.hsn.toLowerCase().includes(query);
        return nameMatch || descMatch || hsnMatch;
      });
      
      renderModalProducts(filtered);
    }

    function handleModalSearchKeyDown(e) {
      const rows = document.querySelectorAll('#modalProductsTable tbody tr');
      if (rows.length === 0 || currentFilteredProducts.length === 0) return;
      
      if (e.key === 'ArrowDown') {
        e.preventDefault();
        if (selectedModalItemIndex >= 0 && selectedModalItemIndex < rows.length) {
          rows[selectedModalItemIndex].style.background = '';
        }
        
        selectedModalItemIndex++;
        if (selectedModalItemIndex >= rows.length) {
          selectedModalItemIndex = 0;
        }
        
        highlightModalRow(rows[selectedModalItemIndex]);
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        if (selectedModalItemIndex >= 0 && selectedModalItemIndex < rows.length) {
          rows[selectedModalItemIndex].style.background = '';
        }
        
        selectedModalItemIndex--;
        if (selectedModalItemIndex < 0) {
          selectedModalItemIndex = rows.length - 1;
        }
        
        highlightModalRow(rows[selectedModalItemIndex]);
      } else if (e.key === 'Enter') {
        e.preventDefault();
        if (selectedModalItemIndex >= 0 && selectedModalItemIndex < rows.length) {
          const globalIdx = parseInt(rows[selectedModalItemIndex].getAttribute('data-global-index'));
          selectProductFromModal(globalIdx);
        } else if (rows.length > 0) {
          const globalIdx = parseInt(rows[0].getAttribute('data-global-index'));
          selectProductFromModal(globalIdx);
        }
      } else if (e.key === 'Escape') {
        e.preventDefault();
        closeProductPickerModal();
        if (activeRowForPicker) {
          activeRowForPicker.querySelector('.row-product-name').focus();
        }
      }
    }

    function highlightModalRow(row) {
      row.style.background = 'rgba(45, 212, 191, 0.2)';
      row.scrollIntoView({ block: 'nearest' });
    }

    function selectProductFromModal(globalIdx) {
      if (!activeRowForPicker) return;
      
      const product = instrumentData[globalIdx];
      activeRowForPicker.setAttribute('data-product-index', globalIdx);
      activeRowForPicker.querySelector('.row-product-name').value = product.name;
      activeRowForPicker.querySelector('.row-hsn').value = product.hsn || '-';
      
      const qtyInput = activeRowForPicker.querySelector('.row-qty');
      const priceInput = activeRowForPicker.querySelector('.row-price');
      
      priceInput.value = parseFloat(product.price || 0).toFixed(2);
      
      if (parseFloat(qtyInput.value) <= 0 || !qtyInput.value) {
        qtyInput.value = 1;
      }
      
      updateRowTotal(qtyInput);
      closeProductPickerModal();
      
      qtyInput.focus();
      qtyInput.select();
    }

    // Modal click listeners
    window.addEventListener('click', function(event) {
      const modal = document.getElementById('productPickerModal');
      const companyModal = document.getElementById('addCompanyModal');
      if (event.target == modal) {
        closeProductPickerModal();
      }
      if (event.target == companyModal) {
        closeAddCompanyModal();
      }
    });

    document.addEventListener('DOMContentLoaded', function() {
      const searchInput = document.getElementById('modalProductSearch');
      if (searchInput) {
        searchInput.addEventListener('input', filterModalProducts);
        searchInput.addEventListener('keydown', handleModalSearchKeyDown);
      }
    });

    // Convert image URL to base64
    async function toDataURL(url) {
      if (!url || typeof url !== 'string' || url.trim() === '') return null;
      try {
        const res = await fetch(url);
        if (!res.ok) return null;
        const blob = await res.blob();
        if (!blob.type.startsWith('image/')) return null;
        return new Promise((resolve) => {
          const reader = new FileReader();
          reader.onloadend = () => resolve(reader.result);
          reader.readAsDataURL(blob);
        });
      } catch (error) {
        console.error('Error converting image:', error);
        return null;
      }
    }

    // Draw linear gradient helper in jsPDF
    function drawLinearGradient(doc, x, y, width, height, r1, g1, b1, r2, g2, b2) {
      const steps = height;
      for (let i = 0; i < steps; i++) {
        const ratio = i / steps;
        const r = Math.round(r1 + (r2 - r1) * ratio);
        const g = Math.round(g1 + (g2 - g1) * ratio);
        const b = Math.round(b1 + (b2 - b1) * ratio);
        doc.setFillColor(r, g, b);
        doc.rect(x, y + i, width, 1, 'F');
      }
    }

    // Helper function to add header, company table
    function addHeaderAndCompanyTable(doc, headerImg, companyData, isFirstPage = true) {
      const { companyName, companyAddress, companyContact, quotationNo, quotationDate, orderByPerson, ordererContact, gst_no } = companyData;
      const leftMargin = 14;
      const totalWidth = 182;
      
      // Add header image
      if (headerImg) {
        doc.addImage(headerImg, 2, 5, 210, 30);
      } else {
        doc.setFillColor(240, 240, 240); // Light Grey
        doc.rect(2, 5, 206, 30, 'F');
        doc.setFont("Helvetica", "bold");
        doc.setFontSize(14);
        doc.setTextColor(150, 150, 150);
        doc.text("Header Image Placeholder (210x30)", 105, 22, { align: "center" });
        doc.setTextColor(0, 0, 0); // reset
      }

      // Company Info Table
      doc.autoTable({
        startY: 40,
        body: [
          [
            {
              content: 'Quotation',
              colSpan: 2,
              styles: { 
                halign: 'center', 
                fontStyle: 'bold', 
                fontSize: 14,
                cellPadding: 0,
                minCellHeight: 8,
                textColor: [0, 0, 0]
              }
            }
          ],
          [
            {
              content: `Party Name: ${companyName}`,
              styles: { 
                halign: 'left', 
                fontSize: 7, 
                cellPadding: 1,
                minCellHeight: 4
              }
            },
            { 
              content: `Quotation No: ${quotationNo}`, 
              styles: { 
                minCellHeight: 4,
                fontStyle: 'bold',
                fontSize: 7,
                cellPadding: 1
              } 
            }
          ],
          [
            {
              content: `Address: ${companyAddress}\nContact No: ${companyContact}`,
              rowSpan: 2,
              styles: { 
                halign: 'left', 
                valign: 'top',
                fontSize: 7, 
                cellPadding: 1,
                minCellHeight: 8
              }
            },
            { 
              content: `Date: ${quotationDate}`, 
              styles: { 
                minCellHeight: 4, 
                fontSize: 7,
                cellPadding: 1
              } 
            }
          ],
          [
            { 
              content: `Due Date: ${quotationDate}`, 
              styles: { 
                minCellHeight: 4, 
                fontSize: 7,
                cellPadding: 1
              } 
            }
          ],
          [
            {
              content: `GSTIN No: ${gst_no}`,
              styles: { 
                halign: 'left', 
                fontSize: 7, 
                cellPadding: 1,
                minCellHeight: 4
              }
            },
            { 
              content: `Order By: ${orderByPerson}` + (ordererContact ? ` (${ordererContact})` : ''), 
              styles: { 
                minCellHeight: 4, 
                fontSize: 7,
                cellPadding: 1
              } 
            }
          ]
        ],
        theme: 'grid',
        styles: {
          fontSize: 7,
          fontStyle: 'bold',
          cellPadding: 1,
          valign: 'middle',
          minCellHeight: 4
        },
        margin: { left: leftMargin, right: 14 },
        tableWidth: totalWidth,
        columnStyles: {
          0: { cellWidth: 100 },
          1: { cellWidth: 82 }
        }
      });

      return doc.lastAutoTable.finalY;
    }

    // Helper function to add footer and signature
    function addFooterAndSignature(doc, footerImg, signImg, companyName, notesLines, addTerms = true) {
      const pageHeight = doc.internal.pageSize.height;
      
      if (addTerms) {
        doc.setFontSize(8);
        doc.text("Terms & Conditions:", 15, pageHeight - 55);
        let terms = notesLines && notesLines.length > 0 ? notesLines : [
          "1. Transport & packing charges extra.",
          "2. GST 18% extra as per actual.",
          "3. Payment 100% Advance.",
          "4. Subject to AHMEDABAD Jurisdiction only. E.&O.E",
        ];
        terms.forEach((t, i) => {
          doc.text(t, 15, pageHeight - 45 + (i - 1) * 5);
        });
      }

      doc.text("For, " + (companyName || "COMPANY NAME").toUpperCase(), 145, pageHeight - 55);
      
      // Add Footer Image
      if (footerImg) {
        doc.addImage(footerImg, 2, pageHeight - 30, 210, 25);
      } else {
        doc.setFillColor(240, 240, 240);
        doc.rect(2, pageHeight - 30, 206, 25, 'F');
        doc.setFont("Helvetica", "bold");
        doc.setFontSize(14);
        doc.setTextColor(150, 150, 150);
        doc.text("Footer Image Placeholder (210x25)", 105, pageHeight - 15, { align: "center" });
        doc.setTextColor(0, 0, 0); // reset
      }

      // Add Signature Image
      if (signImg) {
        doc.addImage(signImg, 150, pageHeight - 53, 40, 12);
      } else {
        doc.setFillColor(240, 240, 240);
        doc.rect(150, pageHeight - 53, 40, 12, 'F');
        doc.setFont("Helvetica", "bold");
        doc.setFontSize(10);
        doc.setTextColor(150, 150, 150);
        doc.text("Signature Box", 170, pageHeight - 45, { align: "center" });
        doc.setTextColor(0, 0, 0); // reset
      }
    }

    // Generate PDF function (Format 1)
    async function generateFormat1PDF() {
      const { jsPDF } = window.jspdf;
      const doc = new jsPDF();

      // Ensure hidden inputs are updated from visible inputs
      syncHiddenFromTable();

      // Get selected instruments with qty & price
      const selectedInstruments = instrumentData
        .map((p, index) => {
          const checkbox = document.getElementById(`check-${index}`);
          if (checkbox && checkbox.checked) {
            const qty = parseFloat(document.getElementById(`qty-${index}`).value) || 0;
            const price = parseFloat(document.getElementById(`price-${index}`).value) || 0;
            return { ...p, qty, price };
          }
          return null;
        })
        .filter(p => p);

      if (selectedInstruments.length === 0) {
        QT.toastError("Please select at least one instrument with quantity.");
        return null;
      }

      // Convert product images to Base64
      const productImages = await Promise.all(selectedInstruments.map(p => toDataURL(p.image)));

      // Fetch header, footer, sign images
      const headerImg = userImages.header_image ? await toDataURL(userImages.header_image) : null;
      const footerImg = userImages.footer_image ? await toDataURL(userImages.footer_image) : null;
      const signImg = userImages.sign_image ? await toDataURL(userImages.sign_image) : null;

      // Fetch dynamic fields
      const rawNotes = document.getElementById('quotationNotes') ? document.getElementById('quotationNotes').value : '';
      const notesLines = rawNotes.split('\n').map(l => l.trim()).filter(l => l.length > 0);
      const userCompanyName = userCompany.company_name || "";

      // Get form values
      const select = document.getElementById('companySelect');
      const option = (select && select.selectedIndex >= 0) ? select.options[select.selectedIndex] : null;
      const companyData = {
        companyName: (option && option.dataset) ? (option.dataset.name || '') : '',
        companyAddress: document.getElementById('companyAddress') ? document.getElementById('companyAddress').value : '',
        companyContact: document.getElementById('companyContact') ? document.getElementById('companyContact').value : '',
        companyEmail: document.getElementById('companyEmail') ? document.getElementById('companyEmail').value : '',
        quotationNo: document.getElementById('quotationNo') ? document.getElementById('quotationNo').value : '',
        quotationDate: document.getElementById('quotationDate') ? document.getElementById('quotationDate').value : '',
        orderByPerson: document.getElementById('orderByPerson') ? document.getElementById('orderByPerson').value : '',
        ordererContact: document.getElementById('ordererContact') ? document.getElementById('ordererContact').value : '',
        gst_no: document.getElementById('gst_no') ? document.getElementById('gst_no').value : ''
      };

      const leftMargin = 14;
      const totalWidth = 182;
      const itemsPerPage = 5;

      // Calculate totals
      const grandTotal = selectedInstruments.reduce((acc, p) => acc + p.qty * p.price, 0);
      const gstInput = document.getElementById('gst_no');
      const hasGST = (gstInput && gstInput.value.trim() !== '');
      const gst = hasGST ? grandTotal * 0.18 : 0;
      const billAmount = grandTotal + gst;

      // Split products into pages
      const totalPages = Math.ceil(selectedInstruments.length / itemsPerPage);
      
      for (let pageNum = 0; pageNum < totalPages; pageNum++) {
        if (pageNum > 0) {
          doc.addPage();
        }

        // Add header and company table for each page
        const startY = addHeaderAndCompanyTable(doc, headerImg, companyData, pageNum === 0);

        // Get products for this page
        const startIdx = pageNum * itemsPerPage;
        const endIdx = Math.min(startIdx + itemsPerPage, selectedInstruments.length);
        const pageProducts = selectedInstruments.slice(startIdx, endIdx);
        const pageProductImages = productImages.slice(startIdx, endIdx);

        const tableColumn = ["Sr.no", "Image", "Product", "HSN", "Qty", "Price", "Total"];
        const tableRows = pageProducts.map((p, index) => [
          startIdx + index + 1,
          "",
          p.description ? `${p.name}\n(${p.description})` : p.name,
          p.hsn || "-",
          p.qty,
          p.price.toFixed(2),
          (p.qty * p.price).toFixed(2),
        ]);

        // Add totals only on the last page
        const isLastPage = pageNum === totalPages - 1;
        if (isLastPage) {
          tableRows.push(
            [
              { content: "Sub Total", colSpan: 6, styles: { halign: "right", fontStyle: "bold", minCellHeight: 10 } },
              { content: grandTotal.toFixed(2), styles: { minCellHeight: 10 } }
            ]
          );
          if (hasGST) {
            tableRows.push(
              [
                { content: "GST @18%", colSpan: 6, styles: { halign: "right", fontStyle: "bold", minCellHeight: 10 } },
                { content: gst.toFixed(2), styles: { minCellHeight: 10 } }
              ]
            );
          }
          tableRows.push(
            [
              { content: hasGST ? "Bill Amount" : "Total", colSpan: 6, styles: { halign: "right", fontStyle: "bold", minCellHeight: 10, fillColor: [240, 240, 240] } },
              { content: billAmount.toFixed(2), styles: { fontStyle: "bold", minCellHeight: 10, fillColor: [240, 240, 240] } }
            ]
          );
        }

        // Products Table
        doc.autoTable({
          startY: startY,
          head: [tableColumn],
          body: tableRows,
          theme: "grid",
          margin: { left: leftMargin, right: 14 },
          tableWidth: totalWidth,
          styles: { fontSize: 8, fontStyle: 'bold', cellWidth: "wrap", minCellHeight: 25 },
          headStyles: { 
            fillColor: [255, 255, 255],
            textColor: [0, 0, 0],
            lineWidth: 0.1,
            lineColor: [128, 128, 128],
            minCellHeight: 10
          },
          columnStyles: {
            0: { cellWidth: 12 },
            1: { cellWidth: 20 },
            2: { cellWidth: 62 },
            3: { cellWidth: 18 },
            4: { cellWidth: 15 },
            5: { cellWidth: 27 },
            6: { cellWidth: 28 }
          },
          didDrawCell: function (data) {
            if (data.column.index === 1 && data.cell.section === "body" && data.row.index < pageProducts.length) {
              const imgData = pageProductImages[data.row.index];
              if (imgData) {
                doc.addImage(imgData, "JPEG", data.cell.x + 2, data.cell.y + 2, 15, 15);
              }
            }
          }
        });

        // Add footer, signature and terms only on last page
        addFooterAndSignature(doc, footerImg, signImg, userCompanyName, notesLines, isLastPage);
      }

      return doc;
    }

    // Generate PDF function (Format 2)
    async function generateFormat2PDF() {
      const { jsPDF } = window.jspdf;
      const doc = new jsPDF();

      // Ensure hidden inputs are updated from visible inputs
      syncHiddenFromTable();

      // Get selected instruments with qty & price
      const selectedInstruments = instrumentData
        .map((p, index) => {
          const checkbox = document.getElementById(`check-${index}`);
          if (checkbox && checkbox.checked) {
            const qty = parseFloat(document.getElementById(`qty-${index}`).value) || 0;
            const price = parseFloat(document.getElementById(`price-${index}`).value) || 0;
            return { ...p, qty, price };
          }
          return null;
        })
        .filter(p => p);

      if (selectedInstruments.length === 0) {
        QT.toastError("Please select at least one instrument with quantity.");
        return null;
      }

      // Convert images to base64
      const productImages = await Promise.all(selectedInstruments.map(p => toDataURL(p.image)));
      const qrImg = userImages.payment_qr_image ? await toDataURL(userImages.payment_qr_image) : null;
      const signImg = userImages.sign_image ? await toDataURL(userImages.sign_image) : null;

      // Get form & Format 2 field values
      const select = document.getElementById('companySelect');
      const option = (select && select.selectedIndex >= 0) ? select.options[select.selectedIndex] : null;
      
      const clientName = document.getElementById('orderByPerson') ? document.getElementById('orderByPerson').value : '';
      const clientCompany = (option && option.dataset) ? (option.dataset.name || '- N/A') : '- N/A';
      const clientAddress = document.getElementById('companyAddress') ? document.getElementById('companyAddress').value : '';
      const clientContact = document.getElementById('companyContact') ? document.getElementById('companyContact').value : '';
      const clientEmail = document.getElementById('companyEmail') ? document.getElementById('companyEmail').value : '';
      const ordererContact = document.getElementById('ordererContact') ? document.getElementById('ordererContact').value : '';

      const quotationNo = document.getElementById('quotationNo') ? document.getElementById('quotationNo').value : '';
      const quotationDateStr = document.getElementById('quotationDate') ? document.getElementById('quotationDate').value : '';
      
      let formattedDate = quotationDateStr;
      try {
        if (quotationDateStr) {
          const d = new Date(quotationDateStr);
          const months = ["JAN", "FEB", "MAR", "APR", "MAY", "JUN", "JUL", "AUG", "SEP", "OCT", "NOV", "DEC"];
          const day = String(d.getDate()).padStart(2, '0');
          const month = months[d.getMonth()];
          const year = d.getFullYear();
          formattedDate = `${day}-${month}-${year}`;
        }
      } catch (e) {}

      const expirationVal = document.getElementById('expirationDate') ? document.getElementById('expirationDate').value : '1 Days';
      const paymentDueDateVal = document.getElementById('paymentDueDate') ? document.getElementById('paymentDueDate').value : '';
      const paymentTermsVal = document.getElementById('paymentTerms') ? document.getElementById('paymentTerms').value : '';
      
      const rawNotes = document.getElementById('quotationNotes') ? document.getElementById('quotationNotes').value : '';
      const notesLines = rawNotes.split('\n').map(l => l.trim()).filter(l => l.length > 0);

      // Bank Details
      const bankHolder = document.getElementById('accHolder') ? document.getElementById('accHolder').value : '';
      const bankNameVal = document.getElementById('bankName') ? document.getElementById('bankName').value : '';
      const bankAccNo = document.getElementById('accNo') ? document.getElementById('accNo').value : '';
      const bankIfsc = document.getElementById('ifscCode') ? document.getElementById('ifscCode').value : '';

      // Dimensions & Math
      const leftMargin = 14;
      const totalWidth = 182;
      const pageHeight = doc.internal.pageSize.height;
      const pageWidth = doc.internal.pageSize.width;

      const subTotal = selectedInstruments.reduce((acc, p) => acc + p.qty * p.price, 0);

      // --- PAGE 1 DRAWING ---

      // Top & Bottom page boundary solid bars
      doc.setFillColor(147, 197, 253);
      doc.rect(0, 0, 210, 5, 'F');
      doc.rect(0, 292, 210, 5, 'F');

      // 1. Blue Header Quote Bar (Solid style)
      doc.setFillColor(210, 228, 242);
      doc.rect(14, 15, 182, 12, 'F');
      
      doc.setFont("Helvetica", "bold");
      doc.setFontSize(16);
      doc.setTextColor(0, 0, 0);
      doc.text("QUOTE", pageWidth / 2, 23, { align: "center" });

      // 2. Vendor Company Details
      doc.setFont("Helvetica", "bold");
      doc.setFontSize(10);
      doc.text(`Company Name: ${userCompany.company_name || 'My Company'}`, 14, 34);
      
      doc.setFont("Helvetica", "bold");
      doc.text(currentUserName || '', 14, 39);
      
      doc.setFontSize(8);
      doc.setTextColor(80, 80, 80);
      doc.text(`Street Address: ${userCompany.company_address || ''}`, 14, 44);
      if (userCompany.gstin_number) {
        doc.text(`GSTIN No: ${userCompany.gstin_number}`, 14, 48);
      }
      
      // Contact & Email
      doc.text(`Phone : ${userCompany.party_contact || ''}`, 14, 52); 
      doc.text(`Email: ${userEmail || ''}`, 14, 56);

      // 3. Invoice Metadata (Right aligned)
      doc.setFont("Helvetica", "bold");
      doc.setFontSize(8);
      doc.setTextColor(0, 0, 0);
      doc.text(`INVOICE # ${quotationNo}`, 196, 34, { align: "right" });
      doc.text(`DATE: ${formattedDate}`, 196, 39, { align: "right" });
      
      doc.setFont("Helvetica", "bold");
      doc.text(`EXPIRATION DATE : ${expirationVal}`, 196, 47, { align: "right" });

      // 4. Client Info "TO" Block
      doc.setFont("Helvetica", "bold");
      doc.setFontSize(10);
      doc.text("TO", 14, 66);
      
      doc.setFont("Helvetica", "bold");
      doc.setFontSize(9);
      doc.text(`Contact Name: ${clientName}` + (ordererContact ? ` (${ordererContact})` : ''), 24, 66);
      doc.text(`Company Name: ${clientCompany}`, 24, 71);
      doc.text(`Street Address: ${clientAddress}`, 24, 76);
      doc.text(`Mobile No: ${clientContact}`, 24, 81);
      doc.text(`Email ID: ${clientEmail}`, 24, 86);

      // 5. Products Table
      const tableColumn = ["SR NO", "IMAGE", "DESCRIPTION", "PRICE", "LINE TOTAL"];
      const tableRows = selectedInstruments.map((p, index) => [
        index + 1,
        "",
        p.description ? `${p.name}\n${p.description}` : p.name,
        `${p.price.toFixed(0)} X ${p.qty}`,
        (p.qty * p.price).toLocaleString('en-IN')
      ]);

      // Add Subtotal and Total to bottom of table rows
      const gstInput = document.getElementById('gst_no');
      const hasGST = (gstInput && gstInput.value.trim() !== '');
      const gst = hasGST ? subTotal * 0.18 : 0;
      const billAmount = subTotal + gst;

      tableRows.push(
        [
          { content: "SUBTOTAL", colSpan: 4, styles: { halign: "right", fontStyle: "bold", minCellHeight: 8 } },
          { content: subTotal.toLocaleString('en-IN', {minimumFractionDigits: 2}), styles: { fontStyle: "bold" } }
        ]
      );
      if (hasGST) {
        tableRows.push(
          [
            { content: "GST @18%", colSpan: 4, styles: { halign: "right", fontStyle: "bold", minCellHeight: 8 } },
            { content: gst.toLocaleString('en-IN', {minimumFractionDigits: 2}), styles: { fontStyle: "bold" } }
          ]
        );
      }
      tableRows.push(
        [
          { content: hasGST ? "BILL AMOUNT" : "TOTAL", colSpan: 4, styles: { halign: "right", fontStyle: "bold", minCellHeight: 8 } },
          { content: billAmount.toLocaleString('en-IN', {minimumFractionDigits: 2}), styles: { fontStyle: "bold", fillColor: [240, 248, 255] } }
        ]
      );

      let lastY = 93;
      doc.autoTable({
        startY: lastY,
        head: [tableColumn],
        body: tableRows,
        theme: "grid",
        margin: { left: leftMargin, right: 14 },
        tableWidth: totalWidth,
        styles: { fontSize: 8.5, fontStyle: 'bold', cellPadding: 3, minCellHeight: 16, valign: "middle" },
        headStyles: {
          fillColor: [210, 228, 242], // Light blue header style matching Quote bar
          textColor: [0, 0, 0],
          fontStyle: "bold",
          lineWidth: 0.1,
          lineColor: [128, 128, 128]
        },
        columnStyles: {
          0: { cellWidth: 15, halign: "center" },
          1: { cellWidth: 20 },
          2: { cellWidth: 87 },
          3: { cellWidth: 30, halign: "center" },
          4: { cellWidth: 30, halign: "right" }
        },
        didDrawCell: function (data) {
          if (data.column.index === 1 && data.cell.section === "body" && data.row.index < selectedInstruments.length) {
            const imgData = productImages[data.row.index];
            if (imgData) {
              doc.addImage(imgData, data.cell.x + 2, data.cell.y + 2, 12, 12);
            }
          }
        }
      });

      lastY = doc.lastAutoTable.finalY + 8;

      // 6. Payment Terms Table
      const ptColumn = ["PAYMENT TERMS", "DUE DATE"];
      const ptRows = [
        [
          paymentTermsVal,
          paymentDueDateVal
        ]
      ];

      doc.autoTable({
        startY: lastY,
        head: [ptColumn],
        body: ptRows,
        theme: "grid",
        margin: { left: leftMargin, right: 14 },
        tableWidth: totalWidth,
        styles: { fontSize: 8, cellPadding: 3, valign: "middle" },
        headStyles: {
          fillColor: [210, 228, 242],
          textColor: [0, 0, 0],
          fontStyle: "bold",
          lineWidth: 0.1,
          lineColor: [128, 128, 128]
        },
        columnStyles: {
          0: { cellWidth: 137 },
          1: { cellWidth: 45, halign: "center", fontStyle: "bold" }
        }
      });

      lastY = doc.lastAutoTable.finalY + 8;

      // 7. Notes List
      if (notesLines.length > 0) {
        doc.setFont("Helvetica", "bold");
        doc.setFontSize(9);
        doc.setTextColor(0, 0, 0);
        doc.text("Notes:", 14, lastY);
        lastY += 5;

        doc.setFont("Helvetica", "bold");
        doc.setFontSize(8);
        notesLines.forEach((note) => {
          const splitNote = doc.splitTextToSize(note, totalWidth - 6);
          doc.text(splitNote, 17, lastY);
          lastY += (splitNote.length * 4);
        });
      }

      lastY += 3;

      // 8. Payment Details & QR Code side-by-side
      doc.setFont("Helvetica", "bold");
      doc.setFontSize(9);
      doc.text("Payment Details:", 14, lastY);
      lastY += 5;

      const detailsStartY = lastY;

      // Bank details on the left
      doc.setFont("Helvetica", "bold");
      doc.setFontSize(8.5);
      doc.text("Bank Details", 24, lastY);
      lastY += 5;
      
      doc.setFont("Helvetica", "bold");
      doc.setFontSize(8);
      doc.text(`Name: ${bankHolder}`, 24, lastY);
      lastY += 4;
      doc.text(`Bank: ${bankNameVal}`, 24, lastY);
      lastY += 4;
      doc.text(`Account No: ${bankAccNo}`, 24, lastY);
      lastY += 4;
      doc.text(`IFSC Code: ${bankIfsc}`, 24, lastY);
      lastY += 4;

      // QR Code image on the right (matching y-level)
      if (qrImg) {
        try {
          doc.addImage(qrImg, "JPEG", 135, detailsStartY, 32, 32);
          doc.setFont("Helvetica", "bold");
          doc.setFontSize(6.5);
          doc.setTextColor(100, 100, 100);
          doc.text("Scan & Pay Using UPI App", 151, detailsStartY + 35, { align: "center" });
        } catch (e) {
          console.error("Error drawing QR image", e);
        }
      }

      // 9. Signatures and prepared by
      let sigY = Math.max(lastY, detailsStartY + 38) + 8;
      
      doc.setFont("Helvetica", "bold");
      doc.setFontSize(8.5);
      doc.setTextColor(0, 0, 0);
      doc.text(`Quotation prepared by: ${currentUserName} - ${userCompany.company_name}`, 14, sigY);
      
      sigY += 5;
      doc.setFontSize(7.5);
      doc.setTextColor(120, 120, 120);
      const footerLegal = "This is a quotation on the goods named, subject to the conditions noted below: Describe any conditions pertaining to these prices and any additional terms of the agreement. You may want to include contingencies that will affect the quotation.";
      const splitLegal = doc.splitTextToSize(footerLegal, totalWidth);
      doc.text(splitLegal, 14, sigY);
      
      sigY += (splitLegal.length * 4) + 4;
      doc.setTextColor(0, 0, 0);
      doc.text("To accept this quotation, sign here and return: __________________________________________________________________________", 14, sigY);

      // --- PAGE 2 DRAWING (HIGHLIGHTS) ---
      const includeHighlightsEl = document.getElementById('includeHighlights');
      const includeHighlightsVal = includeHighlightsEl ? includeHighlightsEl.checked : false;
      if (includeHighlightsVal) {
        doc.addPage();
        
        // Page 2 Top & Bottom boundary solid bars
        doc.setFillColor(147, 197, 253);
        doc.rect(0, 0, 210, 5, 'F');
        doc.rect(0, 292, 210, 5, 'F');

        // 1. Header Solid Bar
        doc.setFillColor(210, 228, 242);
        doc.rect(14, 20, 182, 14, 'F');
        
        const hTitle = document.getElementById('highlightsTitle') ? document.getElementById('highlightsTitle').value : 'Additional Free Services Included (Highlights)';
        doc.setFont("Helvetica", "bold");
        doc.setFontSize(12);
        doc.text(hTitle, pageWidth / 2, 29, { align: "center" });

        // 2. Highlights Bullet points
        const rawHighlights = document.getElementById('highlightsText') ? document.getElementById('highlightsText').value : '';
        const hLines = rawHighlights.split('\n').map(l => l.trim()).filter(l => l.length > 0);

        let hY = 48;
        doc.setFont("Helvetica", "bold");
        doc.setFontSize(9.5);
        hLines.forEach((line) => {
          const splitLine = doc.splitTextToSize(line, totalWidth - 6);
          doc.text(splitLine, 18, hY);
          hY += (splitLine.length * 6) + 4;
        });

        // 3. Thank you business bar at the bottom (Solid)
        doc.setFillColor(210, 228, 242);
        doc.rect(14, pageHeight - 35, 182, 12, 'F');
        
        doc.setFont("Helvetica", "bold");
        doc.setFontSize(12);
        doc.text("Thank you for your business!", pageWidth / 2, pageHeight - 27, { align: "center" });
      }

      return doc;
    }

    // Format 3 — replicates the exact "QUOTE" layout/colors/section order of the
    // approved reference sample: SR NO / DESCRIPTION / PRICE / LINE TOTAL table
    // (a small product thumbnail is inset into the DESCRIPTION cell rather than
    // a separate column, to keep the sample's exact 4-column look), Payment
    // Terms/Due Date box, Notes, Payment Details + bank info + QR, signature
    // lines, and an optional Highlights second page. Reuses the same Format 2
    // input fields (expiration/due date/payment terms/notes/highlights/bank) —
    // only the drawing/layout differs from generateFormat2PDF().
    async function generateFormat3PDF() {
      const { jsPDF } = window.jspdf;
      const doc = new jsPDF();

      syncHiddenFromTable();

      const selectedInstruments = instrumentData
        .map((p, index) => {
          const checkbox = document.getElementById(`check-${index}`);
          if (checkbox && checkbox.checked) {
            const qty = parseFloat(document.getElementById(`qty-${index}`).value) || 0;
            const price = parseFloat(document.getElementById(`price-${index}`).value) || 0;
            return { ...p, qty, price };
          }
          return null;
        })
        .filter(p => p);

      if (selectedInstruments.length === 0) {
        QT.toastError("Please select at least one instrument with quantity.");
        return null;
      }

      const qrImg = userImages.payment_qr_image ? await toDataURL(userImages.payment_qr_image) : null;

      const select = document.getElementById('companySelect');
      const option = (select && select.selectedIndex >= 0) ? select.options[select.selectedIndex] : null;

      const clientName = document.getElementById('orderByPerson') ? document.getElementById('orderByPerson').value : '';
      const clientCompany = (option && option.dataset) ? (option.dataset.name || '- N/A') : '- N/A';
      const clientAddress = document.getElementById('companyAddress') ? document.getElementById('companyAddress').value : '';
      const clientContact = document.getElementById('companyContact') ? document.getElementById('companyContact').value : '';
      const clientEmail = document.getElementById('companyEmail') ? document.getElementById('companyEmail').value : '';

      const quotationNo = document.getElementById('quotationNo') ? document.getElementById('quotationNo').value : '';
      const quotationDateStr = document.getElementById('quotationDate') ? document.getElementById('quotationDate').value : '';

      let formattedDate = quotationDateStr;
      try {
        if (quotationDateStr) {
          const d = new Date(quotationDateStr);
          const months = ["JAN", "FEB", "MAR", "APR", "MAY", "JUN", "JUL", "AUG", "SEP", "OCT", "NOV", "DEC"];
          const day = String(d.getDate()).padStart(2, '0');
          formattedDate = `${day}-${months[d.getMonth()]}-${d.getFullYear()}`;
        }
      } catch (e) {}

      const expirationVal = document.getElementById('expirationDate') ? document.getElementById('expirationDate').value : '1 Days';
      const paymentDueDateVal = document.getElementById('paymentDueDate') ? document.getElementById('paymentDueDate').value : '';
      const paymentTermsVal = document.getElementById('paymentTerms') ? document.getElementById('paymentTerms').value : '';

      const rawNotes = document.getElementById('quotationNotes') ? document.getElementById('quotationNotes').value : '';
      const notesLines = rawNotes.split('\n').map(l => l.trim()).filter(l => l.length > 0);

      const bankHolder = document.getElementById('accHolder') ? document.getElementById('accHolder').value : '';
      const bankNameVal = document.getElementById('bankName') ? document.getElementById('bankName').value : '';
      const bankAccNo = document.getElementById('accNo') ? document.getElementById('accNo').value : '';
      const bankIfsc = document.getElementById('ifscCode') ? document.getElementById('ifscCode').value : '';

      const leftMargin = 14;
      const totalWidth = 182;
      const pageHeight = doc.internal.pageSize.height;
      const pageWidth = doc.internal.pageSize.width;

      const subTotal = selectedInstruments.reduce((acc, p) => acc + p.qty * p.price, 0);

      // --- PAGE 1 ---
      drawLinearGradient(doc, 14, 15, 182, 12, 191, 219, 254, 239, 246, 255);
      doc.setFont("Helvetica", "bold");
      doc.setFontSize(16);
      doc.setTextColor(0, 0, 0);
      doc.text("QUOTE", pageWidth / 2, 23, { align: "center" });

      doc.setFont("Helvetica", "bold");
      doc.setFontSize(10);
      doc.text(`Company Name: ${userCompany.company_name || 'My Company'}`, 14, 34);
      doc.setFont("Helvetica", "bold");
      doc.text(currentUserName || '', 14, 39);
      doc.setFontSize(8);
      doc.setTextColor(80, 80, 80);
      doc.text(`Street Address: ${userCompany.company_address || ''}`, 14, 44);
      if (userCompany.gstin_number) {
        doc.text(`GSTIN No: ${userCompany.gstin_number}`, 14, 48);
      }
      doc.text(`Email: ${userEmail || ''}`, 14, 52);

      doc.setFont("Helvetica", "bold");
      doc.setFontSize(8);
      doc.setTextColor(0, 0, 0);
      doc.text(`INVOICE # ${quotationNo}`, 196, 34, { align: "right" });
      doc.text(`DATE: ${formattedDate}`, 196, 39, { align: "right" });
      doc.setFont("Helvetica", "bold");
      doc.text(`EXPIRATION DATE : ${expirationVal}`, 196, 47, { align: "right" });

      doc.setFont("Helvetica", "bold");
      doc.setFontSize(10);
      doc.setTextColor(0, 0, 0);
      doc.text("TO", 14, 66);
      doc.setFont("Helvetica", "bold");
      doc.setFontSize(9);
      doc.text(`Contact Name: ${clientName}` + (ordererContact ? ` (${ordererContact})` : ''), 24, 66);
      doc.text(`Company Name: ${clientCompany}`, 24, 71);
      doc.text(`Street Address: ${clientAddress}`, 24, 76);
      doc.text(`Mobile No: ${clientContact}`, 24, 81);
      doc.text(`Email ID: ${clientEmail}`, 24, 86);

      // Products table — SR NO / DESCRIPTION / PRICE / LINE TOTAL (4 columns,
      // matching the reference sample exactly — no product image column at all).
      const tableColumn = ["SR NO", "DESCRIPTION", "PRICE", "LINE TOTAL"];
      const tableRows = selectedInstruments.map((p, index) => [
        index + 1,
        p.description ? `${p.name}\n${p.description}` : p.name,
        `${p.price.toFixed(0)} X ${p.qty}`,
        (p.qty * p.price).toLocaleString('en-IN')
      ]);

      const gstInput = document.getElementById('gst_no');
      const hasGST = (gstInput && gstInput.value.trim() !== '');
      const gst = hasGST ? subTotal * 0.18 : 0;
      const billAmount = subTotal + gst;

      tableRows.push(
        [
          { content: "SUBTOTAL", colSpan: 3, styles: { halign: "right", fontStyle: "bold", minCellHeight: 8 } },
          { content: subTotal.toLocaleString('en-IN', {minimumFractionDigits: 2}), styles: { fontStyle: "bold" } }
        ]
      );
      if (hasGST) {
        tableRows.push(
          [
            { content: "GST @18%", colSpan: 3, styles: { halign: "right", fontStyle: "bold", minCellHeight: 8 } },
            { content: gst.toLocaleString('en-IN', {minimumFractionDigits: 2}), styles: { fontStyle: "bold" } }
          ]
        );
      }
      tableRows.push(
        [
          { content: hasGST ? "BILL AMOUNT" : "TOTAL", colSpan: 3, styles: { halign: "right", fontStyle: "bold", minCellHeight: 8 } },
          { content: billAmount.toLocaleString('en-IN', {minimumFractionDigits: 2}), styles: { fontStyle: "bold", fillColor: [240, 248, 255] } }
        ]
      );

      let lastY = 93;
      doc.autoTable({
        startY: lastY,
        head: [tableColumn],
        body: tableRows,
        theme: "grid",
        margin: { left: leftMargin, right: 14 },
        tableWidth: totalWidth,
        styles: { fontSize: 8.5, fontStyle: 'bold', cellPadding: 3, minCellHeight: 16, valign: "middle" },
        headStyles: {
          fillColor: [210, 228, 242],
          textColor: [0, 0, 0],
          fontStyle: "bold",
          lineWidth: 0.1,
          lineColor: [128, 128, 128]
        },
        columnStyles: {
          0: { cellWidth: 15, halign: "center" },
          1: { cellWidth: 107 },
          2: { cellWidth: 30, halign: "center" },
          3: { cellWidth: 30, halign: "right" }
        }
      });

      lastY = doc.lastAutoTable.finalY + 8;

      // Payment Terms / Due Date
      doc.autoTable({
        startY: lastY,
        head: [["PAYMENT TERMS", "DUE DATE"]],
        body: [[paymentTermsVal, paymentDueDateVal]],
        theme: "grid",
        margin: { left: leftMargin, right: 14 },
        tableWidth: totalWidth,
        styles: { fontSize: 8, cellPadding: 3, valign: "middle" },
        headStyles: {
          fillColor: [210, 228, 242],
          textColor: [0, 0, 0],
          fontStyle: "bold",
          lineWidth: 0.1,
          lineColor: [128, 128, 128]
        },
        columnStyles: {
          0: { cellWidth: 137 },
          1: { cellWidth: 45, halign: "center", fontStyle: "bold" }
        }
      });

      lastY = doc.lastAutoTable.finalY + 8;

      // Notes — normalized to a plain "•" bullet regardless of what marker (if
      // any) the user typed, since jsPDF's core fonts can't render pictographic
      // emoji reliably (verified against the reference sample's Highlights page,
      // where several emoji icons failed to render at all).
      if (notesLines.length > 0) {
        doc.setFont("Helvetica", "bold");
        doc.setFontSize(9);
        doc.setTextColor(0, 0, 0);
        doc.text("Notes:", 14, lastY);
        lastY += 5;
        doc.setFont("Helvetica", "bold");
        doc.setFontSize(8);
        notesLines.forEach((note) => {
          const bulleted = "• " + note.replace(/^[-*•]\s*/, '');
          const splitNote = doc.splitTextToSize(bulleted, totalWidth - 6);
          doc.text(splitNote, 17, lastY);
          lastY += (splitNote.length * 4);
        });
      }

      lastY += 3;

      // Payment Details + QR
      doc.setFont("Helvetica", "bold");
      doc.setFontSize(9);
      doc.text("Payment Details:", 14, lastY);
      lastY += 5;

      const detailsStartY = lastY;

      doc.setFont("Helvetica", "bold");
      doc.setFontSize(8.5);
      doc.text("Bank Details", 24, lastY);
      lastY += 5;
      doc.setFont("Helvetica", "bold");
      doc.setFontSize(8);
      doc.text(`Name: ${bankHolder}`, 24, lastY); lastY += 4;
      doc.text(`Bank: ${bankNameVal}`, 24, lastY); lastY += 4;
      doc.text(`Account No: ${bankAccNo}`, 24, lastY); lastY += 4;
      doc.text(`IFSC Code: ${bankIfsc}`, 24, lastY); lastY += 4;

      if (qrImg) {
        try {
          doc.addImage(qrImg, "JPEG", 135, detailsStartY, 32, 32);
          doc.setFont("Helvetica", "bold");
          doc.setFontSize(6.5);
          doc.setTextColor(100, 100, 100);
          doc.text("Scan & Pay Using UPI App", 151, detailsStartY + 35, { align: "center" });
        } catch (e) {
          console.error("Error drawing QR image", e);
        }
      }

      let sigY = Math.max(lastY, detailsStartY + 38) + 8;
      doc.setFont("Helvetica", "bold");
      doc.setFontSize(8.5);
      doc.setTextColor(0, 0, 0);
      doc.text(`Quotation prepared by: ${currentUserName} - ${userCompany.company_name}`, 14, sigY);

      sigY += 5;
      doc.setFontSize(7.5);
      doc.setTextColor(120, 120, 120);
      const footerLegal = "This is a quotation on the goods named, subject to the conditions noted below: Describe any conditions pertaining to these prices and any additional terms of the agreement. You may want to include contingencies that will affect the quotation.";
      const splitLegal = doc.splitTextToSize(footerLegal, totalWidth);
      doc.text(splitLegal, 14, sigY);

      sigY += (splitLegal.length * 4) + 4;
      doc.setTextColor(0, 0, 0);
      doc.text("To accept this quotation, sign here and return: __________________________________________________________________________", 14, sigY);

      // --- PAGE 2 (Highlights) ---
      const includeHighlightsEl = document.getElementById('includeHighlights');
      const includeHighlightsVal = includeHighlightsEl ? includeHighlightsEl.checked : false;
      if (includeHighlightsVal) {
        doc.addPage();
        drawLinearGradient(doc, 14, 20, 182, 14, 191, 219, 254, 239, 246, 255);

        const hTitle = document.getElementById('highlightsTitle') ? document.getElementById('highlightsTitle').value : 'Additional Free Services Included (Highlights)';
        doc.setFont("Helvetica", "bold");
        doc.setFontSize(12);
        doc.setTextColor(0, 0, 0);
        doc.text(hTitle, pageWidth / 2, 29, { align: "center" });

        const rawHighlights = document.getElementById('highlightsText') ? document.getElementById('highlightsText').value : '';
        const hLines = rawHighlights.split('\n').map(l => l.trim()).filter(l => l.length > 0);

        let hY = 48;
        doc.setFont("Helvetica", "bold");
        doc.setFontSize(9.5);
        hLines.forEach((line) => {
          const bulleted = "• " + line.replace(/^[-*•]\s*/, '');
          const splitLine = doc.splitTextToSize(bulleted, totalWidth - 6);
          doc.text(splitLine, 18, hY);
          hY += (splitLine.length * 6) + 4;
        });

        drawLinearGradient(doc, 14, pageHeight - 35, 182, 12, 191, 219, 254, 239, 246, 255);
        doc.setFont("Helvetica", "bold");
        doc.setFontSize(12);
        doc.text("Thank you for your business!", pageWidth / 2, pageHeight - 27, { align: "center" });
      }

      return doc;
    }

    async function generatePDF() {
      const formatEl = document.getElementById('quotationFormat');
      const format = formatEl ? formatEl.value : 'format1';
      if (format === 'format3') {
        return await generateFormat3PDF();
      } else if (format === 'format2') {
        return await generateFormat2PDF();
      } else {
        return await generateFormat1PDF();
      }
    }

    function preview() {
      // Standard new tab preview (fallback)
      const pdfWindow = window.open("", "_blank");
      if (pdfWindow) {
        pdfWindow.document.write("<p style='font-family:sans-serif;text-align:center;margin-top:20%;color:#2dd4bf;'>Generating preview... Please wait.</p>");
      }
      
      generatePDF().then((doc) => {
        if (doc) {
          if (pdfWindow) {
            pdfWindow.location.href = doc.output("bloburl");
          } else {
            window.open(doc.output("bloburl"));
          }
        } else {
          if (pdfWindow) pdfWindow.close();
        }
      }).catch((err) => {
        console.error(err);
        if (pdfWindow) pdfWindow.close();
      });
    }

    function previewSameTab() {
      const previewModal = document.getElementById('pdfPreviewModal');
      const iframe = document.getElementById('pdfPreviewFrame');
      
      iframe.src = '';
      previewModal.style.display = 'block';
      
      generatePDF().then((doc) => {
        if (doc) {
          iframe.src = doc.output('bloburl');
        } else {
          closePdfPreviewModal();
        }
      }).catch((err) => {
        console.error(err);
        QT.toastError("Failed to generate preview.");
        closePdfPreviewModal();
      });
    }

    function closePdfPreviewModal() {
      document.getElementById('pdfPreviewModal').style.display = 'none';
      document.getElementById('pdfPreviewFrame').src = '';
    }

    function printPDF() {
      QT.toastInfo("Generating printable PDF...");
      generatePDF().then((doc) => {
        if (doc) {
          doc.autoPrint();
          const blobUrl = doc.output('bloburl');
          const iframe = document.createElement('iframe');
          iframe.style.display = 'none';
          iframe.src = blobUrl;
          document.body.appendChild(iframe);
          iframe.onload = function() {
            setTimeout(function() {
              iframe.focus();
              iframe.contentWindow.print();
            }, 100);
          };
        }
      });
    }

    function savePDF() {
      generatePDF().then((doc) => {
        if (doc) {
          doc.save("quotation.pdf");
        }
      });
    }

    function shareWhatsApp() {
      const select = document.getElementById('companySelect');
      const option = select.options[select.selectedIndex];
      const companyName = option ? (option.dataset.name || '') : '';
      const quotationNo = document.getElementById('quotationNo').value.trim();
      const contactVal = document.getElementById('companyContact').value.trim();

      if (!companyName) { QT.toastError('Please select a company first.'); return; }
      if (!quotationNo) { QT.toastError('Please enter or generate a quotation number first.'); return; }
      
      let phone = contactVal.replace(/[^0-9]/g, '');
      if (phone.length === 10) {
        phone = '91' + phone; // Default to India country code if 10 digits
      }
      
      if (!phone) {
        QT.toastError('Please provide a valid contact number for WhatsApp sharing.');
        return;
      }

      // Open blank window synchronously first to bypass popup blocker
      const whatsappWindow = window.open("", "_blank");
      if (whatsappWindow) {
        whatsappWindow.document.write("<p style='font-family:sans-serif;text-align:center;margin-top:20%;color:#2dd4bf;'>Generating quotation PDF... Please wait.</p>");
      }

      generatePDF().then((doc) => {
        if (doc) {
          doc.save(`quotation_${quotationNo}.pdf`);
          
          const text = `Dear Client,\n\nYour quotation *${quotationNo}* is ready. Please find the attached PDF.\n\nThank you!`;
          const whatsappUrl = `https://wa.me/${phone}?text=${encodeURIComponent(text)}`;
          
          if (whatsappWindow) {
            whatsappWindow.location.href = whatsappUrl;
          } else {
            window.open(whatsappUrl, '_blank');
          }
        } else {
          if (whatsappWindow) whatsappWindow.close();
        }
      }).catch((err) => {
        console.error("PDF generation error:", err);
        if (whatsappWindow) whatsappWindow.close();
        QT.toastError("Failed to generate PDF quotation.");
      });
    }

    window.onload = function() {
      initHiddenSyncFields();
      
      const today = new Date().toISOString().split('T')[0];
      if (!getParam('qno')) {
        document.getElementById('quotationDate').value = today;
        fetchNextQuotationNo();
        
        // Populate 5 blank rows initially
        for (let i = 0; i < 5; i++) {
          addInvoiceRow();
        }
      } else {
        loadQuotationIfAny();
      }
    };

    async function fetchNextQuotationNo() {
      try {
        const res = await fetch('home.php?action=get_next_quotation_no');
        const data = await res.json();
        if (res.ok && data.next_quotation_no) {
          const qnoInput = document.getElementById('quotationNo');
          qnoInput.value = data.next_quotation_no;
          qnoInput.setAttribute('readonly', 'readonly');
        }
      } catch (e) {
        console.error('Failed to fetch next quotation number', e);
      }
    }


  </script>
</body>
</html>