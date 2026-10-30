# System & Technical Design Document (Design.md)
## Ocean Lombok Tour & Transport — Dynamic CMS & Landing Page Platform

---

### 1. Arsitektur Sistem (High-Level Architecture)

Platform Ocean Lombok Tour dirancang dengan arsitektur **Monolith Modern (Server-Rendered MVC + Dynamic Admin)** menggunakan **Laravel 12** dan **Filament PHP v3** di atas runtime **PHP 8.4**. Arsitektur ini menjamin performa rendering publik yang sangat cepat untuk SEO serta kemudahan pengelolaan konten visual secara terpusat.

> **Kebijakan Keamanan Runtime PHP:**  
> Server menggunakan **PHP 8.4** dari PPA `ondrej/php` dan wajib selalu diperbarui ke rilis patch keamanan terbaru (`apt upgrade`). Pantau pengumuman keamanan resmi di https://www.php.net/ChangeLog-8.php.

```
                              [ Pengunjung Web / Tamu ]
                                         │
                                         ▼ (HTTPS - Port 443)
                         ┌────────────────────────────────┐
                         │   Nginx Web Server / Gateway   │
                         └────────────────┬───────────────┘
                                          │
                   ┌──────────────────────┴──────────────────────┐
                   │                                             │
      (File Gambar / Asset Statis)                  (Permintaan Dinamis / Admin)
                   │                                             │
                   ▼                                             ▼
        ┌─────────────────────┐                      ┌──────────────────────┐
        │   public/storage    │                      │     PHP 8.4 FPM      │
        │  (Foto, Logo, WebP) │                      │   (Patch Terbaru)    │
        └─────────────────────┘                      └──────────┬───────────┘
                                                                │
                                                                ▼
                                                     ┌──────────────────────┐
                                                     │ Laravel 12 Framework │
                                                     └──────────┬───────────┘
                                                                │
                   ┌────────────────────────────────────────────┼─────────────────────────┐
                   │                                            │                         │
                   ▼                                            ▼                         ▼
        ┌─────────────────────┐                      ┌────────────────────┐    ┌────────────────────┐
        │ Public Blade Views  │                      │ Filament v3 Panel  │    │  Bilingual Engine  │
        │ (Tailwind, Lucide)  │                      │ (Livewire 3, TALL) │    │ (Spatie JSON Trans)│
        └─────────────────────┘                      └────────────────────┘    └────────────────────┘
                   │                                            │                         │
                   └────────────────────────────────────────────┼─────────────────────────┘
                                                                │
                                                                ▼ (PDO / Eloquent)
                                                     ┌──────────────────────┐
                                                     │   MariaDB / MySQL    │
                                                     │  (Database Server)   │
                                                     └──────────────────────┘
```

---

### 2. Design System & Frontend Tokens

Landing page mengikuti mockup desain di `README/design/stitch_html_design_replicator/code.html` (pratinjau: `screen.png`). Konfigurasi `tailwind.config` di file tersebut adalah sumber acuan token di bawah ini.

#### 2.1 Tipografi (Typography)
- **Serif (Headings & Display):** `"Newsreader", Georgia, serif`
  - Memberikan nuansa elegan, terpercaya, dan berkelas khas majalah perjalanan kelas dunia.
- **Sans-Serif (Body & UI Elements):** `"Plus Jakarta Sans", sans-serif`
  - Menghadirkan keterbacaan tinggi di layar smartphone, tombol, dan teks navigasi.
- **Monospace (Badges, Codes, Tags):** `"JetBrains Mono", monospace`
  - Digunakan untuk label kategori huruf kapital (misal: `[ LAYANAN KAMI ]`, `BIKE RENTAL`).

#### 2.2 Palet Warna & Konfigurasi Tailwind (Color Tokens & Config)

Agar seluruh class CSS dari mockup `code.html` terkompilasi sempurna oleh Tailwind dan Vite, definisikan konfigurasi `tailwind.config.js` sebagai berikut:

