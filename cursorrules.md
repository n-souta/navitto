# ContentPilot WordPress Plugin Development Rules

## 🚨 最重要：コード生成前に必読

**wordpress-development-guide.md を必ず読んでから実装すること**

このファイルには以下が含まれる：
- 必須チェックリスト（すべての機能で確認必須）
- セキュリティパターン（入力/出力/DB操作）
- WordPress標準のコード例（Before/After形式）
- NGパターン集（絶対にやってはいけないこと）
- 状況別クイックリファレンス

**読まずにコードを生成してはいけない。**

チェックリストをすべて確認し、サンプルコードをコピーして改変する方式で実装すること。

---

## プロジェクト概要
**プラグイン名:** ContentPilot  
**目的:** 長文記事の離脱を防ぐ、目次連携型の固定ナビゲーションプラグイン  
**ターゲット:** ブロックエディタユーザー（WordPress 6.0以上）

---

## 必須準拠事項

### WordPress公式ガイドライン
- **Plugin Handbook 完全準拠:** https://ja.wordpress.org/team/handbook/plugin-development
- **Coding Standards 準拠:** https://developer.wordpress.org/coding-standards/
- **Security Best Practices:** https://developer.wordpress.org/plugins/security/

### ライセンス
- **GPLv2 or later** でリリース
- LICENSE ファイルに明記

---

## コーディング規約

### PHP
```php
// WordPress PHP Coding Standards 準拠
// https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/

// 全てのグローバル関数にプレフィックス
function contentpilot_function_name() {
    // 処理
}

// クラス名は ContentPilot_ で始める
class ContentPilot_ClassName {
    // プロパティとメソッド
}

// 変数名は snake_case
$heading_text = 'Sample';

// PHPDocブロックは必須
/**
 * 見出しを取得する
 *
 * @param int $post_id 投稿ID
 * @return array 見出しの配列
 */
function contentpilot_get_headings( $post_id ) {
    // 実装
}
```

### JavaScript
```javascript
// WordPress JavaScript Coding Standards 準拠
// https://developer.wordpress.org/coding-standards/wordpress-coding-standards/javascript/

// 変数名は camelCase
const headingText = 'Sample';

// グローバルオブジェクトを使用
const ContentPilot = {
    init: function() {
        // 初期化処理
    }
};

// jQuery使用時は $ の代わりに jQuery を使用
jQuery(document).ready(function($) {
    // jQueryコード
});
```

### CSS
```css
/* WordPress CSS Coding Standards 準拠 */
/* https://developer.wordpress.org/coding-standards/wordpress-coding-standards/css/ */

/* クラス名はケバブケース、プレフィックス付き */
.contentpilot-nav {
    display: flex;
}

.contentpilot-nav__item {
    padding: 10px;
}

/* プロパティはアルファベット順 */
.contentpilot-button {
    background-color: #fff;
    border: 1px solid #ccc;
    color: #333;
    padding: 10px 20px;
}
```

---

## セキュリティ

### 必須実装事項

#### 1. Nonce検証
```php
// フォーム送信時
if ( ! isset( $_POST['contentpilot_nonce'] ) || 
     ! wp_verify_nonce( $_POST['contentpilot_nonce'], 'contentpilot_save' ) ) {
    wp_die( __( 'Security check failed', 'contentpilot' ) );
}

// フォームHTML
wp_nonce_field( 'contentpilot_save', 'contentpilot_nonce' );
```

#### 2. データのサニタイズ
```php
// テキスト入力
$text = sanitize_text_field( $_POST['text'] );

// テキストエリア
$textarea = sanitize_textarea_field( $_POST['textarea'] );

// HTML（限定的なタグのみ許可）
$html = wp_kses_post( $_POST['html'] );

// 整数
$number = absint( $_POST['number'] );

// URL
$url = esc_url_raw( $_POST['url'] );

// メール
$email = sanitize_email( $_POST['email'] );
```

