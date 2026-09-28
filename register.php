<?php
/**
 * Sistem Login/Register PHP Native — Tugas Rutin 7
 * Pemrograman Web — Pertemuan 7
 *
 * Halaman pendaftaran: form registrasi + proses (requirement #1, #3, #4, #5).
 * Pola PRG (Post/Redirect/Get): proses POST lalu redirect agar form tidak
 * terkirim ulang saat halaman di-refresh.
 */

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

// Sudah login? tidak perlu register lagi.
if (current_user_id() !== null) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];
$old   = ['name' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $name     = post('name');
    $email    = strtolower(post('email'));
    $password = (string) ($_POST['password'] ?? '');

    $users = load_users();

    /* ---------- VALIDASI (requirement #1) ---------- */

    if ($name === '') {
        $errors['name'] = 'Nama wajib diisi.';
    } elseif (mb_strlen($name) < 3) {
        $errors['name'] = 'Nama minimal 3 karakter.';
    }

    if ($email === '') {
        $errors['email'] = 'Email wajib diisi.';
    } elseif (!is_valid_email($email)) {
        $errors['email'] = 'Format email tidak valid (gunakan filter_var).';
    } elseif (find_user_by_email($users, $email) !== null) {
        $errors['email'] = 'Email sudah terdaftar, gunakan email lain.'; // cek duplikat #5
    }

    if ($password === '') {
        $errors['password'] = 'Password wajib diisi.';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password minimal 8 karakter.';
    }

    /* ---------- SIMPAN KE FILE JSON (#3, #4) ---------- */

    if ($errors === []) {
        $users[] = [
            'id'         => next_user_id($users),
            'name'       => $name,
            'email'      => $email,
            // password disimpan dalam bentuk HASH, bukan plain text (#3)
            'password'   => password_hash($password, PASSWORD_BCRYPT),
            'created_at' => date('Y-m-d H:i:s'),
        ];

        if (save_users($users)) {
            set_flash('success', 'Registrasi berhasil! Silakan login dengan akun Anda.');
            header('Location: login.php');
            exit;
        }

        $errors['general'] = 'Gagal menyimpan data. Coba lagi.';
    }

    $old['name']  = $name;
    $old['email'] = $email;
}

$flash = take_flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi — <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <main class="container">
        <section class="card">
            <header class="card-head">
                <h1>Buat Akun Baru</h1>
                <p>Isi data di bawah untuk mendaftar.</p>
            </header>

            <?php if ($flash): ?>
                <div class="alert <?= $flash['type'] === 'success' ? 'alert-success' : 'alert-error' ?>">
                    <?= e($flash['message']) ?>
                </div>
            <?php endif; ?>

            <?php if (isset($errors['general'])): ?>
                <div class="alert alert-error"><?= e($errors['general']) ?></div>
            <?php endif; ?>

            <form method="POST" action="register.php" novalidate>
                <?= csrf_field() ?>

                <div class="field">
                    <label for="name">Nama Lengkap</label>
                    <input
                        type="text" id="name" name="name"
                        value="<?= e($old['name']) ?>"
                        placeholder="Nama lengkap"
                        required minlength="3" maxlength="100">
                    <?php if (isset($errors['name'])): ?>
                        <small class="field-error"><?= e($errors['name']) ?></small>
                    <?php endif; ?>
                </div>

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
                        placeholder="Minimal 8 karakter"
                        required minlength="8">
                    <?php if (isset($errors['password'])): ?>
                        <small class="field-error"><?= e($errors['password']) ?></small>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary">Daftar</button>
            </form>

            <footer class="card-foot">
                <p>Sudah punya akun? <a href="login.php">Login di sini</a></p>
            </footer>
        </section>
    </main>
</body>
</html>