```javascript
/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './app/Filament/**/*.php',
        './resources/views/filament/**/*.blade.php',
    ],
    theme: {
        extend: {
            colors: {
                base: '#f8fafc',
                surface: '#ffffff',
                subtle: '#f0f9ff',
                breeze: '#e0f2fe',
                border: '#bae6fd',
                borderSubtle: '#e2e8f0',
                ink: {
                    900: '#0f172a',
                    700: '#334155',
                    500: '#64748b',
                    400: '#94a3b8',
                },
                ocean: {
                    900: '#082f49',
                    800: '#0c4a60',
                    700: '#0369a1',
                    600: '#0284c7',
                    500: '#0ea5e9',
                    400: '#38bdf8',
                    300: '#7dd3fc',
                    200: '#bae6fd',
                    100: '#e0f2fe',
                    50: '#f0f9ff',
                },
                amberGlow: {
                    600: '#d97706',
                    500: '#f59e0b',
                    400: '#fbbf24',
                    300: '#fcd34d',
                    200: '#fde68a',
                },
                coralSunset: {
                    600: '#ea580c',
                    500: '#f97316',
                    400: '#fb923c',
                },
            },
            fontFamily: {
                sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                serif: ['"Newsreader"', 'serif'],
                mono: ['"JetBrains Mono"', 'monospace'],
            },
            boxShadow: {
                'sunset': '0 8px 24px -4px rgba(245, 158, 11, 0.35), 0 4px 12px -2px rgba(234, 88, 12, 0.25)',
                'ocean': '0 10px 30px -5px rgba(2, 132, 199, 0.25)',
            },
        },
    },
    plugins: [
        require('@tailwindcss/forms'),
    ],
}
```

**Aturan tombol:**
- **CTA WhatsApp utama** (hero, banner bawah, tombol melayang): gradasi `from-amberGlow-500 to-coralSunset-500` dengan bayangan `shadow-sunset`.
- **CTA header** ("Tanya Ketersediaan"): gradasi `from-ocean-800 to-ocean-700`.
- **Link WhatsApp teks** (kartu hero, footer): `text-coralSunset-600`.

---

### 3. Arsitektur Komponen Frontend (Blade View Hierarchy)

Struktur template diorganisasi secara modular di dalam `resources/views/`:

```
resources/views/
├── layouts/
│   └── app.blade.php              # Layout utama (HTML boilerplate, SEO meta, font loader, Lucide)
├── partials/
│   ├── header.blade.php           # Sticky navbar, Logo OLT dinamis, menu, language switcher
│   ├── hero.blade.php             # Slideshow latar belakang, headline, tombol CTA, kartu cepat OLT
│   ├── services.blade.php         # Grid 4 kartu layanan rental & wisata
│   ├── about.blade.php            # Narasi profil dan jaringan terpercaya Kuta Mandalika
│   ├── destinations.blade.php     # Grid 6 destinasi wisata unggulan dengan tombol WhatsApp
│   ├── standards.blade.php        # 3 pilar standar operasional dan keamanan
│   ├── faq.blade.php              # Accordion tanya-jawab
│   ├── cta-banner.blade.php       # Banner ajakan konsultasi WhatsApp
│   ├── footer.blade.php           # Identitas, alamat, kontak, copyright
│   └── floating-wa.blade.php      # Tombol WhatsApp melayang di sudut kanan bawah
└── home.blade.php                 # Halaman utama yang memanggil semua partials di atas
```

---

### 4. Mekanisme Multi-Bahasa (Bilingual Architecture)

Sistem dirancang dengan **English (EN) sebagai default**, dan **Bahasa Indonesia (ID)** sebagai pilihan:

#### 4.1 Penyimpanan Terjemahan Konten Dinamis
Menggunakan paket standar industri Laravel: `spatie/laravel-translatable`. 
Semua kolom teks yang dinamis (seperti judul, deskripsi, poin keunggulan) disimpan dalam tipe data `JSON` pada database:
```json
{
  "en": "Motorbike Rental",
  "id": "Sewa Sepeda Motor"
}
```

#### 4.2 Teks Statis Antarmuka (UI Labels)
Teks antarmuka statis disimpan di berkas bahasa Laravel:
- `lang/en.json` (English - Default)
- `lang/id.json` (Bahasa Indonesia)

