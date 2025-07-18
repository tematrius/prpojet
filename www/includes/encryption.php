<?php 
function encrypt_file($data, $key) {
    $key = hash('sha256', $key, true);
    $iv = random_bytes(16);
    $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    return $iv . $encrypted;
}

function decrypt_file($data, $key) {
    $key = hash('sha256', $key, true);
    $iv = substr($data, 0, 16);
    $encrypted = substr($data, 16);
    return openssl_decrypt($encrypted, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
}

function generate_key($length = 32) {
    return bin2hex(random_bytes($length));
}