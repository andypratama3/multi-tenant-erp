# ERP Samarinda — Implementation Tracker

Dokumen ini melacak eksekusi `docs/ERP-SAMARINDA-CONTEXT-AND-UI-AUDIT.md` (P0/P1) dan
`docs/ERP-SAMARINDA-UI-INTERAKTIF-PROMPT.md` (UI interaktif).

Legenda: `[ ]` belum, `[~]` sebagian, `[x]` selesai + terverifikasi, `[-]` ditunda / di luar scope sesi.

## Baseline Terkini

- [x] `php artisan test` — 107 passed, 250 assertions
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
- [x] Tabel interaktif: filter status/pihak/rentang tanggal, empty state, ringkasan total, aksi kontekstual
- [~] Export tabel transaksi dan bulk action lanjutan belum ada
- [x] Dashboard role-aware + chart + widget AR/pembelian/stok + quick action
- [~] Global search master, notifikasi database, shortcut dokumen; shortcut keyboard global belum ada
- [x] POS/kasir desktop + validasi harga/stok server-side + guard transaksi ganda
- [~] POS mobile dan scan barcode belum
- [~] Branding, aksesibilitas, responsivitas, performa

## Catatan Sesi

- Report AR aging dihitung di PHP agar portabel MySQL/PostgreSQL/SQLite; tidak lagi memakai `DATEDIFF`.
- PDF invoice memakai identitas perusahaan dari `CompanySettings` (fallback `config/company.php`) dan format uang `Money`.
- Widget stok rendah memakai `StockBalance` per gudang dan titik pesan ulang produk.
- AP: GRN tetap mengkredit hutang `2-0001`; purchase invoice dari GRN hanya dokumen tanpa jurnal.
  Pembayaran supplier memposting `Dr hutang / Cr kas|bank`; AP aging dihitung dari sisa purchase invoice.
- Retur penjualan memposting `Dr pendapatan`, `Dr PPN keluaran` (jika ada), `Cr piutang`, dan mengurangi AR.
- Stock opname/transfer memakai aksi posting terpisah; dokumen posted tidak dapat diposting ulang.
- Laporan keuangan mencakup laba rugi, neraca, dan trial balance dari jurnal posted.
- Period lock ditegakkan di `PostJournalAction`; pengaturan perusahaan dikelola via halaman `super_admin`.
- POS memakai alur SO → konfirmasi → invoice → pembayaran tunai → delivery/posting stok dalam satu transaksi.
- Export e-Faktur menghasilkan CSV per periode dari invoice ber-PPN; NPWP pelanggan wajib diisi untuk hasil lengkap.

## Audit & Hardening

- [x] Semua halaman list memakai guard `ViewAny` (URL langsung tidak bisa dibuka tanpa izin)
- [x] POS: izin kasir, validasi stok, harga/pajak dihitung ulang server-side, proteksi double submit
- [x] Retur: cegah baris duplikat, subtotal diskon proporsional, refund otomatis untuk invoice terbayar
- [x] Void invoice: guard retur/pengiriman/pembayaran + jurnal pembalik
- [x] Invoice: sisa kuantitas SO, kunci SO, validasi pelanggan, update `quantity_invoiced`
- [x] Eager loading dokumen untuk menghindari error strict lazy loading
- [x] Filter tanggal/status/pihak, chart arus kas, dan aksi pada halaman view
- [x] Seluruh ikon Heroicons tervalidasi terhadap paket yang terpasang

## Modul Lanjutan

- [x] Accounts Payable: purchase invoice dari GRN, pembayaran supplier, AP aging
- [x] Retur penjualan + pengurangan AR + opsi restock
- [x] Stock opname dan transfer antar gudang
- [x] Laporan keuangan: laba rugi, neraca, trial balance
- [x] Period lock + pengaturan identitas perusahaan
- [x] POS/kasir desktop (tanpa scan barcode)
- [x] Export e-Faktur CSV per periode

## Log Verifikasi

| Tanggal | Perubahan | Test | PHPStan | Pint |
|---|---|---|---|---|
| 2026-10-25 | Baseline awal | 23 passed | 0 error | passed |
| 2026-10-25 | UI transaksi, otorisasi, laporan, API, dashboard, PDF | 47 passed, 113 assertions | 0 error | passed |
| 2026-10-25 | AP, retur, stock opname/transfer, laporan keuangan, period lock, POS, e-Faktur | 78 passed, 191 assertions | 0 error | passed |
| 2026-10-25 | Audit UI/keamanan, filter, chart arus kas, aksi view, refund retur, guard void | 98 passed, 228 assertions | 0 error | passed |
| 2026-10-25 | Hardening POS, smoke test resource, panel `/dashboard`, tema Filament, lokalisasi label | 106 passed, 236 assertions | 0 error | passed |
| 2026-10-25 | Polish laporan (ringkasan & baris total), POS (select Filament, sticky pembayaran, empty state), pengaturan & e-Faktur | 107 passed, 250 assertions | 0 error | passed |
