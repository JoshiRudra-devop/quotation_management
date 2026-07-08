<?php
// Diagnostic script to check XAMPP setup
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>";
echo "<html><head><title>XAMPP Diagnostic</title>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #1a1a1a; color: #fff; }
    .success { color: #2dd4bf; padding: 10px; margin: 10px 0; background: #0a0a0a; border-left: 4px solid #2dd4bf; }
    .error { color: #f87171; padding: 10px; margin: 10px 0; background: #0a0a0a; border-left: 4px solid #f87171; }
    .warning { color: #fbbf24; padding: 10px; margin: 10px 0; background: #0a0a0a; border-left: 4px solid #fbbf24; }
    h1 { color: #2dd4bf; }
    h2 { color: #2dd4bf; margin-top: 30px; }
    pre { background: #000; padding: 15px; border-radius: 5px; overflow-x: auto; }
</style></head><body>";

echo "<h1>🔍 XAMPP Diagnostic Report</h1>";

// Check 1: PHP Version
echo "<h2>1. PHP Version</h2>";
$phpVersion = phpversion();
echo "<div class='success'>✅ PHP Version: $phpVersion</div>";

// Check 2: Apache Status
echo "<h2>2. Server Information</h2>";
echo "<div class='success'>✅ Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</div>";
echo "<div class='success'>✅ Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "</div>";
echo "<div class='success'>✅ Script Path: " . __FILE__ . "</div>";

// Check 3: File Permissions
echo "<h2>3. File Permissions</h2>";
$file = __FILE__;
if (is_readable($file)) {
    echo "<div class='success'>✅ Current file is readable</div>";
} else {
    echo "<div class='error'>❌ Current file is NOT readable</div>";
}
if (is_writable($file)) {
    echo "<div class='success'>✅ Current file is writable</div>";
} else {
    echo "<div class='warning'>⚠️ Current file is NOT writable</div>";
}

// Check 4: Required Extensions
echo "<h2>4. PHP Extensions</h2>";
$required = ['mysqli', 'session', 'json'];
foreach ($required as $ext) {
    if (extension_loaded($ext)) {
        echo "<div class='success'>✅ $ext extension loaded</div>";
    } else {
        echo "<div class='error'>❌ $ext extension NOT loaded</div>";
    }
}

// Check 5: Config File
echo "<h2>5. Configuration Files</h2>";
if (file_exists('config.php')) {
    echo "<div class='success'>✅ config.php exists</div>";
    if (is_readable('config.php')) {
        echo "<div class='success'>✅ config.php is readable</div>";
    } else {
        echo "<div class='error'>❌ config.php is NOT readable</div>";
    }
} else {
    echo "<div class='error'>❌ config.php does NOT exist</div>";
}

// Check 6: Database Connection
echo "<h2>6. Database Connection Test</h2>";
if (file_exists('config.php')) {
    require_once 'config.php';
    try {
        $con = db_connect();
        if ($con) {
            echo "<div class='success'>✅ Database connection successful!</div>";
            echo "<div class='success'>✅ Connected to: " . $DB_HOST . "</div>";
            $con->close();
        }
    } catch (Exception $e) {
        echo "<div class='error'>❌ Database connection failed: " . $e->getMessage() . "</div>";
    }
} else {
    echo "<div class='warning'>⚠️ Cannot test database - config.php not found</div>";
}

// Check 7: Key Files
echo "<h2>7. Key Files Check</h2>";
$files = ['index.html', 'login.php', 'home.php', 'config.php'];
foreach ($files as $file) {
    if (file_exists($file)) {
        echo "<div class='success'>✅ $file exists</div>";
    } else {
        echo "<div class='error'>❌ $file does NOT exist</div>";
    }
}

// Check 8: Session
echo "<h2>8. Session Test</h2>";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['test'] = 'XAMPP Diagnostic Test';
if (isset($_SESSION['test'])) {
    echo "<div class='success'>✅ Sessions are working</div>";
} else {
    echo "<div class='error'>❌ Sessions are NOT working</div>";
}

// URLs
echo "<h2>9. Access URLs</h2>";
echo "<div class='success'>";
echo "<strong>Your project URLs:</strong><br>";
echo "• Home: <a href='index.html' style='color: #2dd4bf;'>http://localhost/Webdev/quataion/index.html</a><br>";
echo "• Login: <a href='login.php' style='color: #2dd4bf;'>http://localhost/Webdev/quataion/login.php</a><br>";
echo "• Test: <a href='test.php' style='color: #2dd4bf;'>http://localhost/Webdev/quataion/test.php</a><br>";
echo "</div>";

echo "<h2>10. Summary</h2>";
echo "<div class='success'>";
echo "If you see this page, your XAMPP setup is working!<br>";
echo "If you're having issues accessing other pages, check:<br>";
echo "1. Make sure you're using the correct URL (http://localhost/Webdev/quataion/)<br>";
echo "2. Check browser console (F12) for JavaScript errors<br>";
echo "3. Check PHP error logs in XAMPP<br>";
echo "4. Verify Apache is running in XAMPP Control Panel<br>";
echo "</div>";

echo "</body></html>";
?>

