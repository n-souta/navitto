<?php
/**
 * プラグインアンインストール時の処理
 *
 * WordPressの「削除」ボタンからプラグインを削除した際に実行される
 * データベースに保存されたオプションやメタデータを完全に削除する
 *
 * @package ContentPilot
 * @since   1.0.0
 */

// WordPressからの呼び出しでない場合は終了
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * プラグインのオプションを削除
 */
delete_option( 'contentpilot_default_preset' );
delete_option( 'contentpilot_position' );
delete_option( 'contentpilot_min_word_count' );

/**
 * すべての投稿からContentPilot関連のメタデータを削除
 *
 * prepare() を使用してSQLインジェクションを防止
 */
global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE %s",
		$wpdb->esc_like( '_contentpilot_' ) . '%'
	)
);

/**
 * キャッシュをクリア
 */
wp_cache_flush();