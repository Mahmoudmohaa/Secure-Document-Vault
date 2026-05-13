<?php

require_once 'includes/db.php';
require_once 'includes/totp.php';

// 1. Verify user authentication using JWT

if (!isset($_COOKIE['jwt_token'])) {

    die("Please <a href='login.php'>Login</a> first to setup 2FA.");
}

$jwt = $_COOKIE['jwt_token'];

$payload = json_decode(
    base64_decode(
        str_replace(
            ['-', '_'],
            ['+', '/'],
            explode('.', $jwt)[1]
        )
    ),
    true
);

$user_id = $payload['user_id'];

$message = '';

// Retrieve authenticated user information

$stmt = $pdo->prepare(
    "SELECT username, email, two_factor_secret
     FROM users
     WHERE id = ?"
);

$stmt->execute([$user_id]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

// If the user does not already have a secret key,
// generate and store one

if (empty($user['two_factor_secret'])) {

    $secret = SimpleTOTP::generateSecret();

    $update_stmt = $pdo->prepare(
        "UPDATE users
         SET two_factor_secret = ?
         WHERE id = ?"
    );

    $update_stmt->execute([$secret, $user_id]);

    $user['two_factor_secret'] = $secret;
}

$secret = $user['two_factor_secret'];

$appName = urlencode("SecureVault");

$email = urlencode($user['email']);

// Generate QR Code URL for Google Authenticator setup

$qrCodeUrl =
"https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=otpauth://totp/{$appName}:{$email}?secret={$secret}&issuer={$appName}";

// Verify submitted 2FA code

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['auth_code'])) {

    $code = trim($_POST['auth_code']);

    if (SimpleTOTP::verifyCode($secret, $code)) {

        $message =
        "<div class='success'>
            ✅ 2FA Verified Successfully!
            Your account is now highly secure.
        </div>";

    } else {

        $message =
        "<div class='error'>
            ❌ Invalid Code. Please try again.
        </div>";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">

    <title>Setup 2FA - Secure Vault</title>

    <style>

        body {
            font-family: Arial;
            background-color: #f4f4f9;
            display: flex;
            justify-content: center;
            padding-top: 50px;
        }

        .container {
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 400px;
        }

        img {
            margin: 20px 0;
            border: 1px solid #ccc;
            padding: 10px;
        }

        input[type="text"] {
            width: 80%;
            padding: 10px;
            margin-bottom: 10px;
            font-size: 18px;
            text-align: center;
            letter-spacing: 2px;
        }

        button {
            padding: 10px 20px;
            background-color: #28a745;
            color: white;
            border: none;
            cursor: pointer;
        }

        .success {
            color: green;
            margin-bottom: 15px;
            font-weight: bold;
        }

        .error {
            color: red;
            margin-bottom: 15px;
            font-weight: bold;
        }

        .secret-text {
            background: #eee;
            padding: 5px;
            font-family: monospace;
            letter-spacing: 1px;
        }

    </style>

</head>

<body>

<div class="container">

    <h2>Secure Your Account</h2>

    <p>
        1. Download <strong>Google Authenticator</strong> on your phone.
    </p>

    <p>
        2. Scan the QR Code below:
    </p>

    <img src="<?php echo $qrCodeUrl; ?>" alt="QR Code">

    <p>
        Or enter this secret manually:
        <br>
        <span class="secret-text">
            <?php echo $secret; ?>
        </span>
    </p>

    <?php echo $message; ?>

    <form method="POST">

        <p>
            3. Enter the 6-digit code from the app:
        </p>

        <input
            type="text"
            name="auth_code"
            maxlength="6"
            required
            autocomplete="off"
            placeholder="123456"
        >

        <br>

        <button type="submit">
            Verify 2FA
        </button>

    </form>

    <br>

    <a href="dashboard.php">
        Go to Dashboard
    </a>

</div>

</body>
</html>