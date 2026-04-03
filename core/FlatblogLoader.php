<?php
namespace Flatblog\Core;

require_once __DIR__ . '/Parsedown.php';
require_once __DIR__ . '/Post.php';

/**
 * flatnotesディレクトリを安全に解析・出力するローダークラス
 * HTML側の呼び出しに特化したデータプロバイダー
 */
class FlatblogLoader {
    private \Parsedown $parsedown;
    private string $dataDir;
    private ?string $mode = null;
    private ?string $postSlug = null;
    private ?string $searchQuery = null;

    public function __construct(string $dataDir) {
        $this->dataDir = rtrim($dataDir, '/');
        $this->parsedown = new \Parsedown();
        
        // HTTP GETルーティングの解析（HTML側から隠蔽）
        $this->postSlug = $_GET['post'] ?? null;
        $this->searchQuery = $_GET['q'] ?? null;

        if ($this->postSlug !== null) {
            $this->mode = 'post';
            // 安全対策：パストラバーサルの無効化
            $this->postSlug = basename($this->postSlug); 
        } elseif ($this->searchQuery !== null && trim($this->searchQuery) !== '') {
            $this->mode = 'search';
        } else {
            $this->mode = 'list';
        }
    }

    public function isHome(): bool { return $this->mode === 'list'; }
    public function isPost(): bool { return $this->mode === 'post'; }
    public function isSearch(): bool { return $this->mode === 'search'; }
    
    /**
     * 無毒化済みの検索クエリを返す（XSS対策の関所）
     */
    public function getSafeQuery(): string {
        return htmlspecialchars((string)$this->searchQuery, ENT_QUOTES, 'UTF-8');
    }

    public function getResultCount(): int {
        return count($this->getPosts());
    }

    /**
     * @return Post[]
     */
    public function getPosts(): array {
        $files = glob($this->dataDir . '/*.md');
        if (!$files) return [];
        
        $fileData = [];
        foreach ($files as $f) {
            $fileData[$f] = filemtime($f);
        }
        arsort($fileData); // 更新日時で降順ソート
        
        $posts = [];
        foreach ($fileData as $filePath => $mtime) {
            $filename = basename($filePath, '.md');
            $content = file_get_contents($filePath);
            
            // 検索フィルタリング（AND条件がない単純なOR検索）
            if ($this->isSearch()) {
                if (mb_stripos($filename, $this->searchQuery) === false && 
                    mb_stripos($content, $this->searchQuery) === false) {
                    continue; // ヒットしない場合は除外
                }
            }
            
            $posts[] = $this->createPostObject($filename, $filePath, $mtime, $content);
        }
        return $posts;
    }

    /**
     * @return Post|null 該当記事がない場合は静かにnullを返す (Rule of Silence)
     */
    public function getCurrentPost(): ?Post {
        if (!$this->isPost() || !$this->postSlug) return null;
        
        $filePath = $this->dataDir . '/' . $this->postSlug . '.md';
        if (!file_exists($filePath)) {
            return null; 
        }
        
        return $this->createPostObject(
            $this->postSlug, 
            $filePath, 
            filemtime($filePath), 
            file_get_contents($filePath)
        );
    }

    private function createPostObject(string $slug, string $filePath, int $mtime, string $rawContent): Post {
        $title = str_replace(['-', '_'], ' ', $slug); // ファイル名からタイトルを生成
        $date = date("Y-m-d", $mtime);
        
        // Markdown変換と画像パスの動的書き換え
        $rawHtml = $this->parsedown->text($rawContent);
        $htmlContent = str_replace('src="attachments/', 'src="blog/attachments/', $rawHtml);
        
        // オブジェクト化のタイミングでタイトルのエスケープ処理を完了させる（事前無毒化）
        return new Post(
            $slug, 
            htmlspecialchars($title, ENT_QUOTES, 'UTF-8'), 
            $date, 
            $htmlContent
        );
    }
}
