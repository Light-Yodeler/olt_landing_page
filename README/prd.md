# Product Requirements Document (PRD)
## Ocean Lombok Tour & Transport — Dynamic CMS & Landing Page Platform

---

### 1. Product Overview & Vision

#### 1.1 Background
**Ocean Lombok Tour & Transport (OLT)** adalah penyedia jasa rental kendaraan (motor & mobil), layanan antar-jemput bandara (*airport transfer* Lombok Praya - LOP), dan paket tur wisata privat (Gunung Rinjani, Air Terjun Tiu Kelep, Surfing Selong Belanak/Gerupuk, serta Kepulauan 3 Gili) yang berbasis di Kuta Mandalika, Lombok Tengah, Nusa Tenggara Barat.

Sebelumnya, web OLT berupa berkas HTML statis tunggal (`ocean_lombok_tour_landing_page.html`). Untuk meningkatkan daya saing, konversi pemesanan via WhatsApp, dan kemandirian operasional, pemilik bisnis memerlukan platform dinamis yang dilengkapi **Halaman Admin (CMS)** agar seluruh aset visual (logo, favicon, slider latar belakang, foto destinasi) dan teks konten (deskripsi, tarif, nomor kontak, FAQ) dapat diubah langsung tanpa menyentuh kode program.

#### 1.2 Target Audiens
1. **Wisatawan Mancanegara (International Travelers - 60%):** Mencari sewa motor harian/mingguan di Kuta Mandalika, kelas selancar, paket trekking Rinjani, dan *private boat* 3 Gili dengan bahasa pengantar bahasa Inggris.
2. **Wisatawan Domestik (Indonesian Tourists - 40%):** Mencari carter mobil keluarga, antar-jemput bandara LOP, dan tur budaya (Desa Sade, Sukarara, Bukit Merese) dalam bahasa Indonesia.
3. **Pemilik Bisnis & Staf Operasional (Admin/Manager):** Mengelola ketersediaan armada, memperbarui foto armada/destinasi, mengganti nomor WhatsApp operasional, dan mengubah promosi musiman.

#### 1.3 Key Value Proposition
- **Bilingual First:** Bahasa Inggris (*default*) untuk turis global + Bahasa Indonesia untuk pasar domestik dengan tombol pengalih bahasa (*language switcher*) yang mulus.
- **Full Visual & Content Independence:** Pemilik web memiliki kendali 100% atas semua teks, logo, foto destinasi, dan tombol aksi WhatsApp.
- **Conversion-Driven:** Tombol pemesanan langsung terhubung ke WhatsApp dengan pesan pre-fill otomatis sesuai layanan/destinasi yang diklik tamu.
- **Simple, Hassle-Free Authentication:** Login admin ringkas hanya menggunakan **Username** dan **Password** (tanpa kerumitan verifikasi email, konfigurasi server SMTP, atau reset password email).
- **Modern & Security-Hardened Runtime:** Dijalankan di atas runtime **PHP 8.4** yang selalu diperbarui ke rilis patch keamanan terbaru.

---

### 2. User Personas & Use Cases

| Persona | Profil & Kebutuhan | Skenario Penggunaan Utama |
| :--- | :--- | :--- |
| **Sarah (28, Turis Australia)** | Solo traveler berselancar di Kuta Mandalika. Menggunakan smartphone, butuh sewa motor matic dan instruktur surfing. | Membuka landing page (bahasa Inggris), melihat galeri foto dan armada motor, mengklik tombol WhatsApp untuk booking motor diantar langsung ke homestay. |
| **Bambang (38, Wisatawan Jakarta)** | Berlibur bersama keluarga (4 orang). Butuh sewa mobil Avanza/Innova + driver dan rute wisata ramah anak. | Membuka website, mengganti bahasa ke Bahasa Indonesia, membaca komitmen standar layanan, menanyakan ketersediaan penjemputan bandara via WhatsApp. |
| **Owner / Operator OLT** | Pemilik usaha tour & rental, mengelola bisnis dari smartphone / laptop, minim pengalaman teknis koding. | Masuk ke `/admin` menggunakan username dan password sederhana, mengunggah foto armada baru, dan memperbarui nomor WhatsApp tanpa pusing urusan verifikasi email. |

---

### 3. Functional Requirements (Fitur & Spesifikasi)

#### 3.1 Public Landing Page (Frontend)