#### 4.3 Routing & Middleware Pengalih Bahasa
Struktur rute dirancang ramah SEO (*SEO-friendly* dengan kanonikal & hreflang eksplisit):
- Rute Publik:
  - `GET /` $\rightarrow$ Render bahasa Inggris (`en` - bahasa utama/default).
  - `GET /id` $\rightarrow$ Render bahasa Indonesia (`id`).
  - `GET /lang/{locale}` $\rightarrow$ Endpoint helper pengubah bahasa (menyimpan cookie `olt_locale` lalu me-redirect pengunjung kembali ke halaman yang sesuai: `/` untuk EN atau `/id` untuk ID).
- Head Meta Tags Hreflang:
```blade
<link rel="canonical" href="{{ url()->current() }}" />
<link rel="alternate" hreflang="en" href="{{ url('/') }}" />
<link rel="alternate" hreflang="id" href="{{ url('/id') }}" />
<link rel="alternate" hreflang="x-default" href="{{ url('/') }}" />
```

---

### 5. Arsitektur Autentikasi Sederhana & Admin Panel (Filament v3)

Autentikasi dirancang **super ringkas**:
- Hanya membutuhkan **Username** dan **Password**.
- **Tanpa** verifikasi email dan alur *password reset* SMTP yang rentan gagal kirim.
- Akun administrator pertama dibuat oleh seeder dari nilai `ADMIN_USERNAME` dan `ADMIN_PASSWORD` di `.env`. **Tidak ada password default di kode.**
- Akun hasil seeder ditandai `must_change_password = true`, sehingga admin **wajib mengganti password saat login pertama** sebelum bisa membuka menu lain.

#### 5.1 Kustomisasi Login Filament (`app/Filament/Pages/Auth/Login.php`)
```php
namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;

class Login extends BaseLogin
{
    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getUsernameFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getRememberFormComponent(),
                    ])
                    ->statePath('data'),
            ),
        ];
    }

    protected function getUsernameFormComponent(): Component
    {
        return TextInput::make('username')
            ->label('Username')
            ->required()
            ->autocomplete('username')
            ->autofocus();
    }

    protected function getCredentialsFromFormData(array $data): array
    {
        return [
            'username' => $data['username'],
            'password' => $data['password'],
        ];
    }

    protected function throwFailureValidationException(): never
    {
        throw \Illuminate\Validation\ValidationException::withMessages([
            'data.username' => __('filament-panels::pages/auth/login.messages.failed'),
        ]);
    }
}
```

#### 5.2 Konfigurasi Panel Provider (`app/Providers/Filament/AdminPanelProvider.php`)
```php
namespace App\Providers\Filament;

use App\Filament\Pages\Auth\EditProfile;
use App\Filament\Pages\Auth\Login as CustomLogin;
use App\Http\Middleware\EnsurePasswordIsChanged;
use Filament\Http\Middleware\Authenticate;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(CustomLogin::class)
            ->profile(EditProfile::class)
            ->colors([
                // Color::hex() membuat palet 50–950 lengkap dari satu warna brand
                'primary' => Color::hex('#0c4a60'), // ocean-800
            ])
            ->brandName('Ocean Lombok Tour')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            // ->middleware([...]) bawaan hasil `php artisan filament:install --panels` tetap dipakai
            ->authMiddleware([
                Authenticate::class,
                EnsurePasswordIsChanged::class,
            ]);
    }
}
```

#### 5.3 Model Pengguna Sederhana (`app/Models/User.php`)
```php
namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    use Notifiable;

    protected $fillable = [
        'name',
        'username',
        'password',
        'must_change_password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'must_change_password' => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // Seluruh akun pengguna terdaftar berhak mengakses panel admin
        return true;
    }
}
```

#### 5.4 Seeder Akun Admin Pertama (`database/seeders/AdminUserSeeder.php`)
```php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('ADMIN_PASSWORD');

        if (blank($password) || strlen($password) < 12) {
            throw new RuntimeException('Isi ADMIN_PASSWORD di .env (minimal 12 karakter) sebelum menjalankan seeder.');
        }

        // firstOrCreate: menjalankan ulang seeder tidak akan menimpa password yang sudah diganti admin
        User::firstOrCreate(
            ['username' => env('ADMIN_USERNAME', 'admin')],
            [
                'name' => 'Administrator',
                'password' => $password,
                'must_change_password' => true,
            ],
        );
    }
}
```
> Seeder membaca `env()` langsung, jadi jalankan **sebelum** `php artisan config:cache`. Setelah seeding berhasil, hapus baris `ADMIN_PASSWORD` dari `.env`.

