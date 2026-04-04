# Flatblog Theme Development Reference

Flatblog is designed with the **"HTML First"** philosophy. All theme customizations are performed directly in `index.php`. You do not need to learn complex template engines (like Twig or Blade). You simply write standard HTML and use the transparent PHP `$blog` object to inject secure, pre-processed data.

---

## 1. The `$blog` Object (FlatblogLoader)

At the top of `index.php`, the `$blog` object is instantiated. It serves as your sole, read-only data provider. All routing is resolved internally at construction time.

```php
$blog = new \Flatblog\Core\FlatblogLoader(__DIR__ . '/blog');
```

### 🧭 State & Routing Methods

Use these boolean methods inside `if / elseif` blocks to render different HTML layouts based on the current URL.

| Method | Returns `true` when... | URL parameter |
|---|---|---|
| `$blog->isHome()` | Visitor is on the default article list | *(none)* |
| `$blog->isPost()` | Visitor is viewing a specific article | `?post=slug` |
| `$blog->isSearch()` | Visitor is performing a text search | `?q=keyword` |
| `$blog->isTagSearch()` | Visitor is filtering by a tag | `?tag=name` |
| `$blog->isTagsList()` | Visitor is on the all-tags page | `?mode=tags` |

---

### 📦 Data Retrieval Methods

#### Posts & Content

- **`$blog->getPosts(): Post[]`**  
  Returns an array of `Post` objects sorted by last-modified date (newest first).  
  Automatically applies active search or tag filters from the current URL.

- **`$blog->getCurrentPost(): Post|null`**  
  Returns the single `Post` object for the current URL.  
  Returns `null` silently if the article does not exist — use this to render a custom 404 message.

- **`$blog->getResultCount(): int`**  
  Returns the count of posts currently in `getPosts()`.

#### Tags

- **`$blog->getTags(?int $limit = null, string $sort = 'count_desc'): array`**  
  Returns an associative array mapping tag names to their post counts.  
  Example output: `['php' => 5, 'docker' => 3]`
  - `$limit`: Maximum number of tags to return (`null` = all).
  - `$sort`: `'count_desc'` (by frequency, default) or `'name_asc'` (alphabetical).

- **`$blog->getPostTags(): array`**  
  Returns an associative array mapping post slugs to an array of their tag names.  
  This is the inverse of `getTags()` — it lets you display tags *per article card*.  
  Example output: `['my-post' => ['php', 'docker'], 'another-post' => ['php']]`  
  Returns `[]` silently if the background index has not yet been built.

#### Thumbnails & Excerpts (from background index)

These methods read from the background-built cache index (`cache/tags_index.json`).  
On the very first request after a post is updated, the index may not yet be rebuilt — in that case, both methods return `[]` silently, and the UI falls back gracefully (CSS gradient placeholder for images, no excerpt shown).

- **`$blog->getThumbs(): array`**  
  Returns an associative array mapping post slugs to the path of the first local image found in the post's Markdown source (e.g., `attachments/photo.jpg`), or `null` if the post contains no local image.  
  Example: `['my-post' => 'attachments/photo.jpg', 'text-only' => null]`

- **`$blog->getExcerpts(): array`**  
  Returns an associative array mapping post slugs to a plain-text excerpt (first 200 characters, with all Markdown syntax stripped), or `null` if the post body is empty.  
  Example: `['my-post' => 'This is the beginning of the article...', 'empty-post' => null]`

#### Safe Output (XSS guards)

- **`$blog->getSafeQuery(): string`**  
  Returns the current search string (`?q=...`), pre-escaped with `htmlspecialchars()`. Safe to echo directly into HTML.

- **`$blog->getSafeTag(): string`**  
  Returns the current tag filter string (`?tag=...`), pre-escaped with `htmlspecialchars()`. Safe to echo directly into HTML.

---

## 2. The `Post` Object

Whether you are looping over `$blog->getPosts()` or rendering `$blog->getCurrentPost()`, you interact with a **read-only** `Post` data transfer object. All string properties are **pre-sanitized and XSS-proofed** at the data layer — not at render time.

| Property | Type | Description |
|---|---|---|
| `$post->slug` | `string` | Machine-friendly identifier (derived from the filename). |
| `$post->title` | `string` | Human-readable title (derived from slug, already escaped). |
| `$post->date` | `string` | Last-modified date of the `.md` file. Format: `YYYY-MM-DD`. |
| `$post->htmlContent` | `string` | Full article body parsed from Markdown to HTML, with image paths automatically rewritten to be web-accessible. **Do not escape this** — it is already trusted HTML. |

---

## 3. Implementation Example

The following is a minimal but complete example of how routing and data access should flow within `<body>`. It uses all major API methods — adapt it freely for your own design.

