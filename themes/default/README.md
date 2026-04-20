# Default Theme

This is the default theme for Flatblog. It features a clean, minimalist design that focuses on pure readability, closely aligning with Flatblog's core philosophy of eliminating Muda (waste).

## Features
- **Minimalist Design**: A lightweight, distraction-free reading experience.
- **HTML-First**: Focuses on raw, semantic HTML output.
- **High Performance**: No unnecessary assets or complex CSS/JS interactions.

## Data Dependencies
To customize this theme, you can place the following hidden files (starting with `_`) in the `data/` directory.

### `_config.md`
Configures basic site settings.
```markdown
- Theme:: default
- Language:: en (or ja, etc.)
```

### `_lang_*.md` (e.g., `_lang_en.md`)
Overrides UI label texts. The file corresponding to the language code specified in `_config.md` will be loaded.
```markdown
- page_title_default:: My Simple Blog
- search_button:: Search
- header_latest_posts:: Latest Posts
```
