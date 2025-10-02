=== Moving Letter ===
Contributors: netservice
Tags: testimonials, marquee, customer-voices, animation, responsive
Requires at least: 6.0
Tested up to: 6.8
Stable tag: 1.0.1
Requires PHP: 8.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

お客様の声を美しい動くカードで表示するWordPressプラグイン

== Description ==

Moving Letter は、お客様からいただいた声を美しく動くカードで表示するWordPressプラグインです。

**主な機能:**

* カスタム投稿タイプ「お客様の声」でお便りを管理
* スムーズなマルチ行マーキー（流れる文字）アニメーション
* レスポンシブデザインで全デバイスに対応
* ツアーコードによるフィルタリング機能
* 検索機能付きアーカイブページ
* ショートコードで簡単設置
* 管理画面での設定カスタマイズ

**ショートコード例:**
```
[moving_letter]
[moving_letter tour_code="A-1,B-3" visible_desktop="4"]
```

**対応メタフィールド:**
* ニックネーム
* プラン名・URL
* お便り本文
* ツアーコード

== Installation ==

1. プラグインファイルを `/wp-content/plugins/moving-letter` ディレクトリにアップロード
2. WordPress管理画面のプラグインメニューからプラグインを有効化
3. 「お客様の声」メニューから投稿を追加
4. ショートコード `[moving_letter]` をページや投稿に挿入

== Frequently Asked Questions ==

= ショートコードのパラメータは何がありますか？ =

以下のパラメータが使用できます：
* `rows` - 表示行数（デフォルト：3）
* `visible_desktop` - デスクトップ表示枚数（デフォルト：5）
* `visible_tablet` - タブレット表示枚数（デフォルト：3）  
* `visible_mobile` - モバイル表示枚数（デフォルト：2）
* `preload_desktop` - デスクトップ読み込み枚数（デフォルト：7）
* `preload_tablet` - タブレット読み込み枚数（デフォルト：5）
* `preload_mobile` - モバイル読み込み枚数（デフォルト：4）
* `tour_code` - 表示するツアーコード（カンマ区切りで複数指定可）
* `speed` - スクロール速度（秒）
* `pause_on_hover` - ホバー時停止（true/false）
* `gap` - カード間隔（px）

= アーカイブページはどこで確認できますか？ =

`/voices/` でアーカイブページにアクセスできます。検索・フィルタリング機能も利用可能です。

= レスポンシブ対応していますか？ =

はい。モバイル、タブレット、デスクトップに最適化されたレスポンシブデザインです。

== Screenshots ==

1. 管理画面でのお客様の声投稿編集
2. フロントエンドでの動くカード表示
3. アーカイブページの検索・フィルタ機能
4. 設定ページでのカスタマイズ

== Changelog ==

= 1.0.0 =
* 初回リリース
* カスタム投稿タイプ実装
* マーキーアニメーション機能
* レスポンシブ対応
* 検索・フィルタリング機能
* 設定ページ実装

== Upgrade Notice ==

= 1.0.0 =
初回リリースです。

== Technical Documentation ==

**ファイル構造:**
```
moving-letter/
├── moving-letter.php         # メインプラグインファイル
├── includes/
│   ├── helpers.php          # ヘルパー関数
│   ├── cpt.php             # カスタム投稿タイプ
│   ├── meta.php            # メタフィールド
│   ├── shortcode.php       # ショートコード
│   ├── assets.php          # アセット管理
│   └── settings.php        # 設定ページ
├── assets/
│   ├── css/
│   │   ├── moving-letter.css # メインスタイル
│   │   ├── archive.css      # アーカイブスタイル
│   │   └── admin.css        # 管理画面スタイル
│   └── js/
│       └── marquee.js       # マーキーJS
└── templates/
    ├── archive-moving_letter.php # アーカイブテンプレート
    └── part-card.php           # カードパーツ
```

**主要関数:**
* `ml_get_posts()` - 投稿取得
* `ml_get_card_html()` - カードHTML生成
* `ml_get_settings()` - 設定取得
* `ml_render_archive_card()` - アーカイブカード表示

**セキュリティ機能:**
* すべての出力でesc_html/esc_url実装
* nonce・権限チェック
* sanitize処理完備