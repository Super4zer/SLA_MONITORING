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
   ```

4. **Jalankan Server Lokal**
   Untuk kebutuhan development, Anda bisa menggunakan built-in web server PHP:
   ```bash
   php -S localhost:8000 -t public
   ```
   *Note: Pastikan traffic diarahkan ke direktori `public`.*

## Testing Webhook via cURL / Postman

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
