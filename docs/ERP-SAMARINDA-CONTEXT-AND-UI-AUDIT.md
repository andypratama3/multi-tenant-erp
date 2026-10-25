# ERP Samarinda — Konteks Lengkap, Audit UI/UX, dan Prompt Lanjutan

> Dokumen ini adalah **sumber kebenaran tunggal** untuk melanjutkan pengembangan ERP Samarinda.
> Ditulis berdasarkan audit statis + verifikasi runtime pada 25 Oktober 2026.
> Gunakan dokumen ini sebagai konteks awal saat menyerahkan pekerjaan ke Claude/agen lain.

- **Repository**: `/Users/andypratama3/Development/erp-samarinda`
- **Jenis aplikasi**: ERP modular monolith (Laravel + Filament), bukan SaaS multi-tenant
- **Status**: Fondasi domain dan database sudah kuat; **lapisan UI admin belum terhubung ke domain** dan beberapa alur inti belum bisa dijalankan dari UI.
- **Bahasa target UI**: Indonesia (saat ini campuran Inggris/Indonesia)

---

## 0. Cara Memakai Dokumen Ini

1. Baca bagian 1–6 untuk memahami sistem apa adanya.
2. Baca bagian 7–9 untuk memahami UI, UX, dan otorisasi yang sekarang.
3. Gunakan bagian 10–12 sebagai backlog dan rencana kerja.
4. Tempel **bagian 13** ke Claude sebagai prompt utama.
5. Jangan menganggap rekomendasi di bagian 10–11 sebagai fakta kode; itu target yang belum ada.

---

## 1. Ringkasan Eksekutif

ERP Samarinda adalah aplikasi ERP untuk kebutuhan inventori, pembelian, penjualan, akuntansi, pajak, dan pelaporan. Arsitekturnya modular per domain (`app/Domain/*`) dengan Laravel sebagai framework, Filament v5 sebagai panel admin, dan MySQL sebagai database.

Kekuatan yang sudah ada:

- Skema database relasional lengkap (30 migrasi, 24 model, 40 tabel).
- Domain action untuk membuat SO, menerbitkan invoice, mencatat pembayaran, menerima barang, memposting jurnal, dan mencatat pergerakan stok.
- Double-entry accounting dengan COA, journal, dan penomoran dokumen.
- Moving average costing dan stock ledger append-only.
- 23 test Pest lulus; PHPStan level 8 lulus; Pint lulus.

Masalah utama:

- **UI admin bukan jalur transaksi yang sah.** Membuat PO/GRN/SO/Journal lewat Filament akan gagal atau menghasilkan data tidak valid karena nomor dokumen tidak digenerate dan domain action tidak dipanggil.
- **Invoice dari UI tersimpan dengan total 0** dan tidak menghasilkan jurnal, pajak, COGS, atau pengurangan stok.
- **Tidak ada cara menyelesaikan alur inti dari UI**: konfirmasi SO, posting GRN, pembayaran invoice, pengiriman barang, cetak PDF, dan laporan.
- **Halaman Laporan Penjualan error 500** karena memakai komponen Blade Filament v3 yang tidak ada di v5.
- **Otorisasi praktis tidak ada untuk domain**: tidak ada policy domain, sehingga semua user panel bisa mengakses semua resource (detail di bagian 9).
- **API belum siap integrasi**: controller tidak lengkap, parameter binding salah, tidak ada endpoint login/token.
- **Banyak fitur ERP standar belum ada**: POS, AP/purchase invoice, retur, stock opname/transfer, COGS, laporan keuangan, e-Faktur, period lock, pengaturan perusahaan, dan audit UI.

Kesimpulan: jangan menambah fitur baru sebelum **P0 (stabilisasi UI + otorisasi + alur inti)** selesai. Setelah itu, integrasi fitur bisa dilakukan bertahap di atas domain yang sudah ada.

---

## 2. Lingkungan dan Versi (Terverifikasi)

| Komponen | Versi |
|---|---|
| PHP | 8.5.12 |
| Laravel | 13.38.0 |
| Filament | v5.10.3 (composer `^5.7.6`) |
| Livewire | v4.4.8 (composer `^4.2`) |
| Filament Shield | `^4.3` |
| spatie/laravel-permission | `^8.3` |
| spatie/laravel-activitylog | `^5.0` |
| spatie/laravel-medialibrary | `^11.23` |
| spatie/laravel-backup | `^10.3` |
| laravel/horizon | `^5.50` |
| maatwebsite/excel | `^4.0` |
| barryvdh/laravel-dompdf | `^3.1` |
| MySQL | 26.10.0 (Homebrew, macOS arm64) |
| Redis | 8.10.2 |
| Node | v26.12.1 |
| Database | `erp_samarinda` |
| Session / Cache / Queue | Redis |
| Filesystem | `local` |
| Mail | `log` |
| APP_URL | `http://localhost:8000` |
| APP_TIMEZONE (.env) | `Asia/Makassar` |
| Timezone efektif | **UTC** (bug, lihat 8.1) |

Kualitas kode saat audit:

- `php artisan test` → **23 passed**, 45 assertions.
- `vendor/bin/phpstan analyse` → **0 error** (level 8).
- `vendor/bin/pint --test` → **passed**.

Data saat audit:

- 1 user (`super_admin`), 12 permission (semua untuk `Role`), 1 activity log.
- 0 produk, 0 invoice, 0 transaksi.
- Tidak ada data operasional (unit, warehouse, produk, customer, supplier, tax rate).

---

## 3. Arsitektur

### 3.1 Pola

- Modular monolith per domain, bukan package terpisah.
- Setiap domain memiliki `Models`, `Actions`, `Services`, `Filament/Resources`, `Filament/Widgets`.
- Filament panel berada di `/admin` dan melakukan discovery resource dari tiap domain.
- Domain saling berkomunikasi lewat action/service, bukan lewat controller.
- Accounting adalah downstream dari Sales, Purchasing, dan Inventory.

### 3.2 Struktur direktori penting

```
app/
├── Domain/
│   ├── Accounting/
│   │   ├── Actions/PostJournalAction.php
│   │   ├── Filament/Resources/{AccountResource,JournalResource}
│   │   ├── Models/{Account,Journal,JournalLine}
│   │   └── Services/{AccountResolver,GrnJournalService,InvoiceJournalService}
│   ├── Inventory/
│   │   ├── Actions/RecordStockMovementAction.php
│   │   ├── Filament/Resources/{ProductResource,ProductCategoryResource,UnitResource,WarehouseResource}
│   │   ├── Filament/Widgets/LowStockWidget.php
│   │   ├── Models/{Product,ProductCategory,Unit,Warehouse,StockLedger,StockBalance}
│   │   └── Services/BarcodeService.php
│   ├── Purchasing/
│   │   ├── Actions/{CreatePurchaseOrderAction,PostGoodsReceiptNoteAction}
│   │   ├── Filament/Resources/{SupplierResource,PurchaseOrderResource,GoodsReceiptNoteResource}
│   │   ├── Models/{Supplier,PurchaseOrder,PurchaseOrderItem,GoodsReceiptNote,GoodsReceiptNoteItem}
│   │   └── Services/PurchaseNumberGenerator.php
│   ├── Reporting/
│   │   ├── Services/{SalesSummaryReport,InventoryReport}
│   │   └── Exports/SalesSummaryExport.php
│   ├── Sales/
│   │   ├── Actions/{CreateSalesOrderAction,IssueSalesInvoiceAction,RecordPaymentAction}
│   │   ├── Filament/Resources/{CustomerResource,SalesOrderResource,SalesInvoiceResource}
│   │   └── Models/{Customer,SalesOrder,SalesOrderItem,SalesInvoice,SalesInvoiceItem,Delivery,DeliveryItem,Payment}
│   └── Tax/
│       ├── Actions/RecordTaxTransactionAction.php
│       ├── Filament/Resources/TaxRateResource.php
│       └── Models/{TaxRate,TaxTransaction}
├── Filament/
│   ├── Pages/SalesReport.php
│   └── Widgets/{SalesSummaryWidget,StockValuationWidget}
├── Http/Controllers/Api/*
├── Policies/RolePolicy.php
└── Providers/Filament/AdminPanelProvider.php
```

### 3.3 Panel admin

`app/Providers/Filament/AdminPanelProvider.php`:

