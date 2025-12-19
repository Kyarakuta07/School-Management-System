# MOE CSS Utilities Framework

Lightweight utility-first CSS framework untuk Mediterranean of Egypt.

## Quick Start

```html
<link rel="stylesheet" href="/moe/assets/css/utilities.css">
```

## Design Tokens

```css
/* Colors */
var(--gold)      /* Primary gold */
var(--success)   /* Green */
var(--warning)   /* Orange */
var(--error)     /* Red */

/* Spacing */
var(--space-1) /* 4px */
var(--space-2) /* 8px */
var(--space-4) /* 16px */
var(--space-6) /* 32px */

/* Font Sizes */
var(--text-sm)  /* 14px */
var(--text-base)/* 16px */
var(--text-xl) /* 20px */
```

## Utility Classes

### Flexbox
```html
<div class="flex items-center justify-between gap-4">
  <span>Left</span>
  <span>Right</span>
</div>
```

### Grid
```html
<div class="grid grid-cols-3 gap-4">
  <div>1</div>
  <div>2</div>
  <div>3</div>
</div>
```

### Spacing
```html
<div class="p-4 m-2 mt-4 mb-6">
  Padding 16px, Margin 8px all, top 16px, bottom 32px
</div>
```

### Typography
```html
<p class="text-lg font-bold text-gold">Bold gold text</p>
<p class="text-sm text-muted uppercase tracking-wide">Muted label</p>
```

### Components
```html
<!-- Buttons -->
<button class="btn btn-primary">Primary</button>
<button class="btn btn-secondary">Secondary</button>
<button class="btn btn-ghost btn-sm">Small Ghost</button>

<!-- Cards -->
<div class="card">Standard card</div>
<div class="card-gold">Gold bordered card</div>

<!-- Badges -->
<span class="badge badge-gold">Gold</span>
<span class="badge badge-success">Success</span>

<!-- Input -->
<input class="input" placeholder="Enter text...">
```

### Animations
```html
<i class="fas fa-spinner animate-spin"></i>
<div class="animate-pulse">Loading...</div>
<div class="animate-bounce">Bounce!</div>
```

## Responsive

```html
<div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4">
  <!-- 1 col on mobile, 3 on tablet, 4 on desktop -->
</div>

<div class="hidden md:block">
  Only visible on tablet+
</div>
```

## Breakpoints

| Prefix | Min Width |
|--------|-----------|
| (none) | 0px |
| `sm:` | 641px |
| `md:` | 768px |
| `lg:` | 1024px |