#### 5.5 Middleware Wajib Ganti Password (`app/Http/Middleware/EnsurePasswordIsChanged.php`)
```php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        if (
            $request->user()?->must_change_password
            && ! $request->routeIs('filament.admin.auth.profile', 'filament.admin.auth.logout')
        ) {
            return redirect()->route('filament.admin.auth.profile');
        }

        return $next($request);
    }
}
```
> Daftarkan lewat `->authMiddleware()` **tanpa** `isPersistent: true`, agar request Livewire saat menyimpan form profil tidak ikut dialihkan.

#### 5.6 Halaman Profil Admin (`app/Filament/Pages/Auth/EditProfile.php`)
```php
namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Auth\EditProfile as BaseEditProfile;

class EditProfile extends BaseEditProfile
{
    public function form(Form $form): Form
    {
        return $form->schema([
            $this->getNameFormComponent(),
            TextInput::make('username')
                ->label('Username')
                ->required()
                ->maxLength(100)
                ->unique(ignoreRecord: true),
            $this->getPasswordFormComponent()
                ->required(fn (): bool => (bool) auth()->user()?->must_change_password),
            $this->getPasswordConfirmationFormComponent(),
        ]);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (filled($data['password'] ?? null)) {
            $data['must_change_password'] = false;
        }

        return $data;
    }
}
```

#### 5.7 Solusi Jika Lupa Password (Tanpa Email)
Jika pemilik bisnis lupa kata sandi, jalankan perintah berikut di terminal SSH VPS. Password sementara ini wajib diganti lagi saat login berikutnya:
```bash
php artisan tinker --execute="\App\Models\User::where('username', 'admin')->first()->update(['password' => 'PASSWORD_SEMENTARA', 'must_change_password' => true]);"
```

#### 5.8 Image Processing & WebP Auto-Conversion
Untuk memenuhi persyaratan PRD (semua gambar otomatis di-resize dan dikonversi ke `.webp`, max upload 5MB), gunakan `intervention/image-laravel`:

```php
namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

class ImageOptimizerService
{
    public static function optimizeAndStore(
        UploadedFile $file,
        string $directory = 'uploads',
        int $maxWidth = 1920,
        int $quality = 82
    ): string {
        $extension = strtolower($file->getClientOriginalExtension());

        // Jika file adalah SVG atau ICO (misal favicon/logo vektor), simpan langsung tanpa re-encode
        if (in_array($extension, ['svg', 'ico'])) {
            return $file->store($directory, 'public');
        }

        // Baca image menggunakan Intervention Image 3
        $image = Image::read($file);

        // Resize proporsional jika lebar melebihi batas maksimal
        if ($image->width() > $maxWidth) {
            $image->scaleDown(width: $maxWidth);
        }

        // Encode ke format WebP
        $encoded = $image->toWebp($quality);

        $filename = $directory . '/' . Str::random(40) . '.webp';
        Storage::disk('public')->put($filename, (string) $encoded);

        return $filename;
    }
}
```
Pada form resource Filament (misal `DestinationsResource`, `HeroSlidesResource`, `SiteSettings`), integrasikan via:
```php
FileUpload::make('image_path')
    ->image()
    ->maxSize(5120) // 5 MB
    ->saveUploadedFileUsing(function (UploadedFile $file, $get) {
        return \App\Services\ImageOptimizerService::optimizeAndStore($file, 'destinations', 1600);
    })
```

#### 5.9 Pengelolaan Konten Bilingual di Filament
Model menggunakan trait `HasTranslations` dari `spatie/laravel-translatable`:
```php
use Spatie\Translatable\HasTranslations;

class Destination extends Model
{
    use HasTranslations;

    public array $translatable = [
        'name',
        'category_badge',
        'description',
        'location',
        'button_text',
        'whatsapp_message',
    ];
}
```
Pada form Filament, gunakan Tabs bilingual (`English` & `Bahasa Indonesia`) atau helper tab `spatie-laravel-translatable-plugin` agar pengisian teks kedua bahasa nyaman dan terorganisir.

---

### 6. Panduan Lengkap Instalasi & Deployment di VPS Anda (Ubuntu 24.04 LTS — PHP 8.4)

