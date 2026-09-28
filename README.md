# 🔐 Sistem Login/Register PHP Native

**Tugas Rutin 7 — Pemrograman Web (Pertemuan 7: Server-Side PHP, Session & Cookies)**

Sistem autentikasi sederhana yang dibangun sepenuhnya dengan **PHP Native (tanpa framework)** dan menggunakan **file JSON** sebagai "database". Mendukung registrasi dengan validasi, login berbasis session, dashboard terproteksi, logout, serta bonus *Remember Me*, halaman *Edit Profile*, dan tampilan CSS modern.

---

## ✅ Pemetaan Requirement

| # | Requirement | Lokasi Implementasi |
|:-:|-------------|---------------------|
| 1 | Form registrasi dengan validasi (nama, email, password) | `register.php` — blok **VALIDASI**; field `name`, `email`, `password` |
| 2 | Validasi email dengan `filter_var()` | `functions.php` → `is_valid_email()` (`FILTER_VALIDATE_EMAIL`) |
| 3 | Password di-hash dengan `password_hash()` | `register.php` (`password_hash(..., PASSWORD_BCRYPT)`); verifikasi `login.php` (`password_verify()`); `edit_profile.php` |
| 4 | Data disimpan di file JSON | `functions.php` → `load_users()` / `save_users()`; file data: `users.json` |
| 5 | Cek duplikat email saat registrasi | `register.php` → `find_user_by_email()` sebelum insert |
| 6 | Sistem login dengan session | `login.php` + `functions.php` → `login_user()` (`session_regenerate_id`, isi `$_SESSION`) |
| 7 | Dashboard yang diproteksi (redirect bila belum login) | `dashboard.php` → `require_login()`; `edit_profile.php` juga terproteksi |
| 8 | Logout functionality (`session_destroy`) | `logout.php` → `session_unset()`/`$_SESSION=[]` + `session_destroy()` |
| 9 | Sanitasi input dengan `htmlspecialchars()` | `functions.php` → `e()` dipakai di **semua** output HTML dari data user |
| 10 | Pesan error & sukses yang jelas | Flash message (`set_flash`/`take_flash`) + alert CSS di semua halaman; error per-field |

**Bonus (semua diimplementasikan):**

- ⭐ **Remember Me dengan cookies** — cookie `remember_me` berisi `selector:token` acak; database hanya menyimpan **hash SHA-256** token (`create_remember_cookie()`, `attempt_remember_login()` di `functions.php`).
- ⭐ **Halaman edit profile** — ubah nama/email (dengan validasi + cek duplikat) dan ganti password (wajib verifikasi password lama) di `edit_profile.php`.
- ⭐ **Tampilan CSS yang rapi** — tema gelap modern, card, alert berwarna, responsive (`style.css`).

---

## 🗂️ Struktur File

```
login-register/
├── config.php        # Konfigurasi aplikasi + session cookie aman (httponly, samesite)
├── functions.php     # Helper: sanitasi, validasi, JSON storage, CSRF, flash, auth, remember-me
├── index.php         # Landing page publik
├── register.php      # Form & proses registrasi
├── login.php         # Form & proses login (+ Remember Me)
├── dashboard.php     # Halaman terproteksi (butuh login)
├── edit_profile.php  # Bonus: ubah profil & ganti password
├── logout.php        # Hancurkan session + cookie
├── style.css         # Bonus: tampilan modern & responsive
├── users.json        # "Database" file JSON (deliverable, berisi data contoh)
└── README.md
```

## 👤 Akun Contoh (`users.json`)

| Nama | Email | Password | Keterangan |
|------|-------|----------|------------|
| Rey kagami | `adit@example.com` | `Password123!` | Akun contoh #1 |
| Budi Santoso | `budi@example.com` | `Rahasia456#` | Akun contoh #2 |

> Password di file tersimpan sebagai **hash bcrypt**, contoh:
> `$2y$12$i.NjqoCoD7r5Zpg7jZpare//a950RI2dkL7VgBkaPQAwihFf7yR3W`

## 🚀 Cara Menjalankan

### Opsi A — Laragon / XAMPP (Apache + PHP)

1. Pindahkan/copy folder `login-register` ke:
   - **Laragon**: `C:\laragon\www\`
   - **XAMPP**: `C:\xampp\htdocs\`
2. Start Apache dari panel Laragon/XAMPP.
3. Buka di browser:
   - Laragon: `http://login-register.test` (atau `http://localhost/login-register`)
   - XAMPP: `http://localhost/login-register`

### Opsi B — PHP built-in server (tanpa Apache)

```bash
cd login-register
php -S localhost:8000
```

Lalu buka `http://localhost:8000`.

## 🧭 Alur Penggunaan

1. Buka halaman utama → klik **Daftar Akun Baru**.
2. Isi nama (min. 3 karakter), email valid, password (min. 8 karakter) → **Daftar**.
   - Email duplikat akan ditolak dengan pesan yang jelas.
   - Sukses → diarahkan ke halaman login dengan pesan sukses.
3. Login dengan akun (centang **"Ingat saya"** agar tetap login 7 hari meski browser ditutup).
4. Anda masuk ke **Dashboard** (berisi data akun). Coba buka `dashboard.php` dalam jendela incognito tanpa login → akan **di-redirect** ke halaman login.
5. **Edit Profile** untuk mengubah nama/email atau mengganti password.
6. **Logout** untuk mengakhiri session dan menghapus cookie remember-me.
7. Perubahan data langsung tersimpan di `users.json` — buka file tersebut untuk melihat isinya.

## 🛡️ Catatan Keamanan

Aspek keamanan diambil dari materi Pertemuan 7 dan rubrik penilaian ("sanitasi lengkap, password hash, session management benar"):

| Ancaman | Mitigasi di Kode |
|---------|------------------|
| **XSS** (script injection) | Semua output data user melewati `htmlspecialchars()` (fungsi `e()`, `ENT_QUOTES`, UTF-8) — field form, dashboard, flash message |
| **Password leak** | `password_hash()` (bcrypt, cost otomatis) + `password_verify()`; tidak pernah menyimpan/menampilkan plain text |
| **Session fixation** | `session_regenerate_id(true)` setiap login sukses |
| **Session/cookie theft via JS** | Cookie session & remember-me ditandai `HttpOnly`; `SameSite=Lax` |
| **CSRF** | Token acak per session di semua form POST + `hash_equals()` (`csrf_field()`/`csrf_verify()`) |
| **Duplikat akun** | Pencocokan email case-insensitive sebelum registrasi |
| **Remember-me token leak** | Cookie berisi selector+token acak; database menyimpan hash SHA-256 token; expiry dicek server-side |
| **Manipulasi input** | `trim()` seluruh input, validasi panjang + format, `strtolower` untuk email |

## 🔍 Detail Teknis

- **PHP minimal 8.0** (dibangun & diuji di PHP 8.5) — memakai `declare(strict_types=1)`, type declaration pada parameter/return, dan cookie array-syntax modern.
- Tidak ada dependensi eksternal; hanya ekstensi bawaan: `json`, `session`, `hash`.
- `users.json` ditulis dengan `LOCK_EX` (file lock) agar aman dari race condition penulisan.
- Pola **PRG (Post/Redirect/Get)** mencegah form terkirim ulang saat refresh.
- Data user di halaman diambil ulang dari `users.json` setiap request, sehingga edit profil langsung terlihat.

## 📄 Lisensi

Dibuat untuk keperluan tugas kuliah Pemrograman Web — bebas digunakan untuk pembelajaran.
