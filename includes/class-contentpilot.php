<?php
/**
 * ContentPilot メインクラス
 *
 * フロントエンドの表示を管理するシングルトンクラス
 *
 * @package ContentPilot
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ContentPilot_Main {

	private static $instance = null;
	private $version;

	private function __construct() {
		$this->version = CONTENTPILOT_VERSION;
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function init() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * スクリプトとスタイルを読み込む
	 */
	public function enqueue_scripts() {
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

		// JavaScript
		wp_enqueue_script(
			'contentpilot-frontend',
			CONTENTPILOT_PLUGIN_URL . 'assets/js/frontend.js',
			array( 'jquery' ),
			$this->version,
			true
		);

		// 検出データ
		$detector       = ContentPilot_Detector::get_instance();
		$detection_data = $detector->get_detection_data();
		$header_data    = $detector->get_fixed_header_data();

		// 表示モード・H2選択データ
		$display_mode = get_post_meta( $post_id, '_contentpilot_display_mode', true );
		if ( '' === $display_mode ) {
			$display_mode = 'show_all';
		}
		// 後方互換: auto → show_all
		if ( 'auto' === $display_mode ) {
			$display_mode = 'show_all';
		}

		$selected_h2  = array();
		$custom_texts  = array();
		if ( 'select' === $display_mode ) {
			$raw = get_post_meta( $post_id, '_contentpilot_selected_h2', true );
			$selected_h2 = is_array( $raw ) ? $raw : array();
			$raw_texts = get_post_meta( $post_id, '_contentpilot_h2_custom_texts', true );
			$custom_texts = is_array( $raw_texts ) ? $raw_texts : array();
		}

		// 表示開始位置の設定
		$trigger_type = get_post_meta( $post_id, '_contentpilot_trigger_type', true );
		$trigger_data = array(
			'type' => $trigger_type ? $trigger_type : 'immediate',
		);
		if ( 'nth_selected' === $trigger_type ) {
			$trigger_data['nth'] = absint( get_post_meta( $post_id, '_contentpilot_trigger_nth', true ) ) ?: 2;
		}
		if ( 'scroll_px' === $trigger_type ) {
			$trigger_data['scrollPx'] = absint( get_post_meta( $post_id, '_contentpilot_trigger_scroll_px', true ) ) ?: 300;
		}

		// プリセット（投稿メタ優先 → カスタマイザー）
		$preset = get_post_meta( $post_id, '_contentpilot_preset', true );
		if ( empty( $preset ) ) {
			$preset = get_theme_mod( 'contentpilot_preset', 'simple' );
		}

		// カスタマイザーの設定
		$position    = get_theme_mod( 'contentpilot_position', 'top' );
		$show_after  = get_theme_mod( 'contentpilot_show_after_scroll', 100 );

		// JS用テキストデータ（intキーをstringに変換）
		$js_custom_texts = array();
		foreach ( $custom_texts as $k => $v ) {
			$js_custom_texts[ strval( $k ) ] = $v;
		}

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
				'customTexts'     => ! empty( $js_custom_texts ) ? $js_custom_texts : new stdClass(),
				'trigger'         => $trigger_data,
				'detection'       => $detection_data,
				'fixedHeader'     => $header_data,
			)
		);

		// インラインCSS
		$this->generate_inline_css();
	}

	/**
	 * カスタマイザー設定からインラインCSSを生成
	 */
	private function generate_inline_css() {
		$bg_color     = get_theme_mod( 'contentpilot_bg_color', '' );
		$text_color   = get_theme_mod( 'contentpilot_text_color', '' );
		$active_color = get_theme_mod( 'contentpilot_active_color', '' );
		$font_size    = get_theme_mod( 'contentpilot_font_size', 14 );
		$radius       = get_theme_mod( 'contentpilot_border_radius', false );
		$shadow       = get_theme_mod( 'contentpilot_shadow', true );

		$css = ':root {';
		if ( ! empty( $bg_color ) && '#ffffff' !== $bg_color ) {
			$css .= '--contentpilot-bg:' . esc_attr( $bg_color ) . ';';
		}
		if ( ! empty( $text_color ) && '#333333' !== $text_color ) {
			$css .= '--contentpilot-text:' . esc_attr( $text_color ) . ';';
		}
		if ( ! empty( $active_color ) && '#0073aa' !== $active_color ) {
			$css .= '--contentpilot-active-text:' . esc_attr( $active_color ) . ';';
			$css .= '--contentpilot-text-hover:' . esc_attr( $active_color ) . ';';
		}
		if ( intval( $font_size ) !== 14 ) {
			$css .= '--contentpilot-font-size:' . intval( $font_size ) . 'px;';
		}
		if ( $radius ) {
			$css .= '--contentpilot-radius:4px;';
		}
		if ( ! $shadow ) {
			$css .= '--contentpilot-nav-shadow:none;';
		}
		$css .= '}';

		// デフォルト値のみの場合はインラインCSS不要
		if ( ':root {}' !== $css ) {
			wp_add_inline_style( 'contentpilot-frontend', $css );
		}
	}

	/**
	 * 表示条件をチェック
	 */
	public function should_display( $post_id ) {
		// 新しい表示モードを確認
		$display_mode = get_post_meta( $post_id, '_contentpilot_display_mode', true );

		if ( 'hide' === $display_mode ) {
			return false;
		}

		// 後方互換
		if ( '' === $display_mode || 'auto' === $display_mode ) {
			$enabled = get_post_meta( $post_id, '_contentpilot_enabled', true );
			if ( '0' === $enabled ) {
				return false;
			}
		}

		$post    = get_post( $post_id );
		$content = $post ? $post->post_content : '';

		// selectモードでは文字数・H2数チェックをスキップ
		if ( 'select' === $display_mode ) {
			return true;
		}

		// show_allモードでも表示を許可
		if ( 'show_all' === $display_mode ) {
			// 文字数・H2数チェックは引き続き実行
		}

		// 最小文字数チェック
		$min_word_count = get_theme_mod( 'contentpilot_min_word_count', 3000 );
		if ( ! $min_word_count ) {
			$min_word_count = get_option( 'contentpilot_min_word_count', 3000 );
		}
		$word_count = $this->get_word_count( $content );
		if ( $word_count < $min_word_count ) {
			return false;
		}

		// H2見出しが2個以上
		$h2_count = $this->count_h2_headings( $content );
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

	public function get_version() {
		return $this->version;
	}
}
