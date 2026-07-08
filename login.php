<?php
require_once __DIR__ . '/config.php';

// If user is already logged in, redirect to home
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: home.php");
    exit();
}

$con = db_connect();

if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
}

// Handle login form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!csrf_validate($_POST['csrf_token'] ?? '')) {
        $error_message = "Invalid request. Please try again.";
    } else {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (!empty($email) && !empty($password)) {
        $stmt = $con->prepare("SELECT user_id, user_name, email, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();

            // Secure password verification
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['user_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['logged_in'] = true;

                // Fetch user's company_id from companies table
                $stmt_comp = $con->prepare("SELECT company_id FROM companies WHERE user_id = ?");
                $stmt_comp->bind_param("i", $user['user_id']);
                $stmt_comp->execute();
                $res_comp = $stmt_comp->get_result();
                if ($row_comp = $res_comp->fetch_assoc()) {
                    $_SESSION['company_id'] = $row_comp['company_id'];
                } else {
                    $_SESSION['company_id'] = null;
                }
                $stmt_comp->close();

                session_regenerate_id(true);

                flash_set("Welcome back, {$user['user_name']}!", 'success');
                header('Location: home.php');
                exit();
            } else {
                $error_message = "Invalid password!";
            }
        } else {
            $error_message = "Email not found!";
        }
        $stmt->close();
    } else {
        $error_message = "Please fill in all fields!";
    }
    }
}

$con->close();

$prefill_email = isset($_GET['email']) ? trim($_GET['email']) : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<link rel="icon" type="image/png" href="Logo.png">
<link rel="manifest" href="manifest.json">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Easy Auth System</title>
    <style>
        body {
            font-family: "Arial", sans-serif;
            background: #000000ff;
            height: 100vh;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #fff;
            padding: 20px;
        }

        .login-container {
            border: 2px solid #2dd4bf;
            border-radius: 16px;
            padding: 40px 30px;
            width: 100%;
            max-width: 400px;
            animation: fadeIn 0.8s ease;
            box-shadow: 10px 10px 15px 15px #2dd4bf;
            transition: transform 0.3s ease;
            background: #000000;
        }

        .login-container:hover {
            transform: translateY(-8px);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        h1 {
            text-align: center;
            margin-bottom: 30px;
            font-size: 2rem;
            background: linear-gradient(135deg, #ffffff, #2dd4bf);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            font-weight: 600;
            color: #ccc;
            margin-bottom: 8px;
            display: block;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid #2dd4bf55;
            background-color: #1a1a1a;
            color: #fff;
            font-size: 15px;
            transition: border-color 0.3s ease;
            box-sizing: border-box;
        }

        input:focus {
            border-color: #2dd4bf;
            outline: none;
        }

        .btn {
            width: 100%;
            padding: 12px;
            margin-top: 10px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 16px;
            cursor: pointer;
            border: none;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background-color: #2dd4bf;
            color: #000;
        }

        .btn-primary:hover {
            background-color: #14b8a6;
        }

        .btn-secondary {
            background-color: #333;
            color: #fff;
            border: 1px solid #555;
        }

        .btn-secondary:hover {
            background-color: #444;
        }

        .error-message {
            background: #f87171;
            color: white;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            text-align: center;
            font-size: 14px;
        }

        .success-message {
            background: #10b981;
            color: white;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            text-align: center;
            font-size: 14px;
        }

        .text-center {
            text-align: center;
            margin-top: 15px;
        }

        .link {
            color: #2dd4bf;
            text-decoration: underline;
            font-size: 14px;
        }

        .link:hover {
            color: #14b8a6;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 25px 20px;
                box-shadow: 0 4px 12px rgba(45, 212, 191, 0.2);
                border-radius: 12px;
                width: 95%;
            }
            h1 {
                font-size: 1.6rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1><?php echo icon('lock', 24); ?> Welcome Back</h1>

        <?php if (isset($error_message)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['registered'])): ?>
            <div class="success-message">✓ Account created successfully! Please sign in.</div>
        <?php elseif (isset($_GET['logged_out'])): ?>
            <div class="success-message">✓ You have been successfully logged out.</div>
        <?php endif; ?>

        <form method="POST" action="" id="loginForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
            <div class="form-group">
                <label for="email">📞 Contact Number</label>
                <input type="text" id="email" name="email" required placeholder="Enter your contact number" value="<?php echo htmlspecialchars($prefill_email); ?>">
            </div>

            <div class="form-group">
                <label for="password">🔒 Password</label>
                <input type="password" id="password" name="password" required placeholder="Enter your password">
            </div>

            <button type="submit" class="btn btn-primary">Sign In</button>
            <button type="button" class="btn btn-secondary" onclick="window.location.href='register.php'">
                Create New Account
            </button>
        </form>

        <div class="text-center">
            <a href="index.html" class="link">← Back to Home</a>
        </div>
        <div class="text-center">
            <a href="forgotpass.php" class="link">forgot password</a>
        </div>
    </div>
</body>
</html>