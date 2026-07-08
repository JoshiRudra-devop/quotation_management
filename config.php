<?php
// Centralized configuration: database, session, and security helpers

// ==================== ICON SYSTEM ====================
// Shared stroke-style SVG icons (24x24 viewBox), matching sidebar.php's nav icons.
// Use icon('name') instead of emoji, so icon rendering is consistent across every
// OS/browser (emoji glyphs render differently per platform; SVGs don't).
function icon($name, $size = 18) {
    $paths = [
        'sparkle'    => '<path d="M12 2l1.8 5.4L19 9l-5.2 1.6L12 16l-1.8-5.4L5 9l5.2-1.6z"/>',
        'gear'       => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
        'user'       => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'lock'       => '<rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
        'diamond'    => '<path d="M6 3h12l4 6-10 12L2 9z"/><path d="M11 3 8 9l4 12 4-12-3-6"/><path d="M2 9h20"/>',
        'info'       => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>',
        'mail'       => '<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>',
        'phone'      => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13.5a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 2.69h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>',
        'map-pin'    => '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 1 1 18 0z"/><circle cx="12" cy="10" r="3"/>',
        'target'     => '<circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/>',
        'clipboard'  => '<path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>',
        'building'   => '<rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>',
        'box'        => '<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><line x1="3.27" y1="6.96" x2="12" y2="12.01"/><line x1="12" y1="22.08" x2="12" y2="12"/><line x1="20.73" y1="6.96" x2="12" y2="12.01"/>',
        'x-circle'   => '<circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>',
        'search'     => '<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>',
        'plus'       => '<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>',
        'star'       => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
        'arrow-right'=> '<line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>',
        'check'      => '<polyline points="20 6 9 17 4 12"/>',
    ];
    if (!isset($paths[$name])) return '';
    $fill = $name === 'star' ? 'currentColor' : 'none';
    return '<svg viewBox="0 0 24 24" width="' . (int)$size . '" height="' . (int)$size . '" fill="' . $fill . '" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-0.2em;flex-shrink:0;">' . $paths[$name] . '</svg>';
}

// Database credentials (consider moving to environment variables in production)
$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "";
$DB_NAME = "quotation_managment";

// Create mysqli connection
function db_connect() {
    global $DB_HOST, $DB_USER, $DB_PASS, $DB_NAME;
    $con = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
    if ($con->connect_error) {
        die("Connection failed: " . $con->connect_error);
    }
    // Ensure order_by_person column exists in quotations table for normalized schema compatibility
    $con->query("ALTER TABLE quotations ADD COLUMN IF NOT EXISTS order_by_person VARCHAR(255) DEFAULT NULL");
    // Ensure template_format and pdf_url columns exist in quotations table
    $con->query("ALTER TABLE quotations ADD COLUMN IF NOT EXISTS template_format VARCHAR(50) DEFAULT 'format1'");
    $con->query("ALTER TABLE quotations ADD COLUMN IF NOT EXISTS pdf_url VARCHAR(500) DEFAULT NULL");
    $con->query("ALTER TABLE instruments ADD COLUMN IF NOT EXISTS hsn_code VARCHAR(20) DEFAULT NULL");
    $con->query("ALTER TABLE instruments ADD COLUMN IF NOT EXISTS image_url VARCHAR(500) DEFAULT NULL");
    $con->query("ALTER TABLE quotation_items ADD COLUMN IF NOT EXISTS hsn_code VARCHAR(20) DEFAULT NULL");
    $con->query("ALTER TABLE quotation_items ADD COLUMN IF NOT EXISTS image_url VARCHAR(500) DEFAULT NULL");
    // Ensure format_preference column exists in users table and is varchar
    $con->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS format_preference VARCHAR(50) DEFAULT NULL");
    $con->query("ALTER TABLE users MODIFY COLUMN format_preference VARCHAR(50) DEFAULT NULL");
    
    // Ensure company settings columns exist in companies table
    $con->query("ALTER TABLE companies ADD COLUMN IF NOT EXISTS payment_qr_image VARCHAR(500) DEFAULT NULL");
    $con->query("ALTER TABLE companies ADD COLUMN IF NOT EXISTS bank_name VARCHAR(255) DEFAULT NULL");
    $con->query("ALTER TABLE companies ADD COLUMN IF NOT EXISTS account_no VARCHAR(100) DEFAULT NULL");
    $con->query("ALTER TABLE companies ADD COLUMN IF NOT EXISTS ifsc_code VARCHAR(50) DEFAULT NULL");
    $con->query("ALTER TABLE companies ADD COLUMN IF NOT EXISTS account_holder VARCHAR(255) DEFAULT NULL");

    // Additional Quotation Defaults
    $con->query("ALTER TABLE companies ADD COLUMN IF NOT EXISTS default_expiration VARCHAR(100) DEFAULT NULL");
    $con->query("ALTER TABLE companies ADD COLUMN IF NOT EXISTS default_due_date VARCHAR(100) DEFAULT NULL");
    $con->query("ALTER TABLE companies ADD COLUMN IF NOT EXISTS default_payment_terms TEXT DEFAULT NULL");
    $con->query("ALTER TABLE companies ADD COLUMN IF NOT EXISTS default_notes TEXT DEFAULT NULL");
    $con->query("ALTER TABLE companies ADD COLUMN IF NOT EXISTS default_highlights_title VARCHAR(255) DEFAULT NULL");
    $con->query("ALTER TABLE companies ADD COLUMN IF NOT EXISTS default_highlights_text TEXT DEFAULT NULL");
    $con->query("ALTER TABLE companies ADD COLUMN IF NOT EXISTS include_highlights BOOLEAN DEFAULT 1");
    return $con;
}

