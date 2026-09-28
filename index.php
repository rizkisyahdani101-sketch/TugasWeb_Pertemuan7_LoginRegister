<?php
/**
 * Sistem Login/Register PHP Native — Tugas Rutin 7
 *
 * Halaman depan: sambutan publik dengan tautan menuju login dan register.
 */

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

// Jika sudah login (atau punya remember-me yang sah), langsung ke dashboard.
if (current_user_id() === null && attempt_remember_login()) {
    header('Location: dashboard.php');
    exit;
}
if (current_user_id() !== null) {
    header('Location: dashboard.php');
    exit;
}

$flash = take_flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <main class="container">
        <section class="card">
            <header class="card-head">
                <h1><?= e(APP_NAME) ?></h1>
                <p>Tugas Rutin 7 — Pemrograman Web (Pertemuan 7: Session &amp; Cookies)</p>
            </header>

            <?php if ($flash): ?>
                <div class="alert <?= $flash['type'] === 'success' ? 'alert-success' : 'alert-error' ?>">
                    <?= e($flash['message']) ?>
                </div>
            <?php endif; ?>

            <p class="welcome-text">
                Sistem autentikasi sederhana berbasis <strong>PHP Native</strong> dengan
                file <strong>JSON</strong> sebagai penyimpanan data. Mendaftar, login,
                dan data Anda disimpan dengan password ter-hash.
            </p>

            <div class="actions center">
                <a class="btn btn-primary" href="register.php">Daftar Akun Baru</a>
                <a class="btn btn-ghost" href="login.php">Login</a>
            </div>
        </section>
    </main>
</body>
</html>
