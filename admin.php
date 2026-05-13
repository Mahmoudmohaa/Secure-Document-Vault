<?php

require_once 'includes/db.php';

// 1. Verify JWT token existence

if (!isset($_COOKIE['jwt_token'])) {

    die("Unauthorized access! Please <a href='login.php'>Login</a> first.");
}

$jwt = $_COOKIE['jwt_token'];

$token_parts = explode('.', $jwt);

if (count($token_parts) !== 3) {

    die("Invalid Token.");
}

// 2. Decode JWT payload to identify the user role

$payload = json_decode(
    base64_decode(
        str_replace(
            ['-', '_'],
            ['+', '/'],
            $token_parts[1]
        )
    ),
    true
);

// Verify token expiration time

if ($payload['exp'] < time()) {

    setcookie("jwt_token", "", time() - 3600, "/");

    die("Session expired. Please <a href='login.php'>Login</a> again.");
}

// 3. Strict authorization check (RBAC)

if ($payload['role'] !== 'admin') {

    // If the user is not an admin,
    // block access immediately

    header("HTTP/1.1 403 Forbidden");

    die(
        "<div style='color:red; text-align:center; margin-top:50px;'>
            <h2>403 FORBIDDEN</h2>
            <p>
                Access Denied:
                Administrator privileges required.
            </p>
            <a href='dashboard.php'>
                Return to Dashboard
            </a>
        </div>"
    );
}

$message = '';

// 4. Handle user role update request

if (
    $_SERVER['REQUEST_METHOD'] == 'POST' &&
    isset($_POST['user_id']) &&
    isset($_POST['new_role'])
) {

    $target_user_id = (int)$_POST['user_id'];

    $new_role = $_POST['new_role'];

    // Ensure submitted role is valid
    // to prevent manipulation

    $valid_roles = ['admin', 'manager', 'user'];

    if (in_array($new_role, $valid_roles)) {

        $update_stmt = $pdo->prepare(
            "UPDATE users
             SET role = ?
             WHERE id = ?"
        );

        if ($update_stmt->execute([$new_role, $target_user_id])) {

            $message =
            "<div class='success'>
                User role updated successfully!
            </div>";

        } else {

            $message =
            "<div class='error'>
                Failed to update role.
            </div>";
        }
    }
}

// 5. Retrieve all users for admin management

$stmt = $pdo->query(
    "SELECT id, username, email, role, created_at
     FROM users
     ORDER BY id DESC"
);

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Admin Panel - Secure Vault</title>

    <style>

        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            padding: 20px;
        }

        .admin-container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        h2 {
            color: #d9534f;
            border-bottom: 2px solid #d9534f;
            padding-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: center;
        }

        th {
            background-color: #f8f9fa;
        }

        select,
        button {
            padding: 5px;
        }

        button {
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 3px;
        }

        button:hover {
            background-color: #0056b3;
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

        .nav-links {
            margin-bottom: 20px;
        }

    </style>

</head>

<body>

<div class="admin-container">

    <div class="nav-links">

        <a href="dashboard.php">
            ← Back to Dashboard
        </a>

    </div>

    <h2>Admin Control Panel (RBAC)</h2>

    <p>
        Manage users and system roles.
    </p>

    <?php echo $message; ?>

    <table>

        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Email</th>
            <th>Current Role</th>
            <th>Change Role</th>
        </tr>

        <?php foreach ($users as $user): ?>

        <tr>

            <td>
                <?php echo htmlspecialchars($user['id']); ?>
            </td>

            <td>
                <?php echo htmlspecialchars($user['username']); ?>
            </td>

            <td>
                <?php echo htmlspecialchars($user['email']); ?>
            </td>

            <td>
                <strong>
                    <?php echo htmlspecialchars(strtoupper($user['role'])); ?>
                </strong>
            </td>

            <td>

                <form
                    action="admin.php"
                    method="POST"
                    style="margin: 0;"
                >

                    <input
                        type="hidden"
                        name="user_id"
                        value="<?php echo $user['id']; ?>"
                    >

                    <select name="new_role">

                        <option
                            value="user"
                            <?php echo $user['role'] == 'user' ? 'selected' : ''; ?>
                        >
                            User
                        </option>

                        <option
                            value="manager"
                            <?php echo $user['role'] == 'manager' ? 'selected' : ''; ?>
                        >
                            Manager
                        </option>

                        <option
                            value="admin"
                            <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>
                        >
                            Admin
                        </option>

                    </select>

                    <button type="submit">
                        Update
                    </button>

                </form>

            </td>

        </tr>

        <?php endforeach; ?>

    </table>

</div>

</body>
</html>