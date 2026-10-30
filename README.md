# Ocean Lombok Tour & Transport (OLT)

Aplikasi web profil usaha dan sistem manajemen konten (CMS) untuk penyedia jasa transportasi dan paket wisata yang berbasis di Kuta Mandalika, Lombok. Sistem ini menyediakan landing page publik dwibahasa (Indonesia dan Inggris) serta panel admin berbasis peran untuk mengelola seluruh konten visual, armada, destinasi, dan kontak WhatsApp secara terpusat.

Repository: `https://github.com/Light-Yodeler/olt_landing_page.git`

---

## Daftar Fitur

### 1. Landing Page Publik
- **Dwibahasa (Bilingual):** Rute default bahasa Inggris di `/`, rute bahasa Indonesia di `/id`, dan pengalihan dinamis melalui rute `/lang/{locale}` dengan penyimpanan cookie preferensi pengguna (`olt_locale`).
- **Pesan WhatsApp Kontekstual:** Tombol reservasi dan pertanyaan di setiap kartu layanan maupun destinasi menyertakan teks pesan awal yang otomatis terisi (*pre-filled message*) sesuai nama paket yang dipilih.
- **Hero Slider:** Menampilkan galeri foto panorama Lombok dengan transisi halus dan indikator slide aktif.
- **Daftar Layanan & Destinasi:** Menampilkan armada transportasi (sewa motor, mobil harian, antar-jemput bandara LOP) serta destinasi wisata Lombok (pantai kawasan Mandalika, rute Sembalun, air terjun Senaru, dan Gili).
- **Aksesibilitas & Tipografi:** Menggunakan kombinasi font *Newsreader* (serif), *Plus Jakarta Sans* (sans-serif), dan *JetBrains Mono* (angka/badge), kontras warna berstandar WCAG AA, serta navigasi keyboard lengkap (`focus-visible`).

### 2. Panel Admin CMS (Filament v3)
- **Akses Tanpa SMTP:** Autentikasi berbasis `username` dan `password` tanpa ketergantungan layanan email keluar.
- **Proteksi Akun Baru:** Middleware `EnsurePasswordIsChanged` mewajibkan pengguna baru mengganti password default sebelum mengakses modul manajemen konten.
- **Hak Akses Berbasis Peran (RBAC):**
  - **Super Admin:** Akses penuh seluruh sistem, mengelola daftar pengguna (`/admin/users`), mengatur peran akun, dan konfigurasi profil usaha.
  - **Admin:** Mengelola seluruh data konten (Slide, Layanan, Destinasi, Standar, FAQ) dan informasi kontak (WhatsApp, tautan media sosial, logo perusahaan). Tidak memiliki akses ke manajemen pengguna.
  - **Staff:** Mengunggah foto destinasi/slide, mengubah deskripsi wisata, dan mengatur urutan tampilan. Akses ke menu pengaturan nomor WhatsApp, media sosial, dan logo diblokir.
- **Optimasi Gambar Otomatis:** File foto yang diunggah dikonversi langsung ke format WebP dan dikompresi sesuai proporsi resolusi tampilan tanpa menurunkan ketajaman visual.

---

## Kebutuhan Sistem

- **PHP:** Versi 8.2 atau lebih baru (direkomendasikan PHP 8.4)
  - Ekstensi: `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `gd` / `imagick`, `mbstring`, `openssl`, `pdo`, `pdo_sqlite` atau `pdo_mysql`, `session`, `tokenizer`, `xml`
- **Composer:** Versi 2.x
- **Node.js:** Versi 20.x atau 22.x LTS & npm
- **Database:** SQLite (lingkungan pengujian/lokal) atau MariaDB / MySQL 8.0+ (lingkungan produksi)
- **Web Server:** Nginx (produksi) atau PHP Built-in Server (pengembangan lokal)

---

## Panduan Instalasi Lokal

### 1. Kloning Repository
```bash
git clone https://github.com/Light-Yodeler/olt_landing_page.git
cd olt_landing_page
```

### 2. Pasang Dependensi Backend & Frontend
```bash
# Dependensi PHP
composer install

# Dependensi JavaScript & CSS
npm install
```

### 3. Konfigurasi Environment
Salin file konfigurasi environment dari template:
```bash
cp .env.example .env
```

Buka file `.env` dan sesuaikan parameter berikut:
```env
APP_NAME="Ocean Lombok Tour"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=sqlite

ADMIN_USERNAME=admin
ADMIN_PASSWORD=RahasiaAdmin2026!
```

Buat file database SQLite lokal:
```bash
touch database/database.sqlite
```

Generate application key:
```bash
php artisan key:generate
```

### 4. Migrasi & Pengisian Data Awal (Seeding)
Jalankan migrasi tabel beserta seeder data default (Slide, Layanan, Destinasi, Standar Layanan, FAQ, Pengaturan Kontak, dan Akun Super Admin):
```bash
php artisan migrate --seed
```

Buat tautan simbolik direktori penyimpanan media:
```bash
php artisan storage:link
```

### 5. Kompilasi Aset Frontend
```bash
npm run build
```

### 6. Jalankan Server Pengembangan
Jalankan server aplikasi Laravel:
```bash
php artisan serve --port=8000
```

Aplikasi dapat diakses melalui browser:
- **Landing Page (EN):** `http://127.0.0.1:8000`
- **Landing Page (ID):** `http://127.0.0.1:8000/id`
- **Panel Admin:** `http://127.0.0.1:8000/admin`

**Kredensial Default:**
- **Username:** `admin`
- **Password:** `RahasiaAdmin2026!`
*(Pada login pertama kali, sistem akan mengarahkan ke halaman pembaruan password sebelum membuka modul admin).*