- `->id('admin')`, `->path('admin')`, `->login()`.
- Primary color: `Color::Amber` (baris 35–37).
- Tanpa `brandName`, `favicon`, `theme`, `profile`, `passwordReset`, `registration`, `spa`, `databaseNotifications`, `tenancy`.
- Resource discovery dari 5 domain + `app/Filament/Resources` (kosong).
- Widget eksplisit: `AccountWidget`, `SalesSummaryWidget`, `StockValuationWidget`, `LowStockWidget`, `FilamentInfoWidget` (baris 50–56).
- Navigation group dideklarasikan: `Inventaris`, `Pembelian`, `Penjualan`, `Akuntansi`, `Pajak`, `Laporan`, `Pengaturan` (baris 57–65).
- Plugin: `FilamentShieldPlugin`.

Catatan: `FilamentInfoWidget` masih terdaftar sehingga kotak informasi versi Filament tampil di dashboard produksi. Hapus.

---

## 4. Model Data

### 4.1 Tabel (40)

`accounts`, `activity_log`, `cache`, `cache_locks`, `customers`, `deliveries`, `delivery_items`, `failed_jobs`, `goods_receipt_note_items`, `goods_receipt_notes`, `job_batches`, `jobs`, `journal_lines`, `journals`, `migrations`, `model_has_permissions`, `model_has_roles`, `password_reset_tokens`, `payments`, `permissions`, `personal_access_tokens`, `product_categories`, `products`, `purchase_order_items`, `purchase_orders`, `role_has_permissions`, `roles`, `sales_invoice_items`, `sales_invoices`, `sales_order_items`, `sales_orders`, `sessions`, `stock_balances`, `stock_ledgers`, `suppliers`, `tax_rates`, `tax_transactions`, `units`, `users`, `warehouses`.

Tabel yang **tidak ada** padahal dibutuhkan oleh package/fitur:

- `media` (medialibrary belum di-publish/di-migrate) → upload attachment belum bisa.
- `notifications` (database notification tidak diaktifkan).
- Tabel pengaturan perusahaan, period lock, stock adjustment/transfer header, purchase invoice/AP payment, warehouse transfer.

### 4.2 Entitas inti dan relasi

| Entitas | Relasi utama | Catatan |
|---|---|---|
| `Product` | belongsTo `ProductCategory`, `Unit`; hasMany `StockLedger`; hasOne `StockBalance` (via `currentStock`) | `currentStock` ambigu untuk multi-warehouse |
| `StockLedger` | belongsTo `Product`, `Warehouse` | append-only; `UPDATED_AT = null`; tidak boleh update/delete |
| `StockBalance` | belongsTo `Product`, `Warehouse` | unik per `(product_id, warehouse_id)` |
| `PurchaseOrder` | belongsTo `Supplier`; hasMany `PurchaseOrderItem`, `GoodsReceiptNote` | punya `approved_by`/`approved_at`, belum dipakai |
| `GoodsReceiptNote` | belongsTo `PurchaseOrder`, `Warehouse`; hasMany `GoodsReceiptNoteItem` | UI tidak punya item repeater |
| `SalesOrder` | belongsTo `Customer`; hasMany `SalesOrderItem`, `Delivery`, `SalesInvoice` | `so_number` tidak digenerate |
| `SalesInvoice` | belongsTo `Customer`, `SalesOrder`; hasMany `SalesInvoiceItem`, `Payment` | UI membuat total 0 |
| `Delivery` | belongsTo `SalesOrder`, `Warehouse`; hasMany `DeliveryItem` | tidak ada resource Filament |
| `Payment` | belongsTo `SalesInvoice` | hanya lewat action |
| `Journal` | hasMany `JournalLine` | UI bisa edit jurnal posted |
| `Account` | hierarki `parent_id`, `is_leaf` | COA di-seed |
| `TaxRate`, `TaxTransaction` | `TaxTransaction` belongsTo `TaxRate`, `SalesInvoice`/`PurchaseOrder` | reporting pajak belum ada |

### 4.3 Invariant penting

- Stok tidak boleh negatif (dijaga di action, **tidak** ada CHECK constraint di `stock_balances`).
- `stock_ledgers.quantity` tidak boleh 0 (ada CHECK `chk_quantity_not_zero`).
- Jurnal harus balance (debit = credit) — dijaga `PostJournalAction`, bukan database.
- Nomor dokumen unik dan NOT NULL: `po_number`, `grn_number`, `so_number`, `invoice_number`, `journal_number`.
- Invoice tidak boleh melebihi credit limit customer — ada `InsufficientCreditException`, tetapi tidak dipakai di UI/API.
- GRN hanya boleh diposting sekali — ada `PurchaseOrderAlreadyReceivedException`, tidak dipakai konsisten.
- Moving average cost diperbarui saat stok masuk (lihat `RecordStockMovementAction`).

### 4.4 Drift dokumentasi ADR

ADR di `docs/adr/` menyebut nama yang tidak sama dengan kode:

| ADR | Disebut | Aktual |
|---|---|---|
| 0003 | `stock_movements`, `ApplyStockMovement` | `stock_ledgers`, `RecordStockMovementAction` |
| 0004 | `journal_entries`, `PostJournal` | `journals`, `PostJournalAction` |

Jangan mengikuti nama di ADR saat menulis kode baru; ikuti kode aktual. Perbarui ADR setelah integrasi.

---

## 5. Alur Bisnis: Aktual vs Diharapkan

### 5.1 Matriks wiring

| Alur | Domain tersedia | Dipanggil dari UI | Dipanggil dari API | Status |
|---|---|---|---|---|
| Buat PO | `CreatePurchaseOrderAction` | Tidak (create Filament langsung) | Tidak | Rusak di UI |
| Buat GRN | Tidak ada action create GRN (hanya post) | Tidak | Tidak | Tidak ada jalur create |
| Posting GRN (stok masuk) | `PostGoodsReceiptNoteAction` | Tidak | Tidak | Action ada; jurnal tidak dibuat |
| Jurnal GRN | `GrnJournalService` | Tidak | Tidak | Service tidak pernah dipanggil |
| Buat SO | `CreateSalesOrderAction` | Tidak | Ya | UI rusak |
| Konfirmasi SO | inline di API (`status = confirmed`) | Tidak | Ya, tapi binding salah | Rusak |
| Buat invoice | `IssueSalesInvoiceAction` | Tidak | Ya | UI menghasilkan total 0 |
| Jurnal invoice | `InvoiceJournalService` | Tidak | Tidak | Tidak terpakai |
| Pembayaran | `RecordPaymentAction` | Tidak | Ya, tapi binding salah | Tidak bisa dari UI |
| Delivery (stok keluar) | `Delivery` model | Tidak ada resource | Tidak | Tidak ada alur |
| Jurnal pembayaran/COGS | Tidak ada | Tidak | Tidak | Belum ada |
| Jurnal manual | `PostJournalAction` | Tidak | Tidak | UI menyimpan draft langsung |
| Pajak | `RecordTaxTransactionAction` | Tidak | Ya (dipanggil `IssueSalesInvoiceAction`) | UI tidak terhubung |
| Laporan penjualan | `SalesSummaryReport` | Ya | Tidak | Halaman 500 |

### 5.2 Alur pembelian yang benar (target)

1. Admin membuat PO (draft) → nomor `PO-YYYYMMDD-####` digenerate.
2. PO dikirim ke supplier (`sent`), lalu diterima sebagian/penuh.
3. Gudang membuat GRN dari PO → pilih warehouse, isi item yang diterima, qty.
4. Posting GRN:
   - `RecordStockMovementAction` tipe `in` untuk tiap item.
   - `StockBalance` dan moving average cost diperbarui.
   - `GrnJournalService` membuat jurnal Dr Inventory / Cr GRNI atau Accounts Payable.
   - status PO menjadi `partial`/`received`.
5. Purchase invoice/AP dan pembayaran supplier (belum ada).

### 5.3 Alur penjualan yang benar (target)

1. Sales membuat SO (draft) → nomor `SO-YYYYMMDD-####`.
2. Konfirmasi SO → cek credit limit, status `confirmed`.
3. Buat invoice dari SO (prefill item) atau invoice langsung → `IssueSalesInvoiceAction`:
   - hitung subtotal, diskon, pajak, total;
   - buat `InvoiceJournalService` (Dr AR / Cr Revenue / Cr Tax Payable);
   - catat `TaxTransaction`;
   - bila stok dikirim langsung, buat delivery + `RecordStockMovementAction` tipe `out` + jurnal COGS.
