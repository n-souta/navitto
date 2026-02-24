<?php
/**
 * プラグインアンインストール処理
 *
 * @package Navitto
 * @since   1.0.0
 */

// WordPressからの呼び出しでない場合は終了
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// オプション・theme_mods・投稿メタは削除しない（再インストール時に以下を保持するため）
// - ライセンスキー・ライセンス状態
// - カスタマイザーのデザイン設定（プリセット・位置・色など）
// - 投稿ごとの「固定ナビを表示する」設定
// 完全にデータを消したい場合は、オプション名 navitto_* / theme_mod navitto_* / postmeta _navitto_% を手動で削除してください。

// キャッシュをクリア
wp_cache_flush();