##### 3.1.1 Header & Navigasi
- **Logo & Brand Identity:** Menampilkan logo resmi OLT (gambar raster atau fallback SVG) dan nama brand "Ocean Lombok Tour".
- **Menu Navigasi:** Tautan *smooth-scroll* ke bagian:
  - Layanan (*Services*)
  - Destinasi (*Destinations*)
  - Tentang Kami (*About Us*)
  - FAQ
- **Language Switcher (Pengalih Bahasa):**
  - Toggle tautan bahasa: `EN` (English - default di `/`) dan `ID` (Bahasa Indonesia di `/id`). Setiap halaman menyertakan tag `<link rel="alternate" hreflang="..." />` untuk perayap mesin pencari Google, dengan penyimpanan preferensi di cookie.
- **Navigasi Responsif (Mobile Menu):**
  - Tombol hamburger dan panel menu geser/drawer untuk layar smartphone/tablet agar seluruh tautan seksi dan tombol aksi tetap mudah dijangkau.
- **Header CTA:** Tombol "Check Availability" (EN) / "Tanya Ketersediaan" (ID) yang mengarah ke link WhatsApp dengan template pesan kontekstual.

##### 3.1.2 Hero Section & Slideshow
- **Background Carousel / Slideshow:**
  - Slider foto latar belakang resolusi tinggi (destinasi Lombok seperti Bukit Merese, Rinjani, Pantai Kuta, Tiu Kelep).
  - Efek transisi halus (*fade & slow zoom*), navigasi indikator garis di bagian bawah kiri, auto-play interval 5 detik.
- **Headline & Tagline:**
  - Badge kategori dinamis (misal: "Tour & Transport Service in Kuta Mandalika").
  - Judul editorial besar (*headline*) dan paragraf pembuka.
- **Aksi Cepat (CTAs):**
  - Tombol utama: "Contact Our Team" (WhatsApp trigger).
  - Tombol sekunder: "Explore Destinations" (scroll ke galeri destinasi).
- **Hero Quick Info Card:**
  - Menampilkan logo OLT bulat, 4 poin keunggulan singkat (armada terawat, antar-jemput bandara LOP, pemandu berlisensi, jaringan aman), dan link konsultasi instan.

##### 3.1.3 Layanan Utama (Services Section)
- Menampilkan 4 kartu layanan standar (dapat ditambah/dikurangi oleh admin):
  1. *Motorbike Rental* (Sewa Sepeda Motor)
  2. *Car Charter & Airport Transfer* (Mobil & Antar-Jemput Bandara)
  3. *Mount Rinjani Trekking & Waterfalls* (Trekking Rinjani & Air Terjun)
  4. *Surfing Lessons & 3 Gili Trips* (Surfing & Trip 3 Gili)
- Setiap kartu memiliki:
  - Kode/kategori badge kecil (misal: `BIKE RENTAL`, `TRANSPORT`).
  - Ikon (Lucide icon).
  - Judul & deskripsi ringkas.
  - 2 poin rincian fitur/keunggulan.
  - Tombol aksi dengan pesan WhatsApp spesifik per layanan.

##### 3.1.4 Tentang Kami & Jaringan Terpercaya (About Section)
- Menampilkan narasi latar belakang Ocean Lombok Tour, lokasi operasional di Kuta Mandalika, serta penjelasan kolaborasi dengan mitra sopir dan pemandu lokal terverifikasi.

##### 3.1.5 Galeri Destinasi Wisata (Destinations Section)
- Menampilkan grid kartu destinasi (default 6 destinasi unggulan):
  1. *Mount Rinjani & Segara Anak Lake* (Trekking & Mountain)
  2. *Kuta Mandalika & Merese Hill* (South Coast)
  3. *Tiu Kelep & Sendang Gile Waterfalls* (Jungle & Geopark)
  4. *Selong Belanak Beach & Gerupuk Bay* (Surfing Lessons & Trips)
  5. *3 Gili Islands: Trawangan, Meno & Air* (Islands & Coral Reefs)
  6. *Sade Traditional Village & Sukarara Weaving* (Sasak Culture)
- Setiap kartu dilengkapi:
  - Foto utama dengan efek *hover scale*.
  - Label kategori / zona.
  - Nama destinasi & deskripsi ringkas.
  - Informasi wilayah (Lombok Tengah, Utara, Timur, Barat).
  - Tombol WhatsApp "Inquire Tour" / "Tanya Tur".

##### 3.1.6 Komitmen & Standar Operasional (Standards Section)
- 3 pilar pelayanan OLT:
  1. *Joint Vehicle Physical Inspection* (Pengecekan fisik kendaraan & foto bersama).
  2. *Responsive On-Road Assistance* (Bantuan darurat di jalur Kuta - Bandara).
  3. *Licensed Tour & Trekking Guides* (Pemandu resmi & standar keselamatan).

