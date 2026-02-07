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

		$post_id = get_the_ID();

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

		// カスタムカラーのインラインCSS
		$inline_css = $this->generate_inline_css();
		if ( $inline_css ) {
			wp_add_inline_style( 'contentpilot-frontend', $inline_css );
		}

		// JavaScript
		wp_enqueue_script(
			'contentpilot-frontend',
			CONTENTPILOT_PLUGIN_URL . 'assets/js/frontend.js',
			array( 'jquery' ),
			$this->version,
			true
		);

		// 目次検出データ
		$detector       = ContentPilot_Detector::get_instance();
		$detection_data = $detector->get_detection_data();
		$header_data    = $detector->get_fixed_header_data();

		// プリセット（投稿別 > サイト共通）
		$post_preset = get_post_meta( $post_id, '_contentpilot_preset', true );
		$preset      = ! empty( $post_preset ) ? $post_preset : get_theme_mod( 'contentpilot_preset', 'simple' );

		// 配置位置
		$position = get_theme_mod( 'contentpilot_position', 'top' );

		// 表示モード
		$display_mode = get_post_meta( $post_id, '_contentpilot_display_mode', true );
		if ( ! $display_mode ) {
			$display_mode = 'show_all';
		}

		// H2選択・カスタムテキスト（selectモード時のみ）
		$selected_h2  = array();
		$custom_texts = array();
		if ( 'select' === $display_mode ) {
			$selected_h2 = get_post_meta( $post_id, '_contentpilot_selected_h2', true );
			if ( ! is_array( $selected_h2 ) ) {
				$selected_h2 = array();
			}
			$custom_texts = get_post_meta( $post_id, '_contentpilot_h2_custom_texts', true );
			if ( ! is_array( $custom_texts ) ) {
				$custom_texts = array();
			}
			// int キーを文字列に変換（JS用）
			$ct = array();
			foreach ( $custom_texts as $k => $v ) {
				$ct[ strval( $k ) ] = $v;
			}
			$custom_texts = $ct;
		}

		// スクロール開始位置
		$show_after = get_theme_mod( 'contentpilot_show_after_scroll', 100 );

		// データをJavaScriptに渡す
		wp_localize_script(
			'contentpilot-frontend',
			'contentpilotData',
			array(
				'scrollOffset'    => 80,
				'animDuration'    => 500,
				'showAfterScroll' => intval( $show_after ),
				'preset'          => $preset,
				'position'        => $position,
				'displayMode'     => $display_mode,
				'selectedH2'      => $selected_h2,
				'customTexts'     => $custom_texts,
				'detection'       => $detection_data,
				'fixedHeader'     => $header_data,
			)
		);
	}

	/**
	 * カスタマイザーの色設定からインラインCSSを生成
	 *
	 * @return string
	 */
	private function generate_inline_css() {
		$css  = '';
		$vars = array();

		$bg     = get_theme_mod( 'contentpilot_bg_color', '' );
		$text   = get_theme_mod( 'contentpilot_text_color', '' );
		$active = get_theme_mod( 'contentpilot_active_color', '' );
		$fs     = get_theme_mod( 'contentpilot_font_size', 14 );
		$radius = get_theme_mod( 'contentpilot_border_radius', false );
		$shadow = get_theme_mod( 'contentpilot_shadow', false );

		if ( ! empty( $bg ) ) {
			$vars[] = '--contentpilot-bg: ' . sanitize_hex_color( $bg );
		}
		if ( ! empty( $text ) ) {
			$vars[] = '--contentpilot-text: ' . sanitize_hex_color( $text );
		}
		if ( ! empty( $active ) ) {
			$vars[] = '--contentpilot-active-text: ' . sanitize_hex_color( $active );
			$vars[] = '--contentpilot-text-hover: ' . sanitize_hex_color( $active );
		}
		if ( intval( $fs ) !== 14 ) {
			$vars[] = '--contentpilot-font-size: ' . intval( $fs ) . 'px';
			$vars[] = '--contentpilot-font-size-mobile: ' . max( intval( $fs ) - 1, 10 ) . 'px';
		}
		if ( $radius ) {
			$vars[] = '--contentpilot-radius: 8px';
		}
		if ( $shadow ) {
			$vars[] = '--contentpilot-nav-shadow: 0 2px 8px rgba(0, 0, 0, 0.1)';
		}

		if ( ! empty( $vars ) ) {
			$css = '.contentpilot-nav { ' . implode( '; ', $vars ) . '; }';
		}

		return $css;
	}

	/**
	 * 表示条件チェック
	 */
	public function should_display( $post_id ) {
		// 新しい表示モードをチェック
		$display_mode = get_post_meta( $post_id, '_contentpilot_display_mode', true );

		// 旧データとの互換：display_modeが未設定なら旧フィールドを確認
		if ( '' === $display_mode ) {
			$old_enabled = get_post_meta( $post_id, '_contentpilot_enabled', true );
			if ( '0' === $old_enabled ) {
				return false;
			}
			$old_force = get_post_meta( $post_id, '_contentpilot_force_display', true );
			if ( '1' === $old_force ) {
				return true;
			}
		} else {
			// 非表示モード
			if ( 'hide' === $display_mode ) {
				return false;
			}
			// show_all / select は表示（文字数・H2数の条件チェック後）
		}

		$post     = get_post( $post_id );
		$content  = $post ? $post->post_content : '';
		$rendered = do_blocks( $content );

		$min_wc = get_theme_mod( 'contentpilot_min_word_count', 3000 );
		if ( ! $min_wc ) {
			$min_wc = get_option( 'contentpilot_min_word_count', 3000 );
		}
		$wc = $this->get_word_count( $rendered );
		if ( $wc < $min_wc ) {
			return false;
		}

		$h2_count = $this->count_h2_headings( $rendered );
		if ( $h2_count < 2 ) {
			return false;
		}

		return true;
	}

	private function get_word_count( $content ) {
		$text = wp_strip_all_tags( $content );
		$text = preg_replace( '/\s+/', '', $text );
		return mb_strlen( $text, 'UTF-8' );
	}

	private function count_h2_headings( $content ) {
		preg_match_all( '/<h2[^>]*>.*?<\/h2>/is', $content, $matches );
		return count( $matches[0] );
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