```php
<?php
// 1. Resolve site-wide labels and site title
$lang = require __DIR__ . '/lang/en.php';

// 2. Resolve page title before HTML output
if ($blog->isPost()) {
    $pageTitle = ($blog->getCurrentPost()?->title ?? $lang['page_title_post']) . ' - ' . $lang['page_title_default'];
} elseif ($blog->isSearch()) {
    $pageTitle = sprintf($lang['page_title_search'], $blog->getSafeQuery());
} elseif ($blog->isTagSearch()) {
    $pageTitle = sprintf($lang['page_title_tag'], $blog->getSafeTag());
} else {
    $pageTitle = $lang['page_title_default'];
}

// Pre-fetch index-based data once (used in card loops)
$thumbs   = $blog->getThumbs();
$excerpts = $blog->getExcerpts();
$postTags = $blog->getPostTags();
?>

<main>
    <!-- Tag Cloud -->
    <?php $tags = $blog->getTags(50); ?>
    <?php if ($tags): ?>
        <nav class="tag-cloud">
            <?php foreach ($tags as $name => $count): ?>
                <a href="?tag=<?= urlencode($name) ?>">#<?= htmlspecialchars($name) ?> (<?= $count ?>)</a>
            <?php endforeach; ?>
            <a href="?mode=tags">View all tags</a>
        </nav>
    <?php endif; ?>

    <!-- Home: Article Card Grid -->
    <?php if ($blog->isHome()): ?>
        <?php $posts = $blog->getPosts(); ?>
        <?php if (empty($posts)): ?>
            <p>No articles yet.</p>
        <?php else: ?>
            <ul class="post-list">
                <?php foreach ($posts as $post): ?>
                <?php
                    $thumb    = $thumbs[$post->slug] ?? null;
                    $excerpt  = $excerpts[$post->slug] ?? null;
                    $cardTags = $postTags[$post->slug] ?? [];
                ?>
                    <li class="post-card">
                        <a href="?post=<?= urlencode($post->slug) ?>" class="post-card__link">
                            <div class="post-card__image" data-has-image="<?= $thumb ? 'true' : 'false' ?>">
                                <?php if ($thumb): ?>
                                    <img src="blog/<?= htmlspecialchars($thumb) ?>" alt="" loading="lazy">
                                <?php endif; ?>
                            </div>
                            <div class="post-card__body">
                                <span class="date"><?= $post->date ?></span>
                                <h3><?= $post->title ?></h3>
                                <?php if ($excerpt): ?>
                                    <p><?= htmlspecialchars($excerpt) ?></p>
                                <?php endif; ?>
                                <?php foreach ($cardTags as $t): ?>
                                    <a href="?tag=<?= urlencode($t) ?>" class="tag-badge">#<?= htmlspecialchars($t) ?></a>
                                <?php endforeach; ?>
                            </div>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

    <!-- Individual Post -->
    <?php elseif ($blog->isPost()): ?>
        <?php $post = $blog->getCurrentPost(); ?>
        <?php if ($post): ?>
            <nav><a href="./"><?= $lang['link_back_to_list'] ?></a></nav>
            <article>
                <h2><?= $post->title ?></h2>
                <p><?= sprintf($lang['post_updated_at'], $post->date) ?></p>
                <?= $post->htmlContent /* pre-sanitized HTML — do not escape */ ?>
            </article>
        <?php else: ?>
            <h2><?= $lang['error_post_not_found'] ?></h2>
        <?php endif; ?>

    <!-- All Tags Page -->
    <?php elseif ($blog->isTagsList()): ?>
        <nav><a href="./"><?= $lang['link_back_to_list'] ?></a></nav>
        <h2><?= $lang['tags_list_header'] ?></h2>
        <?php foreach ($blog->getTags(1000) as $name => $count): ?>
            <a href="?tag=<?= urlencode($name) ?>">#<?= htmlspecialchars($name) ?> (<?= $count ?>)</a>
        <?php endforeach; ?>

    <?php endif; ?>
</main>
```

---

## 4. Localization & Custom Labels

Flatblog separates site-wide text (labels, button texts, page title formats) into simple PHP files. This makes it easy to change your blog's language or override specific terminology.

### How to use labels
All labels are stored in the `$lang` array, which you load at the top of `index.php`:
```php
$lang = require __DIR__ . '/lang/en.php';
```

### Modifying site names
The site name "Flatblog" is defined in `lang/en.php` under the key `page_title_default`. Changing it there will update the `<title>` across all pages and the default header name.

### Adding a new language
To add support for a new language (e.g., Japanese):
1.  Create a new file: `lang/ja.php`.
2.  Copy the contents of `lang/en.php` into it and translate the strings.
3.  In `index.php`, change the require path:
    ```php
    $lang = require __DIR__ . '/lang/ja.php';
    ```


---

## 4. Advanced: Frontend Build Tools

Flatblog's `assets/` directory is a drop-in target for modern build tools.

- Point your Vite `outDir`, Webpack `output.path`, or Tailwind output to `assets/css/` and `assets/js/`.
- The PHP backend is entirely unaffected — it only references `assets/css/style.css` and `assets/js/script.js`.
- Built output will be served directly by the PHP frontend container with zero configuration.

👉 **[See the full architecture overview here](ARCHITECTURE.md)**
