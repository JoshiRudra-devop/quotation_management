<?php
require_once __DIR__ . '/config.php';

$message = "";
$messageType = "";

// Handle Password Reset
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!csrf_validate($_POST['csrf_token'] ?? '')) {
        $message = "Invalid request. Please refresh and try again.";
        $messageType = "error";
    } else {
        $email = trim($_POST['email'] ?? '');
        $new_password = trim($_POST['new_password'] ?? '');
        $confirm_password = trim($_POST['confirm_password'] ?? '');

        if ($new_password !== $confirm_password) {
            $message = "Passwords do not match!";
            $messageType = "error";
        } elseif (strlen($new_password) < 8) {
            $message = "Password must be at least 8 characters long!";
            $messageType = "error";
        } else {
            $con = db_connect();

            // Check if email exists (matches the real users schema: user_id/user_name, not id/username)
            $stmt = $con->prepare("SELECT user_id, user_name FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                $stmt_update = $con->prepare("UPDATE users SET password = ? WHERE email = ?");
                $stmt_update->bind_param("ss", $hashed_password, $email);

                if ($stmt_update->execute()) {
                    $stmt_update->close();
                    $stmt->close();
                    $con->close();
                    echo "<script>
                        alert('Password reset successful! Please login with your new password.');
                        window.location.href='login.php';
                    </script>";
                    exit();
                } else {
                    $message = "Failed to reset password. Please try again.";
                    $messageType = "error";
                }
                $stmt_update->close();
            } else {
                $message = "Email not found in our system!";
                $messageType = "error";
            }
            $stmt->close();
            $con->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<link rel="icon" type="image/png" href="logo-new.png">
<link rel="manifest" href="manifest.json">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="theme.css?v=<?php echo time(); ?>">
    <script>
        (function() {
            const theme = localStorage.getItem('theme') || 'dark';
            if (theme === 'light') {
                document.documentElement.classList.add('light-mode');
            }
        })();
    </script>
    <style>
        :root {
            --teal: #2dd4bf;
            --teal-hover: #14b8a6;
            --teal-glow: rgba(45, 212, 191, 0.3);
            --teal-border: rgba(45, 212, 191, 0.15);
            --card-gradient: linear-gradient(135deg, #1a1a1a 0%, #0f1f1f 100%);
            --bg-gradient: radial-gradient(circle at top, #103331 0%, #050807 70%);
            --text: #f0fdfa;
            --text-muted: #94a3b8;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Arial", sans-serif;
            background: var(--bg-gradient);
            color: var(--text);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            box-shadow: var(--shadow-card, 0 15px 50px var(--teal-glow));
            border: 2px solid var(--teal);
            background: var(--card-gradient);
            border-radius: 16px;
            padding: 40px 30px;
            max-width: 400px;
            width: 100%;
            animation: fadeIn 1s ease;
            transition: transform 0.3s ease;
        }

        .container:hover {
            transform: translateY(-8px);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        h2 {
            text-align: center;
            margin-bottom: 30px;
            background: linear-gradient(135deg, var(--text), var(--teal));
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 2rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-muted);
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid var(--teal-border);
            background-color: rgba(0, 0, 0, 0.25);
            color: var(--text);
            font-size: 15px;
            transition: border-color 0.3s ease;
        }

        input:focus {
            border-color: var(--teal);
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
            transition: background-color 0.3s ease;
            border: none;
        }

        .btn-primary {
            background-color: var(--teal);
            color: #000;
        }

        .btn-primary:hover {
            background-color: var(--teal-hover);
        }

        .btn-secondary {
            background-color: rgba(255, 255, 255, 0.08);
            color: var(--text);
            border: 1px solid var(--teal-border);
        }

        .btn-secondary:hover {
            background-color: rgba(255, 255, 255, 0.14);
        }

        .btn-link {
            background: none;
            border: none;
            color: var(--teal);
            text-decoration: underline;
            font-size: 14px;
            cursor: pointer;
            margin-top: 15px;
            width: auto;
            padding: 0;
        }

        .btn-link:hover {
            color: var(--teal-hover);
        }

        .text-center {
            text-align: center;
        }

        .message {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
        }

        .message.success {
            background-color: #10b981;
            color: white;
        }

        .message.error {
            background-color: #f87171;
            color: white;
        }

        .info-text {
            text-align: center;
            color: var(--text-muted);
            margin-bottom: 25px;
            font-size: 14px;
        }

        @media (max-width: 480px) {
            .container {
                padding: 30px 20px;
                box-shadow: 0 5px 15px rgba(255, 255, 255, 0.2);
                border-radius: 12px;
                width: 95%;
            }
            h2 {
                font-size: 1.6rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2><?php echo icon('lock', 22); ?> Reset Password</h2>
        <p class="info-text">Enter your email and create a new password.</p>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
            <div class="form-group">
                <label for="email">📧 Email Address:</label>
                <input type="email" id="email" name="email" required placeholder="Enter your registered email">
            </div>

            <div class="form-group">
                <label for="new_password">🔒 New Password:</label>
                <input type="password" id="new_password" name="new_password" required placeholder="Enter new password">
            </div>

            <div class="form-group">
                <label for="confirm_password">🔒 Confirm Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" required placeholder="Re-enter new password">
            </div>

            <button type="submit" class="btn btn-primary">Reset Password</button>
            <button type="button" class="btn btn-secondary" onclick="window.location.href='login.php'">Back to Login</button>
            
            <div class="text-center">
                <button type="button" class="btn-link" onclick="window.location.href='index.html'">Cancel & Return to Home</button>
            </div>
        </form>
    </div>
</body>
</html>