<?php
// ==========================================
// Flatblog フロントエンド (HTML First)
// ==========================================
require_once __DIR__ . '/core/FlatblogLoader.php';

// Docker環境でのマウント先である 'blog' ディレクトリを指定してローダーを起動
$blog = new \Flatblog\Core\FlatblogLoader(__DIR__ . '/blog'); 
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $blog->isPost() ? $blog->getCurrentPost()?->title . ' - Flatblog' : 'Flatblog' ?></title>
    <!-- 抽出された外部CSSの読み込み -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <h1><a href="./">Flatblog</a></h1>
        <div class="search-box">
            <!-- 検索窓。getSafeQuery() により、XSSを注入されても自動で無毒化された文字列が戻る -->
            <form method="get" action="./">
                <input type="search" name="q" value="<?= $blog->getSafeQuery() ?>" placeholder="記事を検索...">
                <button type="submit">検索</button>
            </form>
        </div>
    </header>

    <main>
    <?php $tags = $blog->getTags(50); ?>
    <?php if ($tags): ?>
    <div class="tag-cloud">
        <?php foreach ($tags as $tName => $tCount): ?>
            <a href="?tag=<?= urlencode($tName) ?>">#<?= htmlspecialchars($tName) ?> (<?= $tCount ?>)</a>
        <?php endforeach; ?>
        <a href="?mode=tags" class="tag-list-link">📁すべてのタグを見る</a>
    </div>
    <?php endif; ?>

    <!-- 1. 一覧（ホーム）モード -->
    <?php if ($blog->isHome()): ?>
        <h2>最新の記事</h2>
        <ul class="post-list">
            <!-- $blog->getPosts() を呼ぶだけで安全な配列が取得できる -->
            <?php foreach ($blog->getPosts() as $post): ?>
                <li>
                    <span class="date"><?= $post->date ?></span>
                    <a href="?post=<?= urlencode($post->slug) ?>"><?= $post->title ?></a>
                </li>
            <?php endforeach; ?>
        </ul>

    <!-- 2. 検索結果モード -->
    <?php elseif ($blog->isSearch()): ?>
        <h2>「<?= $blog->getSafeQuery() ?>」の検索結果 (<?= $blog->getResultCount() ?>件)</h2>
        <?php if ($blog->getResultCount() > 0): ?>
            <ul class="post-list">
                <?php foreach ($blog->getPosts() as $post): ?>
                    <li>
                        <span class="date"><?= $post->date ?></span>
                        <a href="?post=<?= urlencode($post->slug) ?>"><?= $post->title ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>該当する記事は見つかりませんでした。</p>
        <?php endif; ?>

    <!-- 3. タグ検索モード -->
    <?php elseif ($blog->isTagSearch()): ?>
        <h2>「#<?= $blog->getSafeTag() ?>」の記事一覧 (<?= $blog->getResultCount() ?>件)</h2>
        <?php if ($blog->getResultCount() > 0): ?>
            <ul class="post-list">
                <?php foreach ($blog->getPosts() as $post): ?>
                    <li>
                        <span class="date"><?= $post->date ?></span>
                        <a href="?post=<?= urlencode($post->slug) ?>"><?= $post->title ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>該当するタグの記事は見つかりませんでした。</p>
        <?php endif; ?>

    <!-- 4. 個別記事モード -->
    <?php elseif ($blog->isPost()): ?>
        <?php $post = $blog->getCurrentPost(); ?>
        <?php if ($post): ?>
            <article>
                <h2><?= $post->title ?></h2>
                <div class="date" style="margin-bottom: 20px;">更新日: <?= $post->date ?></div>
                <div class="content">
                    <!-- ParsedownによるHTML変換済み・パス修正済みの内容 -->
                    <?= $post->htmlContent ?>
                </div>
            </article>
        <?php else: ?>
            <!-- Rule of silence: エラーを出さずHTML側で制御 -->
            <h2>記事が見つかりません</h2>
            <p>お探しの記事は削除されたか、URLが間違っている可能性があります。</p>
        <?php endif; ?>

    <!-- 5. タグ一覧モード -->
    <?php elseif ($blog->isTagsList()): ?>
        <h2>すべてのタグ (上位1000件)</h2>
        <div class="tag-cloud large">
            <?php foreach ($blog->getTags(1000) as $tName => $tCount): ?>
                <a href="?tag=<?= urlencode($tName) ?>">#<?= htmlspecialchars($tName) ?> (<?= $tCount ?>)</a>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>
    </main>

    <footer>
        <!-- Date('Y')等を使わず、静的に書く (HTML First) -->
        <small>&copy; 2026 Flatblog - Built with DataLoader PHP</small>
    </footer>
    <!-- 開発者向けJSプレースホルダー -->
    <script src="assets/js/script.js"></script>
</body>
</html>