// Secure session settings
if (session_status() === PHP_SESSION_NONE) {
    $cookieParams = session_get_cookie_params();
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => $cookieParams['path'],
        'domain' => $cookieParams['domain'],
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// CSRF helpers
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_validate($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token ?? '');
}

// JSON helper
function json_response($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

// Flash-message helpers: survive a redirect (e.g. add-Product.php -> home.php)
// and render as a QT toast on the next page that includes sidebar.php.
function flash_set($message, $type = 'success') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

function toast_script($message, $type = 'success') {
    $allowed = ['success', 'error', 'warn', 'info'];
    if (!in_array($type, $allowed, true)) $type = 'info';
    $fn = 'toast' . ucfirst($type);
    echo "<script>document.addEventListener('DOMContentLoaded', function(){ if (window.QT) QT.$fn(" . json_encode($message) . "); });</script>";
}

function flash_render() {
    if (empty($_SESSION['flash_message'])) return;
    $msg = $_SESSION['flash_message'];
    $type = $_SESSION['flash_type'] ?? 'success';
    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
    toast_script($msg, $type);
}

// One-time form tokens: prevent a double-click/resubmission from inserting the
// same record twice. Distinct from csrf_token()/csrf_validate(), which are
// session-lived and intentionally reused across requests.
function new_form_token($key) {
    $_SESSION["form_token_$key"] = bin2hex(random_bytes(16));
    return $_SESSION["form_token_$key"];
}

function consume_form_token($key, $submitted) {
    $sessionKey = "form_token_$key";
    if (empty($submitted) || empty($_SESSION[$sessionKey]) || !hash_equals($_SESSION[$sessionKey], $submitted)) {
        return false;
    }
    unset($_SESSION[$sessionKey]);
    return true;
}

// ==================== SUBSCRIPTION & TRIAL FUNCTIONS ====================

/**
 * Get user subscription info
 */
function get_user_subscription($user_id) {
    $con = db_connect();
    $stmt = $con->prepare("SELECT subscription_type, subscription_start_date, subscription_end_date, trial_products_count, trial_companies_count, trial_quotations_count, is_active FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $subscription = null;
    if ($result && $result->num_rows > 0) {
        $subscription = $result->fetch_assoc();
    }
    $stmt->close();
    $con->close();
    return $subscription;
}

/**
 * Check if user is on trial
 */
function is_trial_user($user_id) {
    $sub = get_user_subscription($user_id);
    return $sub && $sub['subscription_type'] === 'trial';
}

/**
 * Check if user has premium subscription
 */
function is_premium_user($user_id) {
    $sub = get_user_subscription($user_id);
    if (!$sub) return false;
    
    if ($sub['subscription_type'] === 'trial') return false;
    
    // Check if subscription is still active
    if (!$sub['is_active']) return false;
    
    // Check expiration date for premium plans
    if ($sub['subscription_end_date']) {
        $end_date = new DateTime($sub['subscription_end_date']);
        $now = new DateTime();
        if ($end_date < $now) return false;
    }
    
    return true;
}

/**
 * Check if user can add more products (instruments)
 */
function can_add_product($user_id) {
    $sub = get_user_subscription($user_id);
    if (!$sub) return false;
    
    if (is_premium_user($user_id)) {
        return true; // Premium users have unlimited
    }
    
    if (is_trial_user($user_id)) {
        return $sub['trial_products_count'] < 2; // Trial limit: 2
    }
    
    return false;
}

/**
 * Check if user can add more companies
 */
function can_add_company($user_id) {
    $sub = get_user_subscription($user_id);
    if (!$sub) return false;
    
    if (is_premium_user($user_id)) {
        return true; // Premium users have unlimited
    }
    
    if (is_trial_user($user_id)) {
        return $sub['trial_companies_count'] < 2; // Trial limit: 2
    }
    
    return false;
}

/**
 * Check if user can create more quotations
 */
function can_create_quotation($user_id) {
    $sub = get_user_subscription($user_id);
    if (!$sub) return false;
    
    if (is_premium_user($user_id)) {
        return true; // Premium users have unlimited
    }
    
    if (is_trial_user($user_id)) {
        return $sub['trial_quotations_count'] < 2; // Trial limit: 2
    }
    
    return false;
}

/**
 * Increment trial product count
 */
function increment_trial_products($user_id) {
    $con = db_connect();
    $stmt = $con->prepare("UPDATE users SET trial_products_count = trial_products_count + 1 WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    $con->close();
}

/**
 * Increment trial company count
 */
function increment_trial_companies($user_id) {
    $con = db_connect();
    $stmt = $con->prepare("UPDATE users SET trial_companies_count = trial_companies_count + 1 WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    $con->close();
}

/**
 * Increment trial quotation count
 */
function increment_trial_quotations($user_id) {
    $con = db_connect();
    $stmt = $con->prepare("UPDATE users SET trial_quotations_count = trial_quotations_count + 1 WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    $con->close();
}

/**
 * Update user subscription
 */
function update_subscription($user_id, $subscription_type, $months = 1) {
    $con = db_connect();
    $start_date = new DateTime();
    $end_date = clone $start_date;
    
    if ($subscription_type === 'monthly') {
        $end_date->modify("+{$months} months");
    } elseif ($subscription_type === 'yearly') {
        $end_date->modify("+{$months} years");
    } elseif ($subscription_type === '3yearly') {
        $end_date->modify("+3 years");
    }
    
    $start_date_str = $start_date->format('Y-m-d H:i:s');
    $end_date_str = $end_date->format('Y-m-d H:i:s');
    
    $stmt = $con->prepare("UPDATE users SET subscription_type = ?, subscription_start_date = ?, subscription_end_date = ?, is_active = TRUE WHERE user_id = ?");
    $stmt->bind_param("sssi", $subscription_type, $start_date_str, $end_date_str, $user_id);
    $stmt->execute();
    $stmt->close();
    $con->close();
}

/**
 * Get remaining trial counts
 */
function get_trial_counts($user_id) {
    $sub = get_user_subscription($user_id);
    if (!$sub || !is_trial_user($user_id)) {
        return ['products' => 0, 'companies' => 0, 'quotations' => 0];
    }
    
    return [
        'products' => max(0, 2 - $sub['trial_products_count']),
        'companies' => max(0, 2 - $sub['trial_companies_count']),
        'quotations' => max(0, 2 - $sub['trial_quotations_count'])
    ];
}

// ==================== SECURITY FUNCTIONS ====================

/**
 * Sanitize input for XSS protection
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Validate email
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate and sanitize integer
 */
function sanitize_int($value, $min = null, $max = null) {
    $value = filter_var($value, FILTER_VALIDATE_INT);
    if ($value === false) return null;
    if ($min !== null && $value < $min) return null;
    if ($max !== null && $value > $max) return null;
    return $value;
}

/**
 * Require authentication
 */
function require_auth() {
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header("Location: login.php");
        exit();
    }
}

/**
 * Rate limiting helper (basic implementation)
 */
function check_rate_limit($key, $limit = 10, $window = 60) {
    if (!isset($_SESSION['rate_limit'])) {
        $_SESSION['rate_limit'] = [];
    }
    
    $now = time();
    $key_data = $_SESSION['rate_limit'][$key] ?? ['count' => 0, 'reset' => $now + $window];
    
    if ($now > $key_data['reset']) {
        $key_data = ['count' => 0, 'reset' => $now + $window];
    }
    
    if ($key_data['count'] >= $limit) {
        return false;
    }
    
    $key_data['count']++;
    $_SESSION['rate_limit'][$key] = $key_data;
    return true;
}
?>

