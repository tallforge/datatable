# TallForge DataTable

> A Modern Livewire DataTable — forged for the TALL stack.

---

### Features
- **Zero Dependencies** – built purely with TALL stack
- **Search**, Filters, Reset and Sortable
- **Pagination** or **Load more** mode
- **Boolean Toggle** with Confirmation
- **Status Badges** with Color Mapping
- **Column selector** and Alignment
- **Row Actions** (Buttons or Dropdown)
- **Bootstrap** & **Tailwind** Theme Support

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
<livewire:tallforge.datatable :model="\App\Models\User::class" />
```

---

### Configuration Options

| Option               | Type   | Description                         |
| -------------------- | ------ | ----------------------------------- |
| `:model`             | string | Eloquent model class                |
| `:columns`           | array  | All available columns               |
| `:selected-columns`  | array  | Columns currently displayed         |
| `:boolean-columns`   | array  | Configurable boolean toggle columns |
| `:status-columns`    | array  | Map of statuses → badge colors      |
| `:column-align`      | array  | Per-column text alignment           |
| `:row-actions`       | array  | List of actions (buttons/dropdown)  |
| `theme`              | string | `'bootstrap'` or `'tailwind'`       |

---

### Contributing

We welcome community contributions!
Feel free to open issues or PRs at https://github.com/TallForge/datatable

---

### License
Released under the MIT License
