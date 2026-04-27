<?php
$key = openssl_random_pseudo_bytes(32);

$key = bin2hex(openssl_random_pseudo_bytes(32)); // Generates a 64-char hex string (32 bytes)
echo $key; // Example output: "a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6"