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
 *
 * @package ContentPilot
 */

// 直接アクセスを防ぐ
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * プラグインバージョン
 */
define( 'CONTENTPILOT_VERSION', '1.0.0' );

/**
 * プラグインディレクトリパス
 */
define( 'CONTENTPILOT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/**
 * プラグインURL
 */
define( 'CONTENTPILOT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * プラグインベースネーム
 */
define( 'CONTENTPILOT_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * クラスファイルの読み込み
 */
require_once CONTENTPILOT_PLUGIN_DIR . 'includes/class-contentpilot.php';
require_once CONTENTPILOT_PLUGIN_DIR . 'includes/class-contentpilot-admin.php';
require_once CONTENTPILOT_PLUGIN_DIR . 'includes/class-contentpilot-detector.php';

/**
 * プラグインを初期化する
 *
 * @since 1.0.0
 * @return void
 */
function contentpilot_init() {
	// テキストドメインを読み込む
	load_plugin_textdomain(
		'contentpilot',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);

	// フロントエンド初期化
	$plugin = ContentPilot_Main::get_instance();
	$plugin->init();

	// 管理画面初期化
	if ( is_admin() ) {
		$admin = ContentPilot_Admin::get_instance();
		$admin->init();
	}
}
add_action( 'plugins_loaded', 'contentpilot_init' );

/**
 * プラグインアクティベーション時の処理
 *
 * @since 1.0.0
 * @return void
 */
function contentpilot_activate() {
	// 初期設定を保存
	add_option( 'contentpilot_default_preset', 'simple' );
	add_option( 'contentpilot_position', 'top' );
	add_option( 'contentpilot_min_word_count', 3000 );

	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'contentpilot_activate' );

/**
 * プラグインディアクティベーション時の処理
 *
 * @since 1.0.0
 * @return void
 */
function contentpilot_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'contentpilot_deactivate' );