Panduan ini disesuaikan **secara spesifik** dengan profil VPS Tencent Cloud Anda saat ini agar proses instalasi **tidak mengganggu layanan yang sedang berjalan 24/7** (`9Router` di port `20128` dan `Hermes Agent Discord Gateway`) serta menjaga pemakaian RAM tetap irit di dalam batas **~978 MB RAM bebas** yang tersisa.

#### Profil Server Target (Tencent Cloud SG)
- **IP Publik:** `43.156.112.35`
- **OS & Kernel:** Ubuntu 24.04 LTS (Noble Numbat) — Linux 6.8.0-138-generic
- **Hardware:** 2 vCPU (AMD EPYC 7K62), **2 GB RAM + 2 GB Swap** (Sudah aktif, sisa RAM bebas ~978 MB), 40 GB NVMe SSD (Sisa bebas 28 GB)
- **Sudah Terpasang (JANGAN DI-INSTALL ULANG):**
  - Node.js `v22.23.4` LTS & npm `10.9.9`
  - Python `3.12.3` & `3.14.9` (Hermes Runtime)
  - PM2 `v6.1.3` (systemd aktif) & SQLite `3.45.1`
- **Layanan Aktif 24/7 (Wajib Dilindungi):**
  - `9Router` (Port `20128`)
  - `Hermes Agent Discord Gateway` (systemd user service)

---

#### LANGKAH 1: Masuk ke VPS & Cek Layanan Eksisting
Buka terminal komputer Anda dan hubungkan ke VPS:
```bash
ssh root@43.156.112.35
```
> **Catatan Penting:**
> - **JANGAN membuat Swap baru** karena VPS Anda sudah memiliki 2 GB Swap aktif (`free -h`).
> - **JANGAN menginstal ulang Node.js** karena `v22.23.4` sudah terpasang (`node -v`).
> - **Pertimbangan Timezone:** Jika `9Router` atau `Hermes Agent` bergantung pada jam server saat ini (UTC/SGT), Anda **tidak wajib** mengubah timezone OS. Cukup atur timezone di level aplikasi Laravel (`APP_TIMEZONE=Asia/Makassar` di `.env`).

Jalankan pembaruan daftar paket:
```bash
apt update
```

---

#### LANGKAH 2: Instal Nginx, PHP 8.4 & Tuning Hemat RAM (PHP-FPM)
Gunakan repositori PPA Ondřej Surý untuk memasang PHP 8.4 dengan patch keamanan terbaru:
```bash
apt install -y software-properties-common curl git unzip
add-apt-repository ppa:ondrej/php -y
apt update

# Instal Nginx, PHP 8.4, dan ekstensi yang dibutuhkan Laravel + Filament + Intervention Image
apt install -y nginx php8.4-fpm php8.4-cli php8.4-mysql php8.4-sqlite3 php8.4-curl \
php8.4-gd php8.4-mbstring php8.4-xml php8.4-zip php8.4-bcmath \
php8.4-intl php8.4-imagick
```

**Verifikasi Versi & Patch Keamanan PHP 8.4:**
```bash
php -v
# Untuk memastikan selalu di patch 8.4.x terbaru:
apt --only-upgrade install "php8.4*" -y
```

**Tuning PHP 8.4-FPM agar Hemat RAM (Penting untuk Sisa RAM ~978 MB):**
Secara bawaan, PHP-FPM `pm = dynamic` bisa memakan hingga 300–400 MB RAM. Kita batasi menjadi `pm = ondemand` dengan maksimal 4 worker agar saat sepi hanya memakai ~25 MB RAM dan saat ramai maksimal ~140 MB RAM:
```bash
# Konfigurasi batas upload PHP (PRD mensyaratkan upload foto hingga 5MB)
sed -i 's/upload_max_filesize = .*/upload_max_filesize = 20M/' /etc/php/8.4/fpm/php.ini
sed -i 's/post_max_size = .*/post_max_size = 25M/' /etc/php/8.4/fpm/php.ini
sed -i 's/memory_limit = .*/memory_limit = 128M/' /etc/php/8.4/fpm/php.ini

# Tuning Pool Worker PHP-FPM (ondemand, hemat RAM)
sed -i 's/^pm = .*/pm = ondemand/' /etc/php/8.4/fpm/pool.d/www.conf
sed -i 's/^pm.max_children = .*/pm.max_children = 4/' /etc/php/8.4/fpm/pool.d/www.conf
sed -i 's/^;pm.process_idle_timeout = .*/pm.process_idle_timeout = 10s/' /etc/php/8.4/fpm/pool.d/www.conf
sed -i 's/^;pm.max_requests = .*/pm.max_requests = 500/' /etc/php/8.4/fpm/pool.d/www.conf

systemctl restart php8.4-fpm
systemctl enable nginx php8.4-fpm
systemctl status nginx php8.4-fpm --no-pager
```

