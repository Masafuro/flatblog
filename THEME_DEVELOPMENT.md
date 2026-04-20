# Theme Development Guide

Flatblog's frontend architecture is designed to be as simple and decoupled as possible. Themes are pure HTML/PHP templates that receive pre-sanitized data from the backend.

## 1. Theme Structure

All themes must reside in the `themes/` directory. A theme consists of a folder containing two primary files:

```text
flatblog/
└── themes/
    └── your_theme_name/
        ├── layout.php      # The main HTML structure
        ├── style.css       # The stylesheet (optional but standard)
        └── README.md       # Documentation (Data Dependencies)
```

To activate your theme, users simply edit `_config.md` in Flatnotes:
```markdown
- Theme:: your_theme_name
```

## 2. Writing `layout.php`

`layout.php` is the single entry point for your theme. It is included by `index.php` after all data has been prepared.

### The `$blog` Object
You have access to the `$blog` object (`FlatblogLoader`), which is a read-only data provider. Use it to determine the current route and fetch posts:

```php
<?php if ($blog->isHome()): ?>
    <h2>Latest Posts</h2>
    <?php foreach ($blog->getPosts() as $post): ?>
        <article>
            <h3><a href="?post=<?= urlencode($post->slug) ?>"><?= $post->title ?></a></h3>
            <div class="content"><?= $post->htmlContent ?></div>
        </article>
    <?php endforeach; ?>
<?php elseif ($blog->isPost()): ?>
    <!-- Render individual post... -->
<?php endif; ?>
```

> [!NOTE]
> All strings provided by `$post` (like `$post->title` and `$post->htmlContent`) are **pre-sanitized** by the core. You do not need to (and should not) run `htmlspecialchars()` on them.

### Handling Language and Labels
Flatblog has no PHP language configuration files. Instead, your theme is responsible for providing **default text** for every label, while allowing users to override it via `_lang_*.md`.

You have access to the `$lang` array. **Always use the null coalescing operator (`??`)** to provide a fallback string:

```php
<!-- Correct -->
<button><?= htmlspecialchars($lang['search_button'] ?? 'Search') ?></button>

<!-- Incorrect (Will crash if the user hasn't defined 'search_button') -->
<button><?= htmlspecialchars($lang['search_button']) ?></button>
```

## 3. Data Dependencies (Hidden Files)

Flatblog has no backend settings UI. If your theme requires custom configuration (like a hero image, a specific color scheme, or a site name), you must declare **Data Dependencies**.

Users provide data to your theme by creating files starting with `_` in Flatnotes. These files are ignored by the blog indexer but parsed as metadata.

### Example: Requiring a Hero Image
If your theme needs a hero image, instruct users in your theme's `README.md` to create `_header_image.md`. You can then fetch it in `layout.php`:

```php
<?php
// Fetch thumbnails from the cache
$thumbs = $blog->getThumbs();
$heroImage = $thumbs['_header_image'] ?? 'assets/default-hero.jpg';
?>
<div class="hero" style="background-image: url('blog/<?= htmlspecialchars($heroImage) ?>')">
    ...
</div>
```

### Example: Custom Configurations
If your theme supports a custom layout mode (e.g., `Sidebar:: left`), users can set this in `_config.md`:

```markdown
- Theme:: your_theme_name
- Sidebar:: left
```

You can read this directly from `$blog`:
```php
$sidebar = $blog->getConfig('Sidebar', 'right'); // Default to 'right'
```

## 4. Assets

Your theme's `style.css` should be linked dynamically using the `$themeName` variable provided by `index.php`:

```html
<link rel="stylesheet" href="themes/<?= htmlspecialchars($themeName) ?>/style.css">
```

If you use build tools (Vite, Webpack, Tailwind), configure them to output to `themes/your_theme_name/` or the global `assets/` directory. The PHP backend serves these files transparently.
