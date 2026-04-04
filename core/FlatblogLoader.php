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
    private ?string $tagQuery = null;

    public function __construct(string $dataDir) {
        $this->dataDir = rtrim($dataDir, '/');
        $this->parsedown = new \Parsedown();
        
        // HTTP GETルーティングの解析（HTML側から隠蔽）
        $this->postSlug = $_GET['post'] ?? null;
        $this->searchQuery = $_GET['q'] ?? null;
        $this->tagQuery = $_GET['tag'] ?? null;

        if ($this->postSlug !== null) {
            $this->mode = 'post';
            // 安全対策：パストラバーサルの無効化（マルチバイト対応）
            $this->postSlug = str_replace(['/', '\\', "\0"], '', $this->postSlug); 
        } elseif ($this->tagQuery !== null && trim($this->tagQuery) !== '') {
            $this->mode = 'tag';
        } elseif ($this->searchQuery !== null && trim($this->searchQuery) !== '') {
            $this->mode = 'search';
        } elseif (isset($_GET['mode']) && $_GET['mode'] === 'tags') {
            $this->mode = 'tags_list';
        } else {
            $this->mode = 'list';
        }
    }

    public function isHome(): bool { return $this->mode === 'list'; }
    public function isPost(): bool { return $this->mode === 'post'; }
    public function isSearch(): bool { return $this->mode === 'search'; }
    public function isTagSearch(): bool { return $this->mode === 'tag'; }
    public function isTagsList(): bool { return $this->mode === 'tags_list'; }
    
    public function getSafeTag(): string {
        return htmlspecialchars((string)$this->tagQuery, ENT_QUOTES, 'UTF-8');
    }

    /**
     * 無毒化済みの検索クエリを返す（XSS対策の関所）
     */
    public function getSafeQuery(): string {
        return htmlspecialchars((string)$this->searchQuery, ENT_QUOTES, 'UTF-8');
    }

    public function getResultCount(): int {
        return count($this->getPosts());
    }

    public function getTags(?int $limit = null, string $sort = 'count_desc'): array {
        $this->triggerTagBuildIfNeeded();
        $indexPath = dirname(__DIR__) . '/cache/tags_index.json';
        
        $tags = [];
        if (file_exists($indexPath)) {
            $data = json_decode(file_get_contents($indexPath), true);
            $tags = $data['counts'] ?? [];
        }
        
        if (!empty($tags)) {
            if ($sort === 'count_desc') {
                arsort($tags);
            } elseif ($sort === 'name_asc') {
                ksort($tags);
            }
            
            if ($limit !== null && $limit > 0) {
                $tags = array_slice($tags, 0, $limit, true);
            }
        }
        
        return $tags;
    }

    /**
     * 記事slugをキー、最初のローカル画像パス(またはnull)を値とする配列を返す
     * Rule of Silence: インデックス未生成・画像なしは静かに空配列/nullを返す
     */
    public function getThumbs(): array {
        $indexPath = dirname(__DIR__) . '/cache/tags_index.json';
        if (!file_exists($indexPath)) return [];
        $data = json_decode(file_get_contents($indexPath), true);
        return $data['thumbs'] ?? [];
    }

    /**
     * 記事slugをキー、要約テキスト(またはnull)を値とする配列を返す
     * Rule of Silence: インデックス未生成・本文なしは静かに空配列/nullを返す
     */
    public function getExcerpts(): array {
        $indexPath = dirname(__DIR__) . '/cache/tags_index.json';
        if (!file_exists($indexPath)) return [];
        $data = json_decode(file_get_contents($indexPath), true);
        return $data['excerpts'] ?? [];
    }

    /**
     * 記事slugをキー、タグ名配列を値とする逆引きマップを返す
     * tags_index.json の map（タグ→slug[]）を逆転させる
     * Rule of Silence: インデックス未生成時は静かに空配列を返す
     */
    public function getPostTags(): array {
        $indexPath = dirname(__DIR__) . '/cache/tags_index.json';
        if (!file_exists($indexPath)) return [];
        $data = json_decode(file_get_contents($indexPath), true);
        $map = $data['map'] ?? [];

        // {タグ名: [slug,...]} → {slug: [タグ名,...]} に逆転
        $postTags = [];
        foreach ($map as $tag => $slugs) {
            foreach ($slugs as $slug) {
                $postTags[$slug][] = $tag;
            }
        }
        return $postTags;
    }

    private function triggerTagBuildIfNeeded(): void {
        $files = glob($this->dataDir . '/*.md');
        if (!$files) return;
        
        $latestMtime = 0;
        foreach ($files as $f) {
            $mtime = filemtime($f);
            if ($mtime > $latestMtime) {
                $latestMtime = $mtime;
            }
        }
        
        $indexPath = dirname(__DIR__) . '/cache/tags_index.json';
        $indexMtime = file_exists($indexPath) ? filemtime($indexPath) : 0;
        
        if ($latestMtime > $indexMtime) {
            $script = dirname(__DIR__) . '/core/build_tags.php';
            exec("nohup php " . escapeshellarg($script) . " " . escapeshellarg($this->dataDir) . " > /dev/null 2>&1 &");
        }
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
        
        $tagMap = [];
        if ($this->isTagSearch()) {
            $indexPath = dirname(__DIR__) . '/cache/tags_index.json';
            if (file_exists($indexPath)) {
                $index = json_decode(file_get_contents($indexPath), true);
                if (isset($index['map'][$this->tagQuery])) {
                    $tagMap = array_flip($index['map'][$this->tagQuery]);
                }
            }
        }

        $posts = [];
        foreach ($fileData as $filePath => $mtime) {
            $filename = basename($filePath, '.md');

            if ($this->isTagSearch() && !isset($tagMap[$filename])) {
                continue;
            }
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