#### 3. 出力のエスケープ
```php
// HTML内のテキスト
echo esc_html( $text );

// 属性値
echo '<div class="' . esc_attr( $class ) . '">';

// URL
echo '<a href="' . esc_url( $url ) . '">';

// JavaScript内
echo '<script>var data = ' . wp_json_encode( $data ) . ';</script>';
```

#### 4. 権限チェック
```php
// 管理画面での操作
if ( ! current_user_can( 'edit_posts' ) ) {
    wp_die( __( 'You do not have permission', 'contentpilot' ) );
}

// 投稿編集
if ( ! current_user_can( 'edit_post', $post_id ) ) {
    return;
}
```

#### 5. SQLクエリ
```php
// Prepared Statements を必ず使用
global $wpdb;
$results = $wpdb->get_results( 
    $wpdb->prepare( 
        "SELECT * FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s",
        'post',
        'publish'
    )
);

// 直接クエリは禁止
// BAD: $wpdb->query( "SELECT * FROM {$wpdb->posts} WHERE ID = {$_GET['id']}" );
```

---

## ファイル構成

```
contentpilot/
├── contentpilot.php              # メインファイル（プラグインヘッダー含む）
├── readme.txt                     # WordPress.org用README
├── LICENSE                        # GPLv2ライセンス
├── .gitignore                     # Git除外設定
├── assets/
│   ├── css/
│   │   ├── admin.css             # 管理画面用CSS
│   │   ├── frontend.css          # フロントエンド用CSS
│   │   └── presets/              # デザインプリセット
│   │       ├── simple.css
│   │       ├── modern.css
│   │       └── ...
│   ├── js/
│   │   ├── admin.js              # 管理画面用JS
│   │   └── frontend.js           # フロントエンド用JS
│   └── images/
│       ├── icon.svg              # プラグインアイコン
│       └── banner.jpg            # プラグインバナー
├── includes/
│   ├── class-contentpilot.php           # メインクラス
│   ├── class-contentpilot-detector.php  # 目次検出クラス
│   ├── class-contentpilot-settings.php  # 設定管理クラス
│   ├── class-contentpilot-renderer.php  # HTML生成クラス
│   ├── class-contentpilot-customizer.php # カスタマイザークラス
│   └── functions.php                    # ユーティリティ関数
└── languages/
    ├── contentpilot.pot           # POTテンプレート
    └── contentpilot-ja.po         # 日本語翻訳
```

---

## 命名規則

### プレフィックス
すべてのグローバルな名前に `contentpilot_` または `ContentPilot_` を付与

### 関数名
```php
// グローバル関数
contentpilot_init()
contentpilot_enqueue_scripts()
contentpilot_get_headings()

// プライベート関数（外部から呼ばれない）
_contentpilot_private_function() // アンダースコアで始める
```

### クラス名
```php
class ContentPilot_Main {}
class ContentPilot_Detector {}
class ContentPilot_Settings {}
```

### フック名
```php
// アクション
do_action( 'contentpilot_init' );
do_action( 'contentpilot_before_render' );

// フィルター
apply_filters( 'contentpilot_headings', $headings );
apply_filters( 'contentpilot_nav_html', $html );
```

### メタキー
```php
_contentpilot_enabled        // プラグイン有効/無効
_contentpilot_selected_h2    // 選択されたH2
_contentpilot_preset         // デザインプリセット
_contentpilot_custom_css     // カスタムCSS
```

### オプション名
```php
contentpilot_default_preset        // デフォルトプリセット
contentpilot_position              // 配置位置
contentpilot_auto_detect_theme     // テーマ自動検出
contentpilot_min_word_count        // 最小文字数
```

### CSS/JSハンドル名
```php
// CSS
wp_enqueue_style( 'contentpilot-admin', ... );
wp_enqueue_style( 'contentpilot-frontend', ... );

// JavaScript
wp_enqueue_script( 'contentpilot-admin', ... );
wp_enqueue_script( 'contentpilot-frontend', ... );
```

