# Livewire DataTable

A pure **Laravel Livewire DataTable package** (no jQuery, no external JS).  
Supports:

- Search
- Filters
- Sorting
- Pagination
- Load More mode
- Bootstrap & Tailwind themes

---

## Installation

```bash
composer require iamilyaskazi/livewire-datatable
```

### Publish config & views:
```bash
php artisan vendor:publish --tag=datatable-config
php artisan vendor:publish --tag=datatable-views
```

### Usage
```php
<livewire:data-table
    :model="\App\Models\User::class"
    :columns="['id','name','email']"
    :filters="['status'=>['active','inactive']]"
    theme="tailwind"
    pagination-mode="load-more"
/>
```