---

#### LANGKAH 3: Konfigurasi Database (Pilih Opsi A: MariaDB Hemat RAM atau Opsi B: SQLite)

Karena VPS Anda sudah memiliki layanan AI yang memakan ~1 GB RAM, Anda punya **2 opsi database** yang sama-sama didukung penuh oleh Laravel 12 dan `spatie/laravel-translatable` (kolom JSON):

##### Opsi A (Direkomendasikan sesuai `schema.sql`): MariaDB dengan Tuning Low-Memory (~90 MB RAM)
```bash
apt install -y mariadb-server mariadb-client
```
Tambahkan profil penghemat RAM untuk MariaDB sebelum membuat database:
```bash
cat << 'EOF' > /etc/mysql/mariadb.conf.d/60-low-memory.cnf
[mysqld]
performance_schema = OFF
innodb_buffer_pool_size = 64M
innodb_log_buffer_size = 8M
max_connections = 30
key_buffer_size = 8M
thread_cache_size = 4
host_cache_size = 0
EOF

systemctl restart mariadb
systemctl enable mariadb
```
Buat database dan user khusus aplikasi OLT:
```bash
mysql -u root << 'EOF'
CREATE DATABASE olt_database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'olt_user'@'localhost' IDENTIFIED BY 'PASSWORD_DB_KUAT_ANDA';
GRANT ALL PRIVILEGES ON olt_database.* TO 'olt_user'@'localhost';
FLUSH PRIVILEGES;
EOF
```

##### Opsi B (Alternatif Ultra-Ringan 0 MB RAM): Gunakan SQLite 3.45.1 yang Sudah Terpasang
Jika Anda ingin menghemat RAM 100% tanpa menjalankan daemon MariaDB (karena di VPS sudah terpasang **SQLite 3.45.1** yang mendukung penuh JSON modern), Anda **cukup lewati instalasi MariaDB** dan di Langkah 6 nanti atur `DB_CONNECTION=sqlite` di `.env`.

---

#### LANGKAH 4: Instal Composer (Lewati Node.js)
Node.js `v22.23.4` dan `npm 10.9.9` **sudah terpasang** di VPS Anda, jadi kita hanya perlu menginstal **Composer**:
```bash
curl -sS https://getcomposer.org/installer -o composer-setup.php
php composer-setup.php --install-dir=/usr/local/bin --filename=composer
rm composer-setup.php
composer --version
node -v
npm -v
```

---

#### LANGKAH 5: Setup Proyek Laravel di Web Directory
Buat direktori proyek dan atur kepemilikan user:
```bash
mkdir -p /var/www/ocean-lombok-tour
cd /var/www/ocean-lombok-tour
```

Clone atau salin file proyek Anda ke `/var/www/ocean-lombok-tour`, lalu pasang dependensi dan build aset frontend:
```bash
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader
npm ci || npm install
npm run build
```

---

#### LANGKAH 6: Konfigurasi Environment & Storage Link
```bash
cp .env.example .env
php artisan key:generate
nano .env
```
Sesuaikan parameter `.env` berikut (gunakan IP `http://43.156.112.35` jika belum mengarahkan domain, atau `https://domain-anda.com` jika domain sudah diarahkan ke `43.156.112.35`):
```ini
APP_NAME="Ocean Lombok Tour"
APP_ENV=production
APP_DEBUG=false
APP_TIMEZONE=Asia/Makassar
APP_URL=https://domain-anda.com

# Jika memakai Opsi A (MariaDB):
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=olt_database
DB_USERNAME=olt_user
DB_PASSWORD=PASSWORD_DB_KUAT_ANDA

# (Atau jika memilih Opsi B SQLite, cukup tulis: DB_CONNECTION=sqlite)

FILESYSTEM_DISK=public

# Akun admin pertama (dipakai seeder sekali saja, hapus ADMIN_PASSWORD setelah seeding)
ADMIN_USERNAME=admin
ADMIN_PASSWORD=GANTI_DENGAN_PASSWORD_KUAT_MIN_12_KARAKTER
```