---

## WordPress関数の使用

### アセット読み込み
```php
// CSS読み込み
function contentpilot_enqueue_styles() {
    wp_enqueue_style( 
        'contentpilot-frontend', 
        plugins_url( 'assets/css/frontend.css', __FILE__ ),
        array(),
        CONTENTPILOT_VERSION
    );
}
add_action( 'wp_enqueue_scripts', 'contentpilot_enqueue_styles' );

// JavaScript読み込み
function contentpilot_enqueue_scripts() {
    wp_enqueue_script( 
        'contentpilot-frontend', 
        plugins_url( 'assets/js/frontend.js', __FILE__ ),
        array( 'jquery' ),
        CONTENTPILOT_VERSION,
        true
    );
    
    // データをJavaScriptに渡す
    wp_localize_script( 'contentpilot-frontend', 'contentpilotData', array(
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce' => wp_create_nonce( 'contentpilot_ajax' ),
    ) );
}
add_action( 'wp_enqueue_scripts', 'contentpilot_enqueue_scripts' );
```

### カスタムフィールド登録
```php
function contentpilot_register_meta() {
    register_post_meta( 'post', '_contentpilot_enabled', array(
        'type' => 'boolean',
        'single' => true,
        'default' => true,
        'show_in_rest' => true,
        'auth_callback' => function() {
            return current_user_can( 'edit_posts' );
        }
    ) );
}
add_action( 'init', 'contentpilot_register_meta' );
```

### データベース操作
```php
// 投稿メタの取得
$enabled = get_post_meta( $post_id, '_contentpilot_enabled', true );

// 投稿メタの保存
update_post_meta( $post_id, '_contentpilot_enabled', true );

// オプションの取得
$preset = get_option( 'contentpilot_default_preset', 'simple' );

// オプションの保存
update_option( 'contentpilot_default_preset', 'modern' );
```

### 翻訳
```php
// プラグイン読み込み時にテキストドメインを登録
function contentpilot_load_textdomain() {
    load_plugin_textdomain( 
        'contentpilot', 
        false, 
        dirname( plugin_basename( __FILE__ ) ) . '/languages' 
    );
}
add_action( 'plugins_loaded', 'contentpilot_load_textdomain' );

// 翻訳関数の使用
__( 'Text to translate', 'contentpilot' );          // 返り値として取得
_e( 'Text to translate', 'contentpilot' );          // 直接出力
esc_html__( 'Text to translate', 'contentpilot' );  // エスケープして返り値
esc_html_e( 'Text to translate', 'contentpilot' );  // エスケープして出力
```

---

## コア機能の実装ガイド

### 1. 目次検出機能

#### 優先順位
1. テーマ内蔵の目次
2. 目次プラグイン
3. H2タグから自動生成

