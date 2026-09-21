# Amanah Elektronik

> Sistem penyewaan perangkat elektronik yang terdiri dari aplikasi administrasi berbasis web, REST API, dan aplikasi mobile untuk pelanggan.

## Tentang Project

**Amanah Elektronik** adalah sistem informasi penyewaan perangkat elektronik yang dirancang untuk membantu proses pengelolaan alat, pelanggan, transaksi penyewaan, pembayaran, dan pengembalian.

Project ini dikembangkan dalam tiga bagian utama:

* **Admin Web** — digunakan oleh admin untuk mengelola sistem.
* **REST API** — menangani autentikasi, data, transaksi, dan komunikasi antar aplikasi.
* **Mobile App** — digunakan pelanggan untuk melihat alat dan melakukan proses penyewaan.

## Arsitektur Project

```text
                    ┌─────────────────────┐
                    │   React Native App  │
                    │      Pelanggan      │
                    └──────────┬──────────┘
                               │
                               │ REST API
                               ▼
┌─────────────────────┐   ┌─────────────────────┐
│     React Admin     │──▶│    Laravel API      │
│        Web          │   │      Backend        │
└─────────────────────┘   └──────────┬──────────┘
                                     │
                                     ▼
                              ┌─────────────┐
                              │    MySQL    │
                              └─────────────┘
```

## Fitur Utama

### Admin Web

* Login admin
* Dashboard statistik
* Pengelolaan kategori alat
* Pengelolaan data alat
* Pengelolaan pelanggan
* Pengelolaan penyewaan
* Pengembalian alat
* Laporan penyewaan

### Mobile App Pelanggan

* Registrasi dan login
* Melihat daftar alat
* Melihat detail alat
* Memilih alat untuk disewa
* Checkout penyewaan
* Melihat detail penyewaan
* Melihat riwayat penyewaan
* Pengelolaan profil pelanggan

### Backend

* RESTful API
* Autentikasi JWT
* Pengelolaan data alat
* Pengelolaan kategori
* Pengelolaan pelanggan
* Pengelolaan transaksi penyewaan
* Pengelolaan pengembalian
* Integrasi database MySQL

## Teknologi

| Bagian         | Teknologi                 |
| -------------- | ------------------------- |
| Admin Web      | React.js, JavaScript, CSS |
| Mobile         | React Native, TypeScript  |
| Backend        | Laravel, PHP              |
| Database       | MySQL                     |
| API            | RESTful API               |
| Authentication | JWT                       |
| API Testing    | Postman                   |

## Preview

### Admin Dashboard

> Screenshot dashboard akan ditambahkan di bagian ini.

### Mobile App

> Screenshot aplikasi mobile akan ditambahkan di bagian ini.

## Repository

| Repository                 | Deskripsi                                   |
| -------------------------- | ------------------------------------------- |
| [amanah-elektronik-api]    | Backend REST API menggunakan Laravel        |
| [amanah-elektronik-admin]  | Dashboard administrasi menggunakan React.js |
| [amanah-elektronik-mobile] | Aplikasi pelanggan menggunakan React Native |

## Peran Saya

Dalam project ini saya mengembangkan beberapa bagian sistem, meliputi:

* Mengembangkan antarmuka admin menggunakan React.js.
* Mengembangkan aplikasi mobile pelanggan menggunakan React Native.
* Mengembangkan dan mengintegrasikan RESTful API menggunakan Laravel.
* Mengelola database menggunakan MySQL.
* Mengintegrasikan frontend dan mobile dengan backend API.
* Melakukan pengujian API menggunakan Postman.

## Status Project

Project ini dikembangkan sebagai project pembelajaran dan pengembangan sistem penyewaan perangkat elektronik.

---

**Amanah Elektronik — Rental Management System**
