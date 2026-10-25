# ERP Samarinda — Implementation Tracker

Dokumen ini melacak eksekusi `docs/ERP-SAMARINDA-CONTEXT-AND-UI-AUDIT.md` (P0/P1) dan
`docs/ERP-SAMARINDA-UI-INTERAKTIF-PROMPT.md` (UI interaktif).

Legenda: `[ ]` belum, `[~]` sedang dikerjakan, `[x]` selesai + terverifikasi, `[-]` di luar scope sesi ini.

## Baseline

- [x] `php artisan test` — 23 passed, 45 assertions
- [x] `vendor/bin/phpstan analyse` — 0 error
- [x] `vendor/bin/pint --test` — passed
- [x] Graph codebase-memory terindeks (1084 nodes, 2216 edges), coverage 18 file kunci bersih

## Fase P0 — Stabilisasi (wajib sebelum UI interaktif)

### Konfigurasi & fondasi

- [ ] Timezone efektif `Asia/Makassar` (config membaca `APP_TIMEZONE`)
- [ ] Locale `id`, `Carbon::setLocale('id')`, format tanggal Indonesia
- [ ] Migrasi `notifications` + `media`, storage link
- [ ] Branding panel, grouping navigasi Bahasa Indonesia, hapus widget generik
- [ ] Validasi maxLength/unique selaras database
- [ ] Seeder data dasar idempoten + role/permission domain

### Nomor dokumen

- [ ] Satu generator nomor aman race condition untuk PO, GRN, SO, Invoice, Payment, Journal
- [ ] Tidak ada input nomor manual di UI

### Domain action

- [ ] `CreateGoodsReceiptNoteAction` (header + item)
- [ ] `ConfirmSalesOrderAction` (credit limit)
- [ ] `PostDeliveryAction` (stok keluar, jurnal COGS, quantity_delivered)
- [ ] `VoidSalesInvoiceAction` (jurnal balik, guard pembayaran)
- [ ] `IssueSalesInvoiceAction` memposting jurnal + pajak
- [ ] `PostGoodsReceiptNoteAction` memposting jurnal + validasi sisa PO
- [ ] `RecordPaymentAction` memvalidasi sisa + jurnal kas/bank
- [ ] `PostJournalAction` mendukung jurnal manual draft lalu posting
- [ ] Guard status: dokumen posted/issued tidak bisa diedit langsung

### Otorisasi

- [ ] Policy seluruh model domain + ability khusus
- [ ] Role & permission matrix (super_admin, manajer, sales, kasir, pembelian, gudang, akuntan, auditor)
- [ ] `strictAuthorization()` + `canAccessPanel()` berbasis role
- [ ] Test akses per role

### UI P0

- [ ] Laporan Penjualan v5 (tanpa `x-filament::field`), dark mode, export
- [ ] PO: create via action, aksi kirim/batal, total live
- [ ] GRN: item repeater dari PO, validasi qty, aksi posting
- [ ] SO: create via action, konfirmasi, buat invoice/delivery
- [ ] Invoice: issue via action, pembayaran, PDF, void
- [ ] Delivery resource + posting
- [ ] Journal: draft + posting, validasi debit-kredit, guard posted
- [ ] Halaman view dokumen + timeline + aksi kontekstual

### API

- [ ] Binding route benar, method lengkap/hapus route mati
- [ ] Login/logout/me Sanctum
- [ ] Validasi request + resource + rate limit
- [ ] Feature test API

## Fase UI Interaktif (setelah P0)

- [ ] Fondasi `Money`, `DocumentTotals`, `StatusBadge`, `HasDocumentActions`, `lang/id`
- [ ] Select searchable async di semua relasi besar
- [ ] Form transaksi live + validasi + prefill lintas dokumen
- [ ] Tabel interaktif (filter, tab, summarizer, bulk, export, empty state)
- [ ] Dashboard per peran + chart + quick action + cache
- [ ] Global search, shortcut keyboard, notifikasi database
- [ ] POS/kasir + gudang mobile/scan barcode
- [ ] Branding, aksesibilitas, responsivitas, performa

## Log Verifikasi

| Tanggal | Perubahan | Test | PHPStan | Pint |
|---|---|---|---|---|
| 2026-10-25 | Baseline | 23 passed | 0 error | passed |
