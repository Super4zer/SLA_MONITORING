# SLA Monitoring — Backend

Backend PHP native (tanpa framework) untuk memantau kecepatan respon tim Customer Support terhadap komplain klien di grup WhatsApp, lewat webhook Wablas.

> **Status: Prototype.** Fokus saat ini memastikan alur data (webhook → database → dashboard) berjalan benar. Fitur login/otentikasi sengaja belum diaktifkan.

---

## Stack

| | |
|---|---|
| Bahasa | PHP 8.5 (native, tanpa Laravel/CodeIgniter) |
| Database | MySQL / MariaDB |
| Autoload | Composer (PSR-4 saja) |
| Integrasi WhatsApp | Wablas (webhook) |

---

## Setup

### 1. Install autoload

```bash
composer dump-autoload
```

### 2. Environment

```bash
cp .env.example .env
```

Isi `.env`:

```ini
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=sla_monitoring
DB_USER=root
DB_PASS=password_anda
```

### 3. Database

```bash
mysql -u root -p sla_monitoring < migrations/01_create_monitoring_tables.sql
mysql -u root -p sla_monitoring < migrations/02_create_group_whitelist.sql
```

Tambahkan minimal satu baris ke `ts_group_whitelist` dan `cs_staff_whitelist` supaya webhook tidak ditolak — lihat [Konfigurasi Whitelist](#konfigurasi-whitelist).

### 4. Jalankan server

```bash
php -S localhost:8000 -t public public/index.php
```

`public/index.php` bertindak sebagai router script: request ke file yang benar-benar ada (CSS/JS/gambar) dilayani langsung, sisanya diteruskan ke `routes/api.php`.

### 5. (Opsional) Ekspos ke internet untuk terima webhook asli

```bash
ngrok http 8000
```

Pakai domain statis (`ngrok http --domain=nama-tetap.ngrok-free.app 8000`) supaya URL tidak berubah tiap restart.

---

## Konfigurasi Whitelist

Webhook menolak pesan dari grup atau nomor yang belum terdaftar — ini bukan bug, ini validasi.

```sql
INSERT INTO ts_group_whitelist (group_id, group_name, is_active)
VALUES ('120363410859443191', 'Nama Grup Komplain', 1);

INSERT INTO cs_staff_whitelist (phone_number, staff_name, is_active)
VALUES ('6281234567890', 'Nama Staff', 1);
```

`group_id` harus **persis** sama dengan yang tersimpan di `logs/webhook.log` — jangan diketik manual/ditebak.

---

## Struktur Payload Wablas (dari data asli)

Payload nyata dari Wablas berbentuk nested, **bukan flat**:

```json
{
  "isGroup": true,
  "group": {
    "group_id": "120363410859443191",
    "sender": "6282327395632",
    "subject": "Nama Grup"
  },
  "phone": "120363410859443191",
  "message": "isi pesan",
  "pushName": "Nama Pengirim"
}
```

| Field | Isinya |
|---|---|
| `group.group_id` | ID grup asli |
| `group.sender` | Nomor pengirim pesan |
| `phone` (level atas) | Sama dengan `group.group_id`, **bukan** nomor pengirim |

Setiap payload masuk otomatis dicatat ke `logs/webhook.log` — cek file itu kalau field API berubah di kemudian hari.

---

## Testing Webhook

**Simulasi komplain baru dari klien:**

```bash
curl -X POST http://localhost:8000/webhook/wablas \
  -H "Content-Type: application/json" \
  -d '{
    "isGroup": true,
    "group": { "group_id": "120363410859443191", "sender": "628999999999" },
    "phone": "120363410859443191",
    "message": "Halo, ini komplain saya",
    "pushName": "Klien Test"
  }'
```

**Simulasi balasan staff CS** (nomor harus ada di `cs_staff_whitelist`):

```bash
curl -X POST http://localhost:8000/webhook/wablas \
  -H "Content-Type: application/json" \
  -d '{
    "isGroup": true,
    "group": { "group_id": "120363410859443191", "sender": "6281234567890" },
    "phone": "120363410859443191",
    "message": "Baik, akan segera kami proses.",
    "pushName": "CS Andi"
  }'
```

---

## Alur Status Komplain

```
Komplain masuk
      │
      ▼
┌─────────────┐   >180 detik belum dibalas   ┌──────────────┐
│Belum Direspon├─────────────────────────────►│ Lebih dari   │
└──────┬──────┘                               │  3 Menit     │
       │ dibalas ≤180 detik                   └──────┬───────┘
       ▼                                             │
┌─────────────┐                                      │
│   Ontime    │◄─────── dibalas, tapi >180 detik ────┘
└──────┬──────┘
       │
       │  staff klik tombol "Resolve"
       ▼
┌─────────────┐
│Terselesaikan│
└─────────────┘
```

**Penting:** status *Ontime* dan *Lebih dari 3 Menit* **tidak otomatis** pindah ke *Terselesaikan* hanya karena sudah dibalas. Harus ditekan tombol **Resolve** secara manual.

---

## API Endpoints

### Dashboard (GET)

| Endpoint | Isi |
|---|---|
| `/api/monitoring/waiting` | Belum direspon, masih ≤3 menit |
| `/api/monitoring/overdue` | Lebih dari 3 menit (belum/sudah dibalas, belum di-resolve) |
| `/api/monitoring/ontime` | Sudah dibalas ≤3 menit, belum di-resolve |
| `/api/monitoring/completed` | Sudah ditekan tombol Resolve |

### Aksi (POST)

| Endpoint | Fungsi |
|---|---|
| `/api/monitoring/{id}/resolve` | Tandai selesai, pindah ke Terselesaikan |
| `/api/monitoring/{id}/escalate` | Kirim ke `log.klikdsi` (`{"client_name": "...", "complaint": "..."}`) — **belum terintegrasi API asli, masih placeholder** |

### Webhook (POST)

| Endpoint | Fungsi |
|---|---|
| `/webhook/wablas` | Menerima seluruh event dari Wablas |

---

## Struktur Proyek

```
public/
├── index.php          ← entry point / router script
├── view/               ← halaman (dashboard.php, login.php - belum aktif)
├── js/, css/, images/
routes/
└── api.php             ← daftar semua route
src/
├── Config/              ← koneksi DB, .env loader
├── Controllers/
├── Models/
└── Routing/             ← class Router
migrations/              ← file SQL urut
logs/
└── webhook.log          ← payload mentah tiap webhook masuk
```

---

## Belum Selesai / Diketahui

- Login belum aktif (sengaja, prototype dulu).
- Integrasi `log.klikdsi` di endpoint `escalate` masih placeholder — menunggu dokumentasi API resmi.
- Countdown timer & auto-refresh dashboard: polling tiap beberapa detik, belum WebSocket.
