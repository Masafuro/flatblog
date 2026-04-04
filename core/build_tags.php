<?php
// ==========================================
// Flatblog 記事インデックス非同期ビルダ
// タグ・サムネイル・要約を一括インデックス化
// (Rule of Silence 遵守: 成功時は無言)
// ==========================================
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the CLI.");
}

$dataDir = $argv[1] ?? dirname(__DIR__) . '/blog';
$dataDir = rtrim($dataDir, '/');

if (!is_dir($dataDir)) {
    exit(0);
}

$files = glob($dataDir . '/*.md');
if ($files === false) {
    exit(0);
}

$tagCounts = [];
$tagMap    = [];
$thumbs    = [];
$excerpts  = [];

foreach ($files as $filePath) {
    $filename = basename($filePath, '.md');
    $content = file_get_contents($filePath);

    // ── ①タグ抽出（直前に空白または行頭、#の後ろに空白がない文字列）──
    if (preg_match_all('/(?:^|\s)#([^\s#]+)/u', $content, $matches)) {
        $uniqueTagsInFile = array_unique($matches[1]);
        foreach ($uniqueTagsInFile as $tag) {
            if (!isset($tagCounts[$tag])) {
                $tagCounts[$tag] = 0;
                $tagMap[$tag] = [];
            }
            $tagCounts[$tag]++;
            $tagMap[$tag][] = $filename;
        }
    }

    // ── ②サムネイル：Markdownから最初のローカル画像パスを抽出 ──
    // (Rule of Silence: 画像がない場合は null)
    $thumb = null;
    if (preg_match('/!\[.*?\]\((attachments\/[^\)]+)\)/u', $content, $imgMatch)) {
        $thumb = $imgMatch[1];
    }
    $thumbs[$filename] = $thumb;

    // ── ③要約：Markdown記法を除去して冒頭200文字を抽出 ──
    // (Rule of Silence: 本文が空の場合は null)
    $plain = preg_replace('/(?:^|\s)#[^\s#]+/u', '', $content);    // #タグ行除去
    $plain = preg_replace('/!\[.*?\]\(.*?\)/u', '', $plain);        // 画像記法除去
    $plain = preg_replace('/\[([^\]]+)\]\([^\)]+\)/u', '$1', $plain); // リンク→テキスト
    $plain = preg_replace('/[#*`_~>|\-]+/u', '', $plain);           // その他Markdown記号除去
    $plain = trim(preg_replace('/\s+/u', ' ', $plain));             // 空白正規化
    $excerpts[$filename] = mb_strlen($plain) > 0 ? mb_substr($plain, 0, 200) : null;
}

// カウントが多い順にソート
arsort($tagCounts);

$indexData = [
    'counts'   => $tagCounts,
    'map'      => $tagMap,
    'thumbs'   => $thumbs,
    'excerpts' => $excerpts,
];

$cacheDir = dirname(__DIR__) . '/cache';
if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0777, true); // Rule of silence: fails silently if it cannot create
}
$indexPath = $cacheDir . '/tags_index.json';

// アトミック書き込み（競合防止）
$tmpFile = $indexPath . '.tmp.' . uniqid();
file_put_contents($tmpFile, json_encode($indexData, JSON_UNESCAPED_UNICODE));
rename($tmpFile, $indexPath);

exit(0);