##### 3.1.7 Frequently Asked Questions (FAQ Section)
- Accordion interaktif yang menampilkan pertanyaan seputar syarat sewa, antar-jemput bandara LOP, persiapan Rinjani, dan cara booking perahu ke Gili.

##### 3.1.8 Bottom Banner & Footer
- **Bottom Banner (Consultation CTA):**
  - Judul editorial ajakan konsultasi (Bilingual).
  - Paragraf deskripsi rencana perjalanan (Bilingual).
  - Tombol WhatsApp dengan template pesan konsultasi khusus (Bilingual).
- Footer lengkap dengan logo OLT, deskripsi entitas, nomor kontak WhatsApp, alamat fisik di Kuta Mandalika, dan hak cipta.
- Floating WhatsApp button di sudut kanan bawah layar yang selalu mengambang (*sticky*).

---

#### 3.2 Content Management System (Admin Panel — Filament v3)

##### 3.2.1 Autentikasi Sederhana (Simple Authentication)
- **Kredensial Sederhana:** Login hanya memerlukan **Username** dan **Password** (tanpa email, tanpa verifikasi email, tanpa pengiriman OTP).
- **Tanpa Reset Password Email:** Tidak ada alur reset password berbasis email/SMTP. Hal ini menghilangkan kebutuhan setting mail server/SMTP pihak ketiga.
- **Manajemen Mandiri Kata Sandi:** Pemilik dapat memperbarui username atau kata sandinya kapan saja dari dalam menu profil saat login, atau melalui perintah baris perintah sederhana (`php artisan tinker`) jika kata sandi terlupa.
- **Akun Admin Pertama Tanpa Password Default:** Username dan password admin pertama diambil dari `.env` (`ADMIN_USERNAME`, `ADMIN_PASSWORD`) saat *seeding*. Tidak ada password bawaan yang tertulis di kode.
- **Wajib Ganti Password Saat Login Pertama:** Akun hasil *seeding* (atau yang password-nya di-reset lewat terminal) ditandai wajib ganti password. Selama belum diganti, admin hanya bisa membuka halaman profil dan logout.
- **Proteksi Akses:** Rute admin terproteksi di `/admin` dengan pembatasan percobaan login (*rate limiting* 5x gagal per menit) untuk mencegah serangan *brute force*.

##### 3.2.2 Pengaturan Umum Identitas (*Site Settings*)
Pemilik dapat mengubah:
- **Logo Utama:** Unggah file PNG/SVG/WebP (otomatis memperbarui logo di navbar, kartu hero, banner bawah, dan footer).
- **Favicon:** Unggah icon browser (`.ico` atau `.png`).
- **Nama Perusahaan & Slogan:** Dikelola dalam dua bahasa (EN & ID).
- **Nomor WhatsApp Utama:** Format internasional (contoh: `6287865432190`).
- **Alamat Kantor:** Alamat fisik di Kuta Mandalika.
- **Link Media Sosial:** Instagram, Facebook, TikTok, TripAdvisor, Google Maps.
- **Teks Hak Cipta (*Footer Copyright*).**

##### 3.2.3 Manajemen Hero Slideshow (*Hero Slider Manager*)
- Daftar foto slider aktif dengan fitur drag-and-drop urutan (*reordering*).
- Form tambah/edit slide:
  - Unggah foto latar belakang (format JPG/WebP, resolusi disarankan 1920x1080).
  - Judul/caption internal slide & teks alternatif (Alt Text) bilingual (EN & ID).
  - Status aktif / tidak aktif (*toggle switch*).

##### 3.2.4 Manajemen Teks Hero, Kartu Sambutan & Banner Bawah
- Ubah teks badge atas (EN & ID).
- Ubah teks Headline utama (EN & ID).
- Ubah teks paragraf sambutan (EN & ID).
- Ubah 4 butir poin keunggulan pada kartu ringkasan hero.
- Ubah teks judul, narasi, dan pesan WhatsApp pada Banner Konsultasi Bawah (*Consultation CTA Banner*).

