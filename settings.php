<?php
require_once __DIR__ . '/config.php';

// Require auth
require_auth();

$user_id = $_SESSION['user_id'];
$con = db_connect();

// Handle AJAX for setting format preference
if (isset($_GET['action']) && $_GET['action'] === 'set_format') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (isset($data['format']) && in_array($data['format'], ['format1', 'format2', 'format3', 'old', 'new'])) {
        // Map legacy values if needed
        $format = $data['format'];
        if ($format === 'old') $format = 'format1';
        if ($format === 'new') $format = 'format2';

        $stmt = $con->prepare("UPDATE users SET format_preference = ? WHERE user_id = ?");
        $stmt->bind_param("si", $format, $user_id);
        $stmt->execute();
        $stmt->close();
        $con->close();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit();
    }
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['error' => 'Invalid format']);
    exit();
}

$cloud_name = "div48nrko";
$upload_preset = "quatation_managment";

$successMessage = '';
if (isset($_GET['upgrade']) && $_GET['upgrade'] == 1) {
    $successMessage = "Subscription upgraded successfully! Please complete your company profile (Header, Footer, GSTIN, etc.) to generate official quotations.";
}
$errorMessage = '';

// Fetch current user format preference
$stmt_pref = $con->prepare("SELECT format_preference FROM users WHERE user_id = ?");
$stmt_pref->bind_param("i", $user_id);
$stmt_pref->execute();
$pref_res = $stmt_pref->get_result();
$format_pref = ($pref_res && $pref_res->num_rows > 0) ? $pref_res->fetch_assoc()['format_preference'] : null;
$stmt_pref->close();

