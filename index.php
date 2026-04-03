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
    <!-- 例としてインラインCSSですが、外部CSS（style.css）の読み込みも自由です -->
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; line-height: 1.6; color: #333; }
        header { border-bottom: 2px solid #333; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; padding-bottom: 10px; }
        h1 a { text-decoration: none; color: #333; }
        .post-list { list-style: none; padding: 0; }
        .post-list li { margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px; font-size: 1.1em; }
        .date { color: #888; font-size: 0.85em; margin-right: 15px; }
        a { color: #0066cc; text-decoration: none; }
        a:hover { text-decoration: underline; }
        img { max-width: 100%; height: auto; border-radius: 8px; }
        .search-box input { padding: 5px; }
        article { margin-top: 20px; }
        footer { margin-top: 50px; text-align: center; color: #888; font-size: 0.9em; border-top: 1px solid #eee; padding-top: 20px; }
    </style>
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

    <!-- 3. 個別記事モード -->
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
    <?php endif; ?>
    </main>

    <footer>
        <!-- Date('Y')等を使わず、静的に書く (HTML First) -->
        <small>&copy; 2026 Flatblog - Built with DataLoader PHP</small>
    </footer>
</body>
</html>
