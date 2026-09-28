<?php
/**
 * Sistem Login/Register PHP Native — Tugas Rutin 7
 *
 * Halaman edit profile (bonus): ubah nama dan email, serta ganti password
 * opsional. Mengganti password wajib memasukkan password lama.
 */

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

/* ---------- PROTEKSI HALAMAN (#7) ---------- */
if (current_user_id() === null) {
    attempt_remember_login();
}
require_login();

$users   = load_users();
$userId  = (int) current_user_id();
$user    = find_user_by_id($users, $userId);

if ($user === null) {
    header('Location: logout.php');
    exit;
}

$errors = [];
$old   = ['name' => (string) $user['name'], 'email' => (string) $user['email']];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $name         = post('name');
    $email        = strtolower(post('email'));
    $currentPass  = (string) ($_POST['current_password'] ?? '');
    $newPass      = (string) ($_POST['new_password'] ?? '');
    $confirmPass  = (string) ($_POST['confirm_password'] ?? '');

    /* ---------- VALIDASI ---------- */

    if ($name === '') {
        $errors['name'] = 'Nama wajib diisi.';
    } elseif (mb_strlen($name) < 3) {
        $errors['name'] = 'Nama minimal 3 karakter.';
    }

    if ($email === '') {
        $errors['email'] = 'Email wajib diisi.';
    } elseif (!is_valid_email($email)) {
        $errors['email'] = 'Format email tidak valid.';
    } else {
        $duplicate = find_user_by_email($users, $email);
        if ($duplicate !== null && (int) $duplicate['id'] !== $userId) {
            $errors['email'] = 'Email sudah dipakai akun lain.';
        }
    }

    $wantsPasswordChange = ($newPass !== '' || $confirmPass !== '');

    if ($wantsPasswordChange) {
        if (!password_verify($currentPass, (string) $user['password'])) {
            $errors['current_password'] = 'Password saat ini salah.';
        }
        if (strlen($newPass) < 8) {
            $errors['new_password'] = 'Password baru minimal 8 karakter.';
        } elseif ($newPass !== $confirmPass) {
            $errors['confirm_password'] = 'Konfirmasi password tidak sama.';
        }
    }

    /* ---------- SIMPAN PERUBAHAN ---------- */

    if ($errors === []) {
        foreach ($users as &$row) {
            if ((int) $row['id'] === $userId) {
                $row['name']  = $name;
                $row['email'] = $email;
                if ($wantsPasswordChange) {
                    // password baru disimpan sebagai HASH baru (#3)
                    $row['password'] = password_hash($newPass, PASSWORD_BCRYPT);
                }
                break;
            }
        }
        unset($row);

        if (save_users($users)) {
            // Perbarui session agar nama/email terbaru ikut tampil.
            $_SESSION['user_name']  = $name;
            $_SESSION['user_email'] = $email;

            set_flash('success', 'Profil berhasil diperbarui.');
            header('Location: dashboard.php');
            exit;
        }

        $errors['general'] = 'Gagal menyimpan perubahan. Coba lagi.';
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
    <title>Edit Profile — <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="topbar">
        <span class="brand"><?= e(APP_NAME) ?></span>
        <div class="nav-actions">
            <a class="btn btn-ghost" href="dashboard.php">Dashboard</a>
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
                <h1>Edit Profile</h1>
                <p>Perbarui data akun Anda. Isi bagian password hanya jika ingin menggantinya.</p>
            </header>

            <?php if (isset($errors['general'])): ?>
                <div class="alert alert-error"><?= e($errors['general']) ?></div>
            <?php endif; ?>

            <form method="POST" action="edit_profile.php" novalidate>
                <?= csrf_field() ?>

                <div class="field">
                    <label for="name">Nama Lengkap</label>
                    <input type="text" id="name" name="name" value="<?= e($old['name']) ?>" required minlength="3" maxlength="100">
                    <?php if (isset($errors['name'])): ?>
                        <small class="field-error"><?= e($errors['name']) ?></small>
                    <?php endif; ?>
                </div>

                <div class="field">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?= e($old['email']) ?>" required>
                    <?php if (isset($errors['email'])): ?>
                        <small class="field-error"><?= e($errors['email']) ?></small>
                    <?php endif; ?>
                </div>

                <h2 class="section-title">Ganti Password (opsional)</h2>

                <div class="field">
                    <label for="current_password">Password Saat Ini</label>
                    <input type="password" id="current_password" name="current_password" autocomplete="current-password">
                    <?php if (isset($errors['current_password'])): ?>
                        <small class="field-error"><?= e($errors['current_password']) ?></small>
                    <?php endif; ?>
                </div>

                <div class="field">
                    <label for="new_password">Password Baru</label>
                    <input type="password" id="new_password" name="new_password" autocomplete="new-password">
                    <?php if (isset($errors['new_password'])): ?>
                        <small class="field-error"><?= e($errors['new_password']) ?></small>
                    <?php endif; ?>
                </div>

                <div class="field">
                    <label for="confirm_password">Konfirmasi Password Baru</label>
                    <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password">
                    <?php if (isset($errors['confirm_password'])): ?>
                        <small class="field-error"><?= e($errors['confirm_password']) ?></small>
                    <?php endif; ?>
                </div>

                <div class="actions">
                    <a class="btn btn-ghost" href="dashboard.php">Batal</a>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </section>
    </main>
</body>
</html>