4. Delivery dapat dibuat terpisah; saat posted, stok keluar dan `quantity_delivered` SO diperbarui.
5. Pembayaran via `RecordPaymentAction`:
   - validasi nominal ≤ sisa;
   - buat jurnal Dr Cash/Bank / Cr AR;
   - update `paid_amount` dan status invoice.
6. Cetak invoice PDF dan rekap AR aging.

---

## 6. Permukaan API

File: `routes/api.php`. Semua route di bawah `auth:sanctum`.

| Method | Path | Controller | Realita |
|---|---|---|---|
| GET | `/api/products` | `ProductController@index` | Ada |
| GET | `/api/products/{product}` | `ProductController@show` | Ada |
| POST/PUT/DELETE | `/api/products...` | — | **Route terdaftar, method tidak ada** |
| GET | `/api/customers` | `CustomerController@index` | Ada |
| GET | `/api/customers/{customer}` | `CustomerController@show` | Ada |
| POST/PUT/DELETE | `/api/customers...` | — | **Route terdaftar, method tidak ada** |
| GET | `/api/sales-orders` | `SalesOrderController@index` | Ada |
| POST | `/api/sales-orders` | `SalesOrderController@store` | Ada |
| GET | `/api/sales-orders/{sales_order}` | `SalesOrderController@show` | Ada |
| PUT/DELETE | `/api/sales-orders...` | — | **Method tidak ada** |
| POST | `/api/sales-orders/{id}/confirm` | `SalesOrderController@confirm` | **Binding gagal** |
| GET | `/api/sales-invoices` | `SalesInvoiceController@index` | Ada |
| POST | `/api/sales-invoices` | `SalesInvoiceController@store` | Ada |
| GET | `/api/sales-invoices/{sales_invoice}` | `SalesInvoiceController@show` | Ada |
| PUT/DELETE | `/api/sales-invoices...` | — | **Method tidak ada** |
| POST | `/api/sales-invoices/{id}/payment` | `SalesInvoiceController@recordPayment` | **Binding gagal** |
| GET | `/api/sales-invoices/{id}/pdf` | `SalesInvoiceController@downloadPdf` | **Binding gagal** |
| GET | `/api/stock-balances` | `StockBalanceController@index` | Ada |
| GET | `/api/stock-balances/{productId}` | `StockBalanceController@byProduct` | Ada (return type aman) |

### 6.1 Bug binding

Route memakai `{id}`, sedangkan method memakai `SalesOrder $salesOrder` / `SalesInvoice $salesInvoice`. Laravel hanya melakukan implicit binding jika nama parameter route cocok (termasuk snake_case). Karena tidak cocok, container membuat model kosong baru, lalu `save()` gagal (kolom nomor NOT NULL) atau mengembalikan data kosong.

Perbaikan: ubah route menjadi `{salesOrder}` / `{salesInvoice}`, atau method menjadi `string $id` lalu `findOrFail($id)`.

### 6.2 Bug filter produk

`ProductController@index` memakai `where(...)->orWhere(...)` tanpa grouping. Filter `is_active` bisa bocor karena precedence OR. Bungkus dengan closure.

### 6.3 Kekurangan API

- Tidak ada endpoint login/token (`/api/login`, `/api/logout`, refresh) dan tidak ada cara menerbitkan Sanctum token.
- Tidak ada `ApiResource`/transformer; model dikembalikan mentah.
- Tidak ada validasi numeric/min pada request Sales (`quantity`, `unit_price`).
- Tidak ada idempotency key, rate limit eksplisit, versioning, OpenAPI, atau format error konsisten.
- Tidak ada endpoint delivery, payment list, stock ledger, laporan, pajak, supplier, PO, GRN.
- `apiResource` mendaftarkan route yang tidak ada method → 500.

---

## 7. Inventaris UI Panel (Kondisi Saat Ini)

### 7.1 Navigasi

Grup yang benar-benar muncul:

| Grup | Resource | Label aktual (Inggris) |
|---|---|---|
| `Inventory` | ProductCategory, Product, Unit, Warehouse | Product Categories, Products, Units, Warehouses |
| `Pembelian` | Supplier, PurchaseOrder, GoodsReceiptNote | Suppliers, Purchase Orders, Goods Receipt Notes |
| `Penjualan` | Customer, SalesOrder, SalesInvoice | Customers, Sales Orders, Sales Invoices |
| `Akuntansi` | Account, Journal | Accounts, Journals |
| `Pajak` | TaxRate | Tax Rates |
| `Laporan` | SalesReport | Laporan Penjualan |

Grup `Inventaris` dan `Pengaturan` dideklarasikan tetapi kosong. Akibatnya sidebar menampilkan dua grup inventori (`Inventory` dan `Inventaris`) dan satu grup pengaturan kosong. Ini bug navigasi yang terlihat jelas.

### 7.2 Resource per resource

Semua resource hanya punya halaman `index` (List) dengan create/edit/view/delete berbasis modal. Tidak ada halaman `create`, `edit`, atau `view` tersendiri, tidak ada relation manager, tidak ada infolist.

#### ProductCategoryResource
- Grup: `Inventory`.
- Form: `name` (required, max 255), `slug` (required, max 255), `description` (textarea), `parent_id` (select, nullable), `is_active` (toggle).
- Tabel: `name`, `slug`, `parent.name`, `is_active`.
- Aksi: edit, delete; create di header.
- Gap: maxLength tidak cocok DB (`name` 100, `slug` 100); slug tidak auto-generate; tidak ada unique validation; tidak ada filter.

#### ProductResource
- Grup: `Inventory`, ikon cube.
- Form (12 field, satu kolom): `sku` (max 100), `name` (max 255), `description`, `product_category_id`, `unit_id` (required), `cost_price`, `selling_price`, `barcode`, `is_active`, `is_stockable`, `min_stock`, `reorder_point`.
- Tabel: `sku`, `name`, `category.name`, `unit.symbol`, `cost_price` (IDR), `selling_price` (IDR), `is_active`.
- Gap: maxLength `sku` 100 vs DB 50, `name` 255 vs DB 150; SKU unik tidak divalidasi; tidak ada stok saat ini, tidak ada gambar, tidak ada filter kategori/status; tidak ada quick stock adjustment.

#### UnitResource
- Form: `name` (max 255), `symbol` (max 50), `is_base`.
- Tabel: `name`, `symbol`, `is_base`.
- Gap: maxLength 255/50 vs DB 50/10; unique symbol tidak divalidasi.

#### WarehouseResource
- Form: `code` (max 50), `name` (max 255), `address`, `is_active`.
- Tabel: `code`, `name`, `is_active`.
- Gap: `code` DB 20 (form 50); unique belum divalidasi; tidak ada informasi kapasitas/stok.

#### SupplierResource
- Form: `code`, `name`, `contact_person`, `phone`, `email`, `address`, `tax_id`, `payment_terms_days`, `is_active`.
- Tabel: `code`, `name`, `contact_person`, `phone`, `payment_terms_days`, `is_active`.
- Gap: tidak ada daftar PO/AP per supplier; tidak ada filter status.

#### PurchaseOrderResource
- Grup `Pembelian`, sort 20.
- Form: `supplier_id` (relationship), `ordered_at`, `expected_at`, `status` (draft/sent/partial/received/cancelled), `notes`, `items` repeater (`product_id` via pluck name, `quantity_ordered`, `unit_price`, `tax_rate` %).
- Tabel: `po_number`, `supplier.name`, `ordered_at`, `status` badge, `total`.
- Aksi: view, edit, delete; create header.
- **Gap kritis**: tidak ada input `po_number`; DB NOT NULL + unik → create gagal. Tidak ada preview subtotal/pajak/total. Tidak ada aksi kirim/approve/batalkan. Tidak ada validasi item minimal 1. Edit PO yang sudah received masih terbuka.

#### GoodsReceiptNoteResource
- Grup `Pembelian`, sort 30.
- Form: `purchase_order_id` (relationship), `warehouse_id` (select, **tidak required**), `received_at`, `status` (draft/posted), `notes`.
- Tabel: `grn_number`, `purchase_order.po_number`, `received_at`, `status`.
- Aksi: edit, delete; create header.
- **Gap kritis**: tidak ada `grn_number` (create gagal; `PurchaseNumberGenerator::nextGrnNumber()` sudah ada tetapi tidak pernah dipanggil), **tidak ada repeater item**, tidak ada aksi posting, jadi stok dan jurnal tidak pernah terbentuk. Mengubah status ke `posted` via form hanya mengubah kolom, bukan memposting stok.

