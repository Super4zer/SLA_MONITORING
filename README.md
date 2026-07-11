# SLA Monitoring Backend

Sistem ini adalah backend untuk Monitoring SLA (Service Level Agreement) Customer Support, yang menerima pesan dari WhatsApp (melalui webhook Wablas), memonitor waktu balas (respon), dan menyediakan API untuk Dashboard monitoring.

## Prasyarat
- PHP >= 8.1
- MySQL / MariaDB
- Composer (hanya untuk generate autoload PSR-4)

## Cara Setup

1. **Clone & Install Autoload**
   ```bash
   composer dump-autoload
   ```

2. **Konfigurasi Environment**
   Salin file `.env.example` ke `.env` dan sesuaikan nilainya:
   ```bash
   cp .env.example .env
   ```
   Atur koneksi database:
   ```ini
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_NAME=sla_monitoring
   DB_USER=root
   DB_PASS=password_anda
   ```

3. **Setup Database**
   Buat database `sla_monitoring` (atau sesuai konfigurasi), lalu jalankan file migrasi SQL:
   ```bash
   mysql -u root -p sla_monitoring < migrations/01_create_monitoring_tables.sql
   mysql -u root -p sla_monitoring < migrations/02_create_group_whitelist.sql
   ```
   *Catatan: Pastikan Anda menambahkan ID grup komplain resmi Anda ke tabel `ts_group_whitelist`.*

4. **Jalankan Server Lokal**
   Untuk kebutuhan development, Anda harus menjalankan server PHP dengan *document root* `public` dan menjadikan `index.php` sebagai *router script*-nya:
   ```bash
   php -S localhost:8000 -t public public/index.php
   ```
   *Note: Perintah di atas memastikan semua request API di-routing dengan benar, sekaligus melayani file HTML/CSS secara otomatis.*

## Testing Webhook via cURL / Postman

> **PENTING: Payload Wablas**  
> Struktur payload yang digunakan (`isGroup`, `groupId`, `phone`, `sender`, `message`) merupakan **asumsi umum** webhook. Saat akun Wablas aktif, **selalu cek log mentah (raw payload)** yang tersimpan otomatis di `logs/webhook.log` untuk memastikan nama field sudah sesuai dengan versi API Wablas yang Anda gunakan, dan sesuaikan di `WebhookController.php` bila perlu.

Berikut adalah contoh untuk mensimulasikan webhook dari Wablas:

**1. Simulasi Pesan Masuk dari Klien (CS belum membalas)**
```bash
curl -X POST http://localhost:8000/webhook/wablas \
-H "Content-Type: application/json" \
-d '{
    "isGroup": true,
    "groupId": "group-abc-123",
    "phone": "628999999999",
    "message": "Halo, ini komplain saya",
    "pushName": "Klien A"
}'
```
*Hasil:* Data akan masuk ke tabel `ts_sla_monitoring` dengan status KUNING.

**2. Simulasi Pesan Balasan dari Staff CS**
*(Misal nomor staff CS adalah `6281234567890` yang ada di `cs_staff_whitelist`)*
```bash
curl -X POST http://localhost:8000/webhook/wablas \
-H "Content-Type: application/json" \
-d '{
    "isGroup": true,
    "groupId": "group-abc-123",
    "phone": "6281234567890",
    "message": "Baik pak, akan kami proses.",
    "pushName": "CS Andi"
}'
```
*Hasil:* SLA akan dihitung. Jika balasan < 180 detik, status jadi HIJAU. Jika lebih, jadi MERAH.

## API Dashboard Endpoint

- **Menunggu Balasan (<= 180 detik)**
  `GET http://localhost:8000/api/monitoring/waiting`

- **Terlambat (Overdue / > 180 detik)**
  `GET http://localhost:8000/api/monitoring/overdue`

- **Selesai (Completed SLA)**
  `GET http://localhost:8000/api/monitoring/completed`

- **Tandai Selesai / Resolve**
  `POST http://localhost:8000/api/monitoring/1/resolve`

- **Eskalasi**
  `POST http://localhost:8000/api/monitoring/1/escalate`
  *(Bisa menambahkan payload JSON `{"client_name": "...", "complaint": "..."}`)*
