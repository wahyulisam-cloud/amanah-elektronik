# Amanah Elektronik — Backend & REST API

Backend dan REST API untuk **Amanah Elektronik Rental Management System**, sebuah sistem penyewaan perangkat elektronik yang digunakan untuk mengelola data alat, pelanggan, transaksi penyewaan, pengembalian, dan laporan.

## Tentang

Repository ini merupakan bagian **backend** dari sistem Amanah Elektronik.

Backend dikembangkan menggunakan **Laravel** dan menyediakan REST API yang digunakan oleh aplikasi **Admin Web** dan **Customer Mobile App**.

Sistem Amanah Elektronik terdiri dari tiga repository utama:

| Repository | Teknologi | Keterangan |
|---|---|---|
| **amanah-elektronik** | Laravel | Backend & REST API |
| **amanah-elektronik-admin** | React.js | Admin Web |
| **amanah-elektronik-mobile** | React Native & Expo | Aplikasi Pelanggan |

## Fitur

### Authentication
- Login admin
- JWT Authentication
- Protected API menggunakan middleware authentication
- Logout
- Get authenticated admin

### Management Data
- Manajemen kategori alat
- Manajemen alat
- Manajemen pelanggan
- Manajemen penyewaan
- Detail penyewaan
- Pengembalian alat

### Dashboard & Laporan
- Statistik dashboard
- Data statistik penyewaan
- Laporan penyewaan

### Rental System
- Perhitungan lama penyewaan
- Perhitungan total harga penyewaan
- Pengelolaan jumlah alat yang disewa
- Pengelolaan stok alat
- Status pembayaran
- Status pengembalian

## Teknologi

- PHP
- Laravel
- MySQL
- RESTful API
- JWT Authentication
- Postman

## Struktur API

API digunakan untuk menghubungkan backend dengan aplikasi frontend.

Beberapa endpoint utama yang tersedia:

```text
POST   /api/login

GET    /api/me
POST   /api/logout

GET    /api/dashboard
GET    /api/dashboard/chart

GET    /api/kategori
POST   /api/kategori
PUT    /api/kategori/{id}
DELETE /api/kategori/{id}

GET    /api/alat
POST   /api/alat
PUT    /api/alat/{id}
DELETE /api/alat/{id}

GET    /api/pelanggan
POST   /api/pelanggan
PUT    /api/pelanggan/{id}
DELETE /api/pelanggan/{id}

GET    /api/penyewaan
POST   /api/penyewaan
GET    /api/penyewaan/{id}

GET    /api/penyewaan-detail
