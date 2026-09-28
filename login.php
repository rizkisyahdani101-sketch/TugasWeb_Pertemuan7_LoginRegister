<?php
/**
 * Sistem Login/Register PHP Native — Tugas Rutin 7
 *
 * Halaman login: verifikasi password dengan password_verify(),
 * simpan status login ke session (#6), dukung Remember Me (bonus).
 */

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

// Sudah login? langsung ke dashboard.
if (current_user_id() === null && attempt_remember_login()) {
    header('Location: dashboard.php');
    exit;
}
if (current_user_id() !== null) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];
$old   = ['email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $email    = strtolower(post('email'));
    $password = (string) ($_POST['password'] ?? '');
    $remember = isset($_POST['remember']); // checkbox Remember Me (bonus)

    $users = load_users();

    if ($email === '') {
        $errors['email'] = 'Email wajib diisi.';
    } elseif (!is_valid_email($email)) {
        $errors['email'] = 'Format email tidak valid.';
    }

    if ($password === '') {
        $errors['password'] = 'Password wajib diisi.';
    }

    if ($errors === []) {
        $found = find_user_by_email($users, $email);

        if ($found !== null && password_verify($password, (string) $found['password'])) {
            login_user($found); // session_regenerate_id + isi $_SESSION (#6)

            if ($remember) {
                create_remember_cookie((int) $found['id']);
            } else {
                delete_remember_cookie();
            }

            set_flash('success', 'Login berhasil. Selamat datang, ' . $found['name'] . '!');
            header('Location: dashboard.php');
            exit;
        }

        $errors['general'] = 'Email atau password salah.';
    }

    $old['email'] = $email;
}

$flash = take_flash();

// Parameter ?logout=1 dari logout.php: tampilkan pesan konfirmasi logout.
$justLoggedOut = isset($_GET['logout']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <main class="container">
        <section class="card">
            <header class="card-head">
                <h1>Selamat Datang Kembali</h1>
                <p>Masuk menggunakan email dan password Anda.</p>
            </header>

            <?php if ($flash): ?>
                <div class="alert <?= $flash['type'] === 'success' ? 'alert-success' : 'alert-error' ?>">
                    <?= e($flash['message']) ?>
                </div>
            <?php endif; ?>

            <?php if ($justLoggedOut): ?>
                <div class="alert alert-info">Anda telah berhasil logout. Sampai jumpa!</div>
            <?php endif; ?>

            <?php if (isset($errors['general'])): ?>
                <div class="alert alert-error"><?= e($errors['general']) ?></div>
            <?php endif; ?>

            <form method="POST" action="login.php" novalidate>
                <?= csrf_field() ?>

                <div class="field">
                    <label for="email">Email</label>
                    <input
                        type="email" id="email" name="email"
                        value="<?= e($old['email']) ?>"
                        placeholder="nama@contoh.com"
                        required>
                    <?php if (isset($errors['email'])): ?>
                        <small class="field-error"><?= e($errors['email']) ?></small>
                    <?php endif; ?>
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <input
                        type="password" id="password" name="password"
                        placeholder="Password Anda"
                        required>
                    <?php if (isset($errors['password'])): ?>
                        <small class="field-error"><?= e($errors['password']) ?></small>
                    <?php endif; ?>
                </div>

                <div class="field field-inline">
                    <label class="checkbox">
                        <input type="checkbox" name="remember" value="1">
                        Ingat saya di perangkat ini
                    </label>
                </div>

                <button type="submit" class="btn btn-primary">Login</button>
            </form>

            <footer class="card-foot">
                <p>Belum punya akun? <a href="register.php">Daftar sekarang</a></p>
            </footer>
        </section>
    </main>
</body>
</html>