// Fetch current user details
$stmt_user = $con->prepare("SELECT user_name, email, password, subscription_type, subscription_start_date, subscription_end_date FROM users WHERE user_id = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();

// Fetch company details
$stmt_comp = $con->prepare("SELECT company_id, company_name, company_address, gstin_number, header_image, footer_image, logo_image, sign_image, payment_qr_image, bank_name, account_no, ifsc_code, account_holder, default_expiration, default_due_date, default_payment_terms, default_notes, default_highlights_title, default_highlights_text, include_highlights FROM companies WHERE user_id = ?");
$stmt_comp->bind_param("i", $user_id);
$stmt_comp->execute();
$company = $stmt_comp->get_result()->fetch_assoc();
$stmt_comp->close();

// Handle form submits
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? '')) {
        $errorMessage = "Invalid request. Please refresh and try again.";
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'update_profile') {
            $userName = sanitize_input($_POST['user_name'] ?? '');
            $email = sanitize_input($_POST['email'] ?? '');
            $companyName = sanitize_input($_POST['company_name'] ?? '');
            $companyAddress = sanitize_input($_POST['company_address'] ?? '');
            $gstin = strtoupper(sanitize_input($_POST['gstin_number'] ?? ''));
            
            // Validation
            if (empty($userName) || empty($email) || empty($companyName) || empty($companyAddress)) {
                $errorMessage = "All profile and company fields are required (GSTIN is optional).";
            } elseif (!empty($gstin) && !preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/', $gstin)) {
                $errorMessage = "Invalid GSTIN format! Must be a valid 15-character GSTIN.";
            } else {
                // Check if email/contact is already taken by another user
                $check = $con->prepare("SELECT user_id FROM users WHERE (email = ? OR user_name = ?) AND user_id != ?");
                $check->bind_param("ssi", $email, $userName, $user_id);
                $check->execute();
                $dup = $check->get_result();
                if ($dup->num_rows > 0) {
                    $errorMessage = "Username or Contact Number is already taken by another user.";
                } else {
                    $con->begin_transaction();
                    try {
                        // Update users table
                        $up_user = $con->prepare("UPDATE users SET user_name = ?, email = ? WHERE user_id = ?");
                        $up_user->bind_param("ssi", $userName, $email, $user_id);
                        $up_user->execute();
                        $up_user->close();
                        
                        // Update companies table
                        $up_comp = $con->prepare("UPDATE companies SET company_name = ?, company_address = ?, gstin_number = ? WHERE user_id = ?");
                        $up_comp->bind_param("sssi", $companyName, $companyAddress, $gstin, $user_id);
                        $up_comp->execute();
                        $up_comp->close();
                        
                        $con->commit();
                        
                        // Update session
                        $_SESSION['username'] = $userName;
                        $_SESSION['email'] = $email;
                        
                        // Refresh variables
                        $user['user_name'] = $userName;
                        $user['email'] = $email;
                        $company['company_name'] = $companyName;
                        $company['company_address'] = $companyAddress;
                        $company['gstin_number'] = $gstin;
                        
                        $successMessage = "Profile and company details updated successfully.";
                    } catch (Exception $e) {
                        $con->rollback();
                        $errorMessage = "Database update error. Please try again.";
                    }
                }
                $check->close();
            }
        } elseif ($action === 'change_password') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                $errorMessage = "All password fields are required.";
            } elseif ($newPassword !== $confirmPassword) {
                $errorMessage = "New passwords do not match.";
            } elseif (strlen($newPassword) < 8) {
                $errorMessage = "New password must be at least 8 characters long.";
            } elseif (!password_verify($currentPassword, $user['password'])) {
                $errorMessage = "Current password is incorrect.";
            } else {
                $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $con->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $stmt->bind_param("si", $newHash, $user_id);
                if ($stmt->execute()) {
                    $successMessage = "Password changed successfully.";
                    $user['password'] = $newHash;
                } else {
                    $errorMessage = "Failed to update password. Please try again.";
                }
                $stmt->close();
            }
        } elseif ($action === 'update_assets') {
            function uploadAssetToCloudinary($fileInput, $cloud_name, $upload_preset) {
                if (isset($_FILES[$fileInput]) && $_FILES[$fileInput]['error'] === 0) {
                    $tmpFilePath = $_FILES[$fileInput]['tmp_name'];
                    $fileSize = $_FILES[$fileInput]['size'];
                    $fileType = $_FILES[$fileInput]['type'];
                    
                    if ($fileSize > 5 * 1024 * 1024) {
                        return ['error' => 'File size must be less than 5MB'];
                    }
                    
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    if (!in_array($fileType, $allowed_types)) {
                        return ['error' => 'Only JPG, PNG, GIF, and WEBP images are allowed'];
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
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Allow local SSL connection
                    $response = curl_exec($ch);
                    if ($response === false) {
                        $err = curl_error($ch);
                        curl_close($ch);
                        return ['error' => 'cURL error: ' . $err];
                    }
                    curl_close($ch);
                    $result = json_decode($response, true);
                    if (isset($result['error'])) {
                        return ['error' => 'Cloudinary error: ' . ($result['error']['message'] ?? 'Unknown API error')];
                    }
                    return ['url' => $result['secure_url'] ?? ''];
                }
                return null;
            }
            
            $headerUrl = $company['header_image'];
            $footerUrl = $company['footer_image'];
            $logoUrl = $company['logo_image'];
            $signUrl = $company['sign_image'];
            $paymentQrUrl = $company['payment_qr_image'];
            
            // Text inputs
            $bankName = sanitize_input($_POST['bank_name'] ?? '');
            $accountNo = sanitize_input($_POST['account_no'] ?? '');
            $ifscCode = sanitize_input($_POST['ifsc_code'] ?? '');
            $accountHolder = sanitize_input($_POST['account_holder'] ?? '');
            
            $errors = [];
            
            if (isset($_FILES['header']) && $_FILES['header']['error'] === 0) {
                $upload = uploadAssetToCloudinary('header', $cloud_name, $upload_preset);
                if (isset($upload['error'])) {
                    $errors[] = "Header: " . $upload['error'];
                } else {
                    $headerUrl = $upload['url'];
                }
            }
            
            if (isset($_FILES['footer']) && $_FILES['footer']['error'] === 0) {
                $upload = uploadAssetToCloudinary('footer', $cloud_name, $upload_preset);
                if (isset($upload['error'])) {
                    $errors[] = "Footer: " . $upload['error'];
                } else {
                    $footerUrl = $upload['url'];
                }
            }
            
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === 0) {
                $upload = uploadAssetToCloudinary('logo', $cloud_name, $upload_preset);
                if (isset($upload['error'])) {
                    $errors[] = "Logo: " . $upload['error'];
                } else {
                    $logoUrl = $upload['url'];
                }
            }
            
            if (isset($_FILES['sign']) && $_FILES['sign']['error'] === 0) {
                $upload = uploadAssetToCloudinary('sign', $cloud_name, $upload_preset);
                if (isset($upload['error'])) {
                    $errors[] = "Signature: " . $upload['error'];
                } else {
                    $signUrl = $upload['url'];
                }
            }

            if (isset($_FILES['payment_qr']) && $_FILES['payment_qr']['error'] === 0) {
                $upload = uploadAssetToCloudinary('payment_qr', $cloud_name, $upload_preset);
                if (isset($upload['error'])) {
                    $errors[] = "Payment QR: " . $upload['error'];
                } else {
                    $paymentQrUrl = $upload['url'];
                }
            }
            
            if (count($errors) > 0) {
                $errorMessage = implode("<br/>", $errors);
            } else {
                $default_expiration = sanitize_input($_POST['default_expiration'] ?? '');
                $default_due_date = sanitize_input($_POST['default_due_date'] ?? '');
                $default_payment_terms = sanitize_input($_POST['default_payment_terms'] ?? '');
                $default_notes = sanitize_input($_POST['default_notes'] ?? '');
                $include_highlights = isset($_POST['include_highlights']) ? 1 : 0;
                $default_highlights_title = sanitize_input($_POST['default_highlights_title'] ?? '');
                $default_highlights_text = sanitize_input($_POST['default_highlights_text'] ?? '');

                $stmt = $con->prepare("UPDATE companies SET header_image = ?, footer_image = ?, logo_image = ?, sign_image = ?, payment_qr_image = ?, bank_name = ?, account_no = ?, ifsc_code = ?, account_holder = ?, default_expiration = ?, default_due_date = ?, default_payment_terms = ?, default_notes = ?, default_highlights_title = ?, default_highlights_text = ?, include_highlights = ? WHERE user_id = ?");
                $stmt->bind_param("sssssssssssssssii", $headerUrl, $footerUrl, $logoUrl, $signUrl, $paymentQrUrl, $bankName, $accountNo, $ifscCode, $accountHolder, $default_expiration, $default_due_date, $default_payment_terms, $default_notes, $default_highlights_title, $default_highlights_text, $include_highlights, $user_id);
                if ($stmt->execute()) {
                    $successMessage = "Company settings and assets updated successfully.";
                    $company['header_image'] = $headerUrl;
                    $company['footer_image'] = $footerUrl;
                    $company['logo_image'] = $logoUrl;
                    $company['sign_image'] = $signUrl;
                    $company['payment_qr_image'] = $paymentQrUrl;
                    $company['bank_name'] = $bankName;
                    $company['account_no'] = $accountNo;
                    $company['ifsc_code'] = $ifscCode;
                    $company['account_holder'] = $accountHolder;
                    $company['default_expiration'] = $default_expiration;
                    $company['default_due_date'] = $default_due_date;
                    $company['default_payment_terms'] = $default_payment_terms;
                    $company['default_notes'] = $default_notes;
                    $company['default_highlights_title'] = $default_highlights_title;
                    $company['default_highlights_text'] = $default_highlights_text;
                    $company['include_highlights'] = $include_highlights;
                } else {
                    $errorMessage = "Failed to save assets URL in database: " . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
}

$is_premium = is_premium_user($user_id);
$is_trial = is_trial_user($user_id);
$trial_counts = get_trial_counts($user_id);

$con->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<link rel="icon" type="image/png" href="Logo.png">
<link rel="manifest" href="manifest.json">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Quotation Management System</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Central Styles -->
    <link rel="stylesheet" href="sidebar.css?v=2.3">
    <link rel="stylesheet" href="home.css?v=1.4">
    <link rel="stylesheet" href="theme.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="components.css">
    <script src="utils.js" defer></script>

    <!-- Theme Loader Script (to prevent flash) -->
    <script>
        (function() {
            const theme = localStorage.getItem('theme') || 'dark';
            if (theme === 'light') {
                document.documentElement.classList.add('light-mode');
            }
        })();
    </script>
    
    <style>
        .settings-card {
            background: var(--card-gradient);
            border: 1px solid var(--teal-border);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            animation: slideInUp 0.5s ease;
            transition: all 0.3s ease;
        }
        
        html.light-mode .settings-card {
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03) !important;
        }

        .settings-header {
            margin-bottom: 10px;
            border-bottom: 1px solid var(--teal-border);
            padding-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .settings-header h2 {
            font-family: var(--font-head);
            color: var(--teal);
            font-size: 14px;
            font-weight: 700;
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

        .form-group input[readonly], 
        .form-group textarea[readonly] {
            background: rgba(20, 20, 20, 0.8);
            color: #888;
            cursor: not-allowed;
            border-color: rgba(45, 212, 191, 0.05);
        }

        .alert {
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 12px;
            text-align: center;
        }

        .alert-success {
            background: rgba(45, 212, 191, 0.1);
            border: 1px solid rgba(45, 212, 191, 0.3);
            color: #2dd4bf;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #ef4444;
        }

        .btn-submit {
            background: var(--teal);
            color: #000;
            border: none;
            padding: 8px 16px;
            font-weight: bold;
            font-size: 12px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px var(--teal-glow);
        }

        .btn-submit:hover {
            background: var(--teal-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(45, 212, 191, 0.18);
        }

        /* Asset image box */
        .asset-preview-box {
            border: 1px dashed rgba(45, 212, 191, 0.3);
            padding: 8px;
            border-radius: 6px;
            text-align: center;
            background: rgba(10, 10, 10, 0.4);
            margin-top: 6px;
        }
        
        html.light-mode .asset-preview-box {
            border-color: rgba(0, 0, 0, 0.12) !important;
            background: #f8fafc !important;
        }

        .asset-preview-img {
            max-width: 100%;
            max-height: 80px;
            object-fit: contain;
            border-radius: 4px;
            background: #fff;
            padding: 4px;
        }

        /* Theme button style */
        .theme-toggle-container {
            display: flex;
            gap: 10px;
            margin-top: 8px;
        }

        .theme-btn {
            flex: 1;
            padding: 8px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 6px;
            color: #ccc;
            cursor: pointer;
            font-weight: bold;
            font-size: 12px;
            transition: all 0.2s ease;
        }
        
        html.light-mode .theme-btn {
            background: #ffffff !important;
            border-color: rgba(0, 0, 0, 0.1) !important;
            color: #475569 !important;
        }

        .theme-btn.active {
            border-color: var(--teal);
            background: rgba(45, 212, 191, 0.15);
            color: var(--teal);
            box-shadow: 0 0 8px var(--teal-glow);
        }

        /* Subscription Details styling */
        .subscription-badge-large {
            display: inline-block;
            background: linear-gradient(135deg, var(--teal) 0%, #14b8a6 100%);
            color: #000;
            padding: 6px 14px;
            font-size: 12px;
            font-weight: bold;
            border-radius: 15px;
            margin-bottom: 10px;
            box-shadow: 0 4px 12px var(--teal-glow);
        }

        .subscription-stat-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            font-size: 12px;
        }
        
        html.light-mode .subscription-stat-row {
            border-bottom-color: rgba(0, 0, 0, 0.05) !important;
        }

        .subscription-stat-label {
            color: #999;
        }
        
        html.light-mode .subscription-stat-label {
            color: #64748b !important;
        }

        .subscription-stat-value {
            font-weight: 600;
            color: #fff;
        }
        
        html.light-mode .subscription-stat-value {
            color: #0f172a !important;
        }

        .upgrade-btn-large {
            display: inline-block;
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: #fff;
            padding: 8px 18px;
            font-size: 12px;
            font-weight: bold;
            border-radius: 6px;
            text-decoration: none;
            margin-top: 15px;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.25);
            transition: all 0.3s ease;
        }

        .upgrade-btn-large:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(245, 158, 11, 0.4);
        }

        /* Page layout adjustments for settings */
        .main-header h1 {
            font-size: 1.5rem !important;
        }
        .main-content {
            padding: 20px 25px !important;
        }

        /* Profile welcome strip — shown only here, not on other pages */
        .dash-welcome {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            padding: 14px 16px;
            background: linear-gradient(135deg, rgba(45,212,191,.07) 0%, transparent 70%);
            border: 1px solid var(--teal-border);
            border-radius: 12px;
        }
        .dash-welcome-avatar {
            width: 42px; height: 42px;
            border-radius: 50%;
            border: 2px solid var(--teal);
            overflow: hidden;
            flex-shrink: 0;
        }
        .dash-welcome-avatar svg { width: 100%; height: 100%; }
        .dash-welcome-text { flex: 1; min-width: 0; }
        .dash-welcome-greeting {
            font-size: 13px;
            color: var(--text-muted);
        }
        .dash-welcome-name {
            font-family: var(--font-head);
            font-size: 16px;
            font-weight: 700;
            color: var(--text);
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/sidebar.php'; ?>

    <div class="mainBody">

        <!-- Main Content -->
        <div class="main-content">
            <header class="main-header">
                <h1><?php echo icon('gear', 22); ?> Settings</h1>
            </header>

            <!-- Welcome strip — profile page only -->
            <div class="dash-welcome">
                <div class="dash-welcome-avatar">
                    <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="50" cy="50" r="45" fill="#2dd4bf"/>
                        <circle cx="50" cy="35" r="15" fill="#000"/>
                        <path d="M25 75 Q25 55 50 55 Q75 55 75 75" fill="#000"/>
                    </svg>
                </div>
                <div class="dash-welcome-text">
                    <div class="dash-welcome-greeting">Good day 👋</div>
                    <div class="dash-welcome-name"><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></div>
                </div>
            </div>

            <?php if ($successMessage): ?>
                <div class="alert alert-success">✓ <?php echo $successMessage; ?></div>
            <?php endif; ?>

            <?php if ($errorMessage): ?>
                <div class="alert alert-danger">✕ <?php echo $errorMessage; ?></div>
            <?php endif; ?>
            
            <!-- Quick App Actions (Moved from More Tray) -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 15px; margin-bottom: 25px;">
                <a href="add-company.php" class="settings-card" style="display:flex; flex-direction:column; align-items:center; gap:10px; padding:20px; text-decoration:none; text-align:center;">
                    <div style="width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; background: rgba(45,212,191,.12); color: var(--teal);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px; height:20px;"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/><line x1="12" y1="11" x2="12" y2="17"/><line x1="9" y1="14" x2="15" y2="14"/></svg>
                    </div>
                    <span style="color:var(--text); font-weight:600; font-size:13px;">Add Company</span>
                </a>
                <a href="add-Product.php" class="settings-card" style="display:flex; flex-direction:column; align-items:center; gap:10px; padding:20px; text-decoration:none; text-align:center;">
                    <div style="width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; background: rgba(59,130,246,.12); color: #60a5fa;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px; height:20px;"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><line x1="12" y1="9" x2="12" y2="15"/><line x1="9" y1="12" x2="15" y2="12"/></svg>
                    </div>
                    <span style="color:var(--text); font-weight:600; font-size:13px;">Add Product</span>
                </a>
                <a href="about.php" class="settings-card" style="display:flex; flex-direction:column; align-items:center; gap:10px; padding:20px; text-decoration:none; text-align:center;">
                    <div style="width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; background: rgba(167,139,250,.12); color: #a78bfa;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px; height:20px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    </div>
                    <span style="color:var(--text); font-weight:600; font-size:13px;">About</span>
                </a>
                <a href="contact.php" class="settings-card" style="display:flex; flex-direction:column; align-items:center; gap:10px; padding:20px; text-decoration:none; text-align:center;">
                    <div style="width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; background: rgba(14,165,233,.12); color: #0ea5e9;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px; height:20px;"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13.5a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 2.69h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    </div>
                    <span style="color:var(--text); font-weight:600; font-size:13px;">Contact</span>
                </a>
                <a href="logout.php" class="settings-card" style="display:flex; flex-direction:column; align-items:center; gap:10px; padding:20px; text-decoration:none; text-align:center;">
                    <div style="width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; background: rgba(239,68,68,.12); color: #ef4444;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px; height:20px;"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    </div>
                    <span style="color:var(--text); font-weight:600; font-size:13px;">Logout</span>
                </a>
            </div>

            <!-- Section 1: Profile & Company Details -->
            <div class="settings-card">
                <div class="settings-header">
                    <h2><?php echo icon('user', 20); ?> Profile & Company Details</h2>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="user_name">Full Name</label>
                            <input type="text" id="user_name" name="user_name" required value="<?php echo htmlspecialchars($user['user_name']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="email">Contact Number</label>
                            <input type="text" id="email" name="email" required value="<?php echo htmlspecialchars($user['email']); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="company_name">Company Name</label>
                            <input type="text" id="company_name" name="company_name" required value="<?php echo htmlspecialchars($company['company_name']); ?>">
                        </div>
                        <div class="form-group">
                            <label>GSTIN Number</label>
                            <div style="display: flex; gap: 10px;">
                                <input type="text" id="gstin_number" name="gstin_number" value="<?php echo htmlspecialchars($company['gstin_number']); ?>" readonly style="background: rgba(0,0,0,0.05); cursor: not-allowed; flex: 1;" maxlength="15" pattern="[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}" title="Enter valid 15-character GSTIN">
                                <button type="button" onclick="promptGst()" style="padding: 10px 15px; border-radius: 8px; background: var(--teal); color: #fff; border: none; font-weight: 600; cursor: pointer; white-space: nowrap; transition: 0.3s;">
                                    <?php echo empty($company['gstin_number']) ? 'Add GST No.' : 'Edit GST No.'; ?>
                                </button>
                            </div>
                        </div>
                        <script>
                            function promptGst() {
                                let currentGst = document.getElementById('gstin_number').value;
                                let newGst = prompt("Enter GSTIN Number (leave blank to remove):", currentGst);
                                if (newGst !== null) {
                                    document.getElementById('gstin_number').value = newGst.trim().toUpperCase();
                                }
                            }
                        </script>
                    </div>

                    <div class="form-group">
                        <label for="company_address">Company Address</label>
                        <textarea id="company_address" name="company_address" rows="3" required><?php echo htmlspecialchars($company['company_address']); ?></textarea>
                    </div>

                    <button type="submit" class="btn-submit">Save Profile Changes</button>
                </form>
            </div>

            <!-- Section 2: Upload Images & Sign -->
            <div class="settings-card">
                <div class="settings-header">
                    <h2>📤 Company Branding & Assets</h2>
                </div>
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                    <input type="hidden" name="action" value="update_assets">

                    <div class="form-row">
                        <div class="form-group">
                            <label for="logo">Upload Company Logo (Max 5MB)</label>
                            <input type="file" id="logo" name="logo" accept="image/jpeg,image/png,image/gif,image/webp">
                            <?php if (!empty($company['logo_image'])): ?>
                                <div class="asset-preview-box">
                                    <img src="<?php echo htmlspecialchars($company['logo_image']); ?>" alt="Company Logo" class="asset-preview-img">
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label for="sign">Upload Authorized Signature (Max 5MB)</label>
                            <input type="file" id="sign" name="sign" accept="image/jpeg,image/png,image/gif,image/webp">
                            <?php if (!empty($company['sign_image'])): ?>
                                <div class="asset-preview-box">
                                    <img src="<?php echo htmlspecialchars($company['sign_image']); ?>" alt="Signature" class="asset-preview-img">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="header">Upload PDF Header Banner (Max 5MB)</label>
                            <input type="file" id="header" name="header" accept="image/jpeg,image/png,image/gif,image/webp">
                            <?php if (!empty($company['header_image'])): ?>
                                <div class="asset-preview-box">
                                    <img src="<?php echo htmlspecialchars($company['header_image']); ?>" alt="Header Banner" class="asset-preview-img">
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label for="footer">Upload PDF Footer Banner (Max 5MB)</label>
                            <input type="file" id="footer" name="footer" accept="image/jpeg,image/png,image/gif,image/webp">
                            <?php if (!empty($company['footer_image'])): ?>
                                <div class="asset-preview-box">
                                    <img src="<?php echo htmlspecialchars($company['footer_image']); ?>" alt="Footer Banner" class="asset-preview-img">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div id="bank_details_section">
                        <h3 style="margin-top: 25px; border-bottom: 1px solid rgba(45, 212, 191, 0.2); padding-bottom: 8px; color: #2dd4bf;">💳 Bank & Payment Details (Format 2 & 3)</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="account_holder">Account Holder Name</label>
                            <input type="text" id="account_holder" name="account_holder" value="<?php echo htmlspecialchars($company['account_holder'] ?? ''); ?>" placeholder="Enter holder's name">
                        </div>
                        <div class="form-group">
                            <label for="bank_name">Bank Name</label>
                            <input type="text" id="bank_name" name="bank_name" value="<?php echo htmlspecialchars($company['bank_name'] ?? ''); ?>" placeholder="Enter bank name">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="account_no">Account Number</label>
                            <input type="text" id="account_no" name="account_no" value="<?php echo htmlspecialchars($company['account_no'] ?? ''); ?>" placeholder="Enter account number">
                        </div>
                        <div class="form-group">
                            <label for="ifsc_code">IFSC Code</label>
                            <input type="text" id="ifsc_code" name="ifsc_code" value="<?php echo htmlspecialchars($company['ifsc_code'] ?? ''); ?>" placeholder="Enter IFSC code">
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 25px;">
                        <label for="payment_qr">Upload Payment QR Code Image (Max 5MB)</label>
                        <input type="file" id="payment_qr" name="payment_qr" accept="image/jpeg,image/png,image/gif,image/webp">
                        <?php if (!empty($company['payment_qr_image'])): ?>
                            <div class="asset-preview-box">
                                <img src="<?php echo htmlspecialchars($company['payment_qr_image']); ?>" alt="Payment QR Code" class="asset-preview-img" style="max-width: 150px; border-radius: 8px; margin-top: 10px; border: 1px solid #2dd4bf33;">
                            </div>
                        <?php endif; ?>
                    </div>

                    </div>

                    <button type="submit" class="btn-submit">Upload Assets</button>
                </form>
            </div>

            <!-- Section: Format Preference -->
            <div class="settings-card">
                <div class="settings-header">
                    <h2>📄 Default Quotation Format</h2>
                </div>
                <div class="form-row">
                    <div class="form-group" style="width: 100%;">
                        <label>Select your preferred default quotation format layout.</label>
                        <select id="settingFormatPreference" class="form-control" style="width: 100%; max-width: 400px; padding: 10px; border-radius: 8px; border: 1px solid var(--teal-border); background: var(--surface); color: var(--text); margin-top: 10px;" onchange="updateFormatPreference(this.value)">
                            <option value="">-- Select Default Format --</option>
                            <option value="format1" <?php echo ($format_pref === 'format1' || $format_pref === 'old') ? 'selected' : ''; ?>>Format 1 (Standard Shreeji Style)</option>
                            <option value="format2" <?php echo ($format_pref === 'format2' || $format_pref === 'new') ? 'selected' : ''; ?>>Format 2 (Event / Playout Service Style)</option>
                            <option value="format3" <?php echo ($format_pref === 'format3') ? 'selected' : ''; ?>>Format 3 (Quote Style with Product Thumbnails)</option>
                        </select>
                        <p style="font-size: 12px; color: var(--text-muted); margin-top: 8px;">The format selected above will be automatically used when generating all new quotations. Additional setting fields below will adapt based on your selection.</p>
                    </div>
                </div>
            </div>

            <!-- Section: Quotation Content Defaults -->
            <div class="settings-card">
                <div class="settings-header">
                    <h2>📝 Quotation Content Defaults</h2>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                    <input type="hidden" name="action" value="update_assets"> <!-- Re-use the update_assets action -->
                    <p id="content_defaults_desc" style="color: #999; font-size: 13px; margin-bottom: 15px;">These details will be used as default values for Quotation Formats 2 and 3.</p>

                    <div class="form-row" id="expiration_section">
                        <div class="form-group">
                            <label for="default_expiration">Expiration Period</label>
                            <input type="text" id="default_expiration" name="default_expiration" value="<?php echo htmlspecialchars($company['default_expiration'] ?? '1 Days'); ?>" placeholder="e.g. 1 Days, 7 Days">
                        </div>
                        <div class="form-group">
                            <label for="default_due_date">Payment Due Date</label>
                            <input type="text" id="default_due_date" name="default_due_date" value="<?php echo htmlspecialchars($company['default_due_date'] ?? ''); ?>" placeholder="e.g. 5th November 2025">
                        </div>
                    </div>

                    <div class="form-group" id="payment_terms_section">
                        <label for="default_payment_terms">Payment Terms & Conditions</label>
                        <textarea id="default_payment_terms" name="default_payment_terms" rows="2" placeholder="e.g. An advance payment of 50% is required..."><?php echo htmlspecialchars($company['default_payment_terms'] ?? "To confirm your booking, an advance payment of 50% of the total cost is required."); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="default_notes">Additional Notes (Bullet points, one per line)</label>
                        <textarea id="default_notes" name="default_notes" rows="3" placeholder="Enter custom notes, one per line..."><?php echo htmlspecialchars($company['default_notes'] ?? ''); ?></textarea>
                    </div>

                    <div id="highlights_section" style="margin-top: 15px; border-top: 1px dashed rgba(45, 212, 191, 0.2); padding-top: 15px;">
                        <div class="form-group" style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                            <input type="checkbox" id="include_highlights" name="include_highlights" value="1" <?php echo (!isset($company['include_highlights']) || $company['include_highlights'] == 1) ? 'checked' : ''; ?> style="width: auto;">
                            <label for="include_highlights" style="margin-bottom: 0; cursor: pointer; color: #fff;">Include Highlights Page (Page 2)</label>
                        </div>
                        
                        <div class="form-group">
                            <label for="default_highlights_title">Highlights Title</label>
                            <input type="text" id="default_highlights_title" name="default_highlights_title" value="<?php echo htmlspecialchars($company['default_highlights_title'] ?? 'Additional Free Services Included (Highlights)'); ?>">
                            
                            <label for="default_highlights_text" style="margin-top: 10px;">Highlights Details (one per line)</label>
                            <textarea id="default_highlights_text" name="default_highlights_text" rows="4"><?php echo htmlspecialchars($company['default_highlights_text'] ?? "* Tournament Setup: Complete match setup...\n* Match Scheduling..."); ?></textarea>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-submit">Save Defaults</button>
                </form>
            </div>

            <!-- Section 3: Change Password -->
            <div class="settings-card">
                <div class="settings-header">
                    <h2><?php echo icon('lock', 20); ?> Change Password</h2>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                    <input type="hidden" name="action" value="change_password">

                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" required placeholder="Enter current password">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" required placeholder="Min 8 characters" minlength="8">
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm new password">
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">Update Password</button>
                </form>
            </div>

            <!-- Section 4: Theme & Mode -->
            <div class="settings-card">
                <div class="settings-header">
                    <h2>🌗 App Appearance</h2>
                </div>
                <div class="form-group">
                    <label>Select Mode</label>
                    <p style="color: #999; font-size: 13px; margin-bottom: 15px;">Choose your color theme. The selection will be applied globally across all pages instantly.</p>
                    <div class="theme-toggle-container">
                        <button type="button" class="theme-btn" id="themeDarkBtn" onclick="setAppTheme('dark')">🌙 Dark Mode</button>
                        <button type="button" class="theme-btn" id="themeLightBtn" onclick="setAppTheme('light')">☀️ Light Mode</button>
                    </div>
                </div>
            </div>

            <!-- Section 5: Subscription Details -->
            <div class="settings-card">
                <div class="settings-header">
                    <h2><?php echo icon('diamond', 20); ?> Subscription & Billing</h2>
                </div>
                <div style="text-align: center; padding: 10px 0;">
                    <div style="font-size: 14px; color: #999; margin-bottom: 5px;">Your Active Plan</div>
                    <div class="subscription-badge-large">
                        <?php 
                        if ($is_premium) {
                            echo strtoupper($user['subscription_type']) . " - Premium";
                        } elseif ($is_trial) {
                            echo "FREE TRIAL";
                        } else {
                            echo "NO ACTIVE PLAN";
                        }
                        ?>
                    </div>
                    
                    <div style="max-width: 500px; margin: 20px auto 0 auto; text-align: left; background: rgba(0, 0, 0, 0.2); padding: 20px; border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.05);">
                        <div class="subscription-stat-row">
                            <span class="subscription-stat-label">Plan Type</span>
                            <span class="subscription-stat-value"><?php echo $is_premium ? 'Premium Plan' : 'Free Trial'; ?></span>
                        </div>
                        
                        <?php if ($is_premium): ?>
                            <div class="subscription-stat-row">
                                <span class="subscription-stat-label">Subscription Start</span>
                                <span class="subscription-stat-value"><?php echo date('F j, Y', strtotime($user['subscription_start_date'])); ?></span>
                            </div>
                            <div class="subscription-stat-row">
                                <span class="subscription-stat-label">Renewal / Expiry Date</span>
                                <span class="subscription-stat-value"><?php echo $user['subscription_end_date'] ? date('F j, Y', strtotime($user['subscription_end_date'])) : 'Never'; ?></span>
                            </div>
                        <?php else: ?>
                            <div class="subscription-stat-row">
                                <span class="subscription-stat-label">Trial Quotations Limit</span>
                                <span class="subscription-stat-value"><?php echo $trial_counts['quotations']; ?> remaining (2 limit)</span>
                            </div>
                            <div class="subscription-stat-row">
                                <span class="subscription-stat-label">Trial Products Limit</span>
                                <span class="subscription-stat-value"><?php echo $trial_counts['products']; ?> remaining (2 limit)</span>
                            </div>
                            <div class="subscription-stat-row">
                                <span class="subscription-stat-label">Trial Companies Limit</span>
                                <span class="subscription-stat-value"><?php echo $trial_counts['companies']; ?> remaining (2 limit)</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!$is_premium): ?>
                        <a href="premium.php" class="upgrade-btn-large">💎 Upgrade to Premium Plan</a>
                    <?php else: ?>
                        <a href="premium.php" class="upgrade-btn-large" style="background: linear-gradient(135deg, #475569 0%, #334155 100%);">View Plans & Manage Subscription</a>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

    <!-- Scripts -->
    <script>

        // Auto uppercase GSTIN
        const gstinInput = document.getElementById('gstin_number');
        if (gstinInput) {
            gstinInput.addEventListener('input', function(e) {
                e.target.value = e.target.value.toUpperCase();
            });
        }

        // Global Theme Controls
        function setAppTheme(theme) {
            if (theme === 'light') {
                document.documentElement.classList.add('light-mode');
                document.getElementById('themeLightBtn').classList.add('active');
                document.getElementById('themeDarkBtn').classList.remove('active');
            } else {
                document.documentElement.classList.remove('light-mode');
                document.getElementById('themeDarkBtn').classList.add('active');
                document.getElementById('themeLightBtn').classList.remove('active');
            }
            localStorage.setItem('theme', theme);
        }

        // Read and apply theme on page load
        document.addEventListener('DOMContentLoaded', () => {
            const currentTheme = localStorage.getItem('theme') || 'dark';
            setAppTheme(currentTheme);
        });

        // Image Preview function
        function previewAsset(input, previewId) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.getElementById(previewId);
                    img.src = e.target.result;
                    img.style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Format preference update
        async function updateFormatPreference(format) {
            if (!format) return;
            toggleFieldsByFormat(format);
            try {
                const res = await fetch('settings.php?action=set_format', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ format: format })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    QT.toastSuccess('Default format updated successfully!');
                } else {
                    QT.toastError('Failed to update format preference.');
                }
            } catch (e) {
                console.error(e);
                QT.toastError('Error saving format preference.');
            }
        }

        function toggleFieldsByFormat(format) {
            const bankSection = document.getElementById('bank_details_section');
            const expSection = document.getElementById('expiration_section');
            const termsSection = document.getElementById('payment_terms_section');
            const highlightsSection = document.getElementById('highlights_section');
            const noteDesc = document.getElementById('content_defaults_desc');
            
            if (format === 'format1' || format === 'old' || format === '') {
                if(bankSection) bankSection.style.display = 'none';
                if(expSection) expSection.style.display = 'none';
                if(termsSection) termsSection.style.display = 'none';
                if(highlightsSection) highlightsSection.style.display = 'none';
                if(noteDesc) noteDesc.innerText = 'Format 1 uses "Additional Notes" for Terms & Conditions and standard styling.';
            } else if (format === 'format2' || format === 'new') {
                if(bankSection) bankSection.style.display = 'block';
                if(expSection) expSection.style.display = 'flex';
                if(termsSection) termsSection.style.display = 'block';
                if(highlightsSection) highlightsSection.style.display = 'none';
                if(noteDesc) noteDesc.innerText = 'These details will be used as default values for Quotation Format 2.';
            } else if (format === 'format3') {
                if(bankSection) bankSection.style.display = 'block';
                if(expSection) expSection.style.display = 'flex';
                if(termsSection) termsSection.style.display = 'block';
                if(highlightsSection) highlightsSection.style.display = 'block';
                if(noteDesc) noteDesc.innerText = 'These details will be used as default values for Quotation Format 3.';
            }
        }

        // Apply on load
        document.addEventListener('DOMContentLoaded', () => {
            const sel = document.getElementById('settingFormatPreference');
            if (sel) {
                toggleFieldsByFormat(sel.value);
            }
        });
    </script>
</body>
</html>