#### 実装例
```php
class ContentPilot_Detector {
    
    /**
     * 目次を検出する
     *
     * @param int $post_id 投稿ID
     * @return array 見出しの配列
     */
    public function detect_toc( $post_id ) {
        $content = get_post_field( 'post_content', $post_id );
        
        // 1. テーマ内蔵目次を検出
        $headings = $this->detect_theme_toc( $content );
        
        if ( ! empty( $headings ) ) {
            return $headings;
        }
        
        // 2. 目次プラグインを検出
        $headings = $this->detect_plugin_toc( $content );
        
        if ( ! empty( $headings ) ) {
            return $headings;
        }
        
        // 3. H2タグから自動生成
        return $this->generate_from_h2( $content );
    }
    
    /**
     * テーマ内蔵目次を検出
     */
    private function detect_theme_toc( $content ) {
        $theme = wp_get_theme()->get( 'Name' );
        
        // SWELL
        if ( 'SWELL' === $theme && has_block( 'swl/toc', $content ) ) {
            return $this->parse_swell_toc( $content );
        }
        
        // JIN
        if ( 'JIN' === $theme || 'JIN:R' === $theme ) {
            return $this->parse_jin_toc( $content );
        }
        
        // その他のテーマ...
        
        return array();
    }
    
    /**
     * 目次プラグインを検出
     */
    private function detect_plugin_toc( $content ) {
        // Table of Contents Plus
        if ( is_plugin_active( 'table-of-contents-plus/toc.php' ) ) {
            return $this->parse_toc_plus( $content );
        }
        
        // Easy Table of Contents
        if ( is_plugin_active( 'easy-table-of-contents/easy-table-of-contents.php' ) ) {
            return $this->parse_easy_toc( $content );
        }
        
        // その他のプラグイン...
        
        return array();
    }
    
    /**
     * H2タグから自動生成
     */
    private function generate_from_h2( $content ) {
        $headings = array();
        
        preg_match_all( '/<h2[^>]*>(.*?)<\/h2>/is', $content, $matches );
        
        if ( ! empty( $matches[1] ) ) {
            foreach ( $matches[1] as $index => $heading_text ) {
                $headings[] = array(
                    'text' => wp_strip_all_tags( $heading_text ),
                    'id' => 'heading-' . ( $index + 1 ),
                );
            }
        }
        
        return $headings;
    }
}
```

### 2. 固定ナビゲーション表示

#### JavaScript実装例
```javascript
(function($) {
    'use strict';
    
    const ContentPilot = {
        init: function() {
            this.cacheDom();
            this.bindEvents();
            this.checkScrollPosition();
        },
        
        cacheDom: function() {
            this.$nav = $('.contentpilot-nav');
            this.$navItems = $('.contentpilot-nav__item');
            this.scrollThreshold = 100;
        },
        
        bindEvents: function() {
            const self = this;
            
            // スクロールイベント
            $(window).on('scroll', function() {
                self.checkScrollPosition();
            });
            
            // ナビゲーションクリック
            this.$navItems.on('click', 'a', function(e) {
                e.preventDefault();
                self.smoothScroll($(this).attr('href'));
            });
        },
        
        checkScrollPosition: function() {
            const scrollTop = $(window).scrollTop();
            
            if (scrollTop > this.scrollThreshold) {
                this.$nav.addClass('is-visible');
            } else {
                this.$nav.removeClass('is-visible');
            }
        },
        
        smoothScroll: function(target) {
            const $target = $(target);
            
            if ($target.length) {
                const headerOffset = 80;
                const offsetTop = $target.offset().top - headerOffset;
                
                $('html, body').animate({
                    scrollTop: offsetTop
                }, 500);
            }
        }
    };
    
    // 初期化
    $(document).ready(function() {
        ContentPilot.init();
    });
    
})(jQuery);
```

---

## テスト要件

### 動作確認が必須の環境

#### WordPressテーマ
- [ ] SWELL
- [ ] JIN
- [ ] JIN:R
- [ ] SANGO
- [ ] AFFINGER
- [ ] Cocoon
- [ ] THE THOR
- [ ] Twenty Twenty-Four（デフォルトテーマ）

#### 目次プラグイン
- [ ] Table of Contents Plus
- [ ] Easy Table of Contents
- [ ] Rich Table of Contents
- [ ] LuckyWP Table of Contents
- [ ] プラグインなし（H2自動生成）

#### ブラウザ
- [ ] Google Chrome（最新版）
- [ ] Firefox（最新版）
- [ ] Safari（最新版）
- [ ] Microsoft Edge（最新版）

#### デバイス
- [ ] デスクトップ（1920x1080）
- [ ] タブレット（768x1024）
- [ ] スマートフォン（375x667）

### テストケース

#### 1. 基本機能テスト
```
- [ ] プラグインの有効化・無効化が正常に動作する
- [ ] 管理画面にContentPilot設定が表示される
- [ ] 投稿編集画面にサイドバーが表示される
- [ ] フロントエンドで固定ナビが表示される
- [ ] 固定ナビのリンクが正常に動作する
```

