# Flatblog

A lightning-fast, ultra-secure, flat-file blog system.
It uses [flatnotes](https://github.com/dullage/flatnotes) as a headless Markdown editor and pure HTML/PHP for the frontend. No database required.

## Features

- **Zero Database**: Stores all posts as raw Markdown (`.md`) files.
- **Secure Admin Panel**: Write and edit posts using the built-in flatnotes editor. Any `.md` file saved there is instantly reflected on the public frontend.
- **Smart Tag System**: Automatically detects hashtags (e.g., `#tag`) inside posts and generates high-performance indices in the background via asynchronous lazy evaluation.
- **Card-Based Overview**: The article list displays a thumbnail (extracted from the first local image in each post) and a plain-text excerpt (first 200 characters). Posts without images fall back gracefully to a CSS gradient placeholder — no configuration required.
- **In-Card Tag Badges**: Each article card shows its associated tags as clickable badges, providing immediate navigation context without leaving the overview.
- **Ultra Fast**: Server-side rendered with no database queries and virtually zero overhead.
- **Dead-Simple Customization**: Your entire blog theme lives in `index.php`, `assets/css/style.css`, and `lang/en.php`. Theme colors and layout values are defined as CSS custom properties in `:root`, while site labels and titles are managed in a simple PHP array — override any value in a single block to reskin the entire site. No template engine to learn.

---

## 🚀 Getting Started

### 1. Clone the repository
```bash
git clone https://github.com/Masafuro/flatblog.git
cd flatblog
```

### 2. Configure environment variables
Edit the `.env` file to set your secure admin credentials:
```env
FLAT_USER=admin
FLAT_PASS=your_secure_password
FLAT_SECRET=your_random_secret_key
```

### 3. Start the system
```bash
docker compose up -d
```

---

## 🌐 Usage

After starting the containers:

- **Blog Frontend**: `http://localhost:8880` — what your visitors see.
- **Admin Editor (flatnotes)**: `http://localhost:8881` — log in with your `.env` credentials to write posts, attach images, and manage content.

### Tagging Guidelines

To keep your blog posts clean and prevent tags from being rendered as large headers, follow this standard tagging convention at the top of your Markdown files:

```markdown
` #tag1 #tag2 `

Your content starts here...
```

**Why use backticks with spaces?**
Standard Markdown engines (including Parsedown) often treat a `#tag` at the start of a line as a Level 1 Heading. By enclosing your tags in backticks with **leading and trailing spaces** ( `` ` #tags ` `` ), you prevent header rendering while ensuring Flatblog's processor extracts the tags accurately. 

> **Note:** The internal spaces are required to prevent the final backtick from being accidentally parsed as part of the last tag name.

---

## THANKS

- flatnotes: https://github.com/dullage/flatnotes
- Parsedown: https://github.com/erusev/parsedown
- grayscale-16: https://lospec.com/palette-list/grayscale-16

## 🎨 How to Customize

Flatblog's frontend is divided into clean, extensible layers:

1. **HTML Structure (`index.php`)**: The theme template. Standard HTML with PHP data injection. Modify the DOM freely.
2. **Styling (`assets/css/style.css`)**: The default lightweight CSS theme. All colors and layout values are defined as CSS custom properties in `:root`. To reskin the site, redefine these variables — no other file needs to change. Dark mode is supported automatically via `prefers-color-scheme`.
3. **Language & Labels (`lang/en.php`)**: All site-wide labels, button texts, and page title formats. Edit this file to change the site language or customize specific terminology.
4. **Scripts (`assets/js/script.js`)**: Entry point for custom frontend logic.

**⚡ For Advanced Developers (Vite, Webpack, Tailwind, etc.):**
Point your build tool's output directory to `assets/`. Flatblog's PHP backend has no dependency on specific class names and will serve your built assets transparently.

👉 **[PHP API Reference (FlatblogLoader Cheat Sheet)](REFERENCE.md)**  
👉 **[Architecture & Internal Design](ARCHITECTURE.md)**

---

## 🤖 AI Agent Architectural Review

> This section documents an architectural review written by the AI agent that actively
> participated in developing this codebase. Unlike a static README written by a human author,
> this review reflects direct, hands-on experience with the system's internals —
> examining every file, implementing features, and debugging edge cases.
> It is intended to give readers an honest technical assessment from an agent
> that has skin in the game.

- **Review Date**: 2026-04-04
- **Reviewing Agent**: Claude Sonnet 4.5 (Antigravity / Google DeepMind)
- **Session Scope**: Tag system investigation, thumbnail & excerpt indexing, card-based UI implementation, UX gap analysis and remediation, documentation overhaul.

---

### What This System Does Well

**The Data Loader Pattern is genuinely strong.**
`FlatblogLoader` achieves a clean separation that most PHP projects fail at: it is a pure data provider with no output side-effects. Routing, file I/O, sanitization, and object construction all happen inside the class. The `index.php` template receives only safe, pre-processed objects and has no access to raw user input. This is not accidental — it is a consistently enforced design discipline.

**The security model is honest.**
The `:ro` Docker volume mount on the `blog/` directory is the most consequential security decision in this project, and it is correct. The PHP process is mathematically prevented from corrupting article data at the filesystem level. Combined with XSS sanitization applied at object construction time (not at render time), the attack surface is genuinely small for a system of this complexity.

**The asynchronous index build is a practical trade-off well made.**
Generating tag indices, thumbnails, and excerpts across all Markdown files synchronously on every request would be prohibitive. The `mtime`-comparison trigger with `nohup ... &` background execution is simple, dependency-free, and effective. The atomic write pattern (`rename()` from a temp file) correctly prevents partial reads under concurrent access.

**The "Rule of Silence" is consistently applied.**
Every method that depends on the background index (`getThumbs()`, `getExcerpts()`, `getPostTags()`) returns an empty array silently when the index is unavailable, rather than throwing or logging. The UI handles this gracefully with CSS fallbacks. This is the correct behavior for a system with lazy evaluation semantics.

---

### Honest Limitations and Trade-offs

**The cold-start problem is real and by design.**
The first visitor to arrive after any article is updated will receive no thumbnails, no excerpts, and no tag badges on cards. The index rebuild happens in the background and completes within seconds, but that first request is degraded. For a personal or low-traffic blog this is acceptable. For higher-traffic deployments with frequent updates, it warrants awareness.

**`getPosts()` performs a full file scan on every request.**
There is no query optimization possible here — every page load that calls `getPosts()` reads the filesystem `glob()` and `filemtime()` for all `.md` files. For a blog with tens of articles this is negligible. For hundreds of articles the overhead accumulates. The design consciously prioritizes simplicity over scalability, which is appropriate for its stated purpose.

**`tags_index.json` is a growing monolith.**
The index currently holds four fields: `counts`, `map`, `thumbs`, and `excerpts`. Each new feature that requires pre-computed data will add another field. The index is read in full by every method that uses it, meaning even a call to `getThumbs()` deserializes the entire JSON including tags and excerpts. This is not a problem today, but it is an architectural seam worth watching. A splitting strategy (separate files per field, or a SQLite index) should be considered when article count grows beyond a few hundred.

**Tag extraction conflicts with Markdown headings only at the regex boundary.**
The tag regex `(?:^|\s)#([^\s#]+)` correctly excludes Markdown headings (`# Heading`) because a space follows the `#`. However, inline headings written without spaces (non-standard Markdown) could produce false tag matches. Authors using flatnotes should be aware that `#tags` must not appear at the start of a line if they intended it as a non-tag `#` character.

---

### Summary

Flatblog is a well-executed implementation of the UNIX "Do one thing well" philosophy applied to blogging infrastructure. Its strongest asset is not any individual feature but the **consistency of its constraints** — read-only data mounts, single-boundary sanitization, silent failure modes, and a template layer that cannot reach PHP internals. These constraints make the system predictable and auditable.

The limitations described above are inherent trade-offs of the chosen simplicity-first approach, not oversights. Any developer choosing Flatblog should do so with clear eyes about what they are optimizing for: **operational simplicity and content ownership, not horizontal scalability or query flexibility.**

For its intended use case — a self-hosted, writer-controlled blog with zero database dependency — this architecture is sound.
