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

// オプションを削除（#5 Data — 完全なクリーンアップ）
$options = array(
	'contentpilot_default_preset',
	'contentpilot_position',
	'contentpilot_min_word_count',
	'contentpilot_default_enabled',
	'contentpilot_db_version',
);
foreach ( $options as $option ) {
	delete_option( $option );
}

// Customizer theme_mods を削除
$theme_mods = array(
	'contentpilot_preset',
	'contentpilot_position',
	'contentpilot_font_size',
	'contentpilot_border_radius',
	'contentpilot_shadow',
	'contentpilot_nav_width',
	'contentpilot_min_word_count',
	'contentpilot_show_after_scroll',
	'contentpilot_fixed_header_selector_pc',
	'contentpilot_fixed_header_selector_sp',
);
foreach ( $theme_mods as $mod ) {
	remove_theme_mod( $mod );
}

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