#### CustomerResource
- Form: `code`, `name`, `contact_person`, `phone`, `email`, `address`, `tax_id`, `payment_terms_days`, `credit_limit` (prefix Rp), `is_active`.
- Tabel: `code`, `name`, `phone`, `email`, `payment_terms_days`, `credit_limit`, `is_active`.
- Gap: tidak ada AR outstanding/credit usage; tidak ada filter.

#### SalesOrderResource
- Grup `Penjualan`, sort 20.
- Form: `customer_id` (relationship), `ordered_at`, `required_at`, `status` (draft/confirmed/partial/fulfilled/cancelled), `notes`, `items` repeater (`product_id`, `quantity_ordered`, `unit_price`, `discount_rate`, `tax_rate`).
- Tabel: `so_number`, `customer.name`, `ordered_at`, `status`, `total`.
- Aksi: edit, delete; create header.
- **Gap kritis**: tidak ada `so_number` (create gagal); tidak ada aksi konfirmasi; tidak ada cek credit limit; tidak ada preview total; tidak ada tombol buat invoice/delivery dari SO.

#### SalesInvoiceResource
- Grup `Penjualan`, sort 30.
- Form: `invoice_number` (required, unique, max 30), `customer_id`, `sales_order_id`, `issued_at`, `due_at`, `status`, `items` repeater (`product_id`, `quantity`, `unit_price`, `discount_rate`, `tax_rate`), `notes`.
- Tabel: `invoice_number`, `customer.name`, `issued_at`, `due_at`, `total`, `paid_amount`, `status`.
- Aksi: view, edit, delete; create header.
- **Gap kritis**: create langsung menyimpan model tanpa action → `subtotal`, `tax_amount`, `total` tetap 0; tidak ada jurnal, pajak, COGS, atau stok keluar. Item discount tidak dihitung. Tidak ada aksi pembayaran, PDF, atau batal.

#### DeliveryResource
- **Tidak ada class**. Hanya ada folder `app/Domain/Sales/Filament/Resources/DeliveryResource/Pages/` yang kosong.
- Tidak ada menu delivery, tidak ada cara stok keluar dari penjualan.

#### AccountResource
- Form: `code`, `name`, `type`, `normal_balance`, `parent_id`, `is_leaf`, `is_active`, `description`.
- Tabel: `code`, `name`, `type` badge, `normal_balance`, `is_leaf`, `is_active`.
- Gap: tidak ada tree view; tidak ada proteksi akun yang sudah dipakai jurnal.

#### JournalResource
- Grup `Akuntansi`, sort 20.
- Form: `journal_number` (hint "Auto-generated if left empty" tetapi **tidak ada generator**), `posted_at`, `description`, `status`, repeater `lines` (`account_id`, `type` debit/credit, `amount`, `description`).
- Tabel: `journal_number`, `posted_at`, `description`, `status`, `lines_count`.
- Aksi: edit, delete; create header.
- **Gap kritis**: create tanpa nomor gagal; tidak ada validasi debit = credit; tidak ada aksi post; jurnal posted masih bisa diedit/dihapus.

#### TaxRateResource
- Form: `code` (max 30), `name` (max 150), `type`, `rate` %, `is_active`, `description`.
- Tabel: `code`, `name`, `type`, `rate`, `is_active`.
- Gap: maxLength 30/150 vs DB 20/100; tidak ada riwayat pemakaian.

#### Shield Roles
- Resource dari plugin Shield, di `/admin/shield/roles`.
- Hanya permission `*:Role` yang ada; tidak ada permission domain.

### 7.3 Widget dashboard

| Widget | Isi | Masalah |
|---|---|---|
| `AccountWidget` | Info user | Default, tidak berguna untuk operasional |
| `SalesSummaryWidget` | Penjualan hari ini, bulan ini, AR outstanding, invoice overdue | Query `DB::table('sales_invoices')` tanpa `whereNull('deleted_at')` → invoice soft-deleted ikut terhitung; tidak ada filter role |
| `StockValuationWidget` | Top 10 stok berdasarkan average cost | Hanya 10 baris, tidak agregat per gudang; bukan "top value" |
| `LowStockWidget` | Produk dengan stok ≤ reorder point | Memakai `currentStock` (HasOne) padahal stok per gudang → bisa salah produk/gudang |
| `FilamentInfoWidget` | Info versi Filament | Harus dihapus dari produksi |

Tidak ada chart, tidak ada widget pembelian/AP, pajak, kas, top customer, atau aging summary.

### 7.4 Halaman Laporan Penjualan

`app/Filament/Pages/SalesReport.php` + `resources/views/filament/pages/sales-report.blade.php`.

- Filter: dari tanggal, sampai tanggal, tombol "Update Report".
- Tiga tabel manual: ringkasan harian, produk terlaris, AR aging.
- **Blocker**: memakai `<x-filament::field>` dan `<x-filament::input.label>` yang tidak ada di Filament v5. Verifikasi runtime menghasilkan:
  `InvalidArgumentException: Unable to locate a class or view for component [filament::field].`
  Komponen yang ada: `<x-filament::input>`, `<x-filament::input.wrapper>`, `<x-filament::input.select>`, `<x-filament::button>`.
- `class="filament-input"` juga bukan class v5.
- Tabel manual memakai `bg-gray-100`, `text-gray-*` hardcoded → tidak mendukung dark mode.
- Tidak ada export (padahal `SalesSummaryExport` sudah ada), tidak ada print, tidak ada pagination, tidak ada chart.
- `SalesSummaryReport` memakai `DATEDIFF` (MySQL-only) → menghambat rencana PostgreSQL.

### 7.5 PDF Invoice

- Ada view PDF dan route/controller download, tetapi route binding salah.
- Tidak ada logo perusahaan, NPWP/alamat perusahaan, rekening bank, QR/barcode, nomor halaman.
- Tidak ada tombol cetak di UI.

### 7.6 Halaman publik

- `/` masih halaman welcome default Laravel (bahasa Inggris).
- Tidak ada route `login`, `register`, atau `dashboard`, sehingga navigasi welcome menyembunyikan tautan (kondisi `Route::has(...)` false).
- Tidak ada landing page, company profile, atau portal customer.

---

## 8. Audit UI/UX dan Interaktivitas

Severity: **P0** = memblokir operasi/menyebabkan data salah; **P1** = alur inti tidak lengkap; **P2** = kualitas UX; **P3** = polish.

### 8.1 P0 — Bug dan blocker

| ID | Temuan | Bukti | Dampak |
|---|---|---|---|
| P0-1 | Halaman Laporan Penjualan error 500 | `resources/views/filament/pages/sales-report.blade.php:6,7,12,13` | Laporan tidak bisa dibuka |
| P0-2 | Create PO gagal karena `po_number` kosong | `PurchaseOrderResource` tidak punya field nomor; migrasi NOT NULL | Tidak bisa membuat PO |
| P0-3 | Create GRN gagal karena `grn_number` kosong | `GoodsReceiptNoteResource`; `nextGrnNumber()` tidak dipanggil | Tidak bisa menerima barang |
| P0-4 | Create SO gagal karena `so_number` kosong | `SalesOrderResource` | Tidak bisa membuat SO |
| P0-5 | Create Journal gagal karena `journal_number` kosong | `JournalResource:34-36` | Tidak bisa membuat jurnal |
| P0-6 | Invoice dari UI total 0 dan tanpa jurnal/pajak/COGS/stok | `SalesInvoiceResource` menyimpan model langsung; action tidak dipanggil | Data keuangan salah |
| P0-7 | GRN tidak punya item; posting tidak mungkin | `GoodsReceiptNoteResource:29-48` | Stok tidak pernah masuk |
| P0-8 | Delivery tidak ada resource/action | folder `DeliveryResource/Pages` kosong | Stok tidak pernah keluar |
| P0-9 | Tidak ada aksi konfirmasi SO, pembayaran, PDF, posting jurnal | semua resource hanya edit/delete | Alur inti mandek |
| P0-10 | Semua user panel bisa akses semua resource | tidak ada policy domain + strict authorization off | Risiko keamanan/operasional |
| P0-11 | API route store/update/destroy tanpa method | `routes/api.php:22,25,28,32` | 500 untuk integrasi |
| P0-12 | API binding `{id}` vs `$salesOrder`/`$salesInvoice` | `routes/api.php:29,33,34` | 500 untuk konfirmasi/bayar/PDF |
| P0-13 | Timezone efektif UTC | `config/app.php` hardcode `'UTC'` | Tanggal/jam dan nomor dokumen bisa salah |
| P0-14 | `media` table tidak ada dan `public/storage` belum di-link | medialibrary terpasang tanpa migrasi; `public/storage` tidak ada | Upload/lampiran gagal |
| P0-15 | Pembayaran melebihi sisa tidak ditolak | `RecordPaymentAction.php:35-38` hanya cek status `paid` | `paid_amount` bisa > `total` |
| P0-16 | Credit limit tidak dicek saat SO | `InsufficientCreditException` ada tetapi tidak dipakai | SO melebihi limit tetap dibuat |
| P0-17 | Qty GRN tidak divalidasi terhadap sisa PO | `PostGoodsReceiptNoteAction` hanya menambah `quantity_received` | Penerimaan bisa melebihi pesanan |

