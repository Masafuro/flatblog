<?php
// ==========================================
// Flatblog フロントエンド (HTML First)
// ==========================================
require_once __DIR__ . '/core/FlatblogLoader.php';

// Docker環境でのマウント先である 'blog' ディレクトリを指定してローダーを起動
$blog = new \Flatblog\Core\FlatblogLoader(__DIR__ . '/blog');

// 言語・データの事前取得
$lang      = require __DIR__ . '/lang/en.php';
$thumbs    = $blog->getThumbs();
$excerpts  = $blog->getExcerpts();
$postTags  = $blog->getPostTags();

// ページタイトルとメタ記述をモードに応じて動的に生成
if ($blog->isPost()) {
    $post = $blog->getCurrentPost();
    $pageTitle = $lang['page_title_default'];
    $metaDesc  = ($post && isset($excerpts[$post->slug])) ? $excerpts[$post->slug] : $lang['site_description'];
} else {
    $pageTitle = $lang['page_title_default'];
    $metaDesc  = $lang['site_description'];
}

// ==========================================
// テーマ（フロントコントローラー）への委譲
// ==========================================
$themeName = $blog->getConfig('Theme', 'default');
$themeName = preg_replace('/[^a-zA-Z0-9_-]/', '', $themeName); // パストラバーサル対策

$themeFile = __DIR__ . '/themes/' . $themeName . '/layout.php';

// 指定されたテーマが存在しない場合はdefaultにフォールバック
if (!file_exists($themeFile)) {
    $themeName = 'default';
    $themeFile = __DIR__ . '/themes/default/layout.php';
}

require $themeFile;
