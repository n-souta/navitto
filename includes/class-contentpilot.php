<?php
/**
 * ContentPilot メインクラス
 *
 * フロントエンドの表示を管理するシングルトンクラス
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
 * プラグインのフロントエンド機能を管理
 *
 * @since 1.0.0
 */
class ContentPilot_Main {

	/**
	 * シングルトンインスタンス
	 *
	 * @var ContentPilot_Main|null
	 */
	private static $instance = null;

	/**
	 * プラグインバージョン
	 *
	 * @var string
	 */
	private $version;

	/**
	 * コンストラクタ
	 */
	private function __construct() {
		$this->version = CONTENTPILOT_VERSION;
	}

	/**
	 * シングルトンインスタンスを取得
	 *
	 * @return ContentPilot_Main
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * 初期化
	 */
	public function init() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * スクリプトとスタイルを読み込む
	 */
	public function enqueue_scripts() {
		// 投稿ページ以外は読み込まない
		if ( ! is_singular( 'post' ) ) {
			return;
		}

		$post_id = get_the_ID();

		// 表示条件をチェック
		if ( ! $this->should_display( $post_id ) ) {
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

		// 目次検出データを取得
		$detector       = ContentPilot_Detector::get_instance();
		$detection_data = $detector->get_detection_data();
		$header_data    = $detector->get_fixed_header_data();

		// データをJavaScriptに渡す
		wp_localize_script(
			'contentpilot-frontend',
			'contentpilotData',
			array(
				'scrollOffset'    => 80,
				'animDuration'    => 500,
				'showAfterScroll' => 100,
				'position'        => get_option( 'contentpilot_position', 'top' ),
				'detection'       => $detection_data,
				'fixedHeader'     => $header_data,
			)
		);
	}

	/**
	 * 表示条件をチェック
	 *
	 * @param int $post_id 投稿ID
	 * @return bool
	 */
	public function should_display( $post_id ) {
		// メタデータで無効化されている場合
		$enabled = get_post_meta( $post_id, '_contentpilot_enabled', true );

		// 明示的に無効化されている場合のみ非表示
		if ( '0' === $enabled ) {
			return false;
		}

		// 投稿コンテンツを取得
		$post    = get_post( $post_id );
		$content = $post ? $post->post_content : '';

		// 最小文字数チェック
		$min_word_count = get_option( 'contentpilot_min_word_count', 3000 );
		$word_count     = $this->get_word_count( $content );

		if ( $word_count < $min_word_count ) {
			return false;
		}

		// H2見出しが2個以上あるかチェック
		$h2_count = $this->count_h2_headings( $content );
		if ( $h2_count < 2 ) {
			return false;
		}

		return true;
	}

	/**
	 * 文字数を取得
	 *
	 * @param string $content コンテンツ
	 * @return int
	 */
	private function get_word_count( $content ) {
		$text = wp_strip_all_tags( $content );
		$text = preg_replace( '/\s+/', '', $text );
		return mb_strlen( $text, 'UTF-8' );
	}

	/**
	 * H2見出しの数をカウント
	 *
	 * @param string $content コンテンツ
	 * @return int
	 */
	private function count_h2_headings( $content ) {
		preg_match_all( '/<h2[^>]*>.*?<\/h2>/is', $content, $matches );
		return count( $matches[0] );
	}

	/**
	 * バージョンを取得
	 *
	 * @return string
	 */
	public function get_version() {
		return $this->version;
	}
}