### 8.2 P1 — Alur dan fungsionalitas hilang

- Tidak ada UI purchase invoice/AP dan pembayaran supplier.
- Tidak ada retur penjualan/pembelian.
- Tidak ada stock adjustment, stock opname, stock transfer.
- Tidak ada stock card/ledger UI per produk/gudang.
- Tidak ada COGS journal saat penjualan.
- Tidak ada payment journal (Dr kas/bank, Cr AR) — `RecordPaymentAction` hanya update invoice.
- Tidak ada update `quantity_delivered` pada SO.
- Tidak ada period lock / tutup buku.
- Tidak ada laporan: P&L, neraca, arus kas, trial balance, general ledger, AP aging, pembelian, stok.
- Tidak ada laporan pajak/e-Faktur.
- Tidak ada POS/kasir.
- Tidak ada import/export UI (class sudah ada).
- Tidak ada manajemen user, role UI yang lengkap, company settings.
- Tidak ada activity log UI (tabel `activity_log` ada).
- Tidak ada backup UI (scheduler ada).
- Tidak ada barcode print UI (`BarcodeService` ada).
- Tidak ada notifikasi low stock/overdue/approval.
- Tidak ada lampiran dokumen (PO, GRN, invoice).
- Dashboard Horizon tidak bisa diakses karena gate di `HorizonServiceProvider` memakai allowlist email kosong.
- `public/css`, `public/js`, `public/fonts` kosong dan tidak ada `public/build/manifest.json`; aset panel sepenuhnya dari vendor Filament.
- Tidak ada dokumentasi API/OpenAPI untuk integrasi.

### 8.3 P2 — UX dan konsistensi

| ID | Temuan | Rekomendasi |
|---|---|---|
| P2-1 | Campuran bahasa Inggris/Indonesia | Semua label, grup, status, tombol, pesan validasi → Indonesia; konsisten |
| P2-2 | Grup navigasi ganda dan kosong (`Inventory` + `Inventaris`, `Pengaturan`) | Satukan ke `Inventaris`; isi `Pengaturan` atau hapus |
| P2-3 | Form panjang satu kolom, tanpa section | Gunakan `Section` + `Grid`/`columns(2)`; kelompokkan data umum, alamat, stok, keuangan |
| P2-4 | Tidak ada perhitungan live | `->live()` pada qty/harga/diskon/pajak + placeholder total; tampilkan subtotal, diskon, pajak, total |
| P2-5 | Pilih produk hanya nama | Tampilkan `SKU — Nama — Stok — Harga`; gunakan searchable select + opsi harga otomatis |
| P2-6 | Tidak ada konfirmasi aksi berisiko | Modal konfirmasi dengan ringkasan untuk posting/void/delete |
| P2-7 | Tabel tanpa filter | Filter tanggal, status, customer, supplier, gudang; saved view per role |
| P2-8 | Tabel tanpa bulk action/export | Bulk delete/confirm/export; tombol export mengarah ke class yang sudah ada |
| P2-9 | Tidak ada empty state yang mengarahkan | Empty state + CTA "Buat PO pertama", "Tambah produk" |
| P2-10 | Tidak ada status timeline | Tambahkan infolist/timeline: draft → confirmed → fulfilled → paid |
| P2-11 | Validasi tidak cocok DB | Selaraskan maxLength/unique: lihat 8.4 |
| P2-12 | Dark mode rusak di laporan manual | Pakai komponen Filament atau token `fi-*` |
| P2-13 | `FilamentInfoWidget` tampil | Hapus |
| P2-14 | Tidak ada branding | `brandName`, logo, favicon, login hero, warna konsisten |
| P2-15 | Dashboard generik | Dashboard per role + chart + quick actions |
| P2-16 | Tidak ada feedback loading/optimistic | Gunakan notifikasi Filament, skeleton/loading state |
| P2-17 | Pencarian produk memuat semua opsi | Searchable async + limit; hindari `pluck` seluruh tabel |
| P2-18 | PDF tanpa identitas perusahaan | Header perusahaan, NPWP, bank, QR, nomor halaman |
| P2-19 | Root `/` placeholder | Landing/login redirect atau company profile |

### 8.4 Ketidakcocokan validasi vs database

| Field | Form maxLength | DB | Catatan |
|---|---|---|---|
| `products.sku` | 100 | 50 | Bisa error 1406 |
| `products.name` | 255 | 150 | Bisa error 1406 |
| `product_categories.name` | 255 | 100 | Bisa error 1406 |
| `product_categories.slug` | 255 | 100 | Bisa error 1406 |
| `units.name` | 255 | 50 | Bisa error 1406 |
| `units.symbol` | 50 | 10 | Bisa error 1406 |
| `warehouses.code` | 50 | 20 | Bisa error 1406 |
| `warehouses.name` | 255 | 100 | Bisa error 1406 |
| `tax_rates.code` | 30 | 20 | Bisa error 1406 |
| `tax_rates.name` | 150 | 100 | Bisa error 1406 |

Unique yang hilang: `products.sku`, `product_categories.slug`, `units.symbol`, `warehouses.code`. Sudah ada: `account.code`, `supplier.code`, `customer.code`, `tax_rate.code`, `sales_invoice.invoice_number`.

### 8.5 Aksesibilitas dan responsivitas

- Komponen Filament default sudah baik (label, focus, keyboard).
- Tabel laporan manual tidak punya `<caption>`, scope header eksplisit, atau status ARIA; warna merah hanya penanda visual.
- Form satu kolom panjang memaksa scroll berlebihan di laptop.
- POS/mobile warehouse belum ada; belum ada desain untuk layar kecil.
- Kontras warna Amber default pada tombol perlu dicek di dark mode.

---

## 9. Otorisasi dan Peran (Kondisi Sekarang)

- Hanya `app/Policies/RolePolicy.php` yang ada.
- Tidak ada policy untuk Product, SalesOrder, SalesInvoice, PurchaseOrder, GRN, Journal, dll.
- `User::canAccessPanel()` selalu `return true`.
- `super_admin` memiliki 12 permission, semuanya `*:Role` (hasil Shield), bukan permission domain.
- `Filament::isAuthorizationStrict()` default `false` (tidak diaktifkan di panel).
- Perilaku Filament v5 (`vendor/filament/filament/src/helpers.php:31-98`):
  - Jika policy ada dan method ada → cek Gate.
  - Jika policy **tidak ada** dan strict authorization **off** → **allow**.
- Konsekuensi: setiap user yang bisa login ke `/admin` dapat melihat, membuat, mengubah, dan menghapus **semua** data domain, termasuk jurnal dan harga.
- `Gate::forUser($user)->allows('viewAny', Product::class)` mengembalikan `false` (karena tidak ada policy), tetapi Filament tetap mengizinkan akses karena jalur di atas.
- Role `super_admin` tetap bisa mengelola Role karena 12 permission Role ter-assign.

Target: policy domain + permission granular + `strictAuthorization()` di panel + `canAccessPanel()` berbasis role/aktif.

---

## 10. Rekomendasi UX per Peran

### 10.1 Definisi peran

