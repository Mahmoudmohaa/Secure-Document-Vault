<?php

require_once 'includes/db.php';

// Load security keys from environment variables

$jwt_secret = $_ENV['JWT_SECRET'];

$encryption_key = hash(
    'sha256',
    $_ENV['ENCRYPTION_KEY'],
    true
);

// 1. Validate JWT authentication and document ID

if (
    !isset($_COOKIE['jwt_token']) ||
    !isset($_GET['id'])
) {

    die("Unauthorized access or missing document ID.");
}

$jwt = $_COOKIE['jwt_token'];

$token_parts = explode('.', $jwt);

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

$user_id = $payload['user_id'];

$doc_id = (int)$_GET['id'];

// 2. Retrieve document information from the database
// to verify ownership

$stmt = $pdo->prepare(
    "SELECT *
     FROM documents
     WHERE id = ?
     AND user_id = ?"
);

$stmt->execute([$doc_id, $user_id]);

$doc = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$doc) {

    die(
        "Document not found or you don't have permission to access it."
    );
}

$file_path = 'uploads/' . $doc['encrypted_filename'];

if (!file_exists($file_path)) {

    die(
        "Error: The encrypted file is missing from the server."
    );
}

// 3. Read encrypted file and separate IV from encrypted content

$encrypted_data = base64_decode(
    file_get_contents($file_path)
);

$iv_length = openssl_cipher_iv_length('aes-256-cbc');

$iv = substr($encrypted_data, 0, $iv_length);

$encrypted_contents = substr($encrypted_data, $iv_length);

// 4. Decrypt file contents

$decrypted_contents = openssl_decrypt(
    $encrypted_contents,
    'aes-256-cbc',
    $encryption_key,
    0,
    $iv
);

if ($decrypted_contents === false) {

    die(
        "SECURITY ALERT:
         Decryption failed.
         The file may be corrupted or the key is invalid."
    );
}

// 5. Verify file integrity using SHA-256 hash

$calculated_hash = hash(
    'sha256',
    $decrypted_contents
);

if (!hash_equals($doc['file_hash'], $calculated_hash)) {

    die(
        "SECURITY ALERT:
         File integrity verification failed.
         The document may have been tampered with."
    );
}

// 6. Verify digital signature authenticity

$calculated_signature = hash_hmac(
    'sha256',
    $calculated_hash,
    $jwt_secret
);

if (
    !hash_equals(
        $doc['digital_signature'],
        $calculated_signature
    )
) {

    die(
        "SECURITY ALERT:
         Invalid digital signature detected.
         File authenticity cannot be verified."
    );
}

// 7. If all security checks pass,
// send the decrypted file to the user

header('Content-Type: application/octet-stream');

header(
    'Content-Disposition: attachment; filename="' .
    $doc['original_filename'] .
    '"'
);

header(
    'Content-Length: ' .
    strlen($decrypted_contents)
);

// Send decrypted content to the browser

echo $decrypted_contents;

exit;

?>