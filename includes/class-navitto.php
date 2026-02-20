<?php
/**
 * Navitto メインクラス
 *
 * フロントエンドの表示を管理するシングルトンクラス
 *
 * @package Navitto
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Navitto_Main {

	private static $instance = null;
	private $version;

	private function __construct() {
		$this->version = NAVITTO_VERSION;
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function init() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_filter( 'body_class', array( $this, 'add_body_class' ) );
	}

	/**
	 * body タグにクラスを追加（配置位置の判定用）
	 */
	public function add_body_class( $classes ) {
		if ( ! is_singular( 'post' ) ) {
			return $classes;
		}

		$post_id = get_the_ID();
		if ( ! $this->should_display( $post_id ) ) {
			return $classes;
		}

		$position = get_theme_mod( 'navitto_position', 'top' );
		if ( ! in_array( $position, array( 'top', 'bottom' ), true ) ) {
			$position = 'top';
		}
		$classes[] = 'navitto-pos-' . $position;

		return $classes;
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

		$license_ok = ( get_option( 'navitto_license_status', '' ) === 'valid' );

		// CSS
		wp_enqueue_style(
			'navitto-frontend',
			NAVITTO_PLUGIN_URL . 'assets/css/frontend.css',
			array(),
			$this->version
		);

		// ライセンス有効時のみ Font Awesome とアイコンレジストリを読み込む
		if ( $license_ok ) {
			$fa_css = NAVITTO_PLUGIN_DIR . 'assets/lib/fontawesome/all-nv.min.css';
			if ( file_exists( $fa_css ) ) {
				wp_enqueue_style(
					'navitto-fontawesome',
					NAVITTO_PLUGIN_URL . 'assets/lib/fontawesome/all-nv.min.css',
					array(),
					$this->version
				);
			}
			wp_enqueue_script(
				'navitto-icons',
				NAVITTO_PLUGIN_URL . 'assets/js/navitto-icons.js',
				array(),
				$this->version,
				true
			);
		}

		// JavaScript（ライセンス無効時はアイコン用スクリプトに依存しない）
		wp_enqueue_script(
			'navitto-frontend',
			NAVITTO_PLUGIN_URL . 'assets/js/frontend.js',
			$license_ok ? array( 'jquery', 'navitto-icons' ) : array( 'jquery' ),
			$this->version,
			true
		);

		// 検出データ
		$detector       = Navitto_Detector::get_instance();
		$detection_data = $detector->get_detection_data();
		$header_data    = $detector->get_fixed_header_data();

		// 表示モード・H2選択データ
		$display_mode = get_post_meta( $post_id, '_navitto_display_mode', true );
		if ( '' === $display_mode ) {
			$display_mode = 'show_all';
		}
		// 後方互換: auto → show_all
		if ( 'auto' === $display_mode ) {
			$display_mode = 'show_all';
		}

		$selected_h2  = array();
		$custom_texts  = array();
		$h2_icons      = array();
		if ( 'select' === $display_mode ) {
			$raw = get_post_meta( $post_id, '_navitto_selected_h2', true );
			$selected_h2 = is_array( $raw ) ? $raw : array();
			$raw_texts = get_post_meta( $post_id, '_navitto_h2_custom_texts', true );
			$custom_texts = is_array( $raw_texts ) ? $raw_texts : array();
			// ライセンス有効時のみアイコンデータを読み込む
			if ( $license_ok ) {
				$raw_icons = get_post_meta( $post_id, '_navitto_h2_icons', true );
				$h2_icons = is_array( $raw_icons ) ? $raw_icons : array();
			}
		}

		// 表示開始位置の設定
		$trigger_type = get_post_meta( $post_id, '_navitto_trigger_type', true );
		$trigger_data = array(
			'type' => $trigger_type ? $trigger_type : 'immediate',
		);
		// プリセット（カスタマイザーで一括設定。現在はシンプル・テーマ準拠のみ）
		$preset = get_theme_mod( 'navitto_preset', 'simple' );
		if ( ! in_array( $preset, array( 'simple', 'theme', 'custom' ), true ) ) {
			$preset = 'simple';
		}

		// 固定ナビの表示方法（投稿編集画面でのみ設定）
		$nav_width = get_post_meta( $post_id, '_navitto_nav_width', true );
		if ( ! in_array( $nav_width, array( 'scroll', 'equal' ), true ) ) {
			$nav_width = 'scroll';
		}

		// カスタマイザーの設定
		$position = get_theme_mod( 'navitto_position', 'top' );

		// JS用テキストデータ（intキーをstringに変換）
		$js_custom_texts = array();
		foreach ( $custom_texts as $k => $v ) {
			$js_custom_texts[ strval( $k ) ] = $v;
		}
		$js_h2_icons = array();
		foreach ( $h2_icons as $k => $v ) {
			$js_h2_icons[ strval( $k ) ] = $v;
		}

		$theme_bg_transparent = (bool) get_theme_mod( 'navitto_theme_bg_transparent', false );

		wp_localize_script(
			'navitto-frontend',
			'navittoData',
			array(
				'scrollOffset'        => 80,
				'animDuration'        => 500,
				'showAfterScroll'     => 100,
				'preset'              => $preset,
				'themeBgTransparent'   => $theme_bg_transparent,
				'position'            => $position,
				'displayMode'         => $display_mode,
				'selectedH2'          => $selected_h2,
				'customTexts'         => ! empty( $js_custom_texts ) ? $js_custom_texts : new stdClass(),
				'h2Icons'             => ! empty( $js_h2_icons ) ? $js_h2_icons : new stdClass(),
				'trigger'             => $trigger_data,
				'navWidth'            => $nav_width,
				'detection'           => $detection_data,
				'fixedHeader'         => $header_data,
			)
		);

		// インラインCSS
		$this->generate_inline_css();
	}

	/**
	 * カスタマイザー設定からインラインCSSを生成
	 */
	private function generate_inline_css() {
		$nav_height   = get_theme_mod( 'navitto_nav_height', 'medium' );
		$font_weight  = get_theme_mod( 'navitto_font_weight', 'default' );

		$css = ':root {';

		// ナビの高さとフォントサイズ（medium はデフォルト 56/48px・14px なので出力不要）
		// 各プリセット: [ PC高さ, モバイル高さ, フォントサイズ(px) ]
		$height_map = array(
			'small' => array( '44px', '38px', 12 ),
			'large' => array( '68px', '56px', 16 ),
		);
		if ( isset( $height_map[ $nav_height ] ) ) {
			$css .= '--navitto-height:' . $height_map[ $nav_height ][0] . ';';
			$css .= '--navitto-height-mobile:' . $height_map[ $nav_height ][1] . ';';
			$css .= '--navitto-font-size:' . intval( $height_map[ $nav_height ][2] ) . 'px;';
		}

		// フォントの太さ（デフォルト=500 / 太字=700）
		if ( 'bold' === $font_weight ) {
			$css .= '--navitto-font-weight:700;';
		}
		$css .= '}';

		// カスタムプリセット: 文字色・背景色・選択中テキスト色を CSS 変数で出力
		$preset = get_theme_mod( 'navitto_preset', 'simple' );
		if ( 'custom' === $preset ) {
			$text_color      = get_theme_mod( 'navitto_custom_color_text', '#333333' ) ?: '#333333';
			$bg_color        = get_theme_mod( 'navitto_custom_color_bg', '#ffffff' ) ?: '#ffffff';
			$underline_color = get_theme_mod( 'navitto_custom_color_underline', '#0073aa' ) ?: '#0073aa';
			$css .= '.navitto-nav.cp-preset-custom{';
			$css .= '--navitto-bg:' . esc_attr( $bg_color ) . ';';
			$css .= '--navitto-text:' . esc_attr( $text_color ) . ';';
			$css .= '--navitto-text-hover:' . esc_attr( $underline_color ) . ';';
			$css .= '--navitto-active-text:' . esc_attr( $underline_color ) . ';';
			$css .= '--navitto-active-bg:transparent;';
			$css .= '--navitto-border:' . esc_attr( $underline_color ) . ';';
			$css .= '}';
		}

		// デフォルト値のみの場合はインラインCSS不要
		if ( ':root {}' !== $css ) {
			wp_add_inline_style( 'navitto-frontend', $css );
		}
	}

	/**
	 * 表示条件をチェック
	 */
	public function should_display( $post_id ) {
		// 一括で無効にされている場合は表示しない（display_mode や選択見出しは保持したまま）
		if ( '1' === get_post_meta( $post_id, '_navitto_bulk_disabled', true ) ) {
			return false;
		}

		// 新しい表示モードを確認
		$display_mode = get_post_meta( $post_id, '_navitto_display_mode', true );

		if ( 'hide' === $display_mode ) {
			return false;
		}

		// メタ未設定: 有効化前からあった既存記事は表示しない（一括適用または個別に「表示」を選んで保存した投稿のみ表示）
		if ( '' === $display_mode || 'auto' === $display_mode ) {
			$enabled = get_post_meta( $post_id, '_navitto_enabled', true );
			if ( '0' === $enabled ) {
				return false;
			}
			if ( '' === $display_mode && '' === $enabled ) {
				return false;
			}
		}

		$post    = get_post( $post_id );
		$content = $post ? $post->post_content : '';

		// selectモードではH2数チェックをスキップ
		if ( 'select' === $display_mode ) {
			return true;
		}

		// H2見出しが2個以上
		$h2_count = $this->count_h2_headings( $content );
		if ( $h2_count < 2 ) {
			return false;
		}

		return true;
	}

	private function count_h2_headings( $content ) {
		preg_match_all( '/<h2[^>]*>.*?<\/h2>/is', $content, $matches );
		return count( $matches[0] );
	}

	public function get_version() {
		return $this->version;
	}
}
