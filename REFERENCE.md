# Flatblog Theme Development Reference

Flatblog is designed with the "HTML First" philosophy. All theme customizations are performed directly in a single `index.php` file. You do not need to learn complex custom template engines (like Twig or Blade). You simply write standard HTML and use the transparent PHP `$blog` object to inject secure, pre-processed data.

## 1. The `$blog` Object (FlatblogLoader)

At the top of `index.php`, the `$blog` object is instantiated. It serves as your sole, read-only data provider.

### 🧭 State & Routing Methods
Use these boolean methods inside `if / elseif` blocks to render different HTML layouts based on the current URL.

- `$blog->isHome()`: Returns `true` if the visitor is viewing the default article list.
- `$blog->isPost()`: Returns `true` if the visitor is viewing a specific article (`?post=...`).
- `$blog->isSearch()`: Returns `true` if the visitor is performing a text search (`?q=...`).
- `$blog->isTagSearch()`: Returns `true` if the visitor is filtering by a specific tag (`?tag=...`).

### 📦 Data Retrieval Methods
- `$blog->getPosts()`: Returns an array of `Post` objects. It automatically handles sorting (newest first) and respects the active search or tag filters.
- `$blog->getCurrentPost()`: Returns the single `Post` object corresponding to the current URL. Returns `null` if the article does not exist (allowing you to show a custom 404 message).
- `$blog->getAllTags()`: Returns an array mapping tag names to their post counts (e.g., `['news' => 3, 'tech' => 1]`), sorted by frequency. Useful for generating "Tag Clouds".
- `$blog->getResultCount()`: Returns the integer count of how many articles currently exist in the `$blog->getPosts()` array.
- `$blog->getSafeQuery()`: Returns the text search string (`?q=...`), pre-escaped and safe for HTML injection.
- `$blog->getSafeTag()`: Returns the tag string (`?tag=...`), pre-escaped and safe for HTML injection.

---

## 2. The `Post` Object

Whether you are looping over `$blog->getPosts()` or rendering `$blog->getCurrentPost()`, you will be interacting with a read-only `Post` data transfer object. All string properties are **pre-sanitized and XSS-proofed** at the backend layer.

- `$post->slug`: The machine-friendly identifier (derived from the original filename).
  *(Example Usage: `<a href="?post=<?= urlencode($post->slug) ?>">Link</a>`)*
- `$post->title`: The human-readable title (derived from the slug, pre-escaped).
  *(Example Usage: `<h2><?= $post->title ?></h2>`)*
- `$post->date`: The last modified date of the markdown file (Format: `YYYY-MM-DD`).
- `$post->htmlContent`: The complete Markdown article parsed into high-quality HTML, with image paths automatically properly routed.
  *(Example Usage: `<div class="content"><?= $post->htmlContent ?></div>`)*

---

## 3. Implementation Example

Here is a minimal, clean example of how your logic should flow within the `<body>`:

```php
<main>
    <?php if ($blog->isHome()): ?>
        <h1>Recent Notes</h1>
        <ul>
            <?php foreach ($blog->getPosts() as $post): ?>
                <li>
                    <time><?= $post->date ?></time>
                    <a href="?post=<?= urlencode($post->slug) ?>"><?= $post->title ?></a>
                </li>
            <?php endforeach; ?>
        </ul>

    <?php elseif ($blog->isPost()): ?>
        <?php $post = $blog->getCurrentPost(); ?>
        <?php if ($post): ?>
            <article>
                <h1><?= $post->title ?></h1>
                <?= $post->htmlContent ?>
            </article>
        <?php else: ?>
            <h1>404 - Note missing</h1>
        <?php endif; ?>

    <?php endif; ?>
</main>
```
