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

		// CSS
		wp_enqueue_style(
			'navitto-frontend',
			NAVITTO_PLUGIN_URL . 'assets/css/frontend.css',
			array(),
			$this->version
		);

		// JavaScript
		wp_enqueue_script(
			'navitto-frontend',
			NAVITTO_PLUGIN_URL . 'assets/js/frontend.js',
			array( 'jquery' ),
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
		if ( 'select' === $display_mode ) {
			$raw = get_post_meta( $post_id, '_navitto_selected_h2', true );
			$selected_h2 = is_array( $raw ) ? $raw : array();
			$raw_texts = get_post_meta( $post_id, '_navitto_h2_custom_texts', true );
			$custom_texts = is_array( $raw_texts ) ? $raw_texts : array();
		}

		// 表示開始位置の設定
		$trigger_type = get_post_meta( $post_id, '_navitto_trigger_type', true );
		$trigger_data = array(
			'type' => $trigger_type ? $trigger_type : 'immediate',
		);
		if ( 'nth_selected' === $trigger_type ) {
			$trigger_data['nth'] = absint( get_post_meta( $post_id, '_navitto_trigger_nth', true ) ) ?: 2;
		}
		if ( 'scroll_px' === $trigger_type ) {
			$trigger_data['scrollPx'] = absint( get_post_meta( $post_id, '_navitto_trigger_scroll_px', true ) ) ?: 300;
		}

		// プリセット（カスタマイザーで一括設定。現在はシンプル・テーマ準拠のみ）
		$preset = get_theme_mod( 'navitto_preset', 'simple' );
		if ( ! in_array( $preset, array( 'simple', 'theme' ), true ) ) {
			$preset = 'simple';
		}

		// 固定ナビの表示方法（投稿編集画面でのみ設定）
		$nav_width = get_post_meta( $post_id, '_navitto_nav_width', true );
		if ( ! in_array( $nav_width, array( 'scroll', 'equal' ), true ) ) {
			$nav_width = 'scroll';
		}

		// カスタマイザーの設定
		$position    = get_theme_mod( 'navitto_position', 'top' );
		$show_after  = get_theme_mod( 'navitto_show_after_scroll', 100 );

		// JS用テキストデータ（intキーをstringに変換）
		$js_custom_texts = array();
		foreach ( $custom_texts as $k => $v ) {
			$js_custom_texts[ strval( $k ) ] = $v;
		}

		// カスタム項目（外部リンク等）
		$raw_custom_items = get_post_meta( $post_id, '_navitto_custom_items', true );
		$custom_items     = is_array( $raw_custom_items ) ? $raw_custom_items : array();

		wp_localize_script(
			'navitto-frontend',
			'navittoData',
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
				'navWidth'        => $nav_width,
				'detection'       => $detection_data,
				'fixedHeader'     => $header_data,
				'customItems'     => $custom_items,
			)
		);

		// インラインCSS
		$this->generate_inline_css();
	}

	/**
	 * カスタマイザー設定からインラインCSSを生成
	 */
	private function generate_inline_css() {
		$nav_height = get_theme_mod( 'navitto_nav_height', 'medium' );

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
		$css .= '}';

		// デフォルト値のみの場合はインラインCSS不要
		if ( ':root {}' !== $css ) {
			wp_add_inline_style( 'navitto-frontend', $css );
		}
	}

	/**
	 * 表示条件をチェック
	 */
	public function should_display( $post_id ) {
		// 新しい表示モードを確認
		$display_mode = get_post_meta( $post_id, '_navitto_display_mode', true );

		if ( 'hide' === $display_mode ) {
			return false;
		}

		// 後方互換
		if ( '' === $display_mode || 'auto' === $display_mode ) {
			$enabled = get_post_meta( $post_id, '_navitto_enabled', true );
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

		// 最小文字数チェック（Customizer theme_mod に統一 — #3 Data）
		$min_word_count = get_theme_mod( 'navitto_min_word_count', 3000 );
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