| Role | Tanggung jawab | Akses utama |
|---|---|---|
| `super_admin` | Konfigurasi sistem, user, role, backup | Semua + pengaturan |
| `manajer` | Monitoring, approval, laporan | Read-only + approve PO/adjustment + semua laporan |
| `sales` | Customer, SO, invoice, delivery request | Customer, SO, invoice, AR aging |
| `kasir` | POS, pembayaran langsung | POS, invoice, payment |
| `pembelian` | Supplier, PO, GRN, AP | Supplier, PO, GRN, purchase invoice |
| `gudang` | Stok masuk/keluar, opname, transfer | Produk read, GRN receive, delivery, stock opname, barcode |
| `akuntan` | COA, jurnal, pajak, tutup buku, laporan keuangan | Accounting, tax, financial reports |
| `auditor` | Audit read-only | Semua read-only + activity log |

### 10.2 Matriks permission (ringkas)

| Resource/Aksi | super_admin | manajer | sales | kasir | pembelian | gudang | akuntan | auditor |
|---|---|---|---|---|---|---|---|---|
| Product view | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Product create/update | ✓ | – | – | – | – | – | – | – |
| Product delete | ✓ | – | – | – | – | – | – | – |
| Supplier CRUD | ✓ | read | – | – | ✓ | – | read | read |
| Purchase Order CRUD | ✓ | read+approve | – | – | ✓ | read | read | read |
| GRN create/post | ✓ | read | – | – | ✓ | ✓ | read | read |
| Customer CRUD | ✓ | read | ✓ | read | – | – | read | read |
| Sales Order CRUD/confirm | ✓ | read | ✓ | read | – | read | read | read |
| Sales Invoice issue | ✓ | read | ✓ | ✓ | – | read | read | read |
| Payment record | ✓ | read | ✓ | ✓ | – | – | read | read |
| Delivery create/post | ✓ | read | read | – | – | ✓ | read | read |
| Stock opname/transfer | ✓ | approve | – | – | – | ✓ | read | read |
| Journal create/post | ✓ | read | – | – | – | – | ✓ | read |
| COA CRUD | ✓ | read | – | – | – | – | ✓ | read |
| Tax rate CRUD | ✓ | read | – | – | – | – | ✓ | read |
| Financial reports | ✓ | ✓ | AR only | – | AP only | stock only | ✓ | ✓ |
| Users/Roles/Settings | ✓ | – | – | – | – | – | – | read |
| Activity log | ✓ | ✓ | – | – | – | – | ✓ | ✓ |
| Backup | ✓ | – | – | – | – | – | – | – |

### 10.3 Dashboard per peran

**Semua role**
- Salam + shortcut ke tugas utama.
- Notifikasi: dokumen menunggu aksi, stok rendah, invoice jatuh tempo.

**Sales**
- Penjualan hari ini/bulan ini (chart line).
- SO draft/confirmed, invoice outstanding, AR aging ringkas.
- Top 5 customer dan produk.
- Quick actions: buat SO, buat invoice, catat pembayaran.

**Kasir**
- Langsung ke POS (bukan dashboard admin).
- Shift summary: total transaksi, tunai, non-tunai, selisih kas.

**Pembelian**
- PO draft/sent/partial, GRN draft, AP jatuh tempo.
- Harga beli terakhir per produk.
- Quick actions: buat PO, terima barang.

**Gudang**
- Stok rendah, barang masuk hari ini, pengiriman hari ini.
- Quick actions: terima barang, kirim barang, stock opname, cetak barcode.

**Akuntan**
- Jurnal draft, selisih debit-kredit, pajak bulan ini, AR/AP aging.
- Quick actions: jurnal manual, tutup periode, laporan.

**Manajer**
- Ringkasan KPI: penjualan, laba kotor, kas, AR, AP, stok.
- Approval queue: PO, adjustment, void.
- Trend 12 bulan.

**Auditor**
- Read-only + pencarian activity log + ekspor.

### 10.4 Prinsip UI

- Navigasi berbeda per role: sembunyikan grup/resource yang tidak relevan, jangan hanya disable.
- Aksi utama selalu di header halaman (bukan hanya di baris tabel).
- Gunakan istilah konsisten: "Pesanan Penjualan", "Faktur Penjualan", "Penerimaan Barang", "Jurnal Umum".
- Status memakai badge + teks; jangan hanya warna.
- Form transaksi memakai wizard/section: Header → Item → Ringkasan → Konfirmasi.
- Tampilkan total live dan validasi stok/credit sebelum submit.
- Setiap aksi posting/void memakai modal konfirmasi dengan ringkasan dan alasan.
- Semua dokumen punya halaman view (infolist) + timeline + lampiran + cetak.
- Mobile-first untuk gudang dan kasir.

### 10.5 Kondisi nyata per peran saat ini

| Peran | Yang bisa dilakukan sekarang | Yang tidak bisa |
|---|---|---|
| `super_admin` | Masuk ke semua resource, mengelola Role | Membuat PO/GRN/SO/Journal (gagal nomor), membuat invoice benar |
| Sales | Melihat customer, SO, invoice | Konfirmasi SO, membuat invoice dengan total benar, mencatat pembayaran, mencetak PDF |
| Kasir | Tidak ada menu khusus | POS, pembayaran cepat, shift |
| Pembelian | Melihat supplier, PO, GRN | Membuat PO, menerima barang (tanpa item/posting), AP |
| Gudang | Melihat produk dan stok | Menerima barang, mengirim barang, opname, transfer, cetak barcode |
| Akuntan | Melihat COA dan jurnal | Memposting jurnal, jurnal otomatis, laporan keuangan, tutup buku |
| Manajer | Melihat dashboard generik | KPI, approval, laporan lengkap |
| Auditor | Bisa mengubah semua data (karena tidak ada policy) | Mode read-only dan audit trail UI |

### 10.6 Rekomendasi layout konkret

**Shell halaman**
- Header: breadcrumb, judul, status badge, 1 aksi primer + maksimal 3 aksi sekunder di dropdown.
- Body: section bertitel, jarak konsisten (`gap-6`), tanpa card di dalam card.
- Form: lebar maksimum `max-w-5xl`, grid 2 kolom; field panjang `columnSpanFull`; footer aksi sticky.
- Detail dokumen: 2 kolom — kiri item/timeline, kanan metadata + ringkasan total + aksi kontekstual.

**Form transaksi (PO/SO/Invoice/GRN)**
- Section 1 "Informasi Dokumen": supplier/customer, tanggal, jatuh tempo, gudang, status (read-only saat posted).
- Section 2 "Item": repeater dengan kolom produk (SKU + nama + stok), qty, harga, diskon, pajak, subtotal (read-only).
- Section 3 "Ringkasan": subtotal, diskon, pajak, total, ongkir (jika ada), dibayar, sisa — semua read-only dan live.
- Section 4 "Lampiran & Catatan".
- Tombol: Simpan Draft, Simpan & Posting (dengan modal konfirmasi), Batal.

**Daftar transaksi**
- Baris KPI di atas tabel (total, outstanding, overdue) khusus sales/purchasing.
- Filter bar: rentang tanggal, status, customer/supplier, gudang; tombol Reset; simpan view.
- Tabel density compact, kolom uang rata kanan, status badge, aksi baris + bulk action.
- Empty state dengan CTA, bukan tabel kosong.

**Dashboard**
- 4 KPI card, 2 chart (tren penjualan/pembelian, komposisi), 2 tabel (dokumen terbaru, stok rendah).
- Quick actions di atas: buat SO, invoice, PO, terima barang, jurnal.
- Widget berbeda per role; jangan tampilkan widget yang tidak relevan.

**POS/kasir**
- Layout split: kiri grid produk + pencarian/barcode, kanan cart + pembayaran.
- Target sentuh besar, shortcut keyboard, pembayaran tunai/kartu/transfer, kembalian otomatis.

**Gudang (mobile)**
- Satu kolom, tombol scan besar, input qty numerik, konfirmasi ringkas.
- Tampilkan lokasi/warehouse dan sisa kapasitas.

**Konsistensi visual**
- Gunakan token dan komponen Filament; hindari CSS manual dan warna hardcoded.
- Dukung dark mode; uji kontras.
- Gunakan ikon Heroicon yang konsisten; jangan campur ikon dari library lain.

---

## 11. Backlog Fitur (Gap Analysis)

### 11.1 P0 — Stabilisasi (wajib sebelum fitur baru)

