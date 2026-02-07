# WordPress 開発クイックリファレンス

## 🚨 コード生成前のチェック（必須）

### セキュリティチェック
[ ] wp_verify_nonce() でnonce検証
[ ] current_user_can() で権限確認
[ ] sanitize_*() でサニタイズ
[ ] esc_*() でエスケープ
[ ] $wpdb->prepare() でSQL実行

### 命名規則
- 関数: contentpilot_function_name
- クラス: ContentPilot_ClassName
- メタキー: _contentpilot_key_name

### NGパターン
❌ eval()
❌ base64エンコード
❌ 直接SQL（prepare()なし）
❌ エスケープなし出力
❌ プレフィックスなし

## セキュリティパターン

### 入力のサニタイズ
| 種類 | 関数 |
|------|------|
| テキスト | sanitize_text_field() |
| URL | esc_url_raw() |
| 整数 | absint() |
| HTML | wp_kses_post() |

### 出力のエスケープ
| 場所 | 関数 |
|------|------|
| HTML | esc_html() |
| 属性 | esc_attr() |
| URL | esc_url() |
| JS | wp_json_encode() |

## コードテンプレート

### フォーム処理
```php
if ( isset( $_POST['submit'] ) ) {
    if ( ! wp_verify_nonce( $_POST['nonce'], 'action' ) ) {
        wp_die( 'Security check failed' );
    }
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Permission denied' );
    }
    $value = sanitize_text_field( $_POST['value'] );
    update_option( 'option_name', $value );
}
```

### Ajax処理
```php
add_action( 'wp_ajax_my_action', function() {
    if ( ! wp_verify_nonce( $_POST['nonce'], 'my_nonce' ) ) {
        wp_send_json_error();
    }
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error();
    }
    $value = sanitize_text_field( $_POST['value'] );
    wp_send_json_success( array( 'message' => 'Saved' ) );
} );
```

### メタボックス
```php
add_meta_box(
    'contentpilot_meta',
    'ContentPilot Settings',
    'contentpilot_meta_callback',
    'post',
    'side'
);

function contentpilot_meta_callback( $post ) {
    wp_nonce_field( 'contentpilot_save', 'contentpilot_nonce' );
    $value = get_post_meta( $post->ID, '_contentpilot_field', true );
    ?>
    <input type="text" name="contentpilot_field" value="<?php echo esc_attr( $value ); ?>" />
    <?php
}

add_action( 'save_post', function( $post_id ) {
    if ( ! isset( $_POST['contentpilot_nonce'] ) || 
         ! wp_verify_nonce( $_POST['contentpilot_nonce'], 'contentpilot_save' ) ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }
    $value = sanitize_text_field( $_POST['contentpilot_field'] );
    update_post_meta( $post_id, '_contentpilot_field', $value );
} );
```

### データベース操作
```php
global $wpdb;

// 複数行取得
$results = $wpdb->get_results( 
    $wpdb->prepare( 
        "SELECT * FROM {$wpdb->posts} WHERE post_type = %s", 
        'post' 
    ) 
);

// 単一の値取得
$count = $wpdb->get_var( 
    $wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = %s",
        'publish'
    )
);
```