Hubungkan folder storage ke public dan jalankan migrasi + seeder:
```bash
php artisan storage:link
php artisan migrate --force
php artisan db:seed --force
```

Setelah seeding berhasil, **wajib hapus `ADMIN_PASSWORD` dari `.env`**:
```bash
sed -i '/^ADMIN_PASSWORD=/d' .env
```

Atur izin folder untuk Nginx (`www-data`):
```bash
chown -R www-data:www-data /var/www/ocean-lombok-tour/storage
chown -R www-data:www-data /var/www/ocean-lombok-tour/bootstrap/cache
chmod -R 775 /var/www/ocean-lombok-tour/storage
chmod -R 775 /var/www/ocean-lombok-tour/bootstrap/cache
```

---

#### LANGKAH 7: Konfigurasi Virtual Host Nginx & Firewall (Aman untuk 9Router)
Buat file konfigurasi Nginx:
```bash
nano /etc/nginx/sites-available/ocean-lombok-tour
```
Tempelkan konfigurasi virtual host berikut (bisa diakses lewat domain maupun IP `43.156.112.35` untuk pengujian awal):
```nginx
server {
    listen 80;
    listen [::]:80;
    server_name domain-anda.com www.domain-anda.com 43.156.112.35;
    root /var/www/ocean-lombok-tour/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php index.html;
    charset utf-8;

    client_max_body_size 20M;

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

    # Penanganan endpoint dinamis Livewire 3 (jangan dicegat sebagai file statis)
    location ^~ /livewire/ {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Cache browser untuk aset gambar statis publik
    location ~* \.(jpg|jpeg|png|gif|ico|webp|svg|css|js)$ {
        try_files $uri /index.php?$query_string;
        expires 30d;
        add_header Cache-Control "public, no-transform";
    }
}
```

Aktifkan konfigurasi Nginx:
```bash
ln -s /etc/nginx/sites-available/ocean-lombok-tour /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl reload nginx
```

**Pastikan Port Terbuka di Tencent Cloud Security Group & UFW:**
1. Di **Tencent Cloud Console** $\rightarrow$ **Firewall / Security Group** untuk instance `43.156.112.35`, pastikan Inbound Rule mengizinkan port **80 (HTTP)** dan **443 (HTTPS)** (selain port 22 dan 20128 yang sudah ada).
2. Jika Anda mengaktifkan `ufw` di dalam Ubuntu, **wajib izinkan port 20128 (`9Router`) terlebih dahulu** agar tidak terputus:
   ```bash
   ufw allow OpenSSH
   ufw allow 'Nginx Full'
   ufw allow 20128/tcp comment '9Router AI API Router'
   ```

---

#### LANGKAH 8: Pasang Sertifikat SSL Gratis (HTTPS)
Setelah DNS A Record domain Anda mengarah ke `43.156.112.35`, pasang SSL Let's Encrypt:
```bash
apt install -y certbot python3-certbot-nginx
certbot --nginx -d domain-anda.com -d www.domain-anda.com
```

---

#### LANGKAH 9: Optimasi Produksi Laravel & Cek Kesehatan Server
```bash
cd /var/www/ocean-lombok-tour
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Verifikasi bahwa seluruh layanan (OLT Web + 9Router + Hermes) berjalan berdampingan dengan sehat dan RAM tetap longgar:
```bash
free -h
ss -tulpn | grep -E ':(80|443|20128)'
```

Sekarang website Anda aktif, hemat memori, dan siap digunakan dengan PHP 8.4:
- **Halaman Publik:** `https://domain-anda.com` (atau `http://43.156.112.35`)
- **Halaman Admin:** `https://domain-anda.com/admin`. Login dengan `ADMIN_USERNAME` dan password yang Anda isi di `.env`, lalu Anda akan langsung diarahkan untuk mengganti password.
