<?php
// ==========================================
// Flatblog Theme: Sample A (Corporate)
// ==========================================
$siteName = $blog->getConfig('SiteName', 'Corporate Inc.');
$headerImage = $thumbs['_header_image'] ?? null;
?>
<!DOCTYPE html>
<html lang="<?= $lang['html_lang'] ?? 'en' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars($metaDesc, ENT_QUOTES, 'UTF-8') ?>">
    <title><?= $pageTitle ?></title>
    
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    
    <!-- Theme CSS -->
    <link rel="stylesheet" href="themes/<?= htmlspecialchars($themeName) ?>/style.css">
</head>
<body>
    <header class="corp-header">
        <div class="container header-container">
            <h1 class="logo"><a href="./"><?= htmlspecialchars($siteName) ?></a></h1>
            <nav class="global-nav">
                <a href="./">Home</a>
                <a href="#about">About</a>
                <a href="#news">News</a>
                <a href="?mode=tags">Topics</a>
            </nav>
        </div>
    </header>

    <main>
        <?php if ($blog->isHome()): ?>
            <!-- Hero Section -->
            <section class="hero-section" style="<?= $headerImage ? 'background-image: linear-gradient(rgba(15, 23, 42, 0.7), rgba(15, 23, 42, 0.9)), url(blog/' . htmlspecialchars($headerImage) . ')' : '' ?>">
                <div class="container hero-content">
                    <h2 class="hero-title">Shaping the Future with Data</h2>
                    <p class="hero-subtitle">Innovative solutions for a connected world.</p>
                    <a href="#about" class="btn btn-primary">Learn More</a>
                </div>
            </section>

            <div class="container layout-grid">
                <!-- About Section -->
                <section id="about" class="about-section fade-in">
                    <h3 class="section-title">About Us</h3>
                    <p>We are a leading technology company dedicated to building elegant, database-free architecture. We believe in the power of raw data, observation, and the complete elimination of Muda (waste). Our flat-file systems empower creators to own their content without the overhead of traditional RDBMS.</p>
                </section>

                <!-- Latest News Section -->
                <section id="news" class="news-section fade-in">
                    <h3 class="section-title"><?= htmlspecialchars($lang['header_latest_posts'] ?? 'Latest News') ?></h3>
                    <?php $posts = $blog->getPosts(); ?>
                    <?php if (empty($posts)): ?>
                        <p class="empty-state">No news available.</p>
                    <?php else: ?>
                        <div class="news-grid">
                            <?php foreach (array_slice($posts, 0, 6) as $post): ?>
                                <?php
                                    $thumb    = $thumbs[$post->slug] ?? null;
                                    $excerpt  = $excerpts[$post->slug] ?? null;
                                    $cardTags = $postTags[$post->slug] ?? [];
                                ?>
                                <article class="news-card">
                                    <a href="?post=<?= urlencode($post->slug) ?>" class="news-card__link">
                                        <div class="news-card__image">
                                            <?php if ($thumb): ?>
                                                <img src="blog/<?= htmlspecialchars($thumb) ?>" alt="" loading="lazy">
                                            <?php else: ?>
                                                <div class="image-placeholder"></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="news-card__body">
                                            <span class="news-card__date"><?= $post->date ?></span>
                                            <h4 class="news-card__title"><?= $post->title ?></h4>
                                            <?php if ($excerpt): ?>
                                                <p class="news-card__excerpt"><?= htmlspecialchars($excerpt) ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </a>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            </div>

        <!-- Post Detail Mode -->
        <?php elseif ($blog->isPost()): ?>
            <div class="container page-content">
                <?php $post = $blog->getCurrentPost(); ?>
                <?php if ($post): ?>
                    <nav class="breadcrumb">
                        <a href="./">← Back to Home</a>
                    </nav>
                    <article class="post-detail">
                        <header class="post-header">
                            <h2 class="post-title"><?= $post->title ?></h2>
                            <div class="post-meta">
                                <span class="post-date"><?= $post->date ?></span>
                            </div>
                        </header>
                        <div class="post-body">
                            <?= $post->htmlContent ?>
                        </div>
                    </article>
                <?php else: ?>
                    <h2>Not Found</h2>
                <?php endif; ?>
            </div>

        <!-- Other Modes (Search, Tags) -->
        <?php else: ?>
            <div class="container page-content">
                <nav class="breadcrumb">
                    <a href="./">← Back to Home</a>
                </nav>
                <h2>Archive</h2>
                <ul class="simple-list">
                    <?php foreach ($blog->getPosts() as $post): ?>
                        <li>
                            <span class="date"><?= $post->date ?></span>
                            <a href="?post=<?= urlencode($post->slug) ?>"><?= $post->title ?></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </main>

    <footer class="corp-footer">
        <div class="container">
            <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($siteName) ?>. Built with Flatblog.</p>
        </div>
    </footer>
    
    <script>
        // Simple Intersection Observer for fade-in animations
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        });
        document.querySelectorAll('.fade-in').forEach(el => observer.observe(el));
    </script>
</body>
</html>
