# Sample A (Corporate Theme)

This is a modern, corporate-style theme for Flatblog. It demonstrates the flexibility of the Flatblog front-controller architecture by providing a rich aesthetic suitable for business websites, without requiring a database.

## Features
- **Rich Aesthetics**: A sleek design featuring dark blue and slate gray tones with glassmorphism UI elements.
- **Dynamic Hero Section**: Automatically displays the image specified in `_header_image.md` at the top of the homepage.
- **Latest News Integration**: Automatically pulls recent blog posts into a card-based grid layout on the homepage.
- **Micro-Animations**: Smooth fade-in effects and hover interactions for a dynamic user experience.

## Data Dependencies
To enable all features of this theme, place the following hidden files (starting with `_`) in the `data/` directory.

### `_config.md`
Configures the theme name and the company name ("SiteName") displayed in the header.
```markdown
- Theme:: sample_A
- SiteName:: Flatblog Inc.
- Language:: en (or ja, etc.)
```

### `_header_image.md`
Specifies the large background image for the top page (hero section). Include a single image using Markdown image syntax.
```markdown
# Top Image
![Hero](attachments/your-hero-image.jpg)
```

### `_lang_*.md` (e.g., `_lang_en.md`)
Overrides section titles and other UI elements. The file corresponding to the `Language` specified in `_config.md` will be loaded.
```markdown
- header_latest_posts:: Latest Press Releases
- search_button:: Site Search
```
