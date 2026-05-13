<?php
require_once 'includes/db.php';

// Load security keys from environment variables
$jwt_secret = $_ENV['JWT_SECRET'];
$encryption_key = hash('sha256', $_ENV['ENCRYPTION_KEY'], true); 

$message = '';

// 1. Verify JWT token
if (!isset($_COOKIE['jwt_token'])) {
    die("Unauthorized access! Please <a href='login.php'>Login</a> first.");
}

$jwt = $_COOKIE['jwt_token'];
$token_parts = explode('.', $jwt);

if (count($token_parts) !== 3) {
    die("Invalid Token.");
}

$payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $token_parts[1])), true);

if ($payload['exp'] < time()) {
    setcookie("jwt_token", "", time() - 3600, "/");
    die("Session expired. Please <a href='login.php'>Login</a> again.");
}

$user_id = $payload['user_id'];

// 2. Handle Document Deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_id'])) {
    $delete_id = (int)$_POST['delete_id'];
    
    $stmt = $pdo->prepare("SELECT encrypted_filename FROM documents WHERE id = ? AND user_id = ?");
    $stmt->execute([$delete_id, $user_id]);
    $docToDelete = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($docToDelete) {
        $file_path = 'uploads/' . $docToDelete['encrypted_filename'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
        $del_stmt = $pdo->prepare("DELETE FROM documents WHERE id = ?");
        if ($del_stmt->execute([$delete_id])) {
            $message = "<div class='success'>Document deleted successfully!</div>";
        }
    } else {
        $message = "<div class='error'>Unauthorized action or document not found.</div>";
    }
}

// 3. Handle Document Upload & Encryption
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['document'])) {
    $file = $_FILES['document'];
    $max_size = 5 * 1024 * 1024;
    $allowed_types = ['application/pdf', 'text/plain', 'application/msword'];
    
    if ($file['size'] > $max_size) {
        $message = "<div class='error'>File is too large. Max 5MB allowed.</div>";
    } elseif (!in_array($file['type'], $allowed_types)) {
        $message = "<div class='error'>Invalid file type. Only PDF, TXT, and DOC allowed.</div>";
    } elseif ($file['error'] === UPLOAD_ERR_OK) {
        $original_filename = basename($file['name']);
        $file_contents = file_get_contents($file['tmp_name']);
        
        $file_hash = hash('sha256', $file_contents);
        $digital_signature = hash_hmac('sha256', $file_hash, $jwt_secret);
        
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted_contents = openssl_encrypt($file_contents, 'aes-256-cbc', $encryption_key, 0, $iv);
        $final_encrypted_data = base64_encode($iv . $encrypted_contents);
        
        $encrypted_filename = uniqid('vault_') . '.enc';
        $upload_path = 'uploads/' . $encrypted_filename;
        
        if (file_put_contents($upload_path, $final_encrypted_data)) {
            $stmt = $pdo->prepare("INSERT INTO documents (user_id, original_filename, encrypted_filename, file_hash, digital_signature) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$user_id, $original_filename, $encrypted_filename, $file_hash, $digital_signature])) {
                $message = "<div class='success'>File uploaded, encrypted, and signed successfully!</div>";
            }
        }
    }
}

// 4. Fetch all user documents
$stmt = $pdo->prepare("SELECT id, original_filename, file_hash, uploaded_at FROM documents WHERE user_id = ? ORDER BY uploaded_at DESC");
$stmt->execute([$user_id]);
$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Secure Document Vault</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; padding: 20px; }
        .dashboard-container { max-width: 800px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        h2, h3 { color: #333; }
        .upload-form { margin-top: 20px; padding: 20px; border: 2px dashed #ccc; border-radius: 8px; text-align: center; background-color: #fafafa; }
        input[type="file"] { margin: 15px 0; }
        button { padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; color: white; }
        .btn-upload { background-color: #007bff; }
        .btn-upload:hover { background-color: #0056b3; }
        .btn-download { background-color: #28a745; text-decoration: none; padding: 6px 12px; border-radius: 4px; color: white; font-size: 14px; }
        .btn-delete { background-color: #dc3545; }
        .btn-delete:hover { background-color: #c82333; }
        .error { color: red; margin-bottom: 15px; font-weight: bold; text-align: center; }
        .success { color: green; margin-bottom: 15px; font-weight: bold; text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 14px; }
        th, td { padding: 12px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #f8f9fa; }
        .hash-text { font-family: monospace; font-size: 11px; word-break: break-all; color: #555; }
    </style>
</head>
<body>

<div class="dashboard-container">
    <h2>Welcome to your Secure Vault</h2>

    <div style="text-align: right; margin-bottom: 15px;">
        <?php if ($payload['role'] === 'admin'): ?>
            <a href="admin.php" style="background-color: #17a2b8; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; margin-right: 10px;">Admin Panel</a>
        <?php endif; ?>
        <a href="setup_2fa.php" style="background-color: #ffc107; color: #333; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-weight: bold;">Setup 2FA</a>
        <a href="logout.php" style="background-color: #6c757d; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; margin-left: 10px;">Logout</a>
    </div>

    <p>Upload your sensitive documents here. Files will be encrypted before storage.</p>
    
    <?php echo $message; ?>

    <div class="upload-form">
        <form action="dashboard.php" method="POST" enctype="multipart/form-data">
            <label for="document"><strong>Select File to Upload (Max 5MB, PDF/TXT/DOC):</strong></label><br>
            <input type="file" name="document" id="document" required><br>
            <button type="submit" class="btn-upload">Encrypt & Upload</button>
        </form>
    </div>

    <hr style="margin: 30px 0; border: 1px solid #eee;">

    <h3>Your Uploaded Documents</h3>
    <?php if (count($documents) > 0): ?>
        <table>
            <tr>
                <th>Filename</th>
                <th>Upload Date</th>
                <th>SHA-256 Hash (Metadata)</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($documents as $doc): ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($doc['original_filename']); ?></strong></td>
                <td><?php echo $doc['uploaded_at']; ?></td>
                <td class="hash-text"><?php echo $doc['file_hash']; ?></td>
                <td style="text-align: center; min-width: 150px;">
                    <a href="download.php?id=<?php echo $doc['id']; ?>" class="btn-download" target="_blank">Download</a>
                    <form action="dashboard.php" method="POST" style="display:inline-block; margin-left: 5px;" onsubmit="return confirm('Are you sure you want to permanently delete this document?');">
                        <input type="hidden" name="delete_id" value="<?php echo $doc['id']; ?>">
                        <button type="submit" class="btn-delete">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p style="text-align: center; color: #777;">No documents uploaded yet.</p>
    <?php endif; ?>
</div>

</body>
</html>