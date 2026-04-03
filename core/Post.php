<?php
namespace Flatblog\Core;

/**
 * 読み取り専用の安全な記事データオブジェクト (DTO)
 * データは参照可能だが改変は不可能な完全な状態を保証する。
 */
readonly class Post {
    public function __construct(
        public string $slug,
        public string $title,
        public string $date,
        public string $htmlContent
    ) {}
}
