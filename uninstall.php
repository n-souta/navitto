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
	'navitto_default_enabled',
	'navitto_db_version',
	'navitto_license_key',
	'navitto_license_status',
	'navitto_license_email',
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
	'navitto_custom_color_text',
	'navitto_custom_color_bg',
	'navitto_custom_color_underline',
);
foreach ( $theme_mods as $mod ) {
	remove_theme_mod( $mod );
}

// 投稿メタは削除しない（再インストール時に「固定ナビを表示する」設定を保持するため）
// 完全にデータを消したい場合は手動で DB から _navitto_% を削除してください。

// キャッシュをクリア
wp_cache_flush();
