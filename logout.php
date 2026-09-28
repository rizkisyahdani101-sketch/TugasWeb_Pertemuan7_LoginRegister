<?php
/**
 * Sistem Login/Register PHP Native — Tugas Rutin 7
 *
 * Logout (requirement #8): hapus semua data session, hancurkan session
 * (session_destroy), hapus cookie remember-me, lalu redirect ke login
 * dengan pesan konfirmasi.
 */

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

// Hapus cookie remember-me (beserta jejak token di users.json).
delete_remember_cookie();

// Kosongkan isi $_SESSION, lalu hancurkan session-nya.
$_SESSION = [];
session_destroy();

// Hapus juga cookie session dari browser agar benar-benar bersih.
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires'  => time() - 42000,
        'path'     => $params['path'],
        'domain'   => $params['domain'],
        'secure'   => (bool) $params['secure'],
        'httponly' => true,
        'samesite' => $params['samesite'] ?: 'Lax',
    ]);
}

// Redirect ke halaman login dengan pesan sukses.
header('Location: login.php?logout=1');
exit;
