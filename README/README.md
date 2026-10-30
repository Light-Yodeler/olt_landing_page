# Ocean Lombok Tour & Transport — Platform Dokumentasi & Panduan Proyek

Repositori ini memuat spesifikasi produk, skema database, acuan desain UI/UX, dan panduan teknis implementasi untuk platform **Ocean Lombok Tour & Transport (OLT)** — web *landing page* bilingual dengan Content Management System (CMS) mandiri berbasis **Laravel 12**, **Filament v3**, dan **PHP 8.4**.

---

## 📁 Struktur Direktori Dokumentasi

```
README/
├── README.md              # Indeks pengantar dan ringkasan implementasi (file ini)
├── prd.md                 # Product Requirements Document (Fitur, Persona, NFR, Scope)
├── design.md              # Technical & Architecture Design (Design system, Kode, Setup VPS)
├── schema.sql             # Skema DDL Database referensi MariaDB / MySQL
├── seed-data.md           # Kamus data awal bilingual (EN/ID) & key terjemahan statis
└── design/
    └── stitch_html_design_replicator/
        ├── screen.png     # Tangkapan layar mockup visual landing page
        └── code.html      # Kode prototipe HTML/CSS interaktif dengan Tailwind
```

---

## 🌟 Ringkasan Arsitektur & Fitur Utama

1. **Publik (Frontend):**
   - **Bilingual First:** Bahasa Inggris (`en` di `/`) sebagai default global, dan Bahasa Indonesia (`id` di `/id`).
   - **Modern Editorial UI:** Tipografi *Newsreader* (Serif display) dipadukan dengan *Plus Jakarta Sans* (Body/UI) dan *JetBrains Mono* (Badges). Palet warna *Ocean Azure*, *Sunset Amber*, dan *Coral*.
   - **Conversion-Driven:** Tombol interaktif langsung memicu percakapan WhatsApp dengan pesan otomatis kontekstual sesuai layanan/destinasi yang dipilih.
   - **Core Web Vitals Optimized:** LCP < 2.5s, CLS < 0.1, INP < 200ms. Aset gambar dikonversi otomatis ke format `.webp`.

2. **Panel Admin (Filament v3 CMS):**
   - **Simple Authentication:** Login menggunakan **Username** dan **Password** tanpa kerumitan SMTP / verifikasi email.
   - **Enforced Security:** Seeder admin membaca kredensial dari `.env` dengan flag `must_change_password = 1`. Admin wajib mengganti password saat login pertama sebelum dapat mengakses modul lain.
   - **Full Content Independence:** Admin dapat mengubah logo, favicon, slider latar belakang, teks hero, daftar layanan, kartu destinasi, pilar standar pelayanan, FAQ, hingga teks banner konsultasi.

---

## 🛠️ Tech Stack & Dependensi Utama

| Lapisan / Komponen | Teknologi / Paket | Keterangan |
| :--- | :--- | :--- |
| **Runtime** | PHP 8.4 (patch keamanan terbaru) | Ekstensi: FPM, GD, Imagick, MySQL, Intl, Zip |
| **Framework** | Laravel 12.x | Monolith MVC, Blade Templating, Vite |
| **Admin CMS** | Filament PHP v3.x | TALL Stack (Livewire 3, Alpine.js, Tailwind) |
| **Bilingual Engine** | `spatie/laravel-translatable` + `filament/spatie-laravel-translatable-plugin` | Kolom JSON bilingual di MariaDB / MySQL |
| **Image Processing**| `intervention/image-laravel` | Konversi otomatis unggahan gambar ke `.webp` & resize |
| **Icons** | `mallardduck/blade-lucide-icons` | Render ikon Lucide native SVG tanpa script CDN eksternal |
| **Database** | MariaDB 10.6+ / MySQL 8.0+ | Charset `utf8mb4` |

---

## 🚀 Panduan Memulai di Lokal (Local Development Setup)

### Prasyarat
- PHP 8.4+ dengan ekstensi GD / Imagick, Mbstring, PDO MySQL.
- Composer 2.x
- Node.js 20+ / 22+ & NPM
- MySQL / MariaDB lokal

### Langkah Instalasi
1. **Clone dan Pasang Dependensi:**
   ```bash
   composer install
   npm install
   ```

2. **Pengaturan Konfigurasi Lingkungan (`.env`):**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   Pastikan koneksi database sudah disesuaikan, dan tentukan kredensial admin awal:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=olt_database
   DB_USERNAME=root
   DB_PASSWORD=

   # Kredensial seeder admin awal (min. 12 karakter)
   ADMIN_USERNAME=admin
   ADMIN_PASSWORD=RahasiaAdmin2026!
   ```

3. **Migrasi Database & Seeder:**
   ```bash
   php artisan storage:link
   php artisan migrate --seed
   ```

4. **Jalankan Aplikasi:**
   ```bash
   npm run dev
   # Buka terminal kedua
   php artisan serve
   ```
   - **Halaman Depan:** `http://localhost:8000` (English) atau `http://localhost:8000/id` (Indonesia).
   - **Panel Admin:** `http://localhost:8000/admin`. Masuk dengan username `admin` dan password dari `.env`. Anda akan langsung diarahkan ke halaman profil untuk mengubah password pertama kali.

---

## 📖 Dokumen Terkait
- Baca rincian spesifikasi kebutuhan fungsional dan non-fungsional di [`prd.md`](file:///Users/muhammad_syahid_ramadhon/Documents/web/project/OLT/README/prd.md).
- Baca rincian arsitektur sistem, struktur kode, token desain, dan panduan deployment VPS di [`design.md`](file:///Users/muhammad_syahid_ramadhon/Documents/web/project/OLT/README/design.md).
- Lihat skema struktur tabel database di [`schema.sql`](file:///Users/muhammad_syahid_ramadhon/Documents/web/project/OLT/README/schema.sql).
- Lihat kamus konten awal seeder & kunci terjemahan bilingual (`EN`/`ID`) di [`seed-data.md`](file:///Users/muhammad_syahid_ramadhon/Documents/web/project/OLT/README/seed-data.md).

