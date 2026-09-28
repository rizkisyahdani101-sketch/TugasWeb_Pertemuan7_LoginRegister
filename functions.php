<?php
/**
 * Kumpulan fungsi bantu (helper) untuk sistem login/register PHP Native.
 * Semua logika bersifat reusable agar halaman tetap bersih dan mudah dibaca.
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

/* ==========================================================
 *  SANITASI & VALIDASI INPUT (requirement #2 dan #9)
 * ========================================================== */

/**
 * Sanitasi output: ubah karakter khusus HTML menjadi entitas (anti-XSS).
 * WAJIB dipakai setiap kali menampilkan data yang berasal dari user.
 * (requirement #9: sanitasi input dengan htmlspecialchars)
 */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Ambil nilai $_POST lalu bersihkan dengan trim().
 * Jangan pernah memercayai input user secara langsung.
 */
function post(string $key): string
{
    return trim((string) ($_POST[$key] ?? ''));
}

/**
 * Validasi format email dengan filter_var() (requirement #2).
 */
function is_valid_email(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/* ==========================================================
 *  "DATABASE" FILE JSON (requirement #4)
 * ========================================================== */

/** Baca seluruh data user dari file JSON. */
function load_users(): array
{
    if (!is_file(DATA_FILE)) {
        return [];
    }

    $data = json_decode((string) file_get_contents(DATA_FILE), true);

    return is_array($data) ? $data : [];
}

/** Simpan seluruh data user ke file JSON (pretty print + LOCK_EX agar aman). */
function save_users(array $users): bool
{
    $json = json_encode(
        $users,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    return file_put_contents(DATA_FILE, (string) $json, LOCK_EX) !== false;
}

/** Hitung id berikutnya (id tertinggi + 1). */
function next_user_id(array $users): int
{
    $max = 0;
    foreach ($users as $user) {
        if (isset($user['id']) && (int) $user['id'] > $max) {
            $max = (int) $user['id'];
        }
    }

    return $max + 1;
}

/** Cari user berdasarkan email (cek duplikat #5 dan proses login #6). */
function find_user_by_email(array $users, string $email): ?array
{
    foreach ($users as $user) {
        if (strcasecmp((string) ($user['email'] ?? ''), $email) === 0) {
            return $user;
        }
    }

    return null;
}

/** Cari user berdasarkan id (dipakai dashboard dan edit profile). */
function find_user_by_id(array $users, int $id): ?array
{
    foreach ($users as $user) {
        if ((int) ($user['id'] ?? 0) === $id) {
            return $user;
        }
    }

    return null;
}

/* ==========================================================
 *  CSRF TOKEN (proteksi Cross-Site Request Forgery)
 * ========================================================== */

/** Token CSRF per session, dipasang pada setiap form POST. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['csrf_token'];
}

/** Field tersembunyi CSRF untuk form (sudah tersanitasi). */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/** Verifikasi token CSRF pada setiap request POST; hentikan bila tidak sah. */
function csrf_verify(): void
{
    $posted = (string) ($_POST['csrf_token'] ?? '');

    if (!hash_equals(csrf_token(), $posted)) {
        http_response_code(419);
        exit('Sesi tidak valid (CSRF token salah). Silakan kembali dan coba lagi.');
    }
}

/* ==========================================================
 *  FLASH MESSAGE (pesan sukses/error antar halaman — #10)
 * ========================================================== */

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/** Ambil sekali lalu hapus: pesan tampil tepat satu kali (PRG pattern). */
function take_flash(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

/* ==========================================================
 *  AUTENTIKASI, GUARD, DAN REMEMBER ME (bonus)
 * ========================================================== */

/** Simpan data user yang sedang login ke session (requirement #6). */
function login_user(array $user): void
{
    // Cegah session fixation: ID session baru setiap login berhasil.
    session_regenerate_id(true);

    $_SESSION['user_id']    = (int) $user['id'];
    $_SESSION['user_name']  = (string) $user['name'];
    $_SESSION['user_email'] = (string) $user['email'];
    $_SESSION['last_activity'] = time();
}

/** id user yang sedang login (null bila belum login). */
function current_user_id(): ?int
{
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

/** Guard halaman terproteksi: redirect ke login bila belum login (requirement #7). */
function require_login(): void
{
    if (current_user_id() === null) {
        set_flash('error', 'Silakan login terlebih dahulu.');
        header('Location: login.php');
        exit;
    }
}

/* ---------------- Remember Me (cookie aman, bonus) ----------------
 * Cookie hanya berisi "selector:token" acak. Database menyimpan HASH
 * token, sehingga bocornya isi cookie tidak langsung membahayakan.
 */

/** Buat cookie remember-me + simpan hash token di users.json. */
function create_remember_cookie(int $userId): void
{
    $users    = load_users();
    $selector = bin2hex(random_bytes(16));
    $token    = bin2hex(random_bytes(32));

    $expires = time() + REMEMBER_DURATION;

    foreach ($users as &$user) {
        if ((int) ($user['id'] ?? 0) === $userId) {
            $user['remember_selector']    = $selector;
            $user['remember_token_hash']  = hash('sha256', $token);
            $user['remember_expires_at']  = date('Y-m-d H:i:s', $expires);
            break;
        }
    }
    unset($user);

    save_users($users);

    setcookie(REMEMBER_COOKIE, $selector . ':' . $token, [
        'expires'  => $expires,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        // 'secure' => true,
    ]);
}

/** Hapus cookie remember-me beserta jejaknya di users.json. */
function delete_remember_cookie(): void
{
    if (isset($_COOKIE[REMEMBER_COOKIE])) {
        [$selector] = explode(':', (string) $_COOKIE[REMEMBER_COOKIE], 2);

        $users = load_users();
        foreach ($users as &$user) {
            if (($user['remember_selector'] ?? null) === $selector) {
                unset(
                    $user['remember_selector'],
                    $user['remember_token_hash'],
                    $user['remember_expires_at']
                );
                break;
            }
        }
        unset($user);

        save_users($users);

        setcookie(REMEMBER_COOKIE, '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}

/**
 * Login ulang otomatis dari cookie remember-me.
 * Dipanggil oleh guard ketika session kosong namun cookie tersedia.
 */
function attempt_remember_login(): bool
{
    if (empty($_COOKIE[REMEMBER_COOKIE])) {
        return false;
    }

    $parts = explode(':', (string) $_COOKIE[REMEMBER_COOKIE], 2);
    if (count($parts) !== 2) {
        delete_remember_cookie();

        return false;
    }

    [$selector, $token] = $parts;

    foreach (load_users() as $user) {
        $matchSelector = ($user['remember_selector'] ?? null) === $selector;
        $matchToken    = isset($user['remember_token_hash'])
            && hash_equals((string) $user['remember_token_hash'], hash('sha256', (string) $token));
        $notExpired    = isset($user['remember_expires_at'])
            && strtotime((string) $user['remember_expires_at']) > time();

        if ($matchSelector && $matchToken && $notExpired) {
            login_user($user);

            return true;
        }
    }

    delete_remember_cookie();

    return false;
}