1. Perbaiki halaman Laporan Penjualan dengan Schema Filament v5 (hapus komponen v3).
2. Generate nomor dokumen untuk PO, GRN, SO, Invoice, Payment, Journal. `PurchaseNumberGenerator` sudah punya `nextPoNumber()` dan `nextGrnNumber()` tetapi GRN tidak pernah memanggilnya; SO/Invoice/Payment masih inline; Journal belum ada. Hapus input manual atau jadikan read-only.
3. Hubungkan create/edit Filament ke domain action, atau larang create langsung dan sediakan aksi khusus.
4. Tambahkan item repeater + aksi Post pada GRN.
5. Tambahkan aksi Confirm SO, Issue Invoice, Record Payment, Download PDF, Post Journal.
6. Buat DeliveryResource + aksi post delivery (stok keluar, update qty delivered).
7. Tambahkan policy domain + permission + `strictAuthorization()`.
8. Perbaiki API: lengkapi method, perbaiki binding, tambah auth token.
9. Perbaiki timezone (`config/app.php` membaca `APP_TIMEZONE`).
10. Publish migrasi medialibrary + aktifkan upload.
11. Selaraskan validasi maxLength/unique dengan DB.
12. Hapus `FilamentInfoWidget`; perbaiki grup navigasi.
13. Tambahkan seeder data dasar (unit, warehouse, kategori, produk contoh, tax rate, customer/supplier).

### 11.1.1 Target file utama P0

| Pekerjaan | File |
|---|---|
| Laporan | `app/Filament/Pages/SalesReport.php`, `resources/views/filament/pages/sales-report.blade.php` |
| Nomor dokumen | `app/Domain/Purchasing/Services/PurchaseNumberGenerator.php`, service baru untuk Sales/Accounting |
| GRN | `GoodsReceiptNoteResource.php`, `PostGoodsReceiptNoteAction.php`, `GrnJournalService.php` |
| Invoice | `SalesInvoiceResource.php`, `IssueSalesInvoiceAction.php`, `InvoiceJournalService.php` |
| Delivery | resource baru `DeliveryResource.php`, action baru `PostDeliveryAction.php` |
| Payment | `RecordPaymentAction.php`, aksi di `SalesInvoiceResource.php` |
| Journal | `JournalResource.php`, `PostJournalAction.php` |
| Policy | `app/Policies/{Product,PurchaseOrder,GoodsReceiptNote,SalesOrder,SalesInvoice,Delivery,Journal,Account,TaxRate,...}Policy.php` |
| API | `routes/api.php`, `app/Http/Controllers/Api/*.php`, `app/Http/Requests/*` |
| Timezone | `config/app.php` |
| Media | migrasi medialibrary, `public/storage` symlink |
| Navigasi/branding | `app/Providers/Filament/AdminPanelProvider.php` |
| Seeder | `database/seeders/*` |

### 11.2 P1 — Alur inti

1. Purchase invoice/AP + pembayaran supplier + AP aging.
2. Jurnal COGS saat invoice/delivery.
3. Jurnal pembayaran (kas/bank vs AR).
4. Retur penjualan dan pembelian (dengan jurnal balik).
5. Stock adjustment, stock opname, stock transfer.
6. Stock card/ledger UI.
7. Period lock + validasi tanggal transaksi.
8. Laporan keuangan: trial balance, general ledger, P&L, neraca, arus kas.
9. Laporan pajak + rekap PPN/PPh + e-Faktur export.
10. Laporan pembelian, stok, AP aging, sales by customer.
11. POS/kasir + shift + pembayaran langsung.
12. Import/export UI (produk, customer, supplier, stok awal).
13. Manajemen user + role + permission UI.
14. Company settings (nama, NPWP, alamat, bank, logo, nomor dokumen).
15. Activity log UI.
16. Backup UI + notifikasi.
17. Notifikasi low stock, overdue, approval.

### 11.3 P2 — Efisiensi dan kualitas

1. Barcode scanning + print label.
2. Lampiran dokumen (PO, GRN, invoice, pembayaran).
3. Template dokumen (invoice, PO, GRN) + PDF branding.
4. Dashboard per role + chart.
5. Saved filter/view per user.
6. Bulk actions + export.
7. Multi-currency (jika dibutuhkan) — jangan diasumsikan sekarang.
8. Multi-branch/warehouse reporting.
9. Approval workflow berjenjang.
10. Rekonsiliasi bank.
11. Integrasi marketplace/e-commerce/webhook.
12. Audit trail yang lebih kaya (before/after).

---

## 12. Roadmap Pengerjaan

### Fase 1 — Stabilize (P0)
- Perbaiki laporan, nomor dokumen, action wiring, GRN items, delivery, policy, API, timezone, validasi.
- Definition of Done:
  - Semua test lama lulus + test baru untuk setiap alur.
  - PO → GRN posted → stok masuk → jurnal terbentuk.
  - SO → invoice → jurnal + pajak → pembayaran → jurnal → status paid.
  - Delivery posted → stok keluar + COGS + qty delivered.
  - Setiap role hanya melihat menu yang diizinkan.
  - `phpstan` dan `pint` lulus.

### Fase 2 — Complete Core (P1)
- AP, COGS, retur, opname/transfer, laporan keuangan, pajak, POS, import/export, settings, user management, activity log, backup.
- Definition of Done: seluruh siklus procure-to-pay dan order-to-cash bisa dilakukan end-to-end dari UI; laporan keuangan balance.

### Fase 3 — Scale & Integrate (P2)
- Barcode, lampiran, template, dashboard role, approval, API publik, webhook, multi-branch.
- Definition of Done: API terdokumentasi (OpenAPI), ada idempotency, rate limit, dan test integrasi.

---

## 13. Prompt Siap Tempel untuk Claude

> Salin seluruh blok di bawah ini ke Claude bersama dokumen ini.