#### 2. 目次検出テスト
```
- [ ] SWELL目次ブロックを検出できる
- [ ] JIN目次を検出できる
- [ ] TOC+を検出できる
- [ ] Easy TOCを検出できる
- [ ] 目次がない場合、H2から自動生成される
- [ ] H2が2個未満の場合、ナビが表示されない
```

#### 3. H2選択機能テスト
```
- [ ] 投稿編集画面で全H2が表示される
- [ ] チェックボックスで選択できる
- [ ] 選択したH2のみが固定ナビに表示される
- [ ] 選択を保存できる
- [ ] 選択を読み込める
```

#### 4. デザインプリセットテスト
```
- [ ] 全プリセットが正常に表示される
- [ ] カスタマイザーで変更できる
- [ ] 変更が即座に反映される
- [ ] 投稿個別でプリセットを変更できる
```

#### 5. レスポンシブテスト
```
- [ ] デスクトップで正常に表示される
- [ ] タブレットで横スクロールが動作する
- [ ] スマホで横スクロールが動作する
- [ ] タッチ操作でスクロールできる
```

#### 6. パフォーマンステスト
```
- [ ] ページ読み込み速度への影響が最小限
- [ ] スクロール時の動作が滑らか
- [ ] 大量のH2（50個以上）でも動作する
```

#### 7. セキュリティテスト
```
- [ ] XSS攻撃への耐性がある
- [ ] CSRF攻撃への耐性がある
- [ ] SQLインジェクションへの耐性がある
- [ ] 権限のないユーザーは設定できない
```

---

## パフォーマンス最適化

### 必須実装事項

#### 1. 条件付きアセット読み込み
```php
// 投稿ページでのみ読み込む
function contentpilot_enqueue_scripts() {
    if ( ! is_singular( 'post' ) ) {
        return;
    }
    
    // 文字数チェック
    $post_id = get_the_ID();
    $content = get_post_field( 'post_content', $post_id );
    $word_count = str_word_count( wp_strip_all_tags( $content ) );
    $min_words = get_option( 'contentpilot_min_word_count', 3000 );
    
    if ( $word_count < $min_words ) {
        return;
    }
    
    // アセット読み込み
    wp_enqueue_style( 'contentpilot-frontend', ... );
    wp_enqueue_script( 'contentpilot-frontend', ... );
}
```

#### 2. キャッシュの活用
```php
// 見出しデータをキャッシュ
function contentpilot_get_headings( $post_id ) {
    $cache_key = 'contentpilot_headings_' . $post_id;
    $headings = wp_cache_get( $cache_key );
    
    if ( false === $headings ) {
        // 検出処理
        $headings = $detector->detect_toc( $post_id );
        
        // キャッシュに保存（1時間）
        wp_cache_set( $cache_key, $headings, '', HOUR_IN_SECONDS );
    }
    
    return $headings;
}
```

#### 3. CSS/JSの最小化
```php
// 本番環境では .min.css / .min.js を使用
function contentpilot_get_asset_suffix() {
    return ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
}

$suffix = contentpilot_get_asset_suffix();
wp_enqueue_style( 
    'contentpilot-frontend', 
    plugins_url( 'assets/css/frontend' . $suffix . '.css', __FILE__ )
);
```

---

## Git管理

### ブランチ戦略
```
main          # 本番リリース用
  ↑
develop       # 開発用メインブランチ
  ↑
feature/xxx   # 機能開発用ブランチ
```

### コミットメッセージ
```
[種別] 変更内容

種別:
- feat: 新機能
- fix: バグ修正
- docs: ドキュメント変更
- style: コードフォーマット
- refactor: リファクタリング
- test: テスト追加
- chore: ビルド設定等

例:
feat: H2選択機能を追加
fix: SWELL目次の検出が失敗する問題を修正
docs: README.mdにインストール手順を追加
```

