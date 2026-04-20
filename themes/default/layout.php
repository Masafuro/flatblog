<?php
// ==========================================
// Flatblog Theme Layout (Default)
// ==========================================
?>
<!DOCTYPE html>
<html lang="<?= $lang['html_lang'] ?? 'en' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars($metaDesc, ENT_QUOTES, 'UTF-8') ?>">
    <title><?= $pageTitle ?></title>
    <!-- 抽出された外部CSSの読み込み (テーマ対応) -->
    <link rel="stylesheet" href="themes/<?= htmlspecialchars($themeName) ?>/style.css">
</head>
<body>
    <header>
        <h1><a href="./"><?= $pageTitle ?></a></h1>
        <div class="search-box">
            <!-- 検索窓。getSafeQuery() により、XSSを注入されても自動で無毒化された文字列が戻る -->
            <form method="get" action="./">
                <input type="search" name="q" value="<?= $blog->getSafeQuery() ?>" placeholder="<?= htmlspecialchars($lang['search_placeholder'] ?? 'Search articles...') ?>">
                <button type="submit"><?= htmlspecialchars($lang['search_button'] ?? 'Search') ?></button>
            </form>
        </div>
    </header>

    <main>
    <!-- 1. 一覧（ホーム）モード -->
    <?php if ($blog->isHome()): ?>
        <?php $topTags = $blog->getTags(5); ?>
        <?php if ($topTags): ?>
        <div class="tag-cloud">
            <?php foreach ($topTags as $tName => $tCount): ?>
                <a href="?tag=<?= urlencode($tName) ?>">#<?= htmlspecialchars($tName) ?> (<?= $tCount ?>)</a>
            <?php endforeach; ?>
            <a href="?mode=tags" class="tag-list-link"><?= htmlspecialchars($lang['link_all_tags'] ?? 'View all tags') ?></a>
        </div>
        <?php endif; ?>

        <h2><?= htmlspecialchars($lang['header_latest_posts'] ?? 'Latest Posts') ?></h2>
        <?php $posts = $blog->getPosts(); ?>
        <?php if (empty($posts)): ?>
            <p class="empty-state"><?= htmlspecialchars($lang['empty_no_posts'] ?? 'No posts yet.') ?></p>
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
                            <h3 class="post-card__title"><?= $post->title ?></h3>
                            <?php if ($excerpt): ?>
                                <p class="excerpt"><?= htmlspecialchars($excerpt) ?></p>
                            <?php endif; ?>
                            <?php if ($cardTags): ?>
                                <div class="post-card__tags">
                                    <?php foreach ($cardTags as $t): ?>
                                        <a href="?tag=<?= urlencode($t) ?>" class="tag-badge">#<?= htmlspecialchars($t) ?></a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>

    <!-- 2. 検索結果モード -->
    <?php elseif ($blog->isSearch()): ?>
        <nav class="breadcrumb">
            <a href="./"><?= htmlspecialchars($lang['link_back_to_list'] ?? '← Back to post list') ?></a>
        </nav>
        <h2><?= htmlspecialchars(sprintf($lang['search_results_count'] ?? 'Search results for "%s" (%d items)', $blog->getSafeQuery(), $blog->getResultCount())) ?></h2>
        <?php if ($blog->getResultCount() > 0): ?>
            <ul class="post-list">
                <?php foreach ($blog->getPosts() as $post): ?>
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
                                <h3 class="post-card__title"><?= $post->title ?></h3>
                                <?php if ($excerpt): ?>
                                    <p class="excerpt"><?= htmlspecialchars($excerpt) ?></p>
                                <?php endif; ?>
                                <?php if ($cardTags): ?>
                                    <div class="post-card__tags">
                                        <?php foreach ($cardTags as $t): ?>
                                            <a href="?tag=<?= urlencode($t) ?>" class="tag-badge">#<?= htmlspecialchars($t) ?></a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p><?= htmlspecialchars($lang['search_no_results'] ?? 'No matching posts found.') ?></p>
        <?php endif; ?>

    <!-- 3. タグ検索モード -->
    <?php elseif ($blog->isTagSearch()): ?>
        <nav class="breadcrumb">
            <a href="./"><?= htmlspecialchars($lang['link_back_to_list'] ?? '← Back to post list') ?></a>
        </nav>
        <h2><?= htmlspecialchars(sprintf($lang['tag_posts_count'] ?? 'Post List for #%s (%d items)', $blog->getSafeTag(), $blog->getResultCount())) ?></h2>
        <?php if ($blog->getResultCount() > 0): ?>
            <ul class="post-list">
                <?php foreach ($blog->getPosts() as $post): ?>
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
                                <h3 class="post-card__title"><?= $post->title ?></h3>
                                <?php if ($excerpt): ?>
                                    <p class="excerpt"><?= htmlspecialchars($excerpt) ?></p>
                                <?php endif; ?>
                                <?php if ($cardTags): ?>
                                    <div class="post-card__tags">
                                        <?php foreach ($cardTags as $t): ?>
                                            <a href="?tag=<?= urlencode($t) ?>" class="tag-badge">#<?= htmlspecialchars($t) ?></a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p><?= htmlspecialchars($lang['tag_no_results'] ?? 'No posts found for this tag.') ?></p>
        <?php endif; ?>

    <!-- 4. 個別記事モード -->
    <?php elseif ($blog->isPost()): ?>
        <?php $post = $blog->getCurrentPost(); ?>
        <?php if ($post): ?>
            <!-- 戻るナビゲーション（グループB N°9）-->
            <nav class="breadcrumb">
                <a href="./"><?= htmlspecialchars($lang['link_back_to_list'] ?? '← Back to post list') ?></a>
            </nav>
            <article>
                <h2><?= $post->title ?></h2>
                <div class="date" style="margin-bottom: 10px;"><?= htmlspecialchars(sprintf($lang['post_updated_at'] ?? 'Last updated: %s', $post->date)) ?></div>
                <?php $cardTags = $postTags[$post->slug] ?? []; ?>
                <?php if ($cardTags): ?>
                    <div class="post-card__tags" style="margin-bottom: 20px;">
                        <?php foreach ($cardTags as $t): ?>
                            <a href="?tag=<?= urlencode($t) ?>" class="tag-badge">#<?= htmlspecialchars($t) ?></a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <div class="content">
                    <!-- ParsedownによるHTML変換済・パス修正済みの内容 -->
                    <?= $post->htmlContent ?>
                </div>
            </article>
        <?php else: ?>
            <!-- Rule of silence: エラーを出さずHTML側で制御 -->
            <nav class="breadcrumb"><a href="./"><?= htmlspecialchars($lang['link_back_to_list'] ?? '← Back to post list') ?></a></nav>
            <h2><?= htmlspecialchars($lang['error_post_not_found'] ?? 'Post not found') ?></h2>
            <p><?= htmlspecialchars($lang['error_post_not_found_msg'] ?? 'The post you are looking for may have been deleted or the URL is incorrect.') ?></p>
        <?php endif; ?>

    <!-- 5. タグ一覧モード -->
    <?php elseif ($blog->isTagsList()): ?>
        <!-- 戻るナビゲーション（グループB N°13）-->
        <nav class="breadcrumb">
            <a href="./"><?= htmlspecialchars($lang['link_back_to_list'] ?? '← Back to post list') ?></a>
        </nav>
        <h2><?= htmlspecialchars($lang['tags_list_header'] ?? 'All Tags (Top 1000)') ?></h2>
        <div class="tag-cloud large">
            <?php foreach ($blog->getTags(1000) as $tName => $tCount): ?>
                <a href="?tag=<?= urlencode($tName) ?>">#<?= htmlspecialchars($tName) ?> (<?= $tCount ?>)</a>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>
    </main>

    <footer>
        <!-- Date('Y')等を使わず、静的に書く (HTML First) -->
        <a href="https://github.com/Masafuro/flatblog">
            <small>&copy; 2026 Flatblog - Built with DataLoader PHP</small>
        </a>
    </footer>
    <!-- 開発者向けJSプレースホルダー -->
    <script src="assets/js/script.js"></script>
</body>
</html>
