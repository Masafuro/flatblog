<?php
// ==========================================
// Flatblog フロントエンド (HTML First)
// ==========================================
require_once __DIR__ . '/core/FlatblogLoader.php';

// Docker環境でのマウント先である 'blog' ディレクトリを指定してローダーを起動
$blog = new \Flatblog\Core\FlatblogLoader(__DIR__ . '/blog');

// ページタイトルをモードに応じて動的に生成（グループB）
$lang = require __DIR__ . '/lang/en.php';

if ($blog->isPost()) {
    $pageTitle = ($blog->getCurrentPost()?->title ?? $lang['page_title_post']) . ' - ' . $lang['page_title_default'];
} elseif ($blog->isSearch()) {
    $pageTitle = sprintf($lang['page_title_search'], $blog->getSafeQuery());
} elseif ($blog->isTagSearch()) {
    $pageTitle = sprintf($lang['page_title_tag'], $blog->getSafeTag());
} elseif ($blog->isTagsList()) {
    $pageTitle = $lang['page_title_tags_list'];
} else {
    $pageTitle = $lang['page_title_default'];
}
?>
<!DOCTYPE html>
<html lang="<?= $lang['html_lang'] ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <!-- 抽出された外部CSSの読み込み -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <h1><a href="./"><?= $pageTitle ?></a></h1>
        <div class="search-box">
            <!-- 検索窓。getSafeQuery() により、XSSを注入されても自動で無毒化された文字列が戻る -->
            <form method="get" action="./">
                <input type="search" name="q" value="<?= $blog->getSafeQuery() ?>" placeholder="<?= $lang['search_placeholder'] ?>">
                <button type="submit"><?= $lang['search_button'] ?></button>
            </form>
        </div>
    </header>

    <main>
    <?php
        $thumbs    = $blog->getThumbs();
        $excerpts  = $blog->getExcerpts();
        $postTags  = $blog->getPostTags();
    ?>

    <!-- 1. 一覧（ホーム）モード -->
    <?php if ($blog->isHome()): ?>
        <?php $topTags = $blog->getTags(5); ?>
        <?php if ($topTags): ?>
        <div class="tag-cloud">
            <?php foreach ($topTags as $tName => $tCount): ?>
                <a href="?tag=<?= urlencode($tName) ?>">#<?= htmlspecialchars($tName) ?> (<?= $tCount ?>)</a>
            <?php endforeach; ?>
            <a href="?mode=tags" class="tag-list-link"><?= $lang['link_all_tags'] ?></a>
        </div>
        <?php endif; ?>

        <h2><?= $lang['header_latest_posts'] ?></h2>
        <?php $posts = $blog->getPosts(); ?>
        <?php if (empty($posts)): ?>
            <p class="empty-state"><?= $lang['empty_no_posts'] ?></p>
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
            <a href="./"><?= $lang['link_back_to_list'] ?></a>
        </nav>
        <h2><?= sprintf($lang['search_results_count'], $blog->getSafeQuery(), $blog->getResultCount()) ?></h2>
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
            <p><?= $lang['search_no_results'] ?></p>
        <?php endif; ?>

    <!-- 3. タグ検索モード -->
    <?php elseif ($blog->isTagSearch()): ?>
        <nav class="breadcrumb">
            <a href="./"><?= $lang['link_back_to_list'] ?></a>
        </nav>
        <h2><?= sprintf($lang['tag_posts_count'], $blog->getSafeTag(), $blog->getResultCount()) ?></h2>
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
            <p><?= $lang['tag_no_results'] ?></p>
        <?php endif; ?>

    <!-- 4. 個別記事モード -->
    <?php elseif ($blog->isPost()): ?>
        <?php $post = $blog->getCurrentPost(); ?>
        <?php if ($post): ?>
            <!-- 戻るナビゲーション（グループB N°9）-->
            <nav class="breadcrumb">
                <a href="./"><?= $lang['link_back_to_list'] ?></a>
            </nav>
            <article>
                <h2><?= $post->title ?></h2>
                <div class="date" style="margin-bottom: 10px;"><?= sprintf($lang['post_updated_at'], $post->date) ?></div>
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
            <nav class="breadcrumb"><a href="./"><?= $lang['link_back_to_list'] ?></a></nav>
            <h2><?= $lang['error_post_not_found'] ?></h2>
            <p><?= $lang['error_post_not_found_msg'] ?></p>
        <?php endif; ?>

    <!-- 5. タグ一覧モード -->
    <?php elseif ($blog->isTagsList()): ?>
        <!-- 戻るナビゲーション（グループB N°13）-->
        <nav class="breadcrumb">
            <a href="./"><?= $lang['link_back_to_list'] ?></a>
        </nav>
        <h2><?= $lang['tags_list_header'] ?></h2>
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
