# Flatblog Architecture

This document describes the internal design decisions, data flow, and structural conventions of Flatblog for developers who want to understand or extend the system.

---

## 1. Directory Structure

```
flatblog/
├── index.php               # The single entry point & theme template
├── core/
│   ├── FlatblogLoader.php  # Data provider class (the only PHP API surface)
│   ├── Post.php            # Read-only Post DTO
│   ├── build_tags.php      # CLI-only background index builder
│   └── Parsedown.php       # Markdown-to-HTML parser (vendored)
├── assets/
│   ├── css/style.css       # Default theme stylesheet
│   └── js/script.js        # Frontend script entry point
├── cache/                  # Writable by PHP; generated files only
│   └── tags_index.json     # Auto-generated article index (see §3)
├── blog/                   # Mounted read-only from flatnotes data volume
│   ├── *.md                # Article files written by the flatnotes editor
│   └── attachments/        # Images and file attachments
├── docker-compose.yml
└── .env
```

**Key constraint:** The `blog/` directory is mounted as `:ro` (read-only) into the PHP container. The PHP process can **never** write to the article source files. All writable output from PHP goes exclusively to `cache/`.

---

## 2. The "HTML First" Data Flow

Flatblog deliberately rejects MVC frameworks and template engines. The data flow is a straight line:

```
HTTP Request
    │
    ▼
index.php  ──────────────────────────────────>  HTML Response
    │                                                 ▲
    │  instantiates                                   │
    ▼                                                 │
FlatblogLoader                                        │
    │  reads (via URL params)                         │
    ├── mode: home / post / search / tag / tags_list  │
    │                                                 │
    │  reads from                                     │
    ├── blog/*.md  (read-only filesystem)             │
    └── cache/tags_index.json  (pre-built index)  ────┘
                                              (data injected into HTML template)
```

`FlatblogLoader` is a **pure data provider**: it resolves routing, reads files, and returns sanitized PHP objects. It has no output side-effects. `index.php` consumes this data and produces HTML — nothing more.

---

## 3. Background Index Building

Processing all `.md` files on every request would be inefficient at scale. Flatblog uses a **lazy, asynchronous index build** strategy.

### Trigger Mechanism

On any call to `getTags()`, `FlatblogLoader` calls the private method `triggerTagBuildIfNeeded()`, which:

1. Finds the most recent `mtime` across all `blog/*.md` files.
2. Compares it against the `mtime` of `cache/tags_index.json`.
3. If any `.md` file is newer than the index, fires `build_tags.php` as a background process:

```php
exec("nohup php build_tags.php " . escapeshellarg($dataDir) . " > /dev/null 2>&1 &");
```

The `&` ensures the PHP request completes immediately — **the current visitor never waits** for the index to rebuild.

### What `build_tags.php` Produces

`build_tags.php` reads every `.md` file once and extracts four fields:

| Field | Type | Description |
|---|---|---|
| `counts` | `{tag: int}` | Tag frequency map, sorted descending |
| `map` | `{tag: [slug, ...]}` | Tag → article slug list |
| `thumbs` | `{slug: string\|null}` | First local image path per article, or `null` |
| `excerpts` | `{slug: string\|null}` | Plain-text excerpt (200 chars), or `null` |

The file is written **atomically** via a temp-file-then-rename pattern to prevent partial reads:

```php
$tmpFile = $indexPath . '.tmp.' . uniqid();
file_put_contents($tmpFile, json_encode($indexData, JSON_UNESCAPED_UNICODE));
rename($tmpFile, $indexPath);
```

### Cold-Start Behavior

On the very first request (before any index exists), `getThumbs()`, `getExcerpts()`, and `getPostTags()` all return `[]` silently. The UI falls back gracefully:
- Article cards display a CSS gradient placeholder instead of a thumbnail.
- No excerpt text is shown.
- No tag badges are shown on cards.

The background build completes within seconds. Subsequent requests will have full data.

---

## 4. Security Design

### Read-Only Data Isolation

The most consequential security decision in Flatblog's infrastructure is the Docker volume mount:

```yaml
# docker-compose.yml
volumes:
  - flatnotes_data:/app/data:ro   # PHP can READ articles, never WRITE
```

Even if a PHP vulnerability were exploited, the attacker cannot modify article source files because the filesystem itself denies writes at the OS level.

### XSS Prevention at Object Creation

XSS sanitization is applied **once, at the boundary** — inside `FlatblogLoader::createPostObject()`:

```php
return new Post(
    $slug,
    htmlspecialchars($title, ENT_QUOTES, 'UTF-8'),  // escaped at creation
    $date,
    $htmlContent  // Parsedown output — trusted HTML, not re-escaped
);
```

`$post->title` is always safe to echo with `<?= ?>`. `$post->htmlContent` is trusted HTML from Parsedown and must **not** be re-escaped.

### Path Traversal Prevention

When loading a specific post by slug, all directory-separator characters are stripped from user input before constructing the file path:

```php
$this->postSlug = str_replace(['/', '\\', "\0"], '', $this->postSlug);
```

### URL Parameters

`getSafeQuery()` and `getSafeTag()` apply `htmlspecialchars()` before returning, making them safe for direct HTML injection. They are the only public methods that expose raw user input.

---

## 5. Tag Extraction Logic

Tags are detected inside Markdown using the following regex in `build_tags.php`:

```
/(?:^|\s)#([^\s#]+)/u
```

This matches a `#` preceded by whitespace (or the start of a line), immediately followed by one or more non-whitespace, non-`#` characters. As a result:

- `#php`, ` #docker`, `check #this` → **match** (valid tags)
- `# Heading`, `## Section` → **no match** (space after `#` blocks extraction)

Tags are deduplicated per file before counting.

---

## 6. Extending the Theme

### Adding a New Index Field

To add a new piece of data to the article index (e.g., reading time):

1. **`core/build_tags.php`**: Compute the value inside the `foreach ($files as $filePath)` loop and collect it into a new `$readings` array.
2. Add `'readings' => $readings` to the `$indexData` array.
3. **`core/FlatblogLoader.php`**: Add a `getReadings(): array` method that reads `$data['readings'] ?? []` from the index JSON.
4. **`index.php`**: Call `$blog->getReadings()` and use the data in your card template.

No other files need to change. The system is additive.

### Replacing the Default Theme

Delete or replace the contents of `assets/css/style.css`. The PHP backend has no dependency on any specific CSS class name. Use any CSS methodology you prefer.

### Using a Frontend Build Tool

Set your build tool's output directory to `assets/`. For example, with Vite:

```js
// vite.config.js
export default {
  build: {
    outDir: '../assets',
    rollupOptions: {
      input: { main: './src/main.js' }
    }
  }
}
```

The PHP frontend container serves `assets/` as static files with no configuration required.
