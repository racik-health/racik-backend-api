# Racik Backend (API Laravel)

<!-- <div align="center">
  <img src="https://placehold.co/200x200/10b981/FFFFFF?text=RacikAPI" alt="Logo Racik API" width="200"/>
  <p><em>API Cerdas untuk Rekomendasi Jamu Tradisional</em></p>
</div> -->

## 📝 Deskripsi

Backend **Racik** adalah sebuah RESTful API yang dibangun menggunakan **Laravel**. API ini bertugas untuk melayani semua kebutuhan data dan logika bisnis untuk aplikasi frontend "Racik", platform rekomendasi jamu tradisional Indonesia berbasis AI.

Fungsi utama API ini meliputi:

- Manajemen autentikasi pengguna (register, login, logout, SSO dengan Google).
- Pengelolaan data profil pengguna.
- Pemrosesan dan penyimpanan data analisis gejala pengguna.
- Penyediaan data rekomendasi jamu yang dihasilkan oleh sistem AI.
- Pengelolaan data untuk dashboard pasien.
- Fitur administrasi untuk mengelola data jamu, pengguna, dll.

API ini dirancang untuk digunakan oleh aplikasi frontend Racik (ReactJS) yang dapat diakses di [repo berikut](https://github.com/racik-health/racik-web-ui).

## ⚙️ Cara Menjalankan Proyek

Untuk menjalankan proyek backend API Racik di lingkungan pengembangan lokal Anda, ikuti langkah-langkah berikut:

1.  **Clone repository ini** (Pastikan Anda berada di direktori yang diinginkan):

    ```bash
    git clone https://github.com/racik-health/racik-backend-api.git
    cd racik-backend-api
    ```

2.  **Install semua dependencies PHP** menggunakan Composer:

    ```bash
    composer install
    ```

3.  **Buat file `.env` dari contoh**:
    Salin file `.env.example` menjadi `.env`. File ini berisi konfigurasi environment aplikasi.

    ```bash
    cp .env.example .env
    ```

4.  **Generate Application Key**:
    Kunci ini penting untuk enkripsi dan keamanan sesi.

    ```bash
    php artisan key:generate
    ```

5.  **Konfigurasi Database**:

    - Buat database baru di MySQL (atau DBMS lain yang Anda gunakan). Misalnya, beri nama `racik_health` (sesuai variabel `.env` yang Anda berikan).
    - Buka file `.env` yang baru Anda buat dan sesuaikan konfigurasi database berikut:
        ```env
        DB_CONNECTION=mysql
        DB_HOST=127.0.0.1
        DB_PORT=3306
        DB_DATABASE=racik_health # Pastikan nama database ini sudah dibuat
        DB_USERNAME=root         # Ganti dengan username database Anda
        DB_PASSWORD=             # Ganti dengan password database Anda (kosongkan jika tidak ada)
        ```

6.  **Konfigurasi Variabel Environment Lainnya**:
    Masih di file `.env`, pastikan Anda mengisi variabel-variabel penting berikut beserta penjelasannya:

    - **Admin Default:**

        ```env
        ADMIN_PASSWORD=ISI_DENGAN_PASSWORD_ADMIN_DEFAULT
        ```

        Password default untuk akun admin utama aplikasi. Digunakan saat seeding data admin pertama kali. Gantilah dengan password yang kuat.

    - **Frontend URL:**

        ```env
        FRONTEND_BASE_URL=http://localhost:3000
        ```

        Alamat URL aplikasi frontend Racik. Digunakan untuk kebutuhan CORS dan redirect autentikasi.

    - **Google Gemini API:**

        ```env
        GEMINI_API_KEY=ISI_DENGAN_KUNCI_API_GEMINI_ANDA
        ```

        Kunci API untuk mengakses layanan Google Gemini (AI). Diperlukan untuk fitur rekomendasi berbasis AI.

    - **Google Single Sign-On (SSO):**

        ```env
        GOOGLE_CLIENT_ID=ISI_DENGAN_GOOGLE_CLIENT_ID_ANDA
        GOOGLE_CLIENT_SECRET=ISI_DENGAN_GOOGLE_CLIENT_SECRET_ANDA
        GOOGLE_REDIRECT_URL=http://localhost:8000/api/v1/auth/google/callback
        ```

        Konfigurasi OAuth untuk login menggunakan akun Google. Pastikan `GOOGLE_REDIRECT_URL` sesuai dengan endpoint backend Anda.

    - **Firebase:**
        ```env
        FIREBASE_PROJECT_ID=ISI_DENGAN_FIREBASE_PROJECT_ID_ANDA
        FIREBASE_CREDENTIALS=ISI_DENGAN_JSON_CREDENTIALS_FIREBASE
        FIREBASE_DATABASE_URL=ISI_DENGAN_FIREBASE_DATABASE_URL
        ```
        Konfigurasi untuk integrasi dengan Firebase.
        - `FIREBASE_PROJECT_ID`: ID proyek Firebase Anda.
        - `FIREBASE_CREDENTIALS`: String JSON credential service account Firebase (bisa disimpan dalam satu baris, escape karakter newline).
        - `FIREBASE_DATABASE_URL`: URL database Firebase Anda.

    Pastikan semua variabel di atas sudah diisi dengan benar agar aplikasi dapat berjalan dengan baik.

7.  **Jalankan Migrasi Database dan Seeder**:
    Ini akan membuat struktur tabel di database Anda dan mengisi data awal.

    ```bash
    php artisan migrate --seed
    ```

8.  **Setup Storage Link**:

    ```bash
    php artisan storage:link
    ```

9.  **Optimasi Konfigurasi (Opsional untuk Development, Penting untuk Produksi)**:

    ```bash
    php artisan config:cache
    php artisan route:cache
    ```

    Untuk development, Anda mungkin ingin menjalankan `php artisan config:clear` dan `php artisan route:clear` jika ada perubahan pada file konfigurasi atau rute.

10. **Jalankan Development Server Laravel**:
    ```bash
    php artisan serve
    ```
    Secara default, server akan berjalan di `http://localhost:8000`.

## 🚀 Deploy

Detail langkah deploy akan bergantung pada platform hosting yang Anda pilih (misalnya, Heroku, AWS, DigitalOcean, atau server VPS lainnya). Pastikan untuk mengkonfigurasi variabel environment dengan benar di server produksi Anda.

## 🤝 Berkontribusi

Jika Anda menemukan bug atau ingin menambahkan fitur, silakan buat _issue_ atau _pull request_. Kontribusi sangat dihargai!
