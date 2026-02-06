<?php
/**
 * プラグインアンインストール処理
 *
 * @package ContentPilot
 * @since   1.0.0
 */

// WordPressからの呼び出しでない場合は終了
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// オプションを削除
delete_option( 'contentpilot_default_preset' );
delete_option( 'contentpilot_position' );
delete_option( 'contentpilot_min_word_count' );

// メタデータを削除
global $wpdb;

$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE %s",
		$wpdb->esc_like( '_contentpilot_' ) . '%'
	)
);

// キャッシュをクリア
wp_cache_flush();
