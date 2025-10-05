# TallForge DataTable

> A modern, dependency-free Livewire DataTable for Laravel — forged for the TALL stack.

---

### Features
- Search, filters, reset and sortable
- Pagination or load-more mode
- Boolean toggles with confirmation
- Status badges
- Column selector and alignment
- Row actions (buttons or dropdowns)
- Bootstrap & Tailwind themes

---

## Installation

```bash
composer require tallforge/datatable
```

### Publish config & views:
```bash
php artisan vendor:publish --tag=datatable-config
php artisan vendor:publish --tag=datatable-views
```

### Usage
```php
<livewire:tallforge.datatable
    :model="\App\Models\User::class"
    theme="tailwind"
/>
```