### .gitignore
```
# WordPress
wp-config.php
wp-content/uploads/
wp-content/cache/

# OS
.DS_Store
Thumbs.db

# IDE
.vscode/
.idea/

# Node
node_modules/
npm-debug.log

# Composer
vendor/

# Build
*.min.css
*.min.js
```

---

## 禁止事項

### 絶対にやってはいけないこと

#### 1. セキュリティ違反
```php
// ❌ BAD: eval()の使用
eval( $_POST['code'] );

// ❌ BAD: base64エンコードされたコード
$code = base64_decode( 'ZXZhbCgkX1BPU1RbJ2NvZGUnXSk7' );

// ❌ BAD: 外部サーバーへの無断通信
file_get_contents( 'http://example.com/track.php?user=' . $user_id );
```

#### 2. WordPress Core の改変
```php
// ❌ BAD: wp-config.phpの直接編集
file_put_contents( ABSPATH . 'wp-config.php', $new_config );

// ❌ BAD: WordPress Coreファイルの変更
file_put_contents( ABSPATH . 'wp-includes/post.php', $modified_code );
```

#### 3. グローバルスコープの汚染
```php
// ❌ BAD: プレフィックスなしのグローバル変数
$headings = array();

// ✅ GOOD: プレフィックス付き
$contentpilot_headings = array();

// ✅ BETTER: クラス内で管理
class ContentPilot_Main {
    private $headings = array();
}
```

#### 4. 直接SQL実行
```php
// ❌ BAD: 直接クエリ（SQLインジェクションの危険）
global $wpdb;
$results = $wpdb->query( "SELECT * FROM {$wpdb->posts} WHERE ID = {$_GET['id']}" );

// ✅ GOOD: Prepared Statements
$results = $wpdb->get_results( 
    $wpdb->prepare( "SELECT * FROM {$wpdb->posts} WHERE ID = %d", $_GET['id'] )
);
```

---

## デバッグ

### 開発時のデバッグ設定
```php
// wp-config.php に追加（開発環境のみ）
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
define( 'SCRIPT_DEBUG', true );
```

### ログ出力
```php
// エラーログに出力
if ( WP_DEBUG ) {
    error_log( 'ContentPilot: ' . print_r( $data, true ) );
}

// 開発者向けメッセージ
if ( current_user_can( 'manage_options' ) ) {
    echo '<!-- ContentPilot Debug: ' . esc_html( $debug_message ) . ' -->';
}
```

---

## リリースチェックリスト

### リリース前に必ず確認

#### コード品質
- [ ] すべての関数にPHPDocが記述されている
- [ ] コーディング規約に準拠している
- [ ] セキュリティチェックを通過している
- [ ] 翻訳関数が正しく使用されている
- [ ] デバッグコードが削除されている

#### 機能テスト
- [ ] 全テストケースをクリアしている
- [ ] 主要テーマで動作確認済み
- [ ] 主要ブラウザで動作確認済み
- [ ] レスポンシブ対応を確認済み

#### ドキュメント
- [ ] readme.txt が最新
- [ ] LICENSE ファイルが存在する
- [ ] CHANGELOG が更新されている
- [ ] 使い方マニュアルが用意されている

#### アセット
- [ ] CSS/JSが最小化されている
- [ ] 画像が最適化されている
- [ ] 不要なファイルが削除されている

---

## サポート・問い合わせ

### 開発中の質問
プラグイン開発中に不明点があれば、以下を参考にしてください:

- WordPress Plugin Handbook: https://ja.wordpress.org/team/handbook/plugin-development
- WordPress Developer Resources: https://developer.wordpress.org/
- WordPress Stack Exchange: https://wordpress.stackexchange.com/

### 本仕様書について
この仕様書に関する質問や提案があれば、プロジェクトオーナーに連絡してください。

---

**最終更新: 2025-02-05**
**バージョン: 1.0.0**
