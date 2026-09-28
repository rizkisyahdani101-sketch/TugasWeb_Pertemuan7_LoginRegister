<?php
/**
 * Sistem Login/Register PHP Native — Tugas Rutin 7
 *
 * Dashboard — halaman terproteksi (requirement #7):
 * redirect ke login.php bila belum login, menampilkan data user,
 * dan menyediakan tautan logout (#8).
 */

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

/* ---------- PROTEKSI HALAMAN ---------- */
// Pertama coba pulihkan login dari cookie remember-me (bonus),
// lalu wajibkan session login; jika keduanya tidak ada, redirect (#7).
if (current_user_id() === null) {
    attempt_remember_login();
}
require_login();

$users     = load_users();
$user      = find_user_by_id($users, (int) current_user_id());
$flash     = take_flash();

// Data user hilang dari "database"? paksa logout agar konsisten.
if ($user === null) {
    delete_remember_cookie();
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="topbar">
        <span class="brand"><?= e(APP_NAME) ?></span>
        <div class="nav-actions">
            <span class="who">Halo, <strong><?= e($user['name']) ?></strong></span>
            <a class="btn btn-ghost" href="edit_profile.php">Edit Profile</a>
            <a class="btn btn-danger" href="logout.php">Logout</a>
        </div>
    </nav>

    <main class="container">
        <?php if ($flash): ?>
            <div class="alert <?= $flash['type'] === 'success' ? 'alert-success' : 'alert-error' ?>">
                <?= e($flash['message']) ?>
            </div>
        <?php endif; ?>

        <section class="card wide">
            <header class="card-head">
                <h1>Dashboard</h1>
                <p>Halaman ini hanya dapat diakses setelah login (dilindungi session).</p>
            </header>

            <dl class="profile">
                <div class="profile-row">
                    <dt>ID User</dt>
                    <dd>#<?= e((string) $user['id']) ?></dd>
                </div>
                <div class="profile-row">
                    <dt>Nama</dt>
                    <dd><?= e($user['name']) ?></dd>
                </div>
                <div class="profile-row">
                    <dt>Email</dt>
                    <dd><?= e($user['email']) ?></dd>
                </div>
                <div class="profile-row">
                    <dt>Terdaftar Sejak</dt>
                    <dd><?= e((string) $user['created_at']) ?></dd>
                </div>
                <div class="profile-row">
                    <dt>Status Session</dt>
                    <dd>Aktif — login via session <code><?= e(session_id()) ?></code></dd>
                </div>
            </dl>

            <div class="hint">
                <p><strong>Catatan:</strong> Semua nilai di atas ditampilkan melalui
                <code>htmlspecialchars()</code> agar aman dari serangan XSS (#9).</p>
            </div>
        </section>
    </main>
</body>
</html>
