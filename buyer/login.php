<?php
/**
 * Buyer Portal - Login Page
 * Clean, simple login form
 */

require_once __DIR__ . '/BuyerAuth.php';
$auth = new BuyerAuth();

// Already logged in?
if ($auth->getCurrentBuyer()) {
    header('Location: /buyer/');
    exit;
}

$error = '';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($auth->login($email, $password)) {
        header('Location: /buyer/');
        exit;
    } else {
        $error = 'Invalid email or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buyer Portal - Login</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #1e3a5f 0%, #0f1f33 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-box {
            background: white;
            border-radius: 12px;
            padding: 40px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo h1 {
            font-size: 24px;
            color: #1e3a5f;
        }
        .logo p {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            font-weight: 500;
            margin-bottom: 8px;
            color: #333;
        }
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.2s;
        }
        input:focus {
            outline: none;
            border-color: #1e3a5f;
        }
        .error {
            background: #fee;
            color: #c00;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        button {
            width: 100%;
            padding: 14px;
            background: #1e3a5f;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        button:hover {
            background: #2a4a75;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 13px;
            color: #888;
        }
        .footer a {
            color: #1e3a5f;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="logo">
            <h1>Buyer Portal</h1>
            <p>Access your leads</p>
        </div>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       autocomplete="email">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required
                       autocomplete="current-password">
            </div>

            <button type="submit">Sign In</button>
        </form>

        <div class="footer">
            <a href="/buyer/forgot-password.php">Forgot password?</a>
        </div>
    </div>
</body>
</html>
