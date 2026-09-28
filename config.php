<?php
/**
 * Sistem Login/Register PHP Native — Tugas Rutin 7
 * Pemrograman Web — Pertemuan 7: Server-Side PHP (Form Handling, Session & Cookies)
 *
 * File konfigurasi utama: pengaturan aplikasi, sesi yang aman (httponly + samesite),
 * dan lokasi "database" file JSON.
 */

declare(strict_types=1);

/* ================= KONFIGURASI APLIKASI ================= */

define('APP_NAME', 'Sistem Login/Register PHP');

// "Database" aplikasi: file JSON (requirement #4)
define('DATA_FILE', __DIR__ . '/users.json');

// Cookie Remember Me (bonus)
define('REMEMBER_COOKIE', 'remember_me');
define('REMEMBER_DURATION', 60 * 60 * 24 * 7); // 7 hari (detik)

/* ================= SESI YANG AMAN =================
 * Dipanggil sebelum session_start():
 * - httponly : cookie session tidak dapat dibaca JavaScript (mitigasi XSS)
 * - samesite : Lax memitigasi serangan CSRF lintas situs
 * - secure   : aktifkan (true) jika sudah berjalan di HTTPS
 */
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,        // session cookie: hilang saat browser ditutup
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        // 'secure' => true,
    ]);
    session_name('LOGINREGSESSID');
    session_start();
}
