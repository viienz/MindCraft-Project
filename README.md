# MindCraft Project

Sistem manajemen kursus online dengan fitur multi-role (Mentor, Mentee, Admin) serta dukungan transaksi pembayaran.

## 📁 Struktur Proyek

```
MindCraft-Project/
├── config/
├── controller/
├── database/
│   └── mindcraft.sql
├── model/
├── views/
│   ├── landingpage/
│   ├── admin/
│   ├── mentor/
│   ├── mentee/
│   └── payment/
└── index.php
```

---

## 🚀 Cara Akses dan Menjalankan Proyek

### 1. Buat Database

- Buka `localhost/phpmyadmin`.
- Buat database baru dengan mengimpor file `mindcraft.sql` yang ada di folder `database/`.

### 2. Akses Landing Page

Buka browser dan akses:

```
http://localhost/MindCraft-Project/views/landingpage/landingpage.php
```

### 3. Registrasi Pengguna

- Lakukan registrasi sebagai **mentor** atau **mentee**.
- Data akan otomatis disimpan di database.
- Setelah registrasi:
  - Pengguna **mentee** akan diarahkan ke dashboard mentee.
  - Pengguna **mentor** akan diarahkan ke dashboard mentor.

### 4. Logout

- **Mentee:** Klik ikon **user** di pojok atas.
- **Mentor:** Klik opsi **logout** di sidebar.

### 5. Login Admin

Akses halaman login admin melalui:

```
http://localhost/MindCraft-Project/views/admin/login.php
```

Gunakan akun berikut:

- **Email:** `admin@mindcraft.com`
- **Password:** `admin123`

### 6. Fitur Pembayaran

Untuk melakukan pembayaran:

```
http://localhost/MindCraft-Project/views/payment/payment_form.php
```

Data transaksi akan otomatis disimpan ke tabel `transactions` di database.

---

## 🛠 Teknologi yang Digunakan

- PHP Native (tanpa framework)
- MySQL
- HTML, CSS, JavaScript

---

## 📌 Catatan

- Pastikan XAMPP atau server lokal Anda aktif.
- Letakkan folder `MindCraft-Project` di dalam `htdocs`.
- Aktifkan modul **Apache** dan **MySQL** di XAMPP Control Panel.

---

## 👨‍💻 Developer
- **23523001 - Alma Nurul Salma**: *(PM + Develop Mentor)*
- **23523228 - Qanzul Arays**: *(Backend + Infrastructure + Admin)*
- **23523005 - Laudza Muhammad Anwar Lahiz**: *(Develop Student)*
- **23523147 - Devin Pandya Subarkah**: *(AI Integration)*

> Dibuat untuk keperluan pengembangan sistem kursus berbasis web dengan arsitektur MVC sederhana.
