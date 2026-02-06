# WordPress開発ガイド - ContentPilot用

## 📖 このファイルの使い方

**🚨 重要：このファイルを読まずにコードを生成してはいけない**

### 使用手順
1. **コードを書く前**に該当セクションを読む
2. **チェックリスト**をすべて確認する
3. **サンプルコード**をコピーして改変する（ゼロから書かない）
4. **NGパターン**に該当しないか確認する

### なぜこのファイルが必要か
- WordPress公式ガイドラインは膨大で読み切れない
- セキュリティホールを作らないため
- WordPress審査に通るコードを書くため
- 後からのバグ修正コストを削減するため

---

## 📑 目次

1. [必須チェックリスト](#1-必須チェックリスト)
2. [プラグイン基本構造](#2-プラグイン基本構造)
3. [セキュリティパターン](#3-セキュリティパターン)
4. [データベース操作](#4-データベース操作)
5. [フック使用方法](#5-フック使用方法)
6. [管理画面追加](#6-管理画面追加)
7. [カスタムフィールド](#7-カスタムフィールド)
8. [Ajax実装](#8-ajax実装)
9. [アセット読み込み](#9-アセット読み込み)
10. [翻訳対応](#10-翻訳対応)
11. [NGパターン集](#11-ngパターン集)
12. [状況別クイックリファレンス](#12-状況別クイックリファレンス)

---

## 1. 必須チェックリスト

### コード生成前のチェック（全ての機能で必須）

```
[ ] プラグインヘッダーは正しく書かれているか？
[ ] すべてのグローバル関数に contentpilot_ プレフィックスが付いているか？
[ ] すべてのクラス名に ContentPilot_ プレフィックスが付いているか？
[ ] フォーム送信にnonce検証を実装したか？
[ ] ユーザー入力をサニタイズしたか？
[ ] 出力をエスケープしたか？
[ ] 権限チェックを実装したか？
[ ] 直接SQLを書いていないか？（必ずprepare()を使う）
[ ] 翻訳関数を使っているか？
[ ] PHPDocブロックを書いたか？
```

### セキュリティチェック（フォーム・Ajax・保存処理で必須）

```
[ ] wp_verify_nonce() でnonce検証をしているか？
[ ] current_user_can() で権限確認をしているか？
[ ] sanitize_*() 関数でサニタイズしているか？
[ ] esc_*() 関数でエスケープしているか？
[ ] $wpdb->prepare() でSQLを実行しているか？
```

---

## 2. プラグイン基本構造

### 2-1. プラグインメインファイル（contentpilot.php）

#### ❌ 絶対にこう書かない

```php
<?php
// プラグインヘッダーが不完全
/**
 * Plugin Name: ContentPilot
 */

// グローバルスコープを汚染
function init() {
    // 処理
}
add_action('init', 'init');
```

#### ✅ 必ずこう書く

```php
<?php
/**
 * Plugin Name:       ContentPilot
 * Plugin URI:        https://example.com/contentpilot
 * Description:       長文記事の離脱を防ぐ、目次連携型の固定ナビゲーション
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Your Name
 * Author URI:        https://example.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       contentpilot
 * Domain Path:       /languages
 */

// 直接アクセスを防ぐ
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 定数定義
define( 'CONTENTPILOT_VERSION', '1.0.0' );
define( 'CONTENTPILOT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CONTENTPILOT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// クラスファイルの読み込み
require_once CONTENTPILOT_PLUGIN_DIR . 'includes/class-contentpilot.php';

// プラグイン初期化
function contentpilot_init() {
    $plugin = new ContentPilot_Main();
    $plugin->init();
}
add_action( 'plugins_loaded', 'contentpilot_init' );

// アクティベーション処理
function contentpilot_activate() {
    // 初期設定を保存
    add_option( 'contentpilot_default_preset', 'simple' );
    add_option( 'contentpilot_position', 'top' );
    add_option( 'contentpilot_min_word_count', 3000 );
    
    // フラッシュ（必要な場合のみ）
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'contentpilot_activate' );

// ディアクティベーション処理
function contentpilot_deactivate() {
    // フラッシュ（必要な場合のみ）
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'contentpilot_deactivate' );

// アンインストール処理（別ファイル uninstall.php で定義）
```

### 2-2. メインクラスファイル（includes/class-contentpilot.php）

#### ✅ クラスの基本構造

```php
<?php
/**
 * ContentPilot メインクラス
 *
 * @package ContentPilot
 * @since 1.0.0
 */

// 直接アクセスを防ぐ
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class ContentPilot_Main
 *
 * プラグインのメイン機能を管理
 */
class ContentPilot_Main {
    
    /**
     * シングルトンインスタンス
     *
     * @var ContentPilot_Main
     */
    private static $instance = null;
    
    /**
     * プラグインバージョン
     *
     * @var string
     */
    private $version;
    
    /**
     * コンストラクタ
     */
    private function __construct() {
        $this->version = CONTENTPILOT_VERSION;
    }
    
    /**
     * インスタンスを取得
     *
     * @return ContentPilot_Main
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * 初期化
     */
    public function init() {
        // フックを登録
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'wp_footer', array( $this, 'render_navigation' ) );
    }
    
    /**
     * スクリプトとスタイルを読み込む
     */
    public function enqueue_scripts() {
        // 投稿ページ以外は読み込まない
        if ( ! is_singular( 'post' ) ) {
            return;
        }
        
        // CSS
        wp_enqueue_style(
            'contentpilot-frontend',
            CONTENTPILOT_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            $this->version
        );
        
        // JavaScript
        wp_enqueue_script(
            'contentpilot-frontend',
            CONTENTPILOT_PLUGIN_URL . 'assets/js/frontend.js',
            array( 'jquery' ),
            $this->version,
            true
        );
        
        // データをJSに渡す
        wp_localize_script(
            'contentpilot-frontend',
            'contentpilotData',
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'contentpilot_nonce' ),
            )
        );
    }
    
    /**
     * ナビゲーションを出力
     */
    public function render_navigation() {
        // 投稿ページ以外は表示しない
        if ( ! is_singular( 'post' ) ) {
            return;
        }
        
        $post_id = get_the_ID();
        
        // プラグインが無効化されている場合は表示しない
        $enabled = get_post_meta( $post_id, '_contentpilot_enabled', true );
        if ( ! $enabled ) {
            return;
        }
        
        // 出力
        include CONTENTPILOT_PLUGIN_DIR . 'templates/navigation.php';
    }
}
```

### 2-3. アンインストール処理（uninstall.php）

#### ✅ 正しいアンインストール処理

```php
<?php
/**
 * プラグインアンインストール時の処理
 *
 * @package ContentPilot
 */

// WordPressからの呼び出しでない場合は終了
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// オプションを削除
delete_option( 'contentpilot_default_preset' );
delete_option( 'contentpilot_position' );
delete_option( 'contentpilot_min_word_count' );

// すべての投稿からメタデータを削除
global $wpdb;
$wpdb->query(
    "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_contentpilot_%'"
);

// キャッシュをクリア
wp_cache_flush();
```

---

## 3. セキュリティパターン

### 3-1. 入力のサニタイズ（必須）

**ルール：ユーザーからの入力は絶対に信用しない**

| 入力の種類 | 使う関数 | コード例 |
|-----------|---------|---------|
| テキスト（1行） | `sanitize_text_field()` | `$text = sanitize_text_field( $_POST['text'] );` |
| テキスト（複数行） | `sanitize_textarea_field()` | `$content = sanitize_textarea_field( $_POST['content'] );` |
| HTML（限定タグのみ） | `wp_kses_post()` | `$html = wp_kses_post( $_POST['html'] );` |
| URL | `esc_url_raw()` | `$url = esc_url_raw( $_POST['url'] );` |
| 整数 | `absint()` | `$id = absint( $_POST['id'] );` |
| 真偽値 | `(bool)` または `rest_sanitize_boolean()` | `$enabled = (bool) $_POST['enabled'];` |
| メールアドレス | `sanitize_email()` | `$email = sanitize_email( $_POST['email'] );` |
| ファイル名 | `sanitize_file_name()` | `$filename = sanitize_file_name( $_POST['filename'] );` |
| HTMLクラス名 | `sanitize_html_class()` | `$class = sanitize_html_class( $_POST['class'] );` |
| スラッグ | `sanitize_title()` | `$slug = sanitize_title( $_POST['slug'] );` |

#### ❌ 悪い例

```php
// サニタイズなし - 危険！
$value = $_POST['value'];
update_option( 'contentpilot_setting', $value );
```

#### ✅ 良い例

```php
// 必ずサニタイズ
$value = sanitize_text_field( $_POST['value'] );
update_option( 'contentpilot_setting', $value );
```

### 3-2. 出力のエスケープ（必須）

**ルール：すべての出力はエスケープする**

| 出力先 | 使う関数 | コード例 |
|--------|---------|---------|
| HTML本文（テキスト） | `esc_html()` | `<p><?php echo esc_html( $text ); ?></p>` |
| HTML本文（翻訳） | `esc_html__()` / `esc_html_e()` | `<p><?php esc_html_e( 'Text', 'contentpilot' ); ?></p>` |
| HTML属性値 | `esc_attr()` | `<div class="<?php echo esc_attr( $class ); ?>">` |
| HTML属性（翻訳） | `esc_attr__()` / `esc_attr_e()` | `<input placeholder="<?php esc_attr_e( 'Enter', 'contentpilot' ); ?>">` |
| URL | `esc_url()` | `<a href="<?php echo esc_url( $url ); ?>">` |
| JavaScript内 | `wp_json_encode()` | `var data = <?php echo wp_json_encode( $data ); ?>;` |
| テキストエリア | `esc_textarea()` | `<textarea><?php echo esc_textarea( $text ); ?></textarea>` |
| SQL文（LIKE） | `$wpdb->esc_like()` | `$wpdb->prepare( "... LIKE %s", $wpdb->esc_like( $term ) . '%' )` |

#### ❌ 悪い例

```php
// エスケープなし - XSS脆弱性！
echo '<p>' . $user_input . '</p>';
echo '<div class="' . $class_name . '">';
```

#### ✅ 良い例

```php
// 必ずエスケープ
echo '<p>' . esc_html( $user_input ) . '</p>';
echo '<div class="' . esc_attr( $class_name ) . '">';
```

### 3-3. Nonce検証（フォーム送信・Ajax必須）

**ルール：すべてのフォーム送信とAjax処理にはnonce検証が必要**

#### ✅ フォームでの使用

```php
// フォームHTML（nonce生成）
<form method="post" action="">
    <?php wp_nonce_field( 'contentpilot_save_settings', 'contentpilot_nonce' ); ?>
    <input type="text" name="setting_value" />
    <button type="submit">保存</button>
</form>

// フォーム処理（nonce検証）
if ( isset( $_POST['contentpilot_nonce'] ) ) {
    // nonce検証
    if ( ! wp_verify_nonce( $_POST['contentpilot_nonce'], 'contentpilot_save_settings' ) ) {
        wp_die( __( 'Security check failed', 'contentpilot' ) );
    }
    
    // 権限確認
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'Permission denied', 'contentpilot' ) );
    }
    
    // 処理を続行
    $value = sanitize_text_field( $_POST['setting_value'] );
    update_option( 'contentpilot_setting', $value );
}
```

#### ✅ Ajaxでの使用

```php
// JavaScript側（nonce送信）
jQuery.ajax({
    url: contentpilotData.ajaxUrl,
    type: 'POST',
    data: {
        action: 'contentpilot_save_data',
        nonce: contentpilotData.nonce,
        value: inputValue
    },
    success: function(response) {
        console.log(response);
    }
});

// PHP側（nonce検証）
function contentpilot_ajax_save_data() {
    // nonce検証
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'contentpilot_nonce' ) ) {
        wp_send_json_error( array( 'message' => 'Security check failed' ) );
    }
    
    // 権限確認
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( array( 'message' => 'Permission denied' ) );
    }
    
    // 処理
    $value = sanitize_text_field( $_POST['value'] );
    
    wp_send_json_success( array( 'message' => 'Saved' ) );
}
add_action( 'wp_ajax_contentpilot_save_data', 'contentpilot_ajax_save_data' );
```

### 3-4. 権限チェック（必須）

**ルール：すべての管理操作に権限チェックが必要**

| 操作 | 必要な権限 | チェック方法 |
|------|-----------|-------------|
| プラグイン設定の変更 | `manage_options` | `current_user_can( 'manage_options' )` |
| 投稿の編集 | `edit_post` | `current_user_can( 'edit_post', $post_id )` |
| 投稿の作成 | `edit_posts` | `current_user_can( 'edit_posts' )` |
| 投稿の削除 | `delete_post` | `current_user_can( 'delete_post', $post_id )` |
| メディアアップロード | `upload_files` | `current_user_can( 'upload_files' )` |

#### ✅ 使用例

```php
// 設定保存
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( __( 'You do not have permission to access this page.', 'contentpilot' ) );
}

// 投稿編集
if ( ! current_user_can( 'edit_post', $post_id ) ) {
    return;
}
```

---

## 4. データベース操作

### 4-1. オプションの操作

#### ✅ オプションの取得・保存・削除

```php
// 取得（デフォルト値あり）
$preset = get_option( 'contentpilot_default_preset', 'simple' );

// 保存
update_option( 'contentpilot_default_preset', 'modern' );

// 追加（既に存在する場合は何もしない）
add_option( 'contentpilot_default_preset', 'simple' );

// 削除
delete_option( 'contentpilot_default_preset' );
```

### 4-2. 投稿メタの操作

#### ✅ メタデータの取得・保存・削除

```php
// 取得（単一の値）
$enabled = get_post_meta( $post_id, '_contentpilot_enabled', true );

// 取得（配列）
$selected_h2 = get_post_meta( $post_id, '_contentpilot_selected_h2', true );

// 保存
update_post_meta( $post_id, '_contentpilot_enabled', true );

// 削除
delete_post_meta( $post_id, '_contentpilot_enabled' );
```

### 4-3. カスタムクエリ（必ず prepare() を使う）

#### ❌ 絶対にこう書かない（SQLインジェクション脆弱性）

```php
global $wpdb;
$post_id = $_GET['id'];
$results = $wpdb->get_results( "SELECT * FROM {$wpdb->posts} WHERE ID = $post_id" );
```

#### ✅ 必ずこう書く

```php
global $wpdb;
$post_id = absint( $_GET['id'] );

// prepare()を使う
$results = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->posts} WHERE ID = %d",
        $post_id
    )
);
```

#### ✅ 複数のプレースホルダー

```php
global $wpdb;

// %d = 整数、%s = 文字列、%f = 浮動小数点
$results = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->posts} 
         WHERE post_type = %s 
         AND post_status = %s 
         AND ID > %d",
        'post',
        'publish',
        100
    )
);
```

#### ✅ LIKE句での使用

```php
global $wpdb;
$search_term = sanitize_text_field( $_GET['s'] );

// esc_like()を使う
$like = '%' . $wpdb->esc_like( $search_term ) . '%';

$results = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->posts} WHERE post_title LIKE %s",
        $like
    )
);
```

### 4-4. wpdbメソッド一覧

| メソッド | 用途 | 返り値 |
|---------|------|--------|
| `get_results()` | 複数行を取得 | オブジェクトの配列 |
| `get_row()` | 1行を取得 | オブジェクト |
| `get_col()` | 1列を取得 | 値の配列 |
| `get_var()` | 単一の値を取得 | スカラー値 |
| `insert()` | 行を挿入 | 挿入された行数 |
| `update()` | 行を更新 | 更新された行数 |
| `delete()` | 行を削除 | 削除された行数 |
| `query()` | その他のクエリ実行 | 影響を受けた行数 |

---

## 5. フック使用方法

### 5-1. アクションフック

#### ✅ 基本的な使い方

```php
/**
 * スクリプトを読み込む
 */
function contentpilot_enqueue_scripts() {
    wp_enqueue_style( 'contentpilot-style', ... );
}
add_action( 'wp_enqueue_scripts', 'contentpilot_enqueue_scripts' );

/**
 * 優先度を指定
 */
function contentpilot_init() {
    // 初期化処理
}
add_action( 'init', 'contentpilot_init', 10 ); // 10が優先度（デフォルト）

/**
 * 複数の引数を受け取る
 */
function contentpilot_save_post( $post_id, $post, $update ) {
    // 保存処理
}
add_action( 'save_post', 'contentpilot_save_post', 10, 3 ); // 3つの引数を受け取る
```

#### ✅ クラス内でのフック登録

```php
class ContentPilot_Admin {
    
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }
    
    public function add_menu_page() {
        // メニュー追加
    }
    
    public function register_settings() {
        // 設定登録
    }
}
```

### 5-2. フィルターフック

#### ✅ 基本的な使い方

```php
/**
 * コンテンツを加工
 */
function contentpilot_modify_content( $content ) {
    // コンテンツを変更
    $content = $content . '<div>追加コンテンツ</div>';
    return $content;
}
add_filter( 'the_content', 'contentpilot_modify_content' );

/**
 * 複数の引数を受け取る
 */
function contentpilot_custom_excerpt_length( $length ) {
    return 50; // 抜粋の長さを変更
}
add_filter( 'excerpt_length', 'contentpilot_custom_excerpt_length' );
```

### 5-3. カスタムフック

#### ✅ 自分のプラグインでフックを提供

```php
// フックを実行する側
function contentpilot_render_navigation() {
    $headings = array( ... );
    
    // フィルターで加工可能にする
    $headings = apply_filters( 'contentpilot_headings', $headings );
    
    // アクションで拡張可能にする
    do_action( 'contentpilot_before_render', $headings );
    
    // 出力
    echo '<nav>...</nav>';
    
    do_action( 'contentpilot_after_render', $headings );
}

// 他の開発者が使う側（拡張例）
add_filter( 'contentpilot_headings', function( $headings ) {
    // 見出しに何か追加
    $headings[] = array( 'text' => 'カスタム', 'id' => 'custom' );
    return $headings;
} );
```

### 5-4. よく使うフック一覧

| フック名 | タイミング | 用途 |
|---------|-----------|------|
| `plugins_loaded` | プラグイン読み込み後 | プラグイン初期化 |
| `init` | WordPress初期化時 | カスタム投稿タイプ登録等 |
| `admin_init` | 管理画面初期化時 | 設定登録等 |
| `admin_menu` | 管理メニュー生成時 | メニュー追加 |
| `wp_enqueue_scripts` | フロントエンドアセット読み込み時 | CSS/JS読み込み |
| `admin_enqueue_scripts` | 管理画面アセット読み込み時 | 管理画面CSS/JS読み込み |
| `save_post` | 投稿保存時 | カスタムフィールド保存 |
| `wp_footer` | フッター出力時 | HTML追加 |
| `the_content` | コンテンツ出力時 | コンテンツ加工 |

---

## 6. 管理画面追加

### 6-1. 管理メニューの追加

#### ✅ トップレベルメニュー

```php
/**
 * 管理メニューを追加
 */
function contentpilot_add_admin_menu() {
    add_menu_page(
        __( 'ContentPilot Settings', 'contentpilot' ),     // ページタイトル
        __( 'ContentPilot', 'contentpilot' ),              // メニュータイトル
        'manage_options',                                   // 必要な権限
        'contentpilot-settings',                           // メニュースラッグ
        'contentpilot_settings_page',                      // コールバック関数
        'dashicons-admin-generic',                         // アイコン
        80                                                  // メニュー位置
    );
}
add_action( 'admin_menu', 'contentpilot_add_admin_menu' );

/**
 * 設定ページの内容
 */
function contentpilot_settings_page() {
    // 権限チェック
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'You do not have permission to access this page.', 'contentpilot' ) );
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'contentpilot_settings' );
            do_settings_sections( 'contentpilot-settings' );
            submit_button();
            ?>
        </form>
    </div>
    <?php
}
```

#### ✅ サブメニュー

```php
/**
 * サブメニューを追加
 */
function contentpilot_add_submenu() {
    add_submenu_page(
        'contentpilot-settings',                           // 親メニューのスラッグ
        __( 'Advanced Settings', 'contentpilot' ),         // ページタイトル
        __( 'Advanced', 'contentpilot' ),                  // メニュータイトル
        'manage_options',                                   // 必要な権限
        'contentpilot-advanced',                           // メニュースラッグ
        'contentpilot_advanced_page'                       // コールバック関数
    );
}
add_action( 'admin_menu', 'contentpilot_add_submenu' );
```

### 6-2. 設定の登録

#### ✅ Settings API の使用

```php
/**
 * 設定を登録
 */
function contentpilot_register_settings() {
    // 設定グループを登録
    register_setting(
        'contentpilot_settings',                 // オプショングループ
        'contentpilot_default_preset',          // オプション名
        array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'simple',
        )
    );
    
    // セクションを追加
    add_settings_section(
        'contentpilot_main_section',            // セクションID
        __( 'Main Settings', 'contentpilot' ),  // セクションタイトル
        'contentpilot_section_callback',        // コールバック
        'contentpilot-settings'                 // ページスラッグ
    );
    
    // フィールドを追加
    add_settings_field(
        'contentpilot_default_preset',          // フィールドID
        __( 'Default Preset', 'contentpilot' ), // フィールドラベル
        'contentpilot_preset_field_callback',   // コールバック
        'contentpilot-settings',                // ページスラッグ
        'contentpilot_main_section'             // セクションID
    );
}
add_action( 'admin_init', 'contentpilot_register_settings' );

/**
 * セクションの説明
 */
function contentpilot_section_callback() {
    echo '<p>' . esc_html__( 'Configure the default settings for ContentPilot.', 'contentpilot' ) . '</p>';
}

/**
 * フィールドの表示
 */
function contentpilot_preset_field_callback() {
    $preset = get_option( 'contentpilot_default_preset', 'simple' );
    $presets = array(
        'simple' => __( 'Simple', 'contentpilot' ),
        'modern' => __( 'Modern', 'contentpilot' ),
        'flat'   => __( 'Flat', 'contentpilot' ),
        'dark'   => __( 'Dark', 'contentpilot' ),
    );
    ?>
    <select name="contentpilot_default_preset">
        <?php foreach ( $presets as $value => $label ) : ?>
            <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $preset, $value ); ?>>
                <?php echo esc_html( $label ); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php
}
```

---

## 7. カスタムフィールド

### 7-1. メタボックスの追加

#### ✅ 基本的なメタボックス

```php
/**
 * メタボックスを追加
 */
function contentpilot_add_meta_boxes() {
    add_meta_box(
        'contentpilot_settings',                           // メタボックスID
        __( 'ContentPilot Settings', 'contentpilot' ),     // タイトル
        'contentpilot_meta_box_callback',                  // コールバック
        'post',                                             // 投稿タイプ
        'side',                                             // 位置（side/normal/advanced）
        'default'                                           // 優先度（default/high/low）
    );
}
add_action( 'add_meta_boxes', 'contentpilot_add_meta_boxes' );

/**
 * メタボックスの内容
 */
function contentpilot_meta_box_callback( $post ) {
    // nonce生成
    wp_nonce_field( 'contentpilot_save_meta', 'contentpilot_meta_nonce' );
    
    // 現在の値を取得
    $enabled = get_post_meta( $post->ID, '_contentpilot_enabled', true );
    $selected_h2 = get_post_meta( $post->ID, '_contentpilot_selected_h2', true );
    
    if ( ! is_array( $selected_h2 ) ) {
        $selected_h2 = array();
    }
    ?>
    <p>
        <label>
            <input type="checkbox" name="contentpilot_enabled" value="1" <?php checked( $enabled, 1 ); ?> />
            <?php esc_html_e( 'Enable ContentPilot on this page', 'contentpilot' ); ?>
        </label>
    </p>
    
    <p>
        <strong><?php esc_html_e( 'Select headings to display:', 'contentpilot' ); ?></strong>
    </p>
    
    <?php
    // H2見出しを取得
    $headings = contentpilot_get_h2_headings( $post->ID );
    
    if ( ! empty( $headings ) ) {
        foreach ( $headings as $index => $heading ) {
            $checked = in_array( $index, $selected_h2, true );
            ?>
            <label style="display:block; margin-bottom:5px;">
                <input type="checkbox" 
                       name="contentpilot_selected_h2[]" 
                       value="<?php echo esc_attr( $index ); ?>" 
                       <?php checked( $checked, true ); ?> />
                <?php echo esc_html( $heading['text'] ); ?>
            </label>
            <?php
        }
    } else {
        echo '<p>' . esc_html__( 'No H2 headings found.', 'contentpilot' ) . '</p>';
    }
}

/**
 * メタデータの保存
 */
function contentpilot_save_meta( $post_id ) {
    // 自動保存の場合は何もしない
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    
    // nonce検証
    if ( ! isset( $_POST['contentpilot_meta_nonce'] ) || 
         ! wp_verify_nonce( $_POST['contentpilot_meta_nonce'], 'contentpilot_save_meta' ) ) {
        return;
    }
    
    // 権限チェック
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }
    
    // 有効/無効を保存
    $enabled = isset( $_POST['contentpilot_enabled'] ) ? 1 : 0;
    update_post_meta( $post_id, '_contentpilot_enabled', $enabled );
    
    // 選択されたH2を保存
    $selected_h2 = array();
    if ( isset( $_POST['contentpilot_selected_h2'] ) && is_array( $_POST['contentpilot_selected_h2'] ) ) {
        $selected_h2 = array_map( 'absint', $_POST['contentpilot_selected_h2'] );
    }
    update_post_meta( $post_id, '_contentpilot_selected_h2', $selected_h2 );
}
add_action( 'save_post', 'contentpilot_save_meta' );
```

### 7-2. register_post_meta の使用（ブロックエディタ対応）

#### ✅ REST API経由で使えるメタフィールド

```php
/**
 * カスタムフィールドを登録
 */
function contentpilot_register_post_meta() {
    register_post_meta(
        'post',                          // 投稿タイプ
        '_contentpilot_enabled',        // メタキー
        array(
            'type'              => 'boolean',
            'description'       => __( 'Enable ContentPilot', 'contentpilot' ),
            'single'            => true,
            'default'           => true,
            'show_in_rest'      => true,  // REST APIで利用可能にする
            'sanitize_callback' => 'rest_sanitize_boolean',
            'auth_callback'     => function() {
                return current_user_can( 'edit_posts' );
            },
        )
    );
    
    register_post_meta(
        'post',
        '_contentpilot_selected_h2',
        array(
            'type'              => 'array',
            'description'       => __( 'Selected H2 headings', 'contentpilot' ),
            'single'            => true,
            'default'           => array(),
            'show_in_rest'      => array(
                'schema' => array(
                    'type'  => 'array',
                    'items' => array(
                        'type' => 'integer',
                    ),
                ),
            ),
            'sanitize_callback' => function( $value ) {
                if ( ! is_array( $value ) ) {
                    return array();
                }
                return array_map( 'absint', $value );
            },
            'auth_callback'     => function() {
                return current_user_can( 'edit_posts' );
            },
        )
    );
}
add_action( 'init', 'contentpilot_register_post_meta' );
```

---

## 8. Ajax実装

### 8-1. 基本的なAjax処理

#### ✅ JavaScript側

```javascript
jQuery(document).ready(function($) {
    $('#save-button').on('click', function(e) {
        e.preventDefault();
        
        var data = {
            action: 'contentpilot_save_data',
            nonce: contentpilotData.nonce,
            post_id: contentpilotData.postId,
            value: $('#input-field').val()
        };
        
        $.ajax({
            url: contentpilotData.ajaxUrl,
            type: 'POST',
            data: data,
            beforeSend: function() {
                $('#save-button').prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                } else {
                    alert('Error: ' + response.data.message);
                }
            },
            error: function() {
                alert('Ajax request failed');
            },
            complete: function() {
                $('#save-button').prop('disabled', false);
            }
        });
    });
});
```

#### ✅ PHP側

```php
/**
 * Ajaxデータをエンキュー時に渡す
 */
function contentpilot_enqueue_admin_scripts() {
    wp_enqueue_script(
        'contentpilot-admin',
        CONTENTPILOT_PLUGIN_URL . 'assets/js/admin.js',
        array( 'jquery' ),
        CONTENTPILOT_VERSION,
        true
    );
    
    wp_localize_script(
        'contentpilot-admin',
        'contentpilotData',
        array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'contentpilot_ajax_nonce' ),
            'postId'  => get_the_ID(),
        )
    );
}
add_action( 'admin_enqueue_scripts', 'contentpilot_enqueue_admin_scripts' );

/**
 * Ajax処理（ログインユーザー用）
 */
function contentpilot_ajax_save_data() {
    // nonce検証
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'contentpilot_ajax_nonce' ) ) {
        wp_send_json_error( array(
            'message' => __( 'Security check failed', 'contentpilot' ),
        ) );
    }
    
    // 権限チェック
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( array(
            'message' => __( 'Permission denied', 'contentpilot' ),
        ) );
    }
    
    // データ取得とサニタイズ
    $post_id = absint( $_POST['post_id'] );
    $value = sanitize_text_field( $_POST['value'] );
    
    // 処理
    $result = update_post_meta( $post_id, '_contentpilot_data', $value );
    
    if ( $result ) {
        wp_send_json_success( array(
            'message' => __( 'Data saved successfully', 'contentpilot' ),
        ) );
    } else {
        wp_send_json_error( array(
            'message' => __( 'Failed to save data', 'contentpilot' ),
        ) );
    }
}
add_action( 'wp_ajax_contentpilot_save_data', 'contentpilot_ajax_save_data' );

/**
 * Ajax処理（非ログインユーザー用）
 * 必要な場合のみ実装
 */
// add_action( 'wp_ajax_nopriv_contentpilot_save_data', 'contentpilot_ajax_save_data' );
```

---

## 9. アセット読み込み

### 9-1. フロントエンド

#### ✅ CSS/JSの読み込み

```php
/**
 * フロントエンドのアセットを読み込む
 */
function contentpilot_enqueue_frontend_assets() {
    // 投稿ページ以外は読み込まない
    if ( ! is_singular( 'post' ) ) {
        return;
    }
    
    // CSS
    wp_enqueue_style(
        'contentpilot-frontend',
        CONTENTPILOT_PLUGIN_URL . 'assets/css/frontend.css',
        array(),                    // 依存するスタイル
        CONTENTPILOT_VERSION,
        'all'                       // メディアタイプ
    );
    
    // JavaScript
    wp_enqueue_script(
        'contentpilot-frontend',
        CONTENTPILOT_PLUGIN_URL . 'assets/js/frontend.js',
        array( 'jquery' ),          // 依存するスクリプト
        CONTENTPILOT_VERSION,
        true                        // フッターで読み込む
    );
    
    // データをJavaScriptに渡す
    wp_localize_script(
        'contentpilot-frontend',
        'contentpilotData',
        array(
            'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
            'nonce'         => wp_create_nonce( 'contentpilot_nonce' ),
            'scrollOffset'  => 80,
            'animDuration'  => 500,
        )
    );
}
add_action( 'wp_enqueue_scripts', 'contentpilot_enqueue_frontend_assets' );
```

### 9-2. 管理画面

#### ✅ 管理画面でのアセット読み込み

```php
/**
 * 管理画面のアセットを読み込む
 */
function contentpilot_enqueue_admin_assets( $hook ) {
    // 投稿編集画面のみで読み込む
    if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
        return;
    }
    
    // CSS
    wp_enqueue_style(
        'contentpilot-admin',
        CONTENTPILOT_PLUGIN_URL . 'assets/css/admin.css',
        array(),
        CONTENTPILOT_VERSION
    );
    
    // JavaScript
    wp_enqueue_script(
        'contentpilot-admin',
        CONTENTPILOT_PLUGIN_URL . 'assets/js/admin.js',
        array( 'jquery', 'wp-blocks', 'wp-element', 'wp-editor' ),
        CONTENTPILOT_VERSION,
        true
    );
}
add_action( 'admin_enqueue_scripts', 'contentpilot_enqueue_admin_assets' );
```

### 9-3. インラインスタイル/スクリプト

#### ✅ 動的なCSSを追加

```php
/**
 * カスタムCSSを追加
 */
function contentpilot_add_inline_styles() {
    $preset = get_option( 'contentpilot_default_preset', 'simple' );
    $bg_color = contentpilot_get_preset_color( $preset );
    
    $custom_css = "
        .contentpilot-nav {
            background-color: {$bg_color};
        }
    ";
    
    wp_add_inline_style( 'contentpilot-frontend', $custom_css );
}
add_action( 'wp_enqueue_scripts', 'contentpilot_add_inline_styles' );
```

---

## 10. 翻訳対応

### 10-1. テキストドメインの設定

#### ✅ プラグイン読み込み時

```php
/**
 * テキストドメインを読み込む
 */
function contentpilot_load_textdomain() {
    load_plugin_textdomain(
        'contentpilot',
        false,
        dirname( plugin_basename( __FILE__ ) ) . '/languages'
    );
}
add_action( 'plugins_loaded', 'contentpilot_load_textdomain' );
```

### 10-2. 翻訳関数の使い分け

| 関数 | 用途 | 返り値/出力 |
|------|------|------------|
| `__()` | 翻訳を取得 | 返り値 |
| `_e()` | 翻訳を出力 | 出力 |
| `esc_html__()` | 翻訳を取得してエスケープ | 返り値 |
| `esc_html_e()` | 翻訳を出力してエスケープ | 出力 |
| `esc_attr__()` | 翻訳を取得して属性エスケープ | 返り値 |
| `esc_attr_e()` | 翻訳を出力して属性エスケープ | 出力 |
| `_n()` | 複数形対応の翻訳を取得 | 返り値 |
| `_x()` | 文脈付き翻訳を取得 | 返り値 |

#### ✅ 使用例

```php
// 基本
$text = __( 'Hello World', 'contentpilot' );
_e( 'Hello World', 'contentpilot' );

// エスケープ付き
echo esc_html__( 'Hello World', 'contentpilot' );
esc_html_e( 'Hello World', 'contentpilot' );

// 属性内
echo '<input placeholder="' . esc_attr__( 'Enter text', 'contentpilot' ) . '">';

// 複数形
printf(
    _n( '%d item', '%d items', $count, 'contentpilot' ),
    $count
);

// 文脈付き（同じ単語でも文脈で訳を変える）
$post_text = _x( 'Post', 'noun', 'contentpilot' );      // 名詞の「投稿」
$post_button = _x( 'Post', 'verb', 'contentpilot' );    // 動詞の「投稿する」
```

### 10-3. POTファイルの生成

翻訳ファイル（.pot）は以下のコマンドで生成：

```bash
wp i18n make-pot /path/to/contentpilot /path/to/contentpilot/languages/contentpilot.pot
```

---

## 11. NGパターン集

### 🚫 絶対にやってはいけないこと

#### 1. eval()の使用

```php
// ❌ 絶対NG
eval( $_POST['code'] );
eval( '$result = ' . $_GET['expression'] . ';' );
```

#### 2. base64エンコードされたコード

```php
// ❌ 絶対NG（マルウェアの可能性）
$code = base64_decode( 'ZXZhbCgkX1BPU1RbJ2NvZGUnXSk7' );
eval( $code );
```

#### 3. 外部サーバーへの無断通信

```php
// ❌ 絶対NG（プライバシー侵害）
file_get_contents( 'http://tracking.example.com/?user=' . $user_id );
wp_remote_get( 'http://ads.example.com/track.php?site=' . get_site_url() );
```

#### 4. WordPress Coreファイルの直接編集

```php
// ❌ 絶対NG
file_put_contents( ABSPATH . 'wp-config.php', $new_config );
file_put_contents( ABSPATH . 'wp-includes/post.php', $modified_code );
```

#### 5. グローバルスコープの汚染

```php
// ❌ NG - プレフィックスなし
function init() { }
$headings = array();

// ✅ OK - プレフィックス付き
function contentpilot_init() { }
$contentpilot_headings = array();

// ✅ BETTER - クラス内で管理
class ContentPilot_Main {
    private $headings = array();
}
```

#### 6. 直接SQLクエリ（prepare()なし）

```php
// ❌ 絶対NG - SQLインジェクション脆弱性
global $wpdb;
$id = $_GET['id'];
$wpdb->query( "DELETE FROM {$wpdb->posts} WHERE ID = $id" );

// ✅ OK
$id = absint( $_GET['id'] );
$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->posts} WHERE ID = %d", $id ) );
```

#### 7. エスケープなしの出力

```php
// ❌ NG - XSS脆弱性
echo '<p>' . $_POST['message'] . '</p>';
echo '<div class="' . $_GET['class'] . '">';

// ✅ OK
echo '<p>' . esc_html( $_POST['message'] ) . '</p>';
echo '<div class="' . esc_attr( $_GET['class'] ) . '">';
```

#### 8. サニタイズなしの保存

```php
// ❌ NG
update_option( 'contentpilot_setting', $_POST['value'] );

// ✅ OK
$value = sanitize_text_field( $_POST['value'] );
update_option( 'contentpilot_setting', $value );
```

#### 9. nonce検証なしのフォーム処理

```php
// ❌ NG - CSRF脆弱性
if ( isset( $_POST['submit'] ) ) {
    update_option( 'setting', $_POST['value'] );
}

// ✅ OK
if ( isset( $_POST['submit'] ) && wp_verify_nonce( $_POST['nonce'], 'action' ) ) {
    $value = sanitize_text_field( $_POST['value'] );
    update_option( 'setting', $value );
}
```

#### 10. 権限チェックなしの管理操作

```php
// ❌ NG
function contentpilot_delete_all_posts() {
    // 誰でも実行できてしまう
}

// ✅ OK
function contentpilot_delete_all_posts() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Permission denied' );
    }
    // 処理
}
```

---

## 12. 状況別クイックリファレンス

### フォーム送信を処理したい

```php
// 1. フォームHTML
<form method="post">
    <?php wp_nonce_field( 'my_action', 'my_nonce' ); ?>
    <input type="text" name="my_field" />
    <button type="submit" name="submit">送信</button>
</form>

// 2. 処理
if ( isset( $_POST['submit'] ) ) {
    // nonce検証
    if ( ! wp_verify_nonce( $_POST['my_nonce'], 'my_action' ) ) {
        wp_die( 'Security check failed' );
    }
    
    // 権限チェック
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Permission denied' );
    }
    
    // サニタイズして保存
    $value = sanitize_text_field( $_POST['my_field'] );
    update_option( 'my_option', $value );
}
```

### 投稿保存時に処理を追加したい

```php
function my_save_post( $post_id ) {
    // 自動保存は無視
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    
    // 権限チェック
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;
    
    // 処理
    $value = get_post_meta( $post_id, '_my_field', true );
    // ...
}
add_action( 'save_post', 'my_save_post' );
```

### カスタムフィールドを追加したい

```php
// 1. メタボックス登録
add_action( 'add_meta_boxes', function() {
    add_meta_box( 'my_meta', 'My Settings', 'my_meta_callback', 'post', 'side' );
} );

// 2. メタボックスHTML
function my_meta_callback( $post ) {
    wp_nonce_field( 'my_meta_save', 'my_meta_nonce' );
    $value = get_post_meta( $post->ID, '_my_field', true );
    ?>
    <input type="text" name="my_field" value="<?php echo esc_attr( $value ); ?>" />
    <?php
}

// 3. 保存処理
add_action( 'save_post', function( $post_id ) {
    if ( ! isset( $_POST['my_meta_nonce'] ) || 
         ! wp_verify_nonce( $_POST['my_meta_nonce'], 'my_meta_save' ) ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;
    
    $value = sanitize_text_field( $_POST['my_field'] );
    update_post_meta( $post_id, '_my_field', $value );
} );
```

### Ajaxで保存したい

```php
// 1. JS側
jQuery.ajax({
    url: myData.ajaxUrl,
    type: 'POST',
    data: {
        action: 'my_ajax_action',
        nonce: myData.nonce,
        value: inputValue
    },
    success: function(response) {
        if (response.success) {
            alert(response.data.message);
        }
    }
});

// 2. PHP側
add_action( 'wp_ajax_my_ajax_action', function() {
    if ( ! wp_verify_nonce( $_POST['nonce'], 'my_nonce' ) ) {
        wp_send_json_error( array( 'message' => 'Security check failed' ) );
    }
    
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( array( 'message' => 'Permission denied' ) );
    }
    
    $value = sanitize_text_field( $_POST['value'] );
    update_option( 'my_option', $value );
    
    wp_send_json_success( array( 'message' => 'Saved' ) );
} );
```

### データベースから取得したい

```php
global $wpdb;

// 複数行取得
$results = $wpdb->get_results( 
    $wpdb->prepare( 
        "SELECT * FROM {$wpdb->posts} WHERE post_type = %s", 
        'post' 
    ) 
);

// 1行取得
$post = $wpdb->get_row( 
    $wpdb->prepare( 
        "SELECT * FROM {$wpdb->posts} WHERE ID = %d", 
        $post_id 
    ) 
);

// 単一の値取得
$count = $wpdb->get_var( 
    "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'post'" 
);
```

### 管理画面にメニューを追加したい

```php
add_action( 'admin_menu', function() {
    add_menu_page(
        'My Plugin',                    // ページタイトル
        'My Plugin',                    // メニュータイトル
        'manage_options',               // 権限
        'my-plugin',                    // スラッグ
        'my_settings_page',             // コールバック
        'dashicons-admin-generic',      // アイコン
        80                              // 位置
    );
} );

function my_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Permission denied' );
    }
    ?>
    <div class="wrap">
        <h1>My Plugin Settings</h1>
        <!-- 設定内容 -->
    </div>
    <?php
}
```

---

## 📝 最終チェックリスト

コード生成完了後、以下を確認：

```
[ ] すべてのファイルに直接アクセス防止コードがある
[ ] すべての関数/クラスにPHPDocブロックがある
[ ] プレフィックスが正しく付いている
[ ] nonce検証が実装されている
[ ] 権限チェックが実装されている
[ ] サニタイズが実装されている
[ ] エスケープが実装されている
[ ] prepare()でSQLを実行している
[ ] 翻訳関数を使っている
[ ] NGパターンに該当しない
```

---

**このガイドを読んでからコードを生成すること！**

WordPress公式ガイドラインに準拠した、安全で高品質なプラグインを作成しましょう。
