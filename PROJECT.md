# Project: Ocean Lombok Tour & Transport (OLT) Code & Security Audit

## Architecture
- **Framework**: Laravel 11.x, Livewire 3.x, Filament 3.x Admin Panel.
- **Data Layer**: SQLite (in-memory for automated tests) / MySQL/MariaDB (production), Eloquent ORM.
- **Security Boundaries**: Role-Based Access Control (`User::ROLE_SUPERADMIN`), Livewire component lifecycle authorization (`canView()`, `mount()`), GDPR IP privacy safeguards (`masked_ip`).
- **Performance Architecture**: SARGable database queries, composite and sort indexes, query count bounding, eager loading relationships.

## Feature Inventory
| # | Feature | Description | Milestone | Source |
|---|---------|-------------|-----------|--------|
| 1 | Database Performance Indexing | Create Laravel migration adding index on `visitor_trackings.last_visited_at` and composite index on `(visit_date, visit_count)`. | M1 | survey |
| 2 | Widget Query Aggregation Optimization | Consolidate 13 queries into 2 queries in `VisitorStatsOverview`, removing 7-iteration sparkline loop and eliminating `whereDate()` index suppression. | M1 | survey |
| 3 | SARGable Query Seeking in Charts & Filters | Convert `whereDate()` to direct `where()` with indexed date strings across `MonthlyVisitorsChart` and `VisitorTrackingResource` filters. | M1 | survey |
| 4 | Eager Loading in Login History | Eager load `user` relation in `LoginHistoryResource` table query to eliminate N+1 queries. | M1 | survey |
| 5 | Timezone Configuration Alignment | Add `'timezone' => env('DB_TIMEZONE', '+08:00')` to MySQL/MariaDB connection blocks in `config/database.php` aligning with `Asia/Makassar`. | M1 | survey |
| 6 | Antislop & UI Label Hygiene | Clean up AI slop comments, standardize widget/chart headers and labels to consistent English, and adjust metric colors to semantic standards. | M1 | survey |
| 7 | Widget Authorization Enforcement | Implement `canView(): bool` and `mount(): void` in `VisitorStatsOverview` and `MonthlyVisitorsChart` rejecting non-superadmin (Admin, Staff, Guest) with HTTP 403 Forbidden. | M1 | survey |
| 8 | Visitor IP Privacy Protection | Fix `VisitorTrackingResource` table to display `masked_ip` via `formatStateUsing()` instead of dead `placeholder()`, ensuring raw IP is not exposed. | M1 | survey |
| 9 | Raw SQL Binding Verification | Confirm and maintain safe parameter bindings on all raw SQL statements with zero injection risk. | M1 | survey |
| 10| Widget Security Test Suite | Write automated tests verifying 403 Forbidden for Admin, Staff, and Guest direct Livewire mounting and requests on both widgets. | M1 | survey |
| 11| Widget Feature & Boundary Test Suite | Write automated tests covering calculations, empty datasets, 30-day cutoff, and `Asia/Makassar` timezone formatting. | M1 | survey |
| 12| Performance & IP Privacy Test Suite | Write automated tests verifying bounded query counts (`<= 4` for overview, `1` for chart), index usage, and masked IP rendering in table HTML. | M2 | survey |
| 13| Full Regression Suite Verification | Execute full test suite `php artisan test` ensuring 100% of existing and new tests pass cleanly with zero failures. | M3 | survey |

## Milestones
| # | Name | Scope | Dependencies | Status |
|---|------|-------|-------------|--------|
| M1 | Core Analytics, Performance & Security Remediation | Migration for indexes, query consolidation, SARGable queries, eager loading, database timezone config, antislop cleanup, widget authorization (403), IP privacy masking | none | DONE |
| M2 | Dedicated Automated Table Privacy & E2E Security Tests | Implement `tests/Feature/VisitorTrackingTablePrivacyTest.php` testing HTML cell masking and clipboard copying | M1 | DONE |
| M3 | Final E2E Regression & Coverage Hardening | Run 100% test suite, verify zero regressions, adversarial audit | M2 | DONE |

## Interface Contracts
### `VisitorStatsOverview` Widget Contract
- Class: `App\Filament\Resources\VisitorTrackingResource\Widgets\VisitorStatsOverview`
- Static Authorization: `public static function canView(): bool` -> returns `auth()->user()?->isSuperAdmin() ?? false`
- Lifecycle Authorization: `public function mount(): void` -> aborts with 403 if `!static::canView()`
- Queries: Executes at most 2 queries:
  1. All-time aggregation (`sum(visit_count)`, `count(distinct visitor_hash)`)
  2. 30-day grouped aggregation (`where('visit_date', '>=', $thirtyDaysAgo)->groupBy('visit_date')`)

### `MonthlyVisitorsChart` Widget Contract
- Class: `App\Filament\Resources\VisitorTrackingResource\Widgets\MonthlyVisitorsChart`
- Static Authorization: `public static function canView(): bool` -> returns `auth()->user()?->isSuperAdmin() ?? false`
- Lifecycle Authorization: `public function mount(): void` -> aborts with 403 if `!static::canView()`
- Query: SARGable `where('visit_date', '>=', $thirtyDaysAgo)` utilizing index scan

### `VisitorTrackingResource` Table Privacy Contract
- IP column: `formatStateUsing(fn (VisitorTracking $record): string => $record->masked_ip ?? 'Hidden')`
- Copy action: copies `masked_ip`
- Filter: SARGable `where('visit_date', '>=', ...)`

## Code Layout
- Exclusive File Ownership for M1:
  - `database/migrations/2026_10_31_000003_add_performance_indexes_to_visitor_trackings_table.php`
  - `app/Filament/Resources/VisitorTrackingResource/Widgets/VisitorStatsOverview.php`
  - `app/Filament/Resources/VisitorTrackingResource/Widgets/MonthlyVisitorsChart.php`
  - `app/Filament/Resources/VisitorTrackingResource.php`
  - `app/Filament/Resources/LoginHistoryResource.php`
  - `config/database.php`
- Exclusive File Ownership for M2:
  - `tests/Feature/VisitorTrackingTablePrivacyTest.php`