```
Kamu adalah senior Laravel + Filament engineer yang melanjutkan proyek ERP Samarinda.

KONTEKS
- Baca dokumen docs/ERP-SAMARINDA-CONTEXT-AND-UI-AUDIT.md di repository ini sebagai sumber kebenaran.
- Stack: PHP 8.5, Laravel 13.38, Filament v5, Livewire v4, MySQL, Redis, Pest, PHPStan level 8, Pint.
- Arsitektur: modular monolith di app/Domain/{Inventory,Purchasing,Sales,Accounting,Tax,Reporting}.
- Domain action/service SUDAH ADA dan harus dipakai ulang, bukan ditulis ulang:
  CreatePurchaseOrderAction, PostGoodsReceiptNoteAction, CreateSalesOrderAction,
  IssueSalesInvoiceAction, RecordPaymentAction, RecordStockMovementAction,
  PostJournalAction, RecordTaxTransactionAction, GrnJournalService, InvoiceJournalService.
- Database: 30 migrasi, UUID, soft deletes, stock_ledgers append-only, stock_balances per (product, warehouse).
- Jangan mengubah skema tanpa migrasi baru yang kompatibel.

TUJUAN
Selesaikan Fase 1 (P0) dari dokumen: buat UI admin Filament v5 benar-benar menjadi jalur transaksi yang sah,
lengkapi alur inti, dan pasang otorisasi per role. Setelah itu lanjutkan Fase 2 secara bertahap.

ATURAN WAJIB
1. Gunakan API Filament v5 yang benar. Jangan gunakan komponen Blade v3 seperti
   <x-filament::field> atau <x-filament::input.label>; komponen yang tersedia:
   <x-filament::input>, <x-filament::input.wrapper>, <x-filament::input.select>, <x-filament::button>.
   Untuk form/filter, gunakan Filament\Schemas\Schema dan komponen schema (Section, Grid, DatePicker, Select, Repeater).
2. Jangan menyimpan transaksi langsung dari form Filament. Semua create/update dokumen harus lewat domain action
   atau service yang sudah ada; bila action belum ada, buat action baru di domain yang tepat.
3. Nomor dokumen digenerate server-side dan unik. Jangan meminta user mengetik nomor.
4. Jangan memanggil DB mentah dari Blade. Gunakan service/report class dan komponen Filament.
5. Jangan menambah dependency baru tanpa alasan kuat; package yang sudah tersedia: Shield, permission,
   activitylog, medialibrary, backup, horizon, excel, dompdf.
6. Jangan mengubah atau menghapus test yang ada. Tambahkan test untuk setiap alur baru.
7. Jangan menggunakan emoji di kode/UI.
8. Semua label UI, pesan validasi, dan status dalam Bahasa Indonesia; identifier kode tetap Bahasa Inggris.
9. Jangan melakukan refactor besar di luar scope. Perubahan kecil, terfokus, dan dapat diuji.
10. Setelah setiap perubahan: jalankan `php artisan test`, `vendor/bin/phpstan analyse`, `vendor/bin/pint --test`.
    Jika gagal, perbaiki sampai lulus sebelum lanjut.

URUTAN KERJA
Langkah 1 — Audit ulang singkat
- Baca dokumen, lalu verifikasi file yang relevan.
- Tulis daftar file yang akan diubah dan alasan singkat.

Langkah 2 — P0.1 Perbaiki laporan
- Ganti filter laporan dengan Schema Filament v5 (DatePicker + tombol Action).
- Ganti tabel manual dengan komponen Filament (Table atau tabel Blade dengan token fi-*), dukung dark mode.
- Tambahkan export menggunakan SalesSummaryExport.
- Pastikan halaman bisa dibuka tanpa error untuk user login.

Langkah 3 — P0.2 Nomor dokumen
- Implementasikan generator konsisten untuk PO, GRN, SO, Invoice, Journal.
- Pertahankan format yang sudah dipakai: {PREFIX}-{YYYYMMDD}-{URUTAN 4 digit}; jadikan satu service
  (PO/GRN sudah punya PurchaseNumberGenerator; SO/Invoice/Payment masih inline; Journal belum ada).
  Pastikan unik dan aman dari race condition (transaksi + lock atau retry pada duplicate key).
- Hapus input manual nomor dari form; tampilkan sebagai placeholder/read-only.
- Tambahkan test: dua create berurutan menghasilkan nomor berbeda.

Langkah 4 — P0.3–P0.6 Alur dokumen
- PO: create lewat CreatePurchaseOrderAction; tambahkan aksi Kirim, Batalkan, dan tampilkan total live.
- GRN: tambahkan repeater item dari PO, validasi qty ≤ sisa PO, aksi Post yang memanggil
  PostGoodsReceiptNoteAction; setelah post, stok masuk dan jurnal terbentuk; cegah post ganda.
- SO: create lewat CreateSalesOrderAction; aksi Konfirmasi (cek credit limit), Buat Invoice, Buat Delivery;
  tampilkan total live dan status timeline.
- Invoice: create lewat IssueSalesInvoiceAction (bukan insert langsung); hapus input total manual;
  aksi Catat Pembayaran dan Unduh PDF; cegah edit setelah issued (atau izinkan hanya draft).
- Journal: create lewat PostJournalAction; validasi debit = credit; aksi Post; larang edit/hapus yang sudah posted.
- Payment: modal dengan jumlah, tanggal, metode, referensi; panggil RecordPaymentAction; tampilkan sisa.

Langkah 5 — P0.7 Delivery
- Buat DeliveryResource + halaman create/edit/view.
- Delivery dibuat dari SO (prefill item dan qty outstanding), posting memanggil RecordStockMovementAction tipe out,
  membuat jurnal COGS, dan mengupdate quantity_delivered pada SO.
- Tambahkan test stok berkurang dan jurnal COGS terbentuk.

Langkah 6 — P0.8 Otorisasi
- Buat policy untuk setiap model domain + permission granular (viewAny/view/create/update/delete/restore
  serta ability khusus: confirm, issue, post, void, approve).
- Daftarkan policy di AuthServiceProvider atau auto-discovery.
- Assign permission ke role: super_admin, manajer, sales, kasir, pembelian, gudang, akuntan, auditor.
- Aktifkan `->strictAuthorization()` di panel.
- `User::canAccessPanel()` harus memeriksa role aktif, bukan selalu true.
- Sembunyikan resource/aksi yang tidak diizinkan; tambahkan test akses per role.

Langkah 7 — P0.9 API
- Lengkapi method yang hilang atau hapus route yang tidak didukung.
- Perbaiki binding: gunakan `{salesOrder}`/`{salesInvoice}` atau `findOrFail`.
- Tambahkan endpoint login/logout/token (Sanctum) dan endpoint `/api/me`.
- Tambahkan validasi numeric/min, resource transformer, rate limit, dan test feature.
- Pastikan semua endpoint mengembalikan 200/201/422 yang benar, bukan 500.

Langkah 8 — P0.10 Konfigurasi
- Perbaiki timezone: `config/app.php` membaca `env('APP_TIMEZONE', 'UTC')`.
- Publish migrasi medialibrary dan aktifkan lampiran pada dokumen.
- Selaraskan maxLength dan unique validation dengan skema DB.
- Hapus FilamentInfoWidget; satukan grup navigasi ke Bahasa Indonesia.
- Tambahkan seeder data dasar yang idempoten.

Langkah 9 — Verifikasi
- Jalankan seluruh test, PHPStan, Pint.
- Uji manual alur: PO → GRN → stok → jurnal; SO → invoice → pajak → pembayaran → jurnal; delivery → stok keluar.
- Laporkan: file yang diubah, migrasi baru, test baru, dan sisa risiko.

FORMAT OUTPUT
- Mulai dengan rencana singkat (maksimal 10 baris).
- Kerjakan per langkah; setelah tiap langkah tampilkan ringkasan perubahan dan hasil test.
- Jangan menulis placeholder atau TODO.
- Di akhir, berikan daftar perintah verifikasi dan ringkasan gap yang belum terselesaikan.

MULAI DARI: Langkah 1. Jangan mengerjakan Fase 2 sebelum seluruh P0 lulus.
```

---

## 14. Lampiran

### 14.1 Bukti verifikasi

| Verifikasi | Hasil |
|---|---|
| `php artisan test` | 23 passed, 45 assertions, 549 ms |
| `vendor/bin/phpstan analyse` | 0 error (level 8) |
| `vendor/bin/pint --test` | passed |
| Blade `x-filament::field` | `InvalidArgumentException: Unable to locate a class or view for component [filament::field].` |
| Blade `x-filament::input.label` | `InvalidArgumentException: Unable to locate a class or view for component [filament::input.label].` |
| `x-filament::button`, `x-filament::input` | valid |
| `Route::has('login' / 'dashboard' / 'register')` | semuanya `false` |
| Jumlah permission | 12, semuanya `*:Role` |
| `Gate::allows('viewAny', Product::class)` | `false` |
| Filament tanpa policy + strict off | `Response::allow()` |
| Jumlah resource Filament | 13 (Delivery tidak ada) |
| Jumlah model | 24 |
| Jumlah migrasi | 30 |
| Jumlah tabel | 40 |
| `media` table | tidak ada |
| Data produk/invoice | 0 / 0 |

### 14.2 Asumsi dan batasan

- Audit dilakukan pada environment lokal (MySQL 26.10, Redis 8.10) dan tidak menguji beban produksi.
- Klaim UI didasarkan pada kode dan verifikasi komponen; beberapa alur belum diuji end-to-end karena data kosong dan halaman laporan error.
- Tidak ada akses ke kebutuhan bisnis formal (mis. aturan pajak, format e-Faktur, mata uang, multi-cabang). Jangan mengarang aturan; tanyakan bila dibutuhkan.
- `APP_TIMEZONE` di `.env` benar, tetapi konfigurasi Laravel mengabaikannya. Ini bug, bukan salah konfigurasi env.
- ADR memiliki drift nama tabel/class; kode aktual menang.
- Jangan menganggap package terpasang otomatis aktif: medialibrary belum punya tabel, Horizon gate kosong, dan export/import belum ter-wire.

### 14.3 File referensi utama

```
app/Providers/Filament/AdminPanelProvider.php
app/Providers/HorizonServiceProvider.php
app/Models/User.php
app/Policies/RolePolicy.php
routes/api.php
routes/web.php
config/app.php
config/accounting.php
app/Domain/Sales/Actions/*.php
app/Domain/Purchasing/Actions/*.php
app/Domain/Inventory/Actions/RecordStockMovementAction.php
app/Domain/Accounting/Actions/PostJournalAction.php
app/Domain/Accounting/Services/{InvoiceJournalService,GrnJournalService}.php
app/Domain/*/Filament/Resources/*.php
app/Filament/Pages/SalesReport.php
resources/views/filament/pages/sales-report.blade.php
resources/views/sales/invoices/pdf.blade.php
app/Domain/Sales/Pdfs/SalesInvoicePdf.php
database/migrations/*.php
database/seeders/ChartOfAccountsSeeder.php
docs/adr/000{1..5}-*.md
```
