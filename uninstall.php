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

// オプションを削除（#5 Data — 完全なクリーンアップ）
$options = array(
	'navitto_default_preset',
	'navitto_position',
	'navitto_min_word_count',
	'navitto_default_enabled',
	'navitto_db_version',
);
foreach ( $options as $option ) {
	delete_option( $option );
}

// Customizer theme_mods を削除
$theme_mods = array(
	'navitto_preset',
	'navitto_position',
	'navitto_nav_height',
	'navitto_nav_width',
	'navitto_font_weight',
	'navitto_theme_bg_transparent',
	'navitto_min_word_count',
	'navitto_show_after_scroll',
	'navitto_fixed_header_selector_pc',
	'navitto_fixed_header_selector_sp',
);
foreach ( $theme_mods as $mod ) {
	remove_theme_mod( $mod );
}

// メタデータを削除
global $wpdb;

$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE %s",
		$wpdb->esc_like( '_navitto_' ) . '%'
	)
);

// キャッシュをクリア
wp_cache_flush();
