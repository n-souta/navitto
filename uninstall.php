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

// オプション・theme_mods・投稿メタは削除しない（アンインストール→再インストール時も同じ挙動にするため）
// 保持するデータ: カスタマイザー設定 / 投稿ごとの固定ナビ表示設定
// 完全にデータを消したい場合は、オプション名 navitto_* / theme_mod navitto_* / postmeta _navitto_% を手動で削除してください。

// キャッシュをクリア
wp_cache_flush();