##### 3.2.5 Manajemen Layanan (*Services Manager*)
- CRUD layanan.
- Field yang dikelola:
  - Kode Kategori (misal: `SEWA MOTOR` / `BIKE RENTAL`).
  - Pemilihan Ikon (pilihan dropdown Lucide icon: bike, car, mountain, waves, map-pin, compass, dll).
  - Judul Layanan (Bilingual EN & ID).
  - Deskripsi Layanan (Bilingual EN & ID).
  - Butir Fitur / Keunggulan (List array/repeater, bilingual).
  - Teks Tombol & Pesan Default WhatsApp (Bilingual).
  - Urutan tampilan (*sort order*) dan status tayang (*is_active*).

##### 3.2.6 Manajemen Destinasi Wisata (*Destinations Manager*)
- CRUD destinasi wisata.
- Field yang dikelola:
  - Foto Destinasi (unggah gambar, auto resize & kompresi WebP).
  - Nama Destinasi (Bilingual EN & ID).
  - Badge Kategori (misal: "Trekking & Mountain", "South Coast").
  - Deskripsi Singkat (Bilingual EN & ID).
  - Lokasi / Wilayah (Bilingual EN & ID, misal: "East & North Lombok" / "Lombok Timur & Utara").
  - Teks Tombol Aksi (Bilingual EN & ID, misal: "Inquire Tour →" / "Tanya Tur →").
  - Pesan Template WhatsApp khusus saat destinasi ini diklik.
  - Urutan & Status Aktif.

##### 3.2.7 Manajemen Standar Pelayanan & FAQ (*Standards & FAQ Manager*)
- CRUD 3 pilar komitmen pelayanan (Judul & Deskripsi bilingual).
- CRUD FAQ accordion (Pertanyaan & Jawaban bilingual, urutan tampilan).

---

### 4. Non-Functional Requirements (Kualitas & Performa)

1. **Performa & Kecepatan Akses (Core Web Vitals):**
   - Largest Contentful Paint (LCP) < 2.5 detik.
   - Interaction to Next Paint (INP) < 200ms (Core Web Vitals standar 'Good', menggantikan FID yang telah usang).
   - Cumulative Layout Shift (CLS) < 0.1.
   - Semua gambar yang diunggah melalui admin otomatis di-resize dan dikonversi ke format `.webp`.
2. **Search Engine Optimization (SEO):**
   - Tag `<title>` dan `<meta description>` dinamis untuk masing-masing bahasa (EN & ID).
   - Dukungan OpenGraph meta tags (`og:title`, `og:image`, `og:description`).
   - Tag alternatif bahasa: `<link rel="alternate" hreflang="en" ...>` dan `<link rel="alternate" hreflang="id" ...>`.
3. **Keamanan (Security & Vulnerability Mitigation):**
   - **Pembaruan Keamanan PHP:** Server wajib menjalankan **PHP 8.4** dengan patch keamanan terbaru dan diperbarui rutin (`apt upgrade`) mengikuti pengumuman resmi di php.net.
   - Proteksi CSRF (Cross-Site Request Forgery) di seluruh formulir.
   - Validasi ketat MIME type dan ukuran maksimal pada upload foto (maksimal 5MB, hanya JPG/PNG/WebP/SVG).
   - Password hashing menggunakan bcrypt dengan cost factor standar Laravel.
   - Perlindungan XSS (Cross-Site Scripting) melalui Blade auto-escaping.
   - Konfigurasi SSL/TLS gratis via Let's Encrypt Certbot pada server Nginx.
4. **Kehandalan Server VPS (Reliability & Resource Efficiency):**
   - Berjalan stabil pada VPS dengan RAM minimal 1 GB - 2 GB (Ubuntu 22.04 / 24.04 LTS).
   - Bebas antrian email (*mail queue*) atau SMTP server eksternal karena alur verifikasi dan reset password email ditiadakan.

---

### 5. Ruang Lingkup & Keterbatasan (Scope & Boundaries)

- **In-Scope:**
  - Landing page responsif publik (Bahasa Inggris & Bahasa Indonesia).
  - Admin panel Filament v3 dengan login sederhana (Username + Password).
  - Pengelolaan seluruh foto, logo, teks, layanan, destinasi, standar, dan FAQ.
  - Integrasi click-to-chat WhatsApp dengan pesan otomatis kontekstual.
  - Skrip instalasi & deployment lengkap untuk VPS Linux dengan runtime PHP 8.4 (patch keamanan terbaru).
- **Out-of-Scope (Fase Selanjutnya):**
  - Reset password email & verifikasi email (ditiadakan sesuai permintaan agar praktis & bebas konfigurasi SMTP).
  - Payment gateway online otomatis (Midtrans/Xendit).
  - Kalender ketersediaan armada real-time.
