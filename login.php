<?php

// Load database connection
// (db.php automatically loads env_loader.php)

require_once 'includes/db.php';

// Load security keys from environment variables

$jwt_secret = $_ENV['JWT_SECRET'];
$github_client_id = $_ENV['GITHUB_CLIENT_ID'];

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $username_or_email = trim($_POST['username_or_email']);
    $password = $_POST['password'];

    if (empty($username_or_email) || empty($password)) {

        $error = "Please enter username/email and password.";
    }

    else {

        // 1. Search for the user in the database

        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username_or_email, $username_or_email]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // 2. Verify password validity

        if ($user && password_verify($password, $user['password_hash'])) {

            // 3. Generate JWT (JSON Web Token)

            $header = json_encode([
                'typ' => 'JWT',
                'alg' => 'HS256'
            ]);

            $payload = json_encode([
                'user_id' => $user['id'],
                'role' => $user['role'],

                // Token expires after one hour

                'exp' => time() + (60 * 60)
            ]);

            $base64UrlHeader = str_replace(
                ['+', '/', '='],
                ['-', '_', ''],
                base64_encode($header)
            );

            $base64UrlPayload = str_replace(
                ['+', '/', '='],
                ['-', '_', ''],
                base64_encode($payload)
            );

            $signature = hash_hmac(
                'sha256',
                $base64UrlHeader . "." . $base64UrlPayload,
                $jwt_secret,
                true
            );

            $base64UrlSignature = str_replace(
                ['+', '/', '='],
                ['-', '_', ''],
                base64_encode($signature)
            );

            $jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;

            // 4. Store JWT securely inside an HttpOnly cookie

            setcookie(
                "jwt_token",
                $jwt,
                time() + (60 * 60),
                "/",
                "",
                false,
                true
            );

            // 5. Redirect user to dashboard after successful login

            header("Location: dashboard.php");

            // exit() is important after redirects
            // to prevent further code execution

            exit;

        } else {

            $error = "Invalid credentials. Please try again.";
        }
    }
}

// Generate GitHub OAuth login URL

$github_redirect_url = 'http://localhost/SecureVault/oauth_callback.php';

$github_auth_url =
"https://github.com/login/oauth/authorize?client_id=$github_client_id&redirect_uri=$github_redirect_url&scope=user:email";

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Secure Document Vault</title>

    <style>

        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .login-container {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            width: 350px;
        }

        h2 {
            text-align: center;
            color: #333;
        }

        input[type="text"],
        input[type="password"] {

            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }

        button {

            width: 100%;
            padding: 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-bottom: 10px;
        }

        button:hover {
            background-color: #0056b3;
        }

        .error {
            color: red;
            font-size: 14px;
            text-align: center;
            margin-bottom: 10px;
        }

        .github-btn {

            display: block;
            text-align: center;
            padding: 10px;
            background-color: #333;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        .github-btn:hover {
            background-color: #111;
        }

        .divider {

            text-align: center;
            margin: 15px 0;
            color: #777;
            position: relative;
        }

        .divider::before,
        .divider::after {

            content: "";
            position: absolute;
            top: 50%;
            width: 40%;
            height: 1px;
            background-color: #ddd;
        }

        .divider::before {
            left: 0;
        }

        .divider::after {
            right: 0;
        }

    </style>
</head>

<body>

<div class="login-container">

    <h2>Login</h2>

    <?php if ($error): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>

    <form action="login.php" method="POST">

        <label for="username_or_email">Username or Email:</label>

        <input
            type="text"
            id="username_or_email"
            name="username_or_email"
            required
            autocomplete="username"
        >

        <label for="password">Password:</label>

        <input
            type="password"
            id="password"
            name="password"
            required
            autocomplete="current-password"
        >

        <button type="submit">Login with Password</button>

    </form>

    <div class="divider">Or</div>

    <a
        href="<?php echo htmlspecialchars($github_auth_url); ?>"
        class="github-btn"
    >
        Login with GitHub
    </a>

</div>

</body>
</html>