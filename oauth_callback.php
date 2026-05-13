<?php

require_once 'includes/db.php';

$client_id = $_ENV['GITHUB_CLIENT_ID'];
$client_secret = $_ENV['GITHUB_CLIENT_SECRET'];
$jwt_secret = $_ENV['JWT_SECRET'];

if (isset($_GET['code'])) {

    $code = $_GET['code'];

    // 1. Exchange authorization code for access token

    $post_params = [
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'code' => $code
    ];

    $ch = curl_init('https://github.com/login/oauth/access_token');

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_params);

    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json'
    ]);

    $response = curl_exec($ch);

    curl_close($ch);

    $data = json_decode($response, true);

    if (isset($data['access_token'])) {

        $access_token = $data['access_token'];

        // 2. Retrieve GitHub user information

        $ch = curl_init('https://api.github.com/user');

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_HTTPHEADER, [

            'Accept: application/json',
            'Authorization: Bearer ' . $access_token,

            // GitHub requires the User-Agent header

            'User-Agent: SecureVault-App'
        ]);

        $user_response = curl_exec($ch);

        curl_close($ch);

        $github_user = json_decode($user_response, true);

        $github_username = $github_user['login'];

        // GitHub email may be null if hidden by the user

        $github_email = $github_user['email'];

        // 3. Check if the user already exists in the database

        $stmt = $pdo->prepare(
            "SELECT * FROM users WHERE username = ? OR email = ?"
        );

        $stmt->execute([$github_username, $github_email]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {

            // If the user is new,
            // create an account with a random hashed password

            $random_password = password_hash(
                bin2hex(random_bytes(10)),
                PASSWORD_BCRYPT
            );

            $insert_stmt = $pdo->prepare(
                "INSERT INTO users (username, email, password_hash)
                 VALUES (?, ?, ?)"
            );

            $insert_stmt->execute([
                $github_username,
                $github_email
                    ? $github_email
                    : $github_username . '@github.local',
                $random_password
            ]);

            $user_id = $pdo->lastInsertId();

            $role = 'user';

        } else {

            $user_id = $user['id'];
            $role = $user['role'];
        }

        // 4. Generate JWT
        // Same logic used in login.php

        $header = json_encode([
            'typ' => 'JWT',
            'alg' => 'HS256'
        ]);

        $payload = json_encode([
            'user_id' => $user_id,
            'role' => $role,
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

        // 5. Store JWT and redirect user to dashboard

        setcookie(
            "jwt_token",
            $jwt,
            time() + (60 * 60),
            "/",
            "",
            false,
            true
        );

        header("Location: dashboard.php");

        exit;

    } else {

        die("OAuth Failed: Could not retrieve access token.");
    }

} else {

    die("OAuth Failed: No code provided.");
}

?>