---

## Panduan Deployment VPS (Ubuntu 24.04 LTS)

Panduan ini disusun untuk server Linux Ubuntu dengan alokasi RAM terbatas (misalnya VPS 2 vCPU / 2 GB RAM) agar efisien dan tidak mengganggu proses lain yang berjalan di server.

### 1. Pemasangan Paket Server
```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y nginx git unzip curl certbot python3-certbot-nginx \
    php8.4-fpm php8.4-cli php8.4-common php8.4-mysql php8.4-sqlite3 \
    php8.4-curl php8.4-gd php8.4-mbstring php8.4-xml php8.4-zip php8.4-bcmath
```

### 2. Optimasi PHP-FPM untuk Penghematan Memori
Edit berkas pool PHP-FPM di `/etc/php/8.4/fpm/pool.d/www.conf`:
```ini
pm = ondemand
pm.max_children = 8
pm.process_idle_timeout = 10s
pm.max_requests = 500
```

Edit berkas `/etc/php/8.4/fpm/php.ini`:
```ini
upload_max_filesize = 32M
post_max_size = 32M
memory_limit = 256M
```

Muat ulang service PHP-FPM:
```bash
sudo systemctl restart php8.4-fpm
```

### 3. Pemasangan Kode Aplikasi di Server
```bash
# Buat direktori aplikasi
sudo mkdir -p /var/www/olt
sudo chown -R $USER:$USER /var/www/olt

# Kloning repository
git clone https://github.com/Light-Yodeler/olt_landing_page.git /var/www/olt
cd /var/www/olt

# Pasang dependensi tanpa paket dev
composer install --no-dev --optimize-autoloader
npm install
npm run build

# Salin konfigurasi environment
cp .env.example .env
```

Sesuaikan berkas `.env` di VPS:
```env
APP_NAME="Ocean Lombok Tour"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://olt.web.id

DB_CONNECTION=sqlite
# Atau arahkan ke MySQL/MariaDB jika menggunakan database terpisah

ADMIN_USERNAME=admin
ADMIN_PASSWORD=GantiDenganPasswordKuatMinimal12Karakter!
```

Jalankan perintah inisialisasi:
```bash
php artisan key:generate --force
touch database/database.sqlite
php artisan migrate --force --seed
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Atur kepemilikan izin berkas untuk Nginx:
```bash
sudo chown -R www-data:www-data /var/www/olt/storage /var/www/olt/bootstrap/cache /var/www/olt/database
sudo chmod -R 775 /var/www/olt/storage /var/www/olt/bootstrap/cache /var/www/olt/database
```

### 4. Konfigurasi Nginx
Buat berkas konfigurasi di `/etc/nginx/sites-available/olt.web.id`:
```nginx
server {
    listen 80;
    listen [::]:80;
    server_name olt.web.id www.olt.web.id;
    root /var/www/olt/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php index.html;
    charset utf-8;

    client_max_body_size 32M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Aktifkan konfigurasi dan muat ulang Nginx:
```bash
sudo ln -s /etc/nginx/sites-available/olt.web.id /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### 5. Pemasangan Sertifikat SSL (HTTPS)
Pastikan DNS domain `olt.web.id` dan `www.olt.web.id` sudah mengarah ke IP publik VPS Anda, kemudian jalankan Certbot:
```bash
sudo certbot --nginx -d olt.web.id -d www.olt.web.id
```

---

## Struktur Direktori Utama

```text
├── app/
│   ├── Filament/          # Konfigurasi panel admin, modul resources, dan custom pages
│   ├── Http/Controllers/  # Controller web (delegasi kueri ke Service layer)
│   ├── Http/Middleware/   # Middleware deteksi bahasa dan penegakan pergantian password
│   ├── Models/            # Model Eloquent translatable & otorisasi pengguna
│   ├── Policies/          # Policy otorisasi hak akses (UserPolicy)
│   └── Services/          # Service layer (LandingPageService & ImageOptimizerService)
├── config/                # Konfigurasi aplikasi, autentikasi, database, dan Livewire
├── database/
│   ├── migrations/        # Skema tabel pengguna, peran, slide, destinasi, dan konten
│   └── seeders/           # Data awal bilingual dan akun admin default
├── lang/                  # Kamus terjemahan antarmuka (en.json & id.json)
├── resources/
│   ├── css/               # Konfigurasi token gaya Tailwind CSS v4 (@theme)
│   ├── js/                # Logika slider hero dan drawer navigasi mobile
│   └── views/             # Template Blade modular (layouts, partials, dan custom pages)
├── routes/                # Definisi rute web publik dan pengalihan bahasa
└── tests/                 # Pengujian fitur dan unit otomatis (Feature & Unit tests)
```

---

## Menjalankan Pengujian Otomatis

Untuk memastikan seluruh rute, proteksi autentikasi, fungsi pengalihan bahasa, dan hak akses peran berjalan normal:
```bash
php artisan test
```

Hasil pengujian mencakup verifikasi:
1. Respons halaman utama bahasa Inggris (`/`)
2. Respons halaman utama bahasa Indonesia (`/id`)
3. Pengalihan pengguna tamu saat mengakses rute `/admin`
4. Pengalihan dan penyimpanan cookie pada rute `/lang/{locale}`
5. Penegakan pergantian password akun baru
6. Pembatasan akses pengguna berdasarkan peran (*Super Admin*, *Admin*, dan *Staff*)

---

## Lisensi

Aplikasi ini dikembangkan untuk kebutuhan operasional **Ocean Lombok Tour & Transport**. Hak cipta dilindungi.
