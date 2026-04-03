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
- `$blog->isTagsList()`: Returns `true` if the visitor is viewing all tags page (`?mode=tags`).

### 📦 Data Retrieval Methods
- `$blog->getPosts()`: Returns an array of `Post` objects. It automatically handles sorting (newest first) and respects the active search or tag filters.
- `$blog->getCurrentPost()`: Returns the single `Post` object corresponding to the current URL. Returns `null` if the article does not exist (allowing you to show a custom 404 message).
- `$blog->getTags(?int $limit = null, string $sort = 'count_desc')`: Returns an array mapping tag names to their post counts (e.g., `['news' => 3, 'tech' => 1]`).
    - `$limit`: Max number of tags to return (default: `null` for all).
    - `$sort`: Sorting logic (`'count_desc'` for frequency, `'name_asc'` for alphabetical).
- `$blog->getResultCount()`: Returns the integer count of how many articles currently exist in the `$blog->getPosts()` array.
- `$blog->getSafeQuery()`: Returns the text search string (`?q=...`), pre-escaped and safe for HTML injection.
- `$blog->getSafeTag()`: Returns the tag string (`?tag=...`), pre-escaped and safe for HTML injection.

---

## 2. The `Post` Object

Whether you are looping over `$blog->getPosts()` or rendering `$blog->getCurrentPost()`, you will be interacting with a **read-only** `Post` data transfer object. All string properties are **pre-sanitized and XSS-proofed** at the backend layer.

- `$post->slug`: The machine-friendly identifier (derived from the original filename).
- `$post->title`: The human-readable title (derived from the slug, pre-escaped).
- `$post->date`: The last modified date of the markdown file (Format: `YYYY-MM-DD`).
- `$post->htmlContent`: The complete Markdown article parsed into high-quality HTML, with image paths automatically properly routed.

---

## 3. Implementation Example

Here is a clean example of how your logic should flow within the `<body>`:

```php
<main>
    <!-- Tag Cloud (Sidebar/Header) -->
    <?php $tags = $blog->getTags(50); ?>
    <?php if ($tags): ?>
        <div class="tag-cloud">
            <?php foreach ($tags as $name => $count): ?>
                <a href="?tag=<?= urlencode($name) ?>">#<?= htmlspecialchars($name) ?> (<?= $count ?>)</a>
            <?php endforeach; ?>
            <a href="?mode=tags">View all tags</a>
        </div>
    <?php endif; ?>

    <!-- Display logic based on mode -->
    <?php if ($blog->isHome()): ?>
        <h1>Recent Posts</h1>
        <?php foreach ($blog->getPosts() as $post): ?>
            <section>
                <h2><a href="?post=<?= urlencode($post->slug) ?>"><?= $post->title ?></a></h2>
                <p><?= $post->date ?></p>
            </section>
        <?php endforeach; ?>

    <?php elseif ($blog->isTagsList()): ?>
        <h1>All Tags (Top 1000)</h1>
        <?php foreach ($blog->getTags(1000) as $name => $count): ?>
            <a href="?tag=<?= urlencode($name) ?>">#<?= $name ?> (<?= $count ?>)</a>
        <?php endforeach; ?>

    <?php elseif ($blog->isPost()): ?>
        <?php $post = $blog->getCurrentPost(); ?>
        <?php if ($post): ?>
            <article>
                <h1><?= $post->title ?></h1>
                <?= $post->htmlContent ?>
            </article>
        <?php else: ?>
            <h1>404 - Not found</h1>
        <?php endif; ?>

    <?php endif; ?>
</main>
```
