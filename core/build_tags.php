<?php
// ==========================================
// Flatblog タグインデックス非同期ビルダ
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
$tagMap = [];

foreach ($files as $filePath) {
    $filename = basename($filePath, '.md');
    $content = file_get_contents($filePath);
    
    // #tag を本文から抽出（直前に空白または行頭があり、#の後ろに空白がない文字列）
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
}

// カウントが多い順にソート
arsort($tagCounts);

$indexData = [
    'counts' => $tagCounts,
    'map' => $tagMap
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
