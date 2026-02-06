<?php
/**
 * ContentPilot メインクラス
 *
 * プラグインのコア機能を管理するシングルトンクラス
 *
 * @package ContentPilot
 * @since   1.0.0
 */

// 直接アクセスを防ぐ
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ContentPilot_Main
 *
 * プラグインのメイン機能を管理するクラス
 *
 * @since 1.0.0
 */
class ContentPilot_Main {

	/**
	 * シングルトンインスタンス
	 *
	 * @since 1.0.0
	 * @var ContentPilot_Main|null
	 */
	private static $instance = null;

	/**
	 * プラグインバージョン
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $version;

	/**
	 * コンストラクタ
	 *
	 * シングルトンパターンのためprivate
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		$this->version = CONTENTPILOT_VERSION;
	}

	/**
	 * クローンを禁止
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function __clone() {
		// クローンを禁止
	}

	/**
	 * アンシリアライズを禁止
	 *
	 * @since 1.0.0
	 * @return void
	 * @throws Exception アンシリアライズが試みられた場合
	 */
	public function __wakeup() {
		throw new Exception( esc_html__( 'Cannot unserialize singleton', 'contentpilot' ) );
	}

	/**
	 * シングルトンインスタンスを取得
	 *
	 * @since 1.0.0
	 * @return ContentPilot_Main インスタンス
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * プラグインを初期化
	 *
	 * フックの登録などを行う
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init() {
		// フロントエンドのスクリプトとスタイルを登録
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// フッターにナビゲーションを出力
		add_action( 'wp_footer', array( $this, 'render_navigation' ) );
	}

	/**
	 * フロントエンドのスクリプトとスタイルを読み込む
	 *
	 * @since 1.0.0
	 * @return void
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

		// データをJavaScriptに渡す
		wp_localize_script(
			'contentpilot-frontend',
			'contentpilotData',
			array(
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( 'contentpilot_nonce' ),
				'scrollOffset' => 80,
				'animDuration' => 500,
			)
		);
	}

	/**
	 * ナビゲーションをフッターに出力
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_navigation() {
		// 投稿ページ以外は表示しない
		if ( ! is_singular( 'post' ) ) {
			return;
		}

		$post_id = get_the_ID();

		// プラグインが無効化されている場合は表示しない
		$enabled = get_post_meta( $post_id, '_contentpilot_enabled', true );

		// デフォルトでは有効（メタデータが存在しない場合）
		if ( '' === $enabled ) {
			$enabled = true;
		}

		if ( ! $enabled ) {
			return;
		}

		// テンプレートファイルが存在する場合は読み込む
		$template_path = CONTENTPILOT_PLUGIN_DIR . 'templates/navigation.php';
		if ( file_exists( $template_path ) ) {
			include $template_path;
		}
	}

	/**
	 * プラグインバージョンを取得
	 *
	 * @since 1.0.0
	 * @return string プラグインバージョン
	 */
	public function get_version() {
		return $this->version;
	}
}
