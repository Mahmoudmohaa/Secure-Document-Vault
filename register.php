<?php

// Load database connection

require_once 'includes/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // 1. Validate that all fields are filled

    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {

        $error = "Please fill in all fields.";
    }

    // 2. Verify password confirmation match

    elseif ($password !== $confirm_password) {

        $error = "Passwords do not match.";
    }

    // 3. Enforce password policy
    // Password must contain:
    // - Minimum 8 characters
    // - Uppercase letter
    // - Lowercase letter
    // - Number
    // - Special character

    elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $password)) {

        $error = "Password must be at least 8 characters long and include an uppercase letter, a lowercase letter, a number, and a special character.";
    }

    else {

        // Verify username/email uniqueness

        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);

        if ($stmt->rowCount() > 0) {

            $error = "Username or Email already exists.";
        }

        else {

            // 4. Hash password using bcrypt

            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            // 5. Insert new user securely into the database

            $insert_stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");

            if ($insert_stmt->execute([$username, $email, $hashed_password])) {

                $success = "Registration successful! You can now login.";

                // Optional: redirect user to login page later

            } else {

                $error = "Something went wrong. Please try again.";
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Secure Document Vault</title>

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

        .register-container {
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
        input[type="email"],
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
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }

        button:hover {
            background-color: #218838;
        }

        .error {
            color: red;
            font-size: 14px;
            text-align: center;
            margin-bottom: 10px;
        }

        .success {
            color: green;
            font-size: 14px;
            text-align: center;
            margin-bottom: 10px;
        }

    </style>
</head>

<body>

<div class="register-container">

    <h2>Create Account</h2>

    <?php if ($error): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="success"><?php echo $success; ?></div>
    <?php endif; ?>

    <form action="register.php" method="POST">

        <label for="username">Username:</label>

        <input type="text" id="username" name="username" required>

        <label for="email">Email:</label>

        <input type="email" id="email" name="email" required>

        <label for="password">Password:</label>

        <input type="password" id="password" name="password" required>

        <label for="confirm_password">Confirm Password:</label>

        <input type="password" id="confirm_password" name="confirm_password" required>

        <button type="submit">Register</button>

    </form>

</div>

</body>
</html>