# Project Analysis: Transport Management System

## 1. Tech Stack + Exact Package Versions

### Backend (composer.json constraints)
- PHP: ^8.2
- laravel/framework: ^11.0
- laravel/tinker: ^2.9
- maatwebsite/excel: ^3.1
- fakerphp/faker (dev): ^1.23
- laravel/pint (dev): ^1.13
- laravel/sail (dev): ^1.26
- mockery/mockery (dev): ^1.6
- nunomaduro/collision (dev): ^8.0
- phpunit/phpunit (dev): ^10.5
- spatie/laravel-ignition (dev): ^2.4

> composer.lock not present; exact resolved versions unavailable.

### Frontend (package.json constraints)
- vite: ^5.0
- @tailwindcss/vite: ^4.3.3
- tailwindcss: ^4.3.3
- @tailwindcss/forms: ^0.5.11
- @tailwindcss/typography: ^0.5.20
- alpinejs: ^3.15.12
- axios: ^1.6.4
- laravel-vite-plugin: ^1.0

> package-lock.json not present; exact resolved versions unavailable.

### Stack
- Framework: Laravel 11
- PHP: 8.2+
- Database: SQLite default (MySQL/MariaDB/PostgreSQL/SQL Server supported)
- Frontend: Tailwind CSS v4 + Alpine.js v3 + Vite 5
- Excel: Maatwebsite Excel v3 (PhpSpreadsheet)
- Testing: PHPUnit 10
- Session: database (encrypted, HTTP-only, SameSite=strict)
- Queue: database
- Cache: database

---

## 2. Folder/File Structure

```
D:\transport\
├── _cookies.txt                   # Browser cookie export (laravel_session + XSRF-TOKEN) — SECURITY RISK
├── _headers.txt                   # Captured HTTP headers (419 CSRF failure) — debug artifact
├── _login.html                    # Saved login page HTML (contains CSRF token) — debug artifact
├── _login_full.html               # Another saved login page — debug artifact
├── check_config.php               # Standalone script to inspect session/app config values
├── debug_breakeven.php            # Debug: breakeven profit logic
├── debug_created_at.php           # Debug: created_at handling
├── debug_filter.php               # Debug: transport log filtering
├── debug_test.php                 # Debug: factory + update + profit filter
├── debug_update.php               # Debug: update computed fields
├── debug_update2.php              # Debug: update with SQL query logging
├── find_comment.php               # Debug: PhpSpreadsheet Comment class discovery
├── find_comment2.php              # Debug: Worksheet/Cell comment method reflection
├── find_comment3.php              # Debug: spreadsheet instantiation + comment class
├── find_comment4.php              # Debug: Worksheet comment method signatures
├── vite.config.js                 # Vite: Laravel plugin + Tailwind CSS plugin, host 127.0.0.1
│
├── app/
│   ├── Exports/
│   │   └── TransportLogsExport.php        # Excel export with formulas + styled headers
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Controller.php             # Base controller
│   │   │   ├── DashboardController.php    # Dashboard aggregates + recent logs + cache invalidation
│   │   │   ├── AccountController.php      # Accounts CRUD (Super Admin only)
│   │   │   ├── AccountTransactionController.php # Account transaction CRUD (Super Admin only)
│   │   │   ├── TraceController.php        # Trace lookup page for transport log traceability
│   │   │   ├── TransportLogController.php # CRUD + 4 export endpoints; status filter fixed for Stringable comparison
│   │   │   ├── UserController.php         # CRUD + resetPassword + activate/deactivate
│   │   │   ├── SettingController.php      # Profile edit/update (Super Admin email/password)
│   │   │   ├── ActivityLogController.php  # Index + show (Super Admin only)
│   │   ├── InlineEntityController.php # AJAX create for branches & fuel stations (auth+active)
│   │   │   └── Auth/
│   │   │       ├── AuthenticatedSessionController.php # Login + logout
│   │   │       └── PasswordResetController.php        # Forgot + reset password
│   │   ├── Middleware/
│   │   │   ├── EnsureUserIsActive.php     # Blocks inactive users
│   │   │   ├── LogActivity.php            # Logs CRUD to activity_logs
│   │   │   ├── NoCache.php                # No-cache headers
│   │   │   └── RoleMiddleware.php         # Role checking
│   │   └── Requests/
│   │       ├── Auth/
│   │       │   ├── LoginRequest.php               # Login validation + failed login logging
│   │       │   ├── NewPasswordRequest.php         # Password reset validation
│   │       │   └── PasswordResetLinkRequest.php   # Forgot password validation
│   │       ├── Profile/
│   │       │   └── UpdateProfileRequest.php       # Super Admin can change email/password
│   │       ├── Account/
│   │   │   ├── StoreAccountRequest.php        # Account validation + null coercion for linked + bank fields
│   │       │   └── StoreAccountTransactionRequest.php # Transaction validation (branch_id, direction, amount, etc.)
│   │       └── Transport/
│   │           ├── StoreTransportLogRequest.php   # Create validation + auth
│   │           ├── UpdateTransportLogRequest.php  # Update validation + auth
│   │           └── TransportLogRules.php          # Shared validation rules trait
│   ├── Models/
│   │   ├── User.php                 # Authenticatable; roles; relationships
│   │   ├── Account.php              # Ledger account (fuel_station, motor_parts_shop, staff, company_expense)
│   │   ├── AccountTransaction.php   # Ledger transaction with running balance
│   │   ├── TransportLog.php         # Core model; SoftDeletes; computed totals
│   │   ├── Company.php              # Companies (also used as carriers)
│   │   ├── Branch.php               # Branches
│   │   ├── Vehicle.php              # Vehicles
│   │   ├── Driver.php               # Drivers
│   │   ├── FuelStation.php          # Fuel stations
│   │   ├── ExpenseCategory.php      # Expense categories
│   │   ├── ActivityLog.php          # Audit trail
│   │   ├── StationDebit.php         # Fuel station debits
│   │   ├── StationCredit.php        # Fuel station credits
│   │   └── StationBalance.php       # Fuel station balances
│   ├── Observers/
│   │   ├── TransportLogObserver.php # Recomputes totals on creating/updating; syncs StationDebit and account_transactions on created/updated; reverses on deleting; generates trace_code; invalidates dashboard cache
│   │   └── AccountTransactionObserver.php # Recalculates fuel settlement + invalidates dashboard cache on created/updated/deleted/restored
│   ├── Policies/
│   │   ├── UserPolicy.php           # Super Admin full; Admin read-only
│   │   ├── TransportLogPolicy.php   # Super Admin full; Admin view/create only
│   │   ├── ActivityLogPolicy.php    # Super Admin view only
│   │   ├── AccountPolicy.php        # Super Admin full
│   │   └── AccountTransactionPolicy.php # Super Admin full
│   ├── Providers/
│   │   └── AppServiceProvider.php   # Policies + custom Gates + observer registration
│   └── Console/
│       └── Commands/
│           └── VerifyAccountsIntegrity.php # Scans for orphaned/mismatched records and running-balance drift
│   └── Services/
│       ├── ActivityLogger.php       # Static helper for activity log entries
│       ├── TransportLogService.php  # computeTotals() for financial fields
│       ├── AccountLedgerService.php # create/reverse/delete transactions; balance summary; filtered transactions
│       └── FuelSettlementService.php # Recalculates fuel_paid_amount and fuel_payment_status from linked credit transactions
│
├── config/
│   ├── app.php                      # App config
│   ├── auth.php                     # Auth guards + password broker
│   ├── database.php                 # DB connections (sqlite default)
│   ├── session.php                  # Session config (database, encrypted)
│   ├── mail.php                     # Mail config (log default)
│   ├── cache.php                    # Cache config (database default)
│   ├── queue.php                    # Queue config (database default)
│   ├── logging.php                  # Logging config
│   ├── filesystems.php              # Filesystem config
│   └── services.php                 # Third-party service placeholders
│
├── database/
│   ├── factories/                   # 12 factories
│   ├── migrations/                  # 23 migrations
│   └── seeders/                     # DatabaseSeeder + 6 individual seeders
│
├── resources/
│   ├── css/app.css                  # Tailwind v4 + custom theme
│   ├── js/
│   │   ├── bootstrap.js             # Axios setup
│   │   └── app.js                   # Alpine: theme, layout, transportForm, fuelStationPicker, inlineCreate, toast
│   └── views/
│       ├── layouts/
│       │   ├── app.blade.php        # Main layout
│       │   └── auth.blade.php       # Auth layout
│       ├── components/
│       │   ├── layout/
│       │   │   ├── sidebar.blade.php
│       │   │   ├── header.blade.php
│       │   │   └── breadcrumb.blade.php
│       │   ├── accounts/
│       │   │   ├── account-type-icon.blade.php
│       │   │   ├── balance-summary-card.blade.php
│       │   │   ├── filters-bar.blade.php
│       │   │   ├── ledger-table.blade.php
│       │   │   └── transaction-form-modal.blade.php
│       │   │   ├── _inline-entity-modals.blade.php  # Inline modals to create branches/fuel stations without leaving the page
│       │   └── ui/
│       │       ├── badge.blade.php
│       │       ├── button.blade.php
│       │       ├── input.blade.php
│       │       ├── kpi-card.blade.php
│       │       ├── modal.blade.php
│       │       ├── select.blade.php
│       │       ├── table.blade.php
│       │       └── toast.blade.php
│       ├── accounts/
│       │   ├── index.blade.php           # Accounts hub (4 type cards + recent transactions)
│       │   ├── type-index.blade.php      # Per-type card grid + search/status filter
│       │   ├── show.blade.php            # Ledger: summary + filters + table + modal
│       │   ├── create.blade.php          # New account form
│       │   ├── edit.blade.php            # Edit account form
│       │   └── transactions/
│       │       └── edit.blade.php        # Edit transaction form
│       ├── dashboard/dashboard.blade.php
│       ├── auth/
│       │   ├── login.blade.php
│       │   ├── forgot-password.blade.php
│       │   └── reset-password.blade.php
│       ├── errors/
│       ├── logs/
│       │   ├── index.blade.php
│       │   ├── create.blade.php
│       │   ├── _form.blade.php
│       │   ├── edit.blade.php
│       │   ├── show.blade.php
│       │   ├── activity.blade.php
│       │   └── activity-show.blade.php
│       ├── trace/
│       │   └── show.blade.php        # Traceability lookup page for a single transport log
│       ├── users/
│       │   ├── index.blade.php
│       │   ├── create.blade.php
│       │   ├── _form.blade.php
│       │   ├── edit.blade.php
│       │   └── show.blade.php
│       └── settings/index.blade.php
│
├── routes/
│   ├── web.php                      # All routes
│   └── console.php                  # Artisan inspire + accounts:verify-integrity
│
├── tests/
│   ├── TestCase.php
│   ├── Unit/ExampleTest.php
│   └── Feature/
│       ├── ExampleTest.php
│       ├── ViewFoundationTest.php
│       ├── SettingsPermissionTest.php
│       ├── ProductionFeatureTest.php
│       ├── ObserverLedgerActivityTest.php
│       ├── ExcelExportTest.php
│       ├── DebugProfitFilterTest.php
│       ├── ComputedBreakdownTest.php
│       └── ActivityChangeTrackingTest.php
│       └── AccountLedgerTest.php
│
├── .env.example
├── composer.json
├── package.json
├── vite.config.js
└── README.md
```

---

## 3. Complete DB Schema

### users
- id (PK), name, email (unique), email_verified_at (nullable), password, role (enum: super_admin/admin, default admin), is_active (boolean, default true), remember_token, branch_id (FK → branches.id, nullable, nullOnDelete), created_at, updated_at
- Indexes: email (unique), branch_id

### password_reset_tokens
- email (PK), token, created_at (nullable)

### sessions
- id (PK), user_id (FK → users.id, nullable, indexed), ip_address (varchar 45, nullable), user_agent (text, nullable), payload (longText), last_activity (indexed)

### branches
- id (PK), name, code (unique), address (text, nullable), created_at, updated_at
- Indexes: name

### companies
- id (PK), name, slug (unique), gstin (nullable), contact_info (text, nullable), is_active (boolean, default true), created_at, updated_at
- Indexes: name

### expense_categories
- id (PK), name, slug (unique), is_active (boolean, default true), created_at, updated_at
- Indexes: name

### vehicles
- id (PK), vehicle_no (unique), owner_name (nullable), type (nullable), capacity_kg (decimal 12,2, nullable), mileage_baseline (decimal 12,2, nullable), is_active (boolean, default true), branch_id (FK → branches.id, nullable, nullOnDelete), created_at, updated_at
- Indexes: vehicle_no, branch_id

### drivers
- id (PK), name, license_no (unique), phone (nullable), vehicle_id (FK → vehicles.id, nullable, nullOnDelete), is_active (boolean, default true), branch_id (FK → branches.id, nullable, nullOnDelete), created_at, updated_at
- Indexes: name, branch_id

### fuel_stations
- id (PK), name, slug (unique), contact_info (text, nullable), address (text, nullable), is_active (boolean, default true), branch_id (FK → branches.id, nullable, nullOnDelete), created_at, updated_at
- Indexes: name, branch_id

### station_balances
- id (PK), fuel_station_id (FK → fuel_stations.id, cascadeOnDelete), total_amount (decimal 14,2, default 0), created_at, updated_at
- Unique: fuel_station_id

### station_credits
- id (PK), fuel_station_id (FK → fuel_stations.id, cascadeOnDelete), branch_id (FK → branches.id, nullable, nullOnDelete), amount (decimal 14,2), reference_type (nullable), reference_id (nullable), notes (text, nullable), created_by (FK → users.id, nullable, nullOnDelete), updated_by (FK → users.id, nullable, nullOnDelete), date (date), created_at, updated_at, deleted_at (SoftDeletes)
- Indexes: fuel_station_id, branch_id, (reference_type, reference_id)

### station_debits
- id (PK), fuel_station_id (FK → fuel_stations.id, cascadeOnDelete), branch_id (FK → branches.id, nullable, nullOnDelete), amount (decimal 14,2), reference_type (nullable), reference_id (nullable), notes (text, nullable), created_by (FK → users.id, nullable, nullOnDelete), updated_by (FK → users.id, nullable, nullOnDelete), date (date), created_at, updated_at, deleted_at (SoftDeletes)
- Indexes: fuel_station_id, branch_id, (reference_type, reference_id)

### transport_logs
- id (PK), date (date), vehicle_no, company, transport_name, logsheet_no (nullable, unique), trace_code (nullable, unique, varchar 20), destination (nullable), km (decimal 12,2, default 0), weight (decimal 12,2, default 0), to_bb_sale (decimal 14,2, default 0), paid_sale (decimal 14,2, default 0), to_pay (decimal 14,2, default 0), total_sale (decimal 14,2, default 0, computed), freight (decimal 14,2, default 0), loading (decimal 14,2, default 0), unloading (decimal 14,2, default 0), dd (decimal 14,2, default 0), tempu_expense (decimal 14,2, default 0), commission (decimal 14,2, default 0), total_expense (decimal 14,2, default 0, computed), profit (decimal 14,2, default 0, computed), diesel_advance (decimal 14,2, default 0), cash_advance (decimal 14,2, default 0), total_advance (decimal 14,2, default 0, computed), payment (decimal 14,2, default 0), fuel_station_name (nullable), fuel_station_balance (decimal 14,2, default 0), balance_vehicle_payment (decimal 14,2, default 0, computed), clearing_date (date, nullable), detail (text, nullable), remarks (text, nullable), mileage (decimal 12,2, nullable), dtg_office_expense (decimal 14,2, default 0), vehicle_id (FK → vehicles.id, nullable, nullOnDelete), company_id (FK → companies.id, nullable, nullOnDelete), carrier_id (FK → companies.id, nullable, nullOnDelete), branch_id (FK → branches.id, nullable, nullOnDelete), fuel_station_id (FK → fuel_stations.id, nullable, nullOnDelete), created_by (FK → users.id, nullable, nullOnDelete), updated_by (FK → users.id, nullable, nullOnDelete), created_at, updated_at, deleted_at (SoftDeletes)
- Indexes: date, vehicle_no, company, transport_name, logsheet_no, trace_code, created_by, updated_by, branch_id, fuel_station_id, (date, company), (date, vehicle_no), created_at, clearing_date, (created_at, profit), (created_at, company)
- Check constraints (MySQL/MariaDB): all numeric expense/sale fields >= 0

### activity_logs
- id (PK), user_id (FK → users.id, nullable, nullOnDelete), role (nullable), action, table_name (nullable), subject_type (nullable), record_id (nullable), record_summary (nullable), description (text, nullable), ip_address (nullable), user_agent (nullable), success (boolean, default true), method (nullable), url (nullable), old_values (json, nullable), new_values (json, nullable), changes (json, nullable), device_info (varchar 512, nullable), session_id (varchar 128, nullable), created_at (nullable)
- Indexes: user_id, action, created_at, (subject_type, record_id), role, success, method

### cache
- key (PK), value (mediumText), expiration (integer)

### cache_locks
- key (PK), owner (varchar), expiration (integer)

### jobs
- id (PK), queue (indexed), payload (longText), attempts (unsignedTinyInteger), reserved_at (nullable), available_at, created_at

### job_batches
- id (PK), name, total_jobs, pending_jobs, failed_jobs, failed_job_ids (longText), options (mediumText, nullable), cancelled_at (nullable), created_at, finished_at (nullable)

### failed_jobs
- id (PK), uuid (unique), connection, queue, payload (longText), exception (longText), failed_at (timestamp, useCurrent)

### migrations
- migration (PK), batch (integer)

---

## 4. Seeders/Factories Summary

### Seeders
- **DatabaseSeeder**: 3 branches, 12 companies, 15 vehicles, 15 drivers, 7 expense categories, 8 fuel stations (each with 0 balance), 1 Super Admin (admin@sls.com/password), 2 Admins (admin1@sls.com/password, admin2@sls.com/password), 60 transport logs (with station debits for diesel advances), 30 activity logs.
- **BranchSeeder**: 3 branches.
- **CompanySeeder**: 12 hardcoded Indian logistics companies.
- **VehicleSeeder**: 15 vehicles.
- **DriverSeeder**: 15 drivers.
- **FuelStationSeeder**: 8 fuel stations.
- **ExpenseCategorySeeder**: 7 categories (Freight, Loading, Unloading, DD, Tempu Expense, Commission, DTG Office Expense).

### Factories
- **UserFactory**: fake name/email; password 'password'; states: superAdmin, admin, inactive.
- **BranchFactory**: fake city + 3-char code + address.
- **CompanyFactory**: fake company + logistics suffix; fake GSTIN regex.
- **VehicleFactory**: fake Indian vehicle number; types: Truck/Trailer/Tanker/Container/LCV/HCV; capacity_kg, mileage_baseline.
- **DriverFactory**: fake name; license_no regex; phone; vehicle + branch.
- **FuelStationFactory**: 8 brands + city name.
- **ExpenseCategoryFactory**: picks from 7 hardcoded names.
- **TransportLogFactory**: coherent financial data; 12 company names; 6 fuel station names; 70% clearing_date chance. States: loss, breakEven, overpaid.
- **StationBalanceFactory**: total_amount (-5000 to 50000). States: positive, negative.
- **StationCreditFactory**: reference_type (transport_log/invoice/manual), reference_id, notes, date.
- **StationDebitFactory**: same shape as StationCredit.
- **ActivityLogFactory**: random user + action + table_name + description + created_at.

---

## 5. All Routes Mapped to Controller@Method with Middleware

| Method | URI | Name | Controller@Method | Middleware |
|--------|-----|------|-------------------|-----------|
| GET | / | home | Closure | web |
| GET | /login | login | AuthenticatedSessionController@create | guest, web |
| POST | /login | — | AuthenticatedSessionController@store | guest, throttle:5,1, web |
| GET | /forgot-password | password.request | PasswordResetController@create | guest, web |
| POST | /forgot-password | password.email | PasswordResetController@store | guest, throttle:5,1, web |
| GET | /reset-password | password.reset | PasswordResetController@edit | guest, web |
| POST | /reset-password | password.store | PasswordResetController@update | guest, throttle:5,1, web |
| POST | /logout | logout | AuthenticatedSessionController@destroy | auth, no.cache, web |
| GET | /dashboard | dashboard | DashboardController@index | auth, active, no.cache, web |
| GET | /transport-logs | transport-logs.index | TransportLogController@index | auth, active, no.cache, web |
| GET | /transport-logs/create | transport-logs.create | TransportLogController@create | auth, active, no.cache, web |
| POST | /transport-logs | transport-logs.store | TransportLogController@store | auth, active, no.cache, web |
| GET | /transport-logs/{transport_log} | transport-logs.show | TransportLogController@show | auth, active, no.cache, web |
| GET | /transport-logs/{transport_log}/edit | transport-logs.edit | TransportLogController@edit | auth, active, no.cache, web |
| PUT/PATCH | /transport-logs/{transport_log} | transport-logs.update | TransportLogController@update | auth, active, no.cache, web |
| DELETE | /transport-logs/{transport_log} | transport-logs.destroy | TransportLogController@destroy | auth, active, no.cache, web |
| GET | /transport-logs/export/monthly | transport-logs.export.monthly | TransportLogController@exportMonthly | auth, active, no.cache, web |
| GET | /transport-logs/export/yearly | transport-logs.export.yearly | TransportLogController@exportYearly | auth, active, no.cache, web |
| GET | /transport-logs/export/range | transport-logs.export.range | TransportLogController@exportRange | auth, active, no.cache, web |
| GET | /transport-logs/{transport_log}/export/single | transport-logs.export.single | TransportLogController@exportSingle | auth, active, no.cache, web |
| GET | /settings | settings.edit | SettingController@edit | auth, active, no.cache, web |
| PATCH | /settings | settings.update | SettingController@update | auth, active, no.cache, web |
| GET | /users | users.index | UserController@index | auth, active, no.cache, role:super_admin, web |
| GET | /users/create | users.create | UserController@create | auth, active, no.cache, role:super_admin, web |
| POST | /users | users.store | UserController@store | auth, active, no.cache, role:super_admin, web |
| GET | /users/{user} | users.show | UserController@show | auth, active, no.cache, role:super_admin, web |
| GET | /users/{user}/edit | users.edit | UserController@edit | auth, active, no.cache, role:super_admin, web |
| PUT/PATCH | /users/{user} | users.update | UserController@update | auth, active, no.cache, role:super_admin, web |
| DELETE | /users/{user} | users.destroy | UserController@destroy | auth, active, no.cache, role:super_admin, web |
| POST | /users/{user}/reset-password | users.reset-password | UserController@resetPassword | auth, active, no.cache, role:super_admin, web |
| POST | /users/{user}/deactivate | users.deactivate | UserController@deactivate | auth, active, no.cache, role:super_admin, web |
| POST | /users/{user}/activate | users.activate | UserController@activate | auth, active, no.cache, role:super_admin, web |
| GET | /activity-logs | activity-logs.index | ActivityLogController@index | auth, active, no.cache, role:super_admin, web |
| GET | /activity-logs/{activity_log} | activity-logs.show | ActivityLogController@show | auth, active, no.cache, role:super_admin, web |
| GET | /accounts | accounts.index | AccountController@index | auth, active, no.cache, role:super_admin, web |
| GET | /accounts/create | accounts.create | AccountController@create | auth, active, no.cache, role:super_admin, web |
| POST | /accounts | accounts.store | AccountController@store | auth, active, no.cache, role:super_admin, web |
| GET | /accounts/{account} | accounts.show | AccountController@show | auth, active, no.cache, role:super_admin, web |
| GET | /accounts/{account}/edit | accounts.edit | AccountController@edit | auth, active, no.cache, role:super_admin, web |
| PUT/PATCH | /accounts/{account} | accounts.update | AccountController@update | auth, active, no.cache, role:super_admin, web |
| DELETE | /accounts/{account} | accounts.destroy | AccountController@destroy | auth, active, no.cache, role:super_admin, web |
| GET | /accounts/{account}/transactions/{transaction}/edit | accounts.transactions.edit | AccountTransactionController@edit | auth, active, no.cache, role:super_admin, web |
| POST | /accounts/{account}/transactions | accounts.transactions.store | AccountTransactionController@store | auth, active, no.cache, role:super_admin, web |
| PUT/PATCH | /accounts/{account}/transactions/{transaction} | accounts.transactions.update | AccountTransactionController@update | auth, active, no.cache, role:super_admin, web |
| DELETE | /accounts/{account}/transactions/{transaction} | accounts.transactions.destroy | AccountTransactionController@destroy | auth, active, no.cache, role:super_admin, web |
| GET | /accounts/transport-logs/search | accounts.transport-logs.search | AccountController@searchTransportLogs | auth, active, no.cache, role:super_admin, web |
| GET | /accounts/{account}/pump-flow | accounts.pump-flow | AccountController@pumpFlow | auth, active, no.cache, role:super_admin, web |
| GET | /trace/{trace_code} | trace.show | TraceController@show | auth, active, no.cache, web |
| GET | /403 | errors.forbidden | Closure | web |
| GET | /500 | errors.server | Closure | web |
| GET | /* (fallback) | — | Closure | web |

---

## 6. All Models with Relationships

### User
- Fillable: name, email, password, role, is_active, branch_id
- Hidden: password, remember_token
- Casts: email_verified_at (datetime), password (hashed), is_active (boolean)
- Constants: ROLE_SUPER_ADMIN = 'super_admin', ROLE_ADMIN = 'admin'
- Methods: isSuperAdmin(), isAdmin(), isActive(), initials()
- Relationships: hasMany TransportLog (created_by), hasMany ActivityLog, belongsTo Branch

### Account
- Fillable: type, name, linked_fuel_station_id, linked_driver_id, branch_id, contact_info, address, opening_balance, current_balance, is_active, metadata, aadhar_no, driving_license_no, bank_account_no, bank_ifsc_code, bank_name, created_by, updated_by
- Casts: opening_balance (decimal:2), current_balance (decimal:2), is_active (boolean), metadata (array)
- Relationships: belongsTo FuelStation, belongsTo Driver, belongsTo Branch, hasMany AccountTransaction
- Types: fuel_station, motor_parts_shop, staff, company_expense
- Staff-specific: `aadhar_no` (string, globally unique, 12 digits), `driving_license_no` (string, nullable unless is_driver or linked_driver_id is set)
- Bank details (fuel_station & staff): `bank_account_no` (string, nullable), `bank_ifsc_code` (string, nullable, format validated `^[A-Z]{4}0[A-Z0-9]{6}$` i.e. 4 letters + `0` + 6 alphanumeric; normalized to uppercase on validation), `bank_name` (string, nullable). All three optional; a client-side Alpine hint nudges completing all three when some are filled. Displayed masked on the show/pump-flow pages (e.g. account number "XXXX XXXX 4521", IFSC uppercased). See `StoreAccountRequest.php`, `resources/views/accounts/create.blade.php`, `resources/views/accounts/edit.blade.php`, `resources/views/accounts/show.blade.php`, and `resources/views/accounts/_pump-flow.blade.php`.

### AccountTransaction
- Fillable: account_id, branch_id, direction, amount, payment_mode, payment_plan, installment_no, installment_total, reference_type, reference_id, description, attachment_path, transaction_date, running_balance, created_by, updated_by
- Casts: amount (decimal:2), running_balance (decimal:2), transaction_date (date)
- SoftDeletes
- Relationships: belongsTo Account, belongsTo Branch, belongsTo User (creator), belongsTo User (editor)

### TransportLog
- Fillable: date, vehicle_no, company, transport_name, logsheet_no, destination, km, weight, to_bb_sale, paid_sale, to_pay, total_sale, freight, loading, unloading, dd, tempu_expense, commission, total_expense, profit, diesel_advance, cash_advance, total_advance, payment, fuel_station_name, fuel_station_balance, balance_vehicle_payment, clearing_date, detail, remarks, mileage, dtg_office_expense, created_by, updated_by, vehicle_id, company_id, carrier_id, branch_id, fuel_station_id, created_at, updated_at
- Casts: date, clearing_date (date); all decimals (decimal:2); deleted_at (datetime)
- SoftDeletes
- Relationships: belongsTo User (creator), belongsTo User (editor), belongsTo Vehicle, belongsTo Company (company_id), belongsTo Company (carrier_id), belongsTo Branch, belongsTo FuelStation
- Methods: profitColorClass(), statusBadge(), statusBadgeVariant(), computeTotals()

### Company
- Fillable: name, slug, gstin, contact_info, is_active
- Casts: is_active (boolean)
- Relationships: hasMany Vehicle, hasMany TransportLog (company_id), hasMany TransportLog (carrier_id)
- Scopes: scopeActive()

### Branch
- Fillable: name, code, address
- Relationships: hasMany User, hasMany Vehicle, hasMany Driver, hasMany FuelStation, hasMany TransportLog, hasMany StationCredit, hasMany StationDebit

### Vehicle
- Fillable: vehicle_no, owner_name, type, capacity_kg, mileage_baseline, is_active, branch_id
- Casts: is_active (boolean), capacity_kg (decimal:2), mileage_baseline (decimal:2)
- Relationships: belongsTo Branch, hasMany TransportLog, hasMany Driver
- Scopes: scopeActive()

### Driver
- Fillable: name, license_no, phone, vehicle_id, is_active, branch_id
- Casts: is_active (boolean)
- Relationships: belongsTo Vehicle, belongsTo Branch
- Scopes: scopeActive()

### FuelStation
- Fillable: name, slug, contact_info, address, is_active, branch_id
- Casts: is_active (boolean)
- Relationships: belongsTo Branch, hasMany StationBalance, hasMany StationCredit, hasMany StationDebit, hasMany TransportLog
- Scopes: scopeActive()

### ExpenseCategory
- Fillable: name, slug, is_active
- Casts: is_active (boolean)
- Scopes: scopeActive()

### ActivityLog
- Fillable: user_id, role, action, table_name, subject_type, record_id, record_summary, description, ip_address, user_agent, success, method, url, old_values, new_values, created_at, changes, device_info, session_id
- Casts: created_at (datetime); changes, old_values, new_values (array); success (boolean)
- No timestamps
- Relationships: belongsTo User

### StationDebit
- Fillable: fuel_station_id, branch_id, amount, reference_type, reference_id, notes, created_by, updated_by, date
- Casts: amount (decimal:2), date (date)
- SoftDeletes
- Relationships: belongsTo FuelStation, belongsTo Branch, belongsTo User (creator), belongsTo User (editor)

### StationCredit
- Fillable: fuel_station_id, branch_id, amount, reference_type, reference_id, notes, created_by, updated_by, date
- Casts: amount (decimal:2), date (date)
- SoftDeletes
- Relationships: belongsTo FuelStation, belongsTo Branch, belongsTo User (creator), belongsTo User (editor)

### StationBalance
- Fillable: fuel_station_id, total_amount
- Casts: total_amount (decimal:2)
- Relationships: belongsTo FuelStation

---

## 7. Dynamic UI Rendering (Blade + JS)

### Server-Side Data Flow
1. Request → Middleware (auth → active → no.cache → role)
2. Policy authorization (authorizeResource / explicit gates)
3. Eloquent query with eager loading
4. Pagination with query string preservation
5. View rendered with compact/array data
6. Blade extends `layouts.app`; content via `@yield('content')`

### Frontend Interactivity
- **Theme**: Alpine store toggles `dark` class on `<html>`, persists to localStorage
- **Layout**: Alpine data manages sidebar (open/icon-only), responsive at 768px, Escape closes mobile sidebar
- **Transport Form**: Alpine `transportForm` recalculates total_sale, total_expense, profit, total_advance, balance_vehicle_payment on every input
- **Toast**: Server emits `<meta name="flash-message">`; JS reads on DOMContentLoaded and renders dismissible toast
- **Export Dropdown**: Alpine toggle with click-away close
- **Modals**: Alpine event bus (`$dispatch('open-modal', 'id')`)
- **Confirmations**: Inline `onsubmit="return confirm(...)"` or Alpine `@submit.prevent`

### Data Flow
```
User → Route → Middleware → Controller → Policy → Eloquent → Blade (layout + components) → Vite-built CSS/JS → Browser
```

---

## 8. Full Feature List

### Authentication & Authorization
- Login (email/password, throttled 5/min)
- Session-based auth (database driver, encrypted, HTTP-only, SameSite=strict)
- Password reset (Laravel token broker, 60min expiry)
- Logout (session invalidate + CSRF regenerate)
- Roles: super_admin, admin
- Inactive user auto-logout (EnsureUserIsActive middleware)
- Super Admin can deactivate/activate/delete users (not other Super Admins or self for deactivate/delete)
- Activity logging for login, logout, failed login

### Transport Log Management
- Create/Edit/View/Delete transport logs (SoftDeletes)
- Computed fields enforced server-side: total_sale, total_expense, profit, total_advance, balance_vehicle_payment
- Live recalculation in UI via Alpine.js
- Search by vehicle_no / company / transport_name
- Filter by company, status (profit/loss/breakeven), created_date, clearing_date
- Sort by date, vehicle_no, company, total_sale, total_expense, profit
- Pagination (15 per page) with query string preservation
- Status badges (Cleared/Pending, Profit/Loss/Breakeven)
- Detail view with breakdown cards (Total Sale, Total Expense, Profit, Total Advance, Balance Vehicle Payment)

### Excel Export
- Single log export
- Monthly export (by month/year)
- Yearly export (by year)
- Custom range export (respects active filters)
- Formulas in computed columns (Total Sale, Total Expense, Profit, Total Advance, Balance Vehicle Payment)
- Color-coded headers and formula cells
- Header comments explaining formulas

### User Management (Super Admin only)
- Create/Edit/Delete users
- Reset user password (with current_password confirmation)
- Activate/Deactivate users
- Role assignment (admin/super_admin)

### Activity Logs (Super Admin only)
- Index with filters: action, user, role, module, description, record_id, date_from, date_to, success
- Show detail with changes diff (old → new)
- Tracks: login, logout, failed_login, CRUD operations, exports, password changes, role changes, status changes, profile updates

### Dashboard
- 9 KPI cards: Total Entries, Today's Entries, Today's Profit, Monthly Profit, Pending Vehicle Payments, Pending Fuel Balance, Pending Fuel Settlements, Total Advances, Total Expenses
- Pending Fuel Settlements shows count of unpaid/partial logs + combined remaining due
- Recent Transport Logs table (last 8)
- Recent Admin Actions feed (Super Admin only)
- Full Activity feed (Super Admin only)
- Cached for 1 minute globally; invalidated on transport log and account transaction changes

### Settings / Profile
- Update name (all active users)
- Super Admin can also update email and password
- Activity logging for profile updates, password changes

### Fuel Station Ledger (Implicit)
- StationDebit auto-created when transport log has diesel_advance > 0 and fuel_station_id
- StationDebit soft-deleted when diesel_advance becomes 0 or fuel_station_id removed
- StationDebit reversed on transport log delete
- Activity logging for all station debit operations

### Accounts Ledger (Super Admin only)
- 4 account types: fuel_station, motor_parts_shop, staff, company_expense
- Full CRUD for accounts with opening_balance and current_balance tracking
- Account transactions with running balance recalculation on create/edit/delete
- Transaction types: full, emi (with installments), partial
- Payment modes: cash, bank_transfer, upi, cheque, other
- Filter transactions by date range, direction, payment mode, payment plan, month/year
- Dashboard KPIs for fuel station dues, motor parts dues, monthly staff salary, monthly company expenses
- `lockForUpdate()` on Account row during all balance-affecting operations to prevent race conditions
- Backdated `transaction_date` insertions correctly recalculate all subsequent `running_balance` values
- `php artisan accounts:verify-integrity` command scans for orphaned fuel-station accounts, missing accounts, running-balance drift, and fuel-settlement drift
- Fuel station accounts use a simplified step-based flow (`accounts?type=fuel_station&selected={id}`) as the single canonical view; direct hits to `accounts/{id}` for fuel_station type redirect to that flow
- Edit and Delete actions are available inline on account detail views for all 4 types (fuel station pump header + account cards for other types)
- Staff accounts have mandatory identity fields: `aadhar_no` (required, exactly 12 digits, globally unique) and conditional `driving_license_no` (required when the staff member is marked as a driver via `is_driver` checkbox or when `linked_driver_id` is set). Aadhar is displayed masked on the show page (e.g. "XXXX XXXX 9012"). See `resources/views/accounts/create.blade.php`, `resources/views/accounts/edit.blade.php`, and `app/Http/Requests/Account/StoreAccountRequest.php`.
- Bank detail fields (`bank_account_no`, `bank_ifsc_code`, `bank_name`) added for `fuel_station` and `staff` account types. All three are optional; `bank_ifsc_code` is validated against `^[A-Z]{4}0[A-Z0-9]{6}$` (case-insensitive, normalized to uppercase on validation). A client-side Alpine hint nudges users to complete all three when some are filled (not hard validation). Account number is masked on display (e.g. "XXXX XXXX 4521") on the staff show page and the fuel-station pump-flow detail view. Migration: `2026_09_11_000000_add_bank_detail_fields_to_accounts_table`.

#### Custom Field Options (Super Admin only)
- A `custom_field_options` table (`2026_09_11_000001_create_custom_field_options_table.php`) stores extensible dropdown options for descriptive fields such as `payment_mode` and `payment_plan`.
- Structural enums (`Account.type`, `AccountTransaction.direction`) remain hardcoded in their respective request validators and are NOT managed through this table to preserve ledger integrity.
- The `CustomFieldController` exposes full CRUD (`/settings/custom-fields`) restricted to `super_admin` via the `super_admin` middleware.
- `CustomFieldOptionSeeder` migrates the legacy hardcoded values (cash, bank_transfer, upi, cheque, other for payment_mode; full, emi, partial for payment_plan) into the table for backward compatibility.
- `StoreAccountTransactionRequest` validates `payment_mode` and `payment_plan` against the active `custom_field_options` entries instead of hardcoded lists.
- All transaction forms (`transaction-form-modal.blade.php`, `accounts/transactions/edit.blade.php`) and the filters bar populate their `<select>` elements from the `custom_field_options` table.
- Deactivating an option removes it from new transactions without corrupting historical records.
- Tests: `tests/Feature/CustomFieldOptionsTest.php` (11 tests covering CRUD, authorization, validation, and historical integrity).

### Fuel Station Picker (Transport Log Forms)
- The `fuel_station_name` field on `resources/views/logs/_form.blade.php` has been replaced with a proper searchable dropdown (combobox/typeahead).
- The `fuelStationPicker` Alpine component lives in `resources/js/app.js` and is registered via `Alpine.data('fuelStationPicker', (config) => ({ ... }))`.
- The picker is fed a JSON-serialized list of **active fuel stations** (id, name, branch, current_balance) rendered server-side from `TransportLogController::formViewData()`. No AJAX calls — everything is preloaded for instant filtering.
- Features:
  - Searchable by name OR branch, with ↑/↓/Enter/Esc keyboard navigation
  - Below the field, shows the selected station's **current live ledger balance** (e.g. "Current balance: ₹12,345.67"), color-coded green/red
  - If no station matches, shows a "+ Add 'X' as a new station →" link. With JS enabled it opens an in-page modal (`add-fuel-station-modal`) to create the fuel station inline; the new station is appended to the picker list and auto-selected. If JS is disabled, the link falls back to opening `accounts.create?type=fuel_station&name=...` in a new tab.
  - Click-outside to close, Clear button to reset
  - Selected station's name is also auto-filled into the legacy `fuel_station_name` hidden input for display/backward compatibility
  - Hidden `fuel_station_id` input is the canonical source of truth
- Server-side validation in `TransportLogRules` ensures `fuel_station_id` resolves to a real `fuel_stations` row (`exists:fuel_stations,id`); friendly error message instead of DB crash on bad id.
- Legacy logs that still have a `fuel_station_name` string but no `fuel_station_id` display gracefully on the show page (no crash); the picker is empty by default and the user can re-link by selecting a station.
- On the show page, when `fuel_station_id` is set, the fuel station name is rendered as a **link to its ledger** (`accounts?type=fuel_station&selected={id}`).
- Tests: `tests/Feature/FuelStationComboboxTest.php` and `tests/Feature/FuelStationDisplayRegressionTest.php`.

### Inline Entity Creation (Branches & Fuel Stations)
- Lightweight modal flow (reusing the `<x-ui.modal>` component) lets users create a `branches` or `fuel_stations` row without leaving the current page.
- Triggered from the accounts create/edit forms via a `+` button next to each `linked_fuel_station_id` and `branch_id` dropdown, and from the transport-log fuel-station picker combobox.
- Backed by `app/Http/Controllers/InlineEntityController.php` and routes (Super Admin + Admin, `auth` + `active`):
  - `POST /entities/branches` (`entities.branches.store`) — creates a branch (name, code, address). `code` is upper-cased and uniqueness-validated.
  - `POST /entities/fuel-stations` (`entities.fuel-stations.store`) — creates a fuel station (name, branch_id, contact_info, address, is_active). `slug` is auto-generated (unique suffix added if needed).
- Both endpoints are AJAX (expect `X-Requested-With` + `Accept: application/json`); validation failures return a 422 JSON error payload that the modal renders inline.
- The `inlineCreate` Alpine component (in `resources/js/app.js`) submits the modal form via `fetch`, adds the new option to the target `<select>`, auto-selects it, closes the modal, and dispatches a window `entity-created` event.
- The transport-log picker (`fuelStationPicker`) listens for `entity-created` so a station created from its modal is appended to the live station list and auto-selected (with a balance default of 0).
- Graceful fallback: the picker's "Add new station" link keeps its `accounts.create?type=fuel_station` `target="_blank"` href and only swaps to the in-page modal when JS is available (`@click.prevent="$dispatch('open-modal', 'add-fuel-station-modal')"`), so the new-tab form still works if JS fails.
- Shared modal markup lives in `resources/views/accounts/_inline-entity-modals.blade.php` (both `add-branch-modal` and `add-fuel-station-modal`). The fuel-station modal itself contains a "+ Add New Branch" trigger.
- Tests: `tests/Feature/InlineEntityCreationTest.php`.

### Traceability
- Each transport log gets a unique `trace_code` (format `TL-000001`) on creation
- Trace code displayed prominently on transport log show page with a Copy button
- `GET /trace/{trace_code}` lookup page accessible to any authenticated active user
- Trace page shows consolidated reconciliation: transport log financials, linked fuel station name + current balance, fuel_payment_status, full payment history
- Green "Consistent" / red "Discrepancy Found" badge based on real-time audit: does `fuel_paid_amount` equal sum of linked credit transactions, and does `current_balance` match computed running balance?
- "View full ledger →" link on transport log edit form's fuel settlement card navigates directly to the linked fuel station's account ledger

### Multi-Payment Fuel Settlement Tracking
- transport_logs table has cached fuel_paid_amount (decimal 14,2) and fuel_payment_status (nullable enum: unpaid/partial/paid/overpaid, or NULL when the log has no fuel component)
- FuelSettlementService::recalculateForLog() is the single source of truth for these cached fields. It is called from:
  1. AccountTransactionObserver (created/updated/deleted/restored of any linked credit transaction)
  2. TransportLogObserver::created/updated/restored (to handle edits to diesel_advance, fuel_station_id changes, and restore-after-soft-delete)
- Transport log show page shows "Payment History for This Log" panel with status badge, progress bar, and chronological transaction list
- "Record Payment" button on transport log show page opens transaction modal pre-linked to that log
- Transport log edit page shows compact read-only fuel settlement status and payment history
- Master-detail fuel station accounts page (`accounts?type=fuel_station`) with Alpine.js two-pane layout: pump picker list + live ledger fragment. Transactions can be initiated directly from the pump detail view via the "+ Add Transaction" button, which opens the same `transaction-form-modal` component used elsewhere. The modal pre-fills `account_id` to the selected pump, supports optional transport log linking via the existing `/accounts/transport-logs/search` type-ahead, and calls the exact same `AccountLedgerService::createTransaction()` path as all other entry points.
- Transport log search endpoint (GET /accounts/transport-logs/search) for linking payments to logs via type-ahead; returns `destination` and `driver_name` (from the linked vehicle's first driver) in addition to the existing fields
- Supports flexible multi-part settlements: pay nothing first, partial second, rest on third — each as separate account_transaction
- AccountTransaction mirroring: creates debit transactions in account_transactions table for diesel advances; recalculates running balances on changes
- Fuel Station Ledger section rendered on transport log show page via AccountLedgerService::getBalanceSummary() and getFilteredTransactions()

#### Finalized Edge-Case Policies (after `tests/Feature/FuelSettlementEdgeCasesTest.php` audit)

| # | Scenario | Behavior |
|---|----------|----------|
| 1 | Transport log with `diesel_advance = 0` and no `fuel_station_id` | `fuel_payment_status` is set to **NULL** (not `'unpaid'`). "Unpaid" implies money is owed; NULL means "this log has no fuel component". `fuel_paid_amount` is 0. |
| 2 | Admin edits `diesel_advance` upward after payments exist (e.g. 5000 paid in full, then advance raised to 8000) | The transport log observer detects the change, calls `recalculateSettlement()`. Status correctly reverts to **`partial`**, `fuel_paid_amount` unchanged, due = 3000. |
| 3 | Admin edits `diesel_advance` downward below what was already paid (e.g. paid 6000 of 5000, then reduce advance to 4000) | Status correctly shows **`overpaid`** by 2000. No errors, math is correct. |
| 4 | Transport log is **soft-deleted** while it has active linked payments | **Policy: keep the account_transactions in place.** They are historical ledger entries for the fuel station; deleting them would corrupt the pump's balance history. The transport log's settlement view naturally disappears with it. The station's `current_balance` is unchanged. See `test_soft_deleting_log_preserves_account_transactions_and_balances`. |
| 5 | Transport log is **restored** (un-soft-deleted) | The `restored` observer event triggers `recalculateSettlement()`, which recomputes `fuel_paid_amount` and `fuel_payment_status` from currently-existing (non-deleted) credit transactions. Any transactions created or deleted while the log was soft-deleted are picked up. |
| 6 | Two different logs accidentally linked to the same `(reference_type, reference_id)` | **Impossible by design.** The pair `(reference_type='App\\Models\\TransportLog', reference_id=<id>)` is treated as a strict unique logical key — each credit transaction can only ever be linked to one transport log. There is no share-collision possible. See `test_reference_pair_is_strict_per_log`. |
| 7 | Out-of-order deletes (e.g. delete the earliest payment when later ones still exist, producing a state that would imply negative balance) | Recalculation is always driven by `SUM(amount)` of currently-non-deleted credits. The status is correctly computed each time: `overpaid` if paid > advance, `paid` if equal, `partial` if paid > 0, `unpaid` if paid = 0. The fuel station account's `current_balance` is clamped at 0 (you can't owe a pump money). |
| 8 | Concurrent payment attempts on the same log from two browser tabs | The `AccountLedgerService::createTransaction()` method acquires a `lockForUpdate()` on the Account row inside `DB::transaction()`. Combined with the `recalculateSettlement()` call from `AccountTransactionObserver::created`, this serializes concurrent writes so the cached `fuel_paid_amount` and `current_balance` always reflect the actual committed state. Verified by `test_concurrent_payments_are_safely_serialized`. |
| 9 | Admin changes a log’s `fuel_station_id` from an old station to a new station with no existing payments, while the diesel advance remains the same | The transport-log observer reverses the old station’s mirrored diesel-advance debit by soft-deleting the old `AccountTransaction` on the old station’s linked fuel account, then creates the new mirrored debit on the new station’s linked fuel account for the same submitted `diesel_advance` value inside the same database transaction. The new station’s `current_balance` and cached fuel settlement are recalculated immediately. |
| 10 | Admin changes both `fuel_station_id` and `diesel_advance` in one edit, while old payments already exist on the log | The reassignor uses the currently submitted `diesel_advance` amount when creating the mirrored debit on the new station, and it reverses any existing payment credits on the old station’s account before resetting the log settlement fields to zero. The old station debit is removed from the old account and the new debit is created on the new account in the same transaction, preventing double-application or stale-amount drift. |
| 11 | Admin clears `fuel_station_id` entirely (sets it to `null`) | The old station’s mirrored diesel-advance debit is soft-deleted through the same `reverseTransaction()` ledger path, no new account debit is created for an empty fuel station, and the cached `fuel_paid_amount` is forced back to `0` while `fuel_payment_status` is reset to `NULL` because the log no longer carries a fuel component. |
| 12 | Admin reassigns a log with prior real payment credits recorded against the old station account | The safest policy is to treat those payments as having been recorded against the wrong station because the log’s station was wrong. They are reversed from the old station’s linked account via the same soft-delete ledger path, the reassignment is logged audibly with `ActivityLogger`, and the cached `fuel_paid_amount` / `fuel_payment_status` reset to zero so the user must re-record payments on the new fuel station if the settlement should continue. |

### Integration Fixes Applied
- **LogsheetController@clearLogsheet**: Fixed leading-zero matching in clearing input — invoice displays log sheet numbers with leading zeros (e.g. `0045350959`) but the DB stores them without (e.g. `45350959`). Added `ltrim($request->input('log_sheet_no'), '0')` normalization before validation and lookup, with empty-string fallback for all-zero inputs. Also added `$request->merge()` to ensure the `exists` validator checks against the normalized value.
- **resources/views/logsheets/show.blade.php**: Fixed `data_get()` lookup keys in raw consignment rows table — used original Excel column names (`Invoice No`, `Payer`, `Payer Name`, `Town`, `Volume`) but raw_data is stored with canonical keys (`invoice_no`, `payer`, `payer_name`, `town`, `volume`), causing all 6 data columns to display as blank. Changed to canonical keys.
- **LogsheetImportService@parseDate**: Fixed Excel serial date handling — PhpSpreadsheet's `Excel::toArray()` returns date cells as Excel serial integers (e.g. `46182` for 2026-06-09), not `DateTime` objects. The old `parseDate()` used `Carbon::createFromFormat('Y-m-d', $value)` on numeric values, which threw `Carbon\Exceptions\InvalidFormatException` ("The separation symbol could not be found"). Since the entire import runs inside `DB::transaction()`, this exception caused a full rollback and a 500 error on upload — the feature appeared completely broken. Fix: numeric values are now converted via `Carbon::createFromFormat('Y-m-d', '1899-12-30')->addDays($value)`. Also added handling for Excel "null date" strings (`00.00.0000`) which previously parsed as garbage dates.
- **TransportLogController**: Fixed status filter (`profit`/`loss`/`breakeven`) by casting `$request->string('status')` to native string before comparison (Stringable object !== string)
- **TransportLogObserver**: Split event handlers so `creating`/`updating` recompute totals, while `created`/`updated` sync StationDebit and mirror AccountTransaction with correct model ID; added `saved` handler to invalidate dashboard cache; added `creating` handler to auto-generate `trace_code`; `mirrorToAccountTransactions` now uses `lockForUpdate` on Account row and delegates to `recalculateRunningBalances()` for correct backdated-transaction handling
- **TransportLog model**: Added `created_at` and `updated_at` to `$fillable` to allow test backdating and observer preservation through `forceFill`; added `trace_code` to `$fillable`
- **ProductionFeatureTest**: Fixed filter combination test to zero out all expense fields so `profit > 0` matches the `status=profit` filter
- **StoreAccountRequest**: Fixed `prepareForValidation()` TypeError by passing `[$field => null]` array to `merge()` instead of two separate arguments
- **AccountController**: Added `transactions` eager load to prevent N+1 on type-index page; removed pointless `?? true` on `isEmpty()` bool in index view
- **AccountLedgerService**: Fixed balance formula inconsistency — `getBalanceSummary()` now recalculates running balance using the same `max(0, balance - amount)` clamping as `createTransaction()`, so summary matches `current_balance`; `createTransaction()` and `reverseTransaction()` now lock the Account row with `lockForUpdate()` and recalculate all running balances via `recalculateRunningBalances()`; removed stale `$previousBalance` read outside DB transaction
- **transaction-form-modal**: Added missing `branch_id` required select field; fixed infinite recursion in `@submit.prevent` by removing erroneous `$refs.form.requestSubmit()` call; added `enctype="multipart/form-data"` and inline validation error displays
- **transactions/edit**: Fixed broken direction toggle — replaced duplicate `paymentPlan` assignments with proper `direction` Alpine variable and `x-model` binding
- **Accounts views**: Added validation error summary blocks and inline `@error` displays to `create`, `edit`, `transactions/edit`, and `transaction-form-modal` so users see server-side validation failures instead of silent reloads
- **FuelSettlementService + migration**: Added `fuel_paid_amount` and `fuel_payment_status` cached columns to `transport_logs`; service recalculates from credit transactions linked via polymorphic reference
- **AccountTransactionObserver**: Auto-recalculates transport log fuel settlement whenever a linked credit transaction is created/updated/deleted/restored; invalidates dashboard cache keys after every recalculation
- **TransportLogController@show**: Replaced generic "Fuel Station Ledger" section with dedicated "Payment History for This Log" panel showing status badge, progress bar, and transaction history via FuelSettlementService
- **logs/_form.blade.php**: Added compact read-only fuel settlement widget on edit pages so admins see payment status without leaving the form; added "View full ledger →" link inside the fuel settlement card that navigates to the linked fuel station's account ledger when `fuel_station_id` is set
- **DashboardController**: Changed aggregate cache key from per-user to global (`dashboard.aggregates`) since aggregates are user-independent; added cache invalidation for all dashboard keys on transport log and account transaction changes
- **VerifyAccountsIntegrity command**: Added `php artisan accounts:verify-integrity` that scans for orphaned fuel-station account references, fuel stations with transport logs but no Account, running-balance drift across all transactions, and fuel-settlement drift between cached `fuel_paid_amount` and actual credit transaction sums
- **TraceController + trace show view**: Added `GET /trace/{trace_code}` route and `TraceController@show` that loads a transport log by trace code and renders a consolidated reconciliation view with Consistency/Discrepancy indicator
- **trace_code migration**: Added `trace_code` (varchar 20, nullable, unique) column to `transport_logs` table; auto-generated as `TL-` + zero-padded 6-digit ID on transport log creation
- **Users page bug fix**: Removed `.e2e/seed.php` dev artifact that was creating an extra `E2E Admin` user outside of `DatabaseSeeder`, causing `/users` to show more than the expected 3 login-capable users; reseeding now produces exactly 1 super admin + 2 admins
- **Fuel station empty state fix**: Replaced confusing near-blank table state in `accounts/_pump-flow.blade.php` with a friendly empty-state card (icon + message + "Record Transaction" button) styled consistently with other empty states in the app; moved transaction modals in `accounts/type-index.blade.php` outside the `x-show="!selectedId"` container so they remain available when a pump is selected
- **AccountController show redirect for fuel_station**: `AccountController@show` now redirects `fuel_station` type accounts to the step-based pump flow (`accounts?type=fuel_station&selected={id}`) instead of rendering the generic full ledger page, making the step-based view the single source of truth for fuel station ledgers
- **Inline edit/delete on account detail views**: Added Edit (pencil icon) and Delete (trash icon) buttons to the fuel station pump detail header and to every non-fuel-station account card in `accounts/type-index.blade.php`, linking to the existing `accounts.edit` and `accounts.destroy` routes with standard confirm dialogs
- **AccountFactory default type**: Changed default `type` from random element to `motor_parts_shop` to prevent flaky test failures caused by random `fuel_station` accounts hitting the new redirect

---

## 8. Logsheet Import & Clearing

### Schema
The project now includes a dedicated logsheet-ingestion pipeline that mirrors the existing Excel-first import workflow without mutating the canonical `transport_logs` table. The formal storage model is split into four physical objects:

- `logsheet_imports` stores the upload metadata: `date_from`, `date_to`, `original_filename`, `uploaded_by`, `row_count`, `consolidated_count`, `duplicate_count`, `invalid_count`, and `status` (`pending`, `completed`, `invalid`). Each import is owned by the uploading `User`.
- `logsheet_raw_rows` persists every source row from the Excel file as a raw JSON payload, keyed by `import_id`, `row_number_in_file`, and `log_sheet_no`. It also carries `is_valid` and `validation_error` columns so a row can be saved as a failure record instead of being silently dropped.
- `logsheets` is the consolidated ledger of grouped logsheet summaries. It stores the canonical `log_sheet_no` and the rolled-up totals (`total_gross_wt`, `total_booked_amount`, `total_actual_amount`, `total_diff`) plus the date and other document metadata such as `vehicle_no`, `tprt_code`, `tprt_name`, `destination`, `sap_invoice_no`, `posting_date`, `bill_date`, and `vendor_inv_no`.
- `logsheet_clearings` captures the audit trail for a clearing action (`invoice_no_reference`, `notes`, `cleared_by`, `cleared_at`) and is linked back to a specific `Logsheet` row.

The concrete Eloquent objects are in the namespace `App\Models`: `LogsheetImport`, `LogsheetRawRow`, `Logsheet`, and `LogsheetClearing`. They are wired with `belongsTo()` and `hasMany()` relationships so the controller can present both import metadata and raw-row evidence on the detail page.

### Service
The import engine is `App\Services\LogsheetImportService`. Its `import(UploadedFile $file)` method performs the following lifecycle:

1. Starts a `DB::transaction()` and creates a `LogsheetImport` row with `status = pending` and the originating filename.
2. Reads the spreadsheet with `Maatwebsite\Excel\Facades\Excel::toArray()`, validates the required column headers (`Log Sheet No`, `Date`, `Tprt Code`, `Tprt Name`, `Container ID`, `Destination`, `SAPInvoiceNo`, `Posting Date`, `Bill Date`, `VendorInvNo`, `Actual Rate`, `Invoice No`, `Inv-Date`, `Payer`, `Payer Name`, `Town`, `Volume`, `Gross Wt`, `Booked Amount`, `Actual Amount`, `Diff`), and short-circuits with an `invalid` import status if the workbook lacks any of them.
3. Iterates row-by-row, normalizes the payload into the `raw_data` field, validates `log_sheet_no` presence, and persists invalid rows as `LogsheetRawRow` records with `is_valid = false` and a `validation_error` message.
4. Groups valid rows by `log_sheet_no` and computes the per-sheet totals by summing `Gross Wt`, `Booked Amount`, `Actual Amount`, and `Diff` values, then uses `Logsheet::updateOrCreate()` to write the consolidated row atomically.
5. Stores raw row audits on `logsheet_raw_rows` and updates the import metadata (`consolidated_count`, `invalid_count`, `duplicate_count`) before finishing. The `parseDate()` helper normalizes Excel date cells and numeric date-token values into a `Y-m-d` string.

The service is intentionally conservative: it records all import evidence in normalized raw rows and never drops a file without leaving a trace in the import metadata and raw-row model.

### Routes
The logsheet route group is intentionally scoped behind the existing Super Admin authority gate and no-cache middleware:

```php
Route::middleware(['auth', 'active', 'no.cache', 'role:super_admin'])->group(function () {
    Route::get('/logsheets', [LogsheetController::class, 'index'])->name('logsheets.index');
    Route::post('/logsheets', [LogsheetController::class, 'store'])->name('logsheets.store');
    Route::get('/logsheets/{logsheet}', [LogsheetController::class, 'show'])->name('logsheets.show');
    Route::post('/logsheets/clear', [LogsheetController::class, 'clearLogsheet'])->name('logsheets.clear');
});
```

The route surface supports the primary UX: upload an Excel workbook (`POST /logsheets`), list the consolidated logsheet summaries (`GET /logsheets`), inspect one grouped logsheet with its raw consignment rows (`GET /logsheets/{logsheet}`), and record a clearing event (`POST /logsheets/clear`).

### UI
The UI is deliberately simple and visible to the admin team:

- `resources/views/logsheets/index.blade.php` renders a searchable/filterable summary page with:
  - an upload form that accepts `.xlsx`, `.xls`, and `.csv` files,
  - date-from / date-to filters,
  - a compact clearing input box (`log_sheet_no`) that posts to `logsheets.clear`,
  - a table of consolidated logsheets with totals and status (`Pending` / `Cleared`) columns.
- `resources/views/logsheets/show.blade.php` renders the per-logsheet detail page; it presents the header values (`Date`, `Vehicle`, `Tprt Code`, `Tprt Name`, `Destination`, `SAP Invoice No`, `Posting`, `Bill`, `Vendor Inv No`) and a raw-row table showing the individual consignment payloads (`row_number_in_file`, `Invoice No`, `Inv-Date`, `Payer`, `Payer Name`, `Town`, `Volume`).
- `resources/views/components/layout/sidebar.blade.php` exposes a `Logsheets` navigation entry so the workflow remains reachable from the main app shell.

The controller behind the screens is `App\Http\Controllers\LogsheetController`, which coordinates `index()`, `store()`, `show()`, and `clearLogsheet()` against `LogsheetImportService` and the relevant Eloquent models.

---

## 9. Security / Config Concerns

### Critical
1. **Leaked session cookies in repo**: `_cookies.txt` contains live `laravel_session` and `XSRF-TOKEN` values for `127.0.0.1`. Anyone with access to this repo can hijack authenticated sessions.
2. **Leaked CSRF token in saved HTML**: `_login.html` and `_login_full.html` contain a live `csrf-token` meta tag value (`ef8RVhcFaVxT8h9WtYJE3ghv1DmXSQxqyvbIpUi3` and `85Moeu1BWKMrQK4AIlab5JTKTNsubNA5Zbb0Rn9U`). These are session-specific tokens.
3. **Debug scripts in web root**: `debug_*.php` and `find_comment*.php` are accessible via HTTP if the web server serves them. They bootstrap Laravel and could leak data or be used for further exploitation.
4. **check_config.php in web root**: Accessible via HTTP; exposes session domain, secure flag, APP_URL, session cookie name.

### High
5. **Default Super Admin credentials in seeder**: `admin@sls.com` / `password`. If seeders are run in any environment (including production), this creates a known backdoor account.
6. **APP_DEBUG=true in .env.example**: If copied to production without changing, detailed error pages with stack traces are exposed.
7. **SESSION_SECURE_COOKIE=false in .env.example**: Cookies sent over HTTP. Should be `true` in production.
8. **MAIL_FROM_ADDRESS=hello@example.com**: Generic placeholder; if not changed, outbound mail may be rejected or flagged.

### Medium
9. **No HTTPS enforcement**: No `\App\Http\Middleware\TrustProxies` customization visible; if behind a proxy, secure cookies may break.
10. **Activity logs store full user_agent + URL + IP**: Privacy consideration; ensure compliance with local regulations.
11. **Password reset throttle is 60 seconds** (`auth.php` passwords.throttle): Reasonable, but worth noting.
12. **composer.lock and package-lock.json missing**: Reproducibility risk; `npm install` / `composer install` may resolve to newer patch versions than tested.

### Low
13. **No CSP headers configured**: Not a direct vulnerability in this app, but missing defense-in-depth.
14. **Vite dev server bound to 127.0.0.1**: Correct for local dev, but ensure not accidentally exposed.

---

## 10. Testing Coverage

### Unit
- `ExampleTest.php`: Trivial assertion (true is true).

### Feature
- `ViewFoundationTest.php`: Login page public; protected pages redirect guests; Super Admin access all areas; Admin blocked from super_admin areas.
- `SettingsPermissionTest.php`: Admin cannot change email/password; Super Admin can; all can update name.
- `ProductionFeatureTest.php`: Comprehensive smoke tests covering auth, logout (session invalidation, CSRF regeneration, activity logging, 204 for JSON, no-cache headers), authorization policies, transport log CRUD (including computed field enforcement, negative profit), search/filter/sort/pagination (company, status, created_date, clearing_date, filter combinations, invalid dates, query string preservation), dashboard, users CRUD, settings, activity logs, error pages.
- `ObserverLedgerActivityTest.php`: Creating transport log with diesel_advance logs station_debit activity with correct changes payload; mirrored account transaction exists with correct reference_id.
- `ExcelExportTest.php`: Single/monthly/yearly/range exports; formula presence in all 5 computed columns; filter respect; authentication requirement.
- `DebugProfitFilterTest.php`: Profit/loss filter correctness.
- `ComputedBreakdownTest.php`: Show/edit page breakdown text presence; Excel formulas and header comments for all computed columns.
- `ActivityChangeTrackingTest.php`: Update records field-level changes in activity_logs.
- `AccountLedgerTest.php`: 80+ tests covering account CRUD for all 4 types, transaction types (full/EMI/partial), running balance recalculation after edit/delete, authorization (admin forbidden / guest redirected), filtering (date, direction, payment mode, payment plan, month/year), transport log payment history rendering, validation failures, soft-delete behavior, dashboard KPIs including pending fuel settlements, real HTTP POST persistence for all types, view existence checks for every accounts route, branch_id requirement for transactions, direction toggle update behavior, empty linked field null coercion, balance summary consistency after credit, fuel settlement recalculation (paid/partial/overpaid status transitions), transport log search endpoint authorization, end-to-end linked transport log settlement via HTTP, and fuel stations scope N+1 avoidance.
- `TraceCodeAndIntegrityTest.php`: 3 tests — trace_code uniqueness on creation, discrepancy detection on manually-corrupted fuel_paid_amount via DB tampering, and `accounts:verify-integrity` clean-run on a freshly seeded database.
- `TraceSearchTest.php`: 4 tests — transport logs index searches by exact and partial trace_code, exact trace_code in header search redirects to `/trace/{trace_code}`, partial trace_code falls through to normal results, and trace page displays creator/editor/vehicle/company/branch with links to show/edit.
- `FuelSettlementEdgeCasesTest.php`: 8 tests covering the finalized fuel settlement edge-case policies — no-fuel-component returns null status, editing advance up/down recalculates correctly, soft-delete preserves account transactions, restore re-syncs settlement, strict reference pair, out-of-order deletes remain sane, concurrent writes are safely serialized.
- `FuelStationComboboxTest.php`: 4 tests — valid `fuel_station_id` succeeds and links correctly, invalid `fuel_station_id` returns a validation error, null `fuel_station_id` succeeds, and create form renders the active stations list.
- `FuelStationDisplayRegressionTest.php`: 3 regression tests — legacy log with only `fuel_station_name` and no `fuel_station_id` displays gracefully on show/edit, log with no fuel component correctly omits the Payment History panel, and a log with a real `fuel_station_id` renders the station name as a link to its ledger.
- `PumpDetailTransactionTest.php`: 5 tests — adding transaction from pump detail with linked transport log updates settlement, adding without linked log doesn't touch transport logs, transport log search returns destination and driver, pump detail page shows add transaction button and modal, completing payment from pump detail marks log as paid.
- `StaffAccountIdentityTest.php`: 7 tests — staff account creation requires aadhar_no, rejects invalid aadhar formats (letters/wrong length), requires driving_license_no when is_driver is checked, accepts valid staff+driver submissions, show page displays masked aadhar and license, non-staff accounts don't require aadhar, and aadhar_no is globally unique.
- `UsersIndexTest.php`: 1 test — confirms `/users` returns exactly the seeded super_admin + admin count and contains none of the seeded driver names.
- `LogsheetImportServiceTest.php`: 1 test — verifies service groups rows by log_sheet_no, sums gross_wt/booked_amount/actual_amount/diff correctly, and preserves raw row count as consignment_count.
- `LogsheetClearingTest.php`: 4 tests — clearing a real logsheet updates status/sets cleared_at/cleared_by and creates a logsheet_clearings audit row; idempotent clearing (second clear shows "already cleared" and creates no duplicate record); nonexistent log_sheet_no returns clean validation error; missing log_sheet_no returns validation error.
- `LogsheetClearingLeadingZeroTest.php`: 2 tests — clearing with exact log_sheet_no works; clearing with leading zeros (invoice format `0045350959`) correctly matches DB-stored value (`45350959`).
- `LogsheetImportAggregationTest.php`: 2 tests — aggregation sums totals correctly across 3 log sheets (total_gross_wt, total_booked_amount, total_actual_amount, total_diff all match raw row sums; total_gross_wt for 45350959 = 5999.802 per spec); duplicate import preserves all consignment data; show blade view displays raw row data correctly using canonical keys (Invoice No, Payer, Payer Name, Town, Volume all visible per consignment row).

## 11. Developer Orientation Guide — Where to Find Everything

This section is a practical, task-oriented map for anyone new to the codebase. It tells you exactly which files to open for common changes, walks through one real request from button click to database to screen, explains the key domain terms, and lists the legacy landmines to watch out for.

### 1. If you want to change X, edit Y

| Task | File(s) to edit |
|------|------------------|
| Change how transport log profit / totals are calculated | `app/Services/TransportLogService.php` (`computeTotals`) |
| Change what shows on the dashboard | `app/Http/Controllers/DashboardController.php` + `resources/views/dashboard/dashboard.blade.php` |
| Add a new field to transport logs | Migration in `database/migrations/`, then `app/Models/TransportLog.php` (`$fillable`), then `resources/views/logs/_form.blade.php` |
| Change how fuel payments are tracked / status flips | `app/Services/FuelSettlementService.php` + `app/Observers/AccountTransactionObserver.php` + `app/Observers/TransportLogObserver.php` |
| Add a new account type | `app/Models/Account.php` type logic, `app/Http/Requests/Account/StoreAccountRequest.php`, `resources/views/accounts/type-index.blade.php` |
| Change who can see or do what | `app/Policies/` (per-model rules) + `app/Http/Middleware/RoleMiddleware.php` (role gates) |
| Change sidebar navigation | `resources/views/components/layout/sidebar.blade.php` |
| Change validation rules for any form | The relevant request class in `app/Http/Requests/` |
| Add a new Artisan command | `app/Console/Commands/` + register in `routes/console.php` |
| Change the transport log list filters / export | `app/Http/Controllers/TransportLogController.php` (index / export methods) |
| Change account transaction balance logic | `app/Services/AccountLedgerService.php` (create / reverse / recalculate) |
| Change the trace lookup page | `app/Http/Controllers/TraceController.php` + `resources/views/trace/show.blade.php` |
| Change how activity logging works | `app/Services/ActivityLogger.php` + `app/Http/Middleware/LogActivity.php` |
| Change the fuel station picker on transport log forms | `resources/views/logs/_form.blade.php` + `fuelStationPicker` Alpine component in `resources/js/app.js` + `app/Http/Controllers/TransportLogController.php` (`formViewData()`) |
| Change staff account identity fields (Aadhar / driving license) | `app/Http/Requests/Account/StoreAccountRequest.php` + `resources/views/accounts/create.blade.php` + `resources/views/accounts/edit.blade.php` + `resources/views/accounts/show.blade.php` |
| Add inline "Add New" branch / fuel station modals | `app/Http/Controllers/InlineEntityController.php` + `resources/views/accounts/_inline-entity-modals.blade.php` + routes in `routes/web.php` + `inlineCreate` Alpine data in `resources/js/app.js` + `fuelStationPicker.onEntityCreated` listener |
| Add bank detail fields to accounts | `2026_09_11_000000_add_bank_detail_fields_to_accounts_table` migration + `app/Http/Requests/Account/StoreAccountRequest.php` + `app/Models/Account.php` + `resources/views/accounts/{create,edit,show}.blade.php` + `_pump-flow.blade.php` |
| Add/edit dropdown options like payment mode | `/settings/custom-fields` page + `custom_field_options` table + `CustomFieldController` + `CustomFieldOptionSeeder` + `StoreAccountTransactionRequest` validation + `transaction-form-modal.blade.php` + `accounts/transactions/edit.blade.php` + `filters-bar.blade.php` |

### 2. Request lifecycle walkthrough: "What happens when a Super Admin records a fuel payment"

This is the concrete, end-to-end story of one common action — adding a partial payment to a fuel station account from the transport log edit page.

**Step 1 — The button click**
The admin is on the transport log edit page (`resources/views/logs/edit.blade.php`). They see the compact Fuel Settlement card showing "Unpaid" or "Partial". They click **"Record Payment"**, which opens a modal (`resources/views/components/accounts/transaction-form-modal.blade.php`). They fill in the amount, pick "Partial" payment plan, and submit.

**Step 2 — The route**
The modal form POSTs to `accounts.transactions.store`. In `routes/web.php` this is defined as:
`POST /accounts/{account}/transactions` → `AccountTransactionController@store`

**Step 3 — The controller**
`AccountTransactionController@store` validates the request using `StoreAccountTransactionRequest`, then calls `AccountLedgerService::createTransaction()`. It wraps the call in `DB::transaction()`.

**Step 4 — The service and the lock**
Inside `AccountLedgerService::createTransaction()`:
1. The Account row is locked with `lockForUpdate()` so no other request can change the balance at the same time.
2. A new `account_transactions` row is inserted with `direction = credit`, the entered amount, and a `reference_type` / `reference_id` pointing back to the transport log.
3. `recalculateRunningBalances()` walks every transaction for that account in chronological order and rewrites each row's `running_balance` so they all stay perfectly consistent — even if someone backdated a transaction.
4. The locked Account row's `current_balance` is updated to the latest running balance.

**Step 5 — The observer fires**
Because the new transaction has `reference_type = TransportLog::class`, the `AccountTransactionObserver` catches the `created` event. It calls `FuelSettlementService::recalculateForLog($log)`, which:
1. Sums all **non-deleted** credit transactions linked to this transport log.
2. Writes that sum into `transport_logs.fuel_paid_amount`.
3. Compares it to `diesel_advance` and sets `fuel_payment_status` to `paid`, `partial`, or `overpaid`.

**Step 6 — Cache invalidation**
Both the observer and the service clear the dashboard cache keys (`dashboard.aggregates`, etc.) so the "Pending Fuel Settlements" KPI updates on the next dashboard load.

**Step 7 — Back to the screen**
The controller redirects back to the account show page with a success message. When the admin returns to the transport log (or opens the trace lookup page), they now see:
- The payment listed in the "Payment History for This Log" panel.
- The status badge changed from "Unpaid" to "Partial" (or "Paid" if it covered the full advance).
- The progress bar reflects the new paid amount.
- The dashboard KPI will refresh within 1 minute (cache TTL).

If the admin had the trace page (`/trace/{trace_code}`) open in another tab, a manual refresh shows the updated status and a green "Consistent" badge, because `fuel_paid_amount` now matches the actual sum of credit transactions.

### 3. Glossary

| Term | Plain-English meaning |
|------|----------------------|
| **trace_code** | A unique, human-readable ID printed on every transport log (format `TL-000001`). It lets anyone with access look up that log's financials via `/trace/{trace_code}` without knowing the internal database ID. |
| **running_balance** | The balance of an account **after** each individual transaction, stored directly on the `account_transactions` row. It makes it fast to show "balance at this point in history" without recalculating from scratch. It is always kept in sync by the service layer. |
| **fuel_payment_status** | A cached flag on `transport_logs` (`unpaid` / `partial` / `paid` / `overpaid`, or NULL when the log has no fuel component) that summarizes how much of the diesel advance has been settled via credit transactions in the accounts system. |
| **Account** | The "ledger header" for a counter-party — e.g. a fuel pump, a motor parts shop, a driver (staff), or an expense category. It has an opening balance and a current balance. |
| **AccountTransaction** | A single financial event (debit or credit) against an Account. This is the table where all money movement is recorded. |
| **StationDebit / StationCredit** | Legacy tables that predate the full Accounts system. They still get created automatically when a transport log has a diesel advance (debit) or when a fuel payment is recorded (credit), but the **Accounts system is now the source of truth**. The legacy tables exist mainly for backward compatibility and historical reporting. |
| **lockForUpdate()** | A database-level row lock (pessimistic lock). When the service layer edits a balance, it locks the Account row so two admins cannot record payments at the exact same millisecond and corrupt the balance. |

### 4. Known simplifications / things to watch out for

1. **Legacy tables still in play**: `station_debits` and `station_credits` are still populated by the `TransportLogObserver` for backward compatibility, but the authoritative financial data now lives in `accounts` and `account_transactions`. Do not use the legacy tables for new calculations.

2. **Fuel station detail views were unified**: There used to be two separate pages for viewing a fuel pump's ledger — the generic `accounts/{id}` page and the simplified `accounts?type=fuel_station&selected={id}` flow. As of the latest fix, `accounts/{id}` **redirects** to the simplified flow for `fuel_station` type. The full page still exists for the other 3 account types.

3. **Dashboard cache is global, not per-user**: The dashboard aggregates are cached under a single key (`dashboard.aggregates`) because the numbers are the same for every user. They are invalidated automatically whenever a transport log or linked account transaction changes. The cache TTL is 1 minute as a safety net.

4. **trace_code is generated from the model ID**: The trace code is `TL-` + zero-padded 6-digit ID. It is generated in the `TransportLogObserver::created` handler using the actual inserted model ID, so it is always unique and never collides.

5. **AccountFactory default type changed to motor_parts_shop**: The factory no longer picks a random type by default. Tests that need a specific type should use the explicit state methods (`fuelStation()`, `staff()`, etc.) or pass `['type' => '...']`.

6. **Users page only shows login-capable users**: The `users` table is strictly for application authentication. Drivers live in the separate `drivers` table and must never appear on `/users`. A leftover `.e2e/seed.php` script that was creating an extra test user was removed to keep seeding clean.

7. **Fuel station empty state**: When a pump has zero transactions, the pump-flow fragment now shows a friendly empty-state card with a "Record Transaction" button instead of a blank table, so it is clear the pump is active but simply has no activity yet.
8. **Copy button UX**: Both the transport log show page and the trace lookup page now use an inline Alpine-powered copy interaction. Clicking the copy button shows a green checkmark + "Copied!" text for 1.5 seconds, replacing the previous `alert()` popup.
9. **Fuel-station reassignment warning/confirmation UX**: When a transport log’s `fuel_station_id` is changed, the edit form warning banner and required checkbox already surface a human-readable warning that the reassignment will reverse any prior payment credits recorded against the old station and require re-recording them on the new station if desired. This is a deliberate, money-moving safety confirmation and is therefore treated as a known simplification of the UI flow rather than an automatic silent reassignment.
10. **trace_code search**: The transport logs index search now matches against `trace_code` in addition to `vehicle_no`, `company`, and `transport_name`. The global header search detects exact trace_code matches and redirects straight to `/trace/{trace_code}`, while partial matches fall through to the normal transport logs search results.
10. **Trace page enrichment**: The `/trace/{trace_code}` page now displays creator/editor names, vehicle, company, carrier, branch, and direct links to the transport log's show and edit pages, making it the definitive "everything related to this log" view.
11. **Staff identity fields**: Staff accounts (`type = staff`) now require `aadhar_no` (12 digits, globally unique) and conditionally require `driving_license_no` when the staff member is marked as a driver (`is_driver` checkbox or `linked_driver_id` is set). The Aadhar is displayed masked on the show page (e.g. "XXXX XXXX 9012"). These fields are stored as raw columns on the `accounts` table (not in `metadata`), with a unique index on `aadhar_no`.
12. **Fuel station picker on transport log forms**: The free-text `fuel_station_name` input has been replaced with an Alpine-powered searchable combobox (`fuelStationPicker` in `resources/js/app.js`) that lists active fuel stations with live balance previews. Its "Add new station" link now opens an in-page modal (`add-fuel-station-modal`, backed by `InlineEntityController`) for inline creation with new-tab fallback; the picker also listens for a global `entity-created` event so newly created stations appear and auto-select. Added parallel "+ Add New Branch/Fuel Station" modals on account create/edit forms and an `InlineEntityController` exposing `POST /entities/{branches,fuel-stations}`.
13. **Pump detail transaction flow**: Adding a transaction from a pump's detail view (`accounts?type=fuel_station&selected={id}`) uses the exact same `transaction-form-modal` component and `AccountLedgerService::createTransaction()` code path as all other entry points. The modal pre-fills `account_id` to the selected pump, optionally links to a transport log via the existing `/accounts/transport-logs/search` type-ahead (now returning `destination` and `driver_name`), and correctly triggers the observer chain to update linked transport log settlement status.
14. **Bank detail fields on accounts**: Added optional `bank_account_no`, `bank_ifsc_code`, and `bank_name` columns to the `accounts` table (migration `2026_09_11_000000_add_bank_detail_fields_to_accounts_table`) for `fuel_station` and `staff` account types. `bank_ifsc_code` is validated against `^[A-Z]{4}0[A-Z0-9]{6}$` (case-insensitive, normalized to uppercase) and all three are optional. A client-side Alpine hint nudges users to complete all three when only some are filled (not hard validation). Account numbers are masked on display (e.g. "XXXX XXXX 4521") on the staff show page and the fuel-station pump-flow detail view. Tests in `tests/Feature/AccountBankDetailsTest.php`.
15. **Custom field options for descriptive dropdowns**: A `custom_field_options` table (`2026_09_11_000001_create_custom_field_options_table.php`) stores extensible options for `payment_mode` and `payment_plan`. The `CustomFieldController` at `/settings/custom-fields` (restricted to `super_admin`) provides full CRUD for these options. `StoreAccountTransactionRequest` validates against the active options in the table instead of hardcoded lists. All transaction forms and the filters bar populate their `<select>` elements from the table. Deactivating an option removes it from new transactions without corrupting historical records. `CustomFieldOptionSeeder` migrates legacy hardcoded values for backward compatibility. Tests in `tests/Feature/CustomFieldOptionsTest.php`.

16. **creatable-select is load-bearing across the app**: The `<x-ui.creatable-select>` component is used on `/accounts/create`, `/accounts/edit`, `/transport-logs/create`, `/transport-logs/{id}/edit`, `/settings/custom-fields`, and potentially other pages. A single syntax error or prop-passing bug in this component was capable of silently breaking the majority of authenticated routes. Any future change to this component must be followed by a full manual smoke-test pass across every page listed above, not just automated tests. See Integration Fixes Applied item 16 for the historical root-cause of a prop HTML-encoding bug that broke dropdown options across the app.

16. **creatable-select component fix**: The `<x-ui.creatable-select>` component's Blade props `options` and `createFormFields` used `{{ }}` (HTML-encoding) instead of `:` (unescaped binding) in `accounts/edit.blade.php` and `logs/_form.blade.php`. This caused JSON strings passed via HTML attributes to have `"` encoded as `&quot;`, which broke `json_decode()` inside the component, resulting in empty `options` and `createFormFields` arrays. This silently prevented the "Add New" modal from rendering in the dropdown, leaving Fuel Station and Branch pickers without their inline creation flows. Fixed by changing `options="{{ ... }}"` → `:options="..."` and `create-form-fields="{{ ... }}"` → `:create-form-fields="..."`. This was the root cause of `/accounts/13/edit`, `/accounts?type=fuel_station&selected={id}`, `/transport-logs/create`, and `/transport-logs/{id}/edit` showing broken or hidden dropdowns — the component rendered but without its modal config, the dropdown options never populated. Also resolved cascading failures across all pages using this component.
