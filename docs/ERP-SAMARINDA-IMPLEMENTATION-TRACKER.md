# ERP Samarinda — Implementation Tracker

Dokumen ini melacak eksekusi `docs/ERP-SAMARINDA-CONTEXT-AND-UI-AUDIT.md` (P0/P1) dan
`docs/ERP-SAMARINDA-UI-INTERAKTIF-PROMPT.md` (UI interaktif).

Legenda: `[ ]` belum, `[~]` sebagian, `[x]` selesai + terverifikasi, `[-]` ditunda / di luar scope sesi.

## Baseline Terkini

- [x] `php artisan test` — 47 passed, 113 assertions
- [x] `vendor/bin/phpstan analyse` — 0 error
- [x] `vendor/bin/pint --test` — passed
- [x] Migrasi `notifications`, `media`, `document_sequences` sudah dijalankan
- [x] `public/storage` tersedia
- [x] Timezone efektif `Asia/Makassar`, locale `id`
- [x] Seeder operasional idempoten (role, COA, unit, kategori, gudang, PPN 11%)
- [x] API Sanctum + feature test
- [x] Dashboard role-aware + chart + widget stok/pembelian/AR

## Fase P0 — Stabilisasi

### Konfigurasi & fondasi

- [x] Timezone efektif `Asia/Makassar` (config membaca `APP_TIMEZONE`)
- [x] Locale `id`, `Carbon::setLocale('id')`, format tanggal Indonesia
- [x] Migrasi `notifications` + `media`, storage link
- [x] Branding panel, grouping navigasi Bahasa Indonesia, widget generik dihapus
- [x] Validasi maxLength/unique selaras database pada master dan field kunci transaksi
- [x] Seeder data dasar idempoten + role/permission domain

### Nomor dokumen

- [x] Satu generator nomor aman race condition untuk PO, GRN, SO, Invoice, Payment, Journal
- [x] Tidak ada input nomor manual di UI

### Domain action

- [x] `CreateGoodsReceiptNoteAction` (header + item)
- [x] `ConfirmSalesOrderAction` (credit limit)
- [x] `PostDeliveryAction` (stok keluar, jurnal COGS, quantity_delivered)
- [x] `VoidSalesInvoiceAction` (jurnal balik, guard pembayaran)
- [x] `IssueSalesInvoiceAction` memposting jurnal + pajak
- [x] `PostGoodsReceiptNoteAction` memposting jurnal + validasi sisa PO
- [x] `RecordPaymentAction` memvalidasi sisa + jurnal kas/bank
- [x] `PostJournalAction` mendukung jurnal manual draft lalu posting
- [x] Guard status: dokumen posted/issued tidak bisa diedit langsung

### Otorisasi

- [x] Policy seluruh model domain + ability khusus
- [x] Role & permission matrix (super_admin, manajer, sales, kasir, pembelian, gudang, akuntan, auditor)
- [x] `strictAuthorization()` + `canAccessPanel()` berbasis role
- [x] Test akses per role

### UI P0

- [x] Laporan Penjualan v5 (tanpa `x-filament::field`), dark mode, export Excel
- [x] PO: create via action, aksi kirim/batal, total live
- [x] GRN: item repeater dari PO, validasi qty, aksi posting
- [x] SO: create via action, konfirmasi, buat invoice/delivery
- [x] Invoice: issue via action, pembayaran, PDF, void
- [x] Delivery resource + posting
- [x] Journal: draft + posting, validasi debit-kredit, guard posted
- [x] Halaman view dokumen + timeline aktivitas + aksi kontekstual

### API

- [x] Binding route benar, method lengkap/hapus route mati
- [x] Login/logout/me Sanctum
- [x] Validasi request + resource + rate limit
- [x] Feature test API

## Fase UI Interaktif

- [~] Fondasi `Money`, format dokumen, label Indonesia; `StatusBadge`/`HasDocumentActions` belum diekstrak
- [~] Select searchable/preload pada relasi besar; async search belum diterapkan
- [x] Form transaksi live + validasi + prefill lintas dokumen (PO → GRN, SO → invoice/delivery)
- [~] Tabel interaktif: filter, empty state, bulk delete master; summarizer/export tabel transaksi belum ada
- [~] Dashboard role-aware + chart + widget AR/pembelian/stok; quick action/cache belum ada
- [~] Global search master, notifikasi database; shortcut keyboard belum ada
- [-] POS/kasir + gudang mobile/scan barcode
- [~] Branding, aksesibilitas, responsivitas, performa

## Catatan Sesi

- Report AR aging dihitung di PHP agar portabel MySQL/PostgreSQL/SQLite; tidak lagi memakai `DATEDIFF`.
- PDF invoice memakai identitas perusahaan dari `config/company.php` dan format uang `Money`.
- Root `/` diarahkan ke `/admin`.
- Filter transaksi mencakup status, pihak terkait, dan rentang tanggal; edit/hapus dibatasi untuk draft.
- `GoodsReceiptNote` sekarang memiliki relasi `warehouse`.
- Widget stok rendah memakai `StockBalance` per gudang dan titik pesan ulang produk.
- Sisa backlog produk besar (POS, AP/purchase invoice, retur, stock opname/transfer, laporan keuangan,
  e-Faktur, period lock, pengaturan perusahaan) tetap di luar sesi ini.

## Log Verifikasi

| Tanggal | Perubahan | Test | PHPStan | Pint |
|---|---|---|---|---|
| 2026-10-25 | Baseline awal | 23 passed | 0 error | passed |
| 2026-10-25 | UI transaksi, otorisasi, laporan, API, dashboard, PDF | 47 passed, 113 assertions | 0 error | passed |
