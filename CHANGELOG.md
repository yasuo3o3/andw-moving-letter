# Changelog

All notable changes to this project will be documented in this file.

## [1.0.1] - 2025-09-01

### Security
- REST API権限制御強化（管理者/編集者のみアクセス許可）
- XSS脆弱性修正（wp_json_encode()で安全な文字列出力）
- CSRF保護強化（設定保存時のnonce検証）

### Performance
- autoload設定最適化（ml_settings を 'no' に変更）
- 条件付きアセット読み込み（必要ページのみ）
- filemtime()によるキャッシュバスティング

### Compatibility
- PHP 8.3対応（float→int変換警告解消）
- WordPress 6.0+ 対応
- マルチサイト対応強化

### Accessibility
- prefers-reduced-motion対応（アニメーション制限）

### Operations
- uninstall.php追加（完全なクリーンアップ）
- プラグイン無効化時の権限掃除
- WP-CLI対応予定

## [1.0.0] - 2025-08-18

### Added
- 初回リリース
- カスタム投稿タイプ「お客様の声」
- マーキーアニメーション機能
- レスポンシブ対応
- 検索・フィルタリング機能
- 設定ページ実装