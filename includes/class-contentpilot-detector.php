<?php
/**
 * ContentPilot 目次検出クラス
 *
 * テーマ内蔵目次・目次プラグインを検出し、
 * 検出優先順位に基づいてフロントエンドに情報を渡す
 *
 * @package ContentPilot
 * @since   1.1.0
 */

// 直接アクセスを防ぐ
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ContentPilot_Detector
 *
 * 目次ソースの検出と優先順位管理
 *
 * @since 1.1.0
 */
class ContentPilot_Detector {

	/**
	 * シングルトンインスタンス
	 *
	 * @var ContentPilot_Detector|null
	 */
	private static $instance = null;

	/**
	 * テーマ内蔵目次のセレクタ定義
	 *
	 * 検出優先順位1: テーマの目次
	 *
	 * @var array
	 */
	private $theme_toc_selectors = array(
		'swell'   => array(
			'name'      => 'SWELL',
			'container' => '.wp-block-swl-toc',
			'items'     => '.wp-block-swl-toc a',
		),
		'jin'     => array(
			'name'      => 'JIN / JIN:R',
			'container' => '.jin-toc',
			'items'     => '.jin-toc a',
		),
		'sango'   => array(
			'name'      => 'SANGO',
			'container' => '.sng-toc',
			'items'     => '.sng-toc a',
		),
		'affinger' => array(
			'name'      => 'AFFINGER',
			'container' => '.st-toc',
			'items'     => '.st-toc a',
		),
		'cocoon'  => array(
			'name'      => 'Cocoon',
			'container' => '.toc',
			'items'     => '.toc a',
		),
		'the_thor' => array(
			'name'      => 'THE THOR',
			'container' => '.ep-toc',
			'items'     => '.ep-toc a',
		),
	);

	/**
	 * 目次プラグインのセレクタ定義
	 *
	 * 検出優先順位2: 目次プラグイン
	 *
	 * @var array
	 */
	private $plugin_toc_selectors = array(
		'toc_plus'     => array(
			'name'      => 'Table of Contents Plus',
			'container' => '#toc_container',
			'items'     => '#toc_container a',
		),
		'easy_toc'     => array(
			'name'      => 'Easy Table of Contents',
			'container' => '.ez-toc-container',
			'items'     => '.ez-toc-container a',
		),
		'rich_toc'     => array(
			'name'      => 'Rich Table of Contents',
			'container' => '.rtoc-mokuji',
			'items'     => '.rtoc-mokuji a',
		),
		'lwptoc'       => array(
			'name'      => 'LuckyWP Table of Contents',
			'container' => '.lwptoc',
			'items'     => '.lwptoc a',
		),
	);

	/**
	 * コンストラクタ
	 */
	private function __construct() {
		// シングルトン
	}

	/**
	 * シングルトンインスタンスを取得
	 *
	 * @return ContentPilot_Detector
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * 現在のテーマスラッグを取得（キャッシュ付き）
	 *
	 * @var string|null
	 */
	private $theme_slug_cache = null;

	/**
	 * 現在のテーマスラッグを取得
	 *
	 * @return string テーマスラッグ（小文字）
	 */
	public function get_current_theme_slug() {
		if ( null !== $this->theme_slug_cache ) {
			return $this->theme_slug_cache;
		}

		$theme  = wp_get_theme();
		$parent = $theme->parent();
		$this->theme_slug_cache = strtolower( $parent ? $parent->get_stylesheet() : $theme->get_stylesheet() );

		return $this->theme_slug_cache;
	}

	/**
	 * 有効な目次プラグインを検出
	 *
	 * @return array 有効な目次プラグインのキー配列
	 */
	public function detect_active_toc_plugins() {
		$active = array();

		// 代表的な目次プラグインのメインファイルパターン
		$plugin_checks = array(
			'toc_plus' => array(
				'table-of-contents-plus/toc.php',
			),
			'easy_toc' => array(
				'easy-table-of-contents/easy-table-of-contents.php',
			),
			'rich_toc' => array(
				'rich-table-of-contents/richtext-table-of-content.php',
			),
			'lwptoc'   => array(
				'flavor/flavor.php',
				'flavor-flavor/flavor.php',
				'flavor-flavor.php',
			),
		);

		foreach ( $plugin_checks as $key => $files ) {
			foreach ( $files as $file ) {
				if ( is_plugin_active( $file ) ) {
					$active[] = $key;
					break;
				}
			}
		}

		return $active;
	}

	/**
	 * テーマキーマッピング（キャッシュ付き）
	 *
	 * @var string|false|null
	 */
	private $theme_key_cache = null;

	/**
	 * テーマが目次機能を内蔵しているか判定
	 *
	 * @return string|false 対応するテーマキー、または false
	 */
	public function detect_theme_toc() {
		if ( null !== $this->theme_key_cache ) {
			return $this->theme_key_cache;
		}

		$theme_slug = $this->get_current_theme_slug();
		$theme_map  = array(
			'swell' => 'swell', 'jin' => 'jin', 'jin-r' => 'jin', 'jinr' => 'jin',
			'sango' => 'sango', 'affinger' => 'affinger', 'affinger5' => 'affinger',
			'affinger6' => 'affinger', 'wing-affinger5' => 'affinger',
			'cocoon' => 'cocoon', 'cocoon-child' => 'cocoon',
			'the-thor' => 'the_thor', 'the_thor' => 'the_thor',
		);

		foreach ( $theme_map as $slug => $key ) {
			if ( strpos( $theme_slug, $slug ) !== false ) {
				$this->theme_key_cache = $key;
				return $key;
			}
		}

		$this->theme_key_cache = false;
		return false;
	}

	/**
	 * 検出情報をまとめてJSに渡すデータを生成
	 *
	 * @return array JS用の検出データ
	 */
	public function get_detection_data() {
		$order = array();
		$theme_key = $this->detect_theme_toc();

		// 1. テーマ内蔵目次
		if ( $theme_key && isset( $this->theme_toc_selectors[ $theme_key ] ) ) {
			$toc = $this->theme_toc_selectors[ $theme_key ];
			$order[] = array( 'source' => 'theme', 'name' => $toc['name'], 'container' => $toc['container'], 'items' => $toc['items'] );
		}

		// 2. 目次プラグイン
		$added = array();
		foreach ( $this->detect_active_toc_plugins() as $key ) {
			if ( isset( $this->plugin_toc_selectors[ $key ] ) ) {
				$toc = $this->plugin_toc_selectors[ $key ];
				$order[] = array( 'source' => 'plugin', 'name' => $toc['name'], 'container' => $toc['container'], 'items' => $toc['items'] );
				$added[] = $toc['container'];
			}
		}

		// 3. 汎用検出（未追加のセレクタ）
		foreach ( array_merge( $this->theme_toc_selectors, $this->plugin_toc_selectors ) as $toc ) {
			if ( ! in_array( $toc['container'], $added, true ) ) {
				$order[] = array( 'source' => 'generic', 'name' => $toc['name'], 'container' => $toc['container'], 'items' => $toc['items'] );
				$added[] = $toc['container'];
			}
		}

		// 4. H2フォールバック
		$order[] = array( 'source' => 'h2', 'name' => 'H2 Auto Detect', 'container' => '', 'items' => '' );

		return array( 'detectionOrder' => $order, 'detectedTheme' => $theme_key ? $this->theme_toc_selectors[ $theme_key ]['name'] : '', 'detectedPlugin' => '', 'source' => 'h2' );
	}

	/**
	 * テーマ目次セレクタを外部から追加するフィルター用
	 *
	 * @param string $key     セレクタキー
	 * @param array  $selector セレクタ情報（name, container, items）
	 * @return void
	 */
	public function add_theme_toc_selector( $key, $selector ) {
		$this->theme_toc_selectors[ $key ] = $selector;
	}

	/**
	 * プラグイン目次セレクタを外部から追加するフィルター用
	 *
	 * @param string $key     セレクタキー
	 * @param array  $selector セレクタ情報（name, container, items）
	 * @return void
	 */
	public function add_plugin_toc_selector( $key, $selector ) {
		$this->plugin_toc_selectors[ $key ] = $selector;
	}

	/**
	 * テーマの固定ヘッダーセレクタ定義
	 *
	 * 主要テーマの固定ヘッダーを検出するためのセレクタ
	 * PC/SPで異なる場合、配列で指定
	 *
	 * @var array
	 */
	private $fixed_header_selectors = array(
		'cocoon'   => array(
			'pc' => '#header',
			'sp' => '#header',
		),
		'jin'      => array(
			'pc' => '#header-box',
			'sp' => '#header-box',
		),
		'jin-r'    => array(
			'pc' => '#commonHeader',
			'sp' => '#commonHeader',
		),
		'sango'    => array(
			'pc' => 'header.header',
			'sp' => 'header.header',
		),
		'affinger' => array(
			'pc' => '#st-menuwide',
			'sp' => '#st-menuwide',
		),
		'swell'    => array(
			'pc' => '#fix_header',
			'sp' => '#header',
		),
		'the_thor' => array(
			'pc' => '.l-header',
			'sp' => '.l-header',
		),
		'stork19'  => array(
			'pc' => '#header',
			'sp' => '#header',
		),
		'diver'    => array(
			'pc' => '#nav_fixed',
			'sp' => '#nav_fixed',
		),
	);

	/**
	 * 固定ヘッダーセレクタのマッピング
	 *
	 * @var array
	 */
	private $header_key_map = array(
		'jin-r' => 'jin-r', 'jinr' => 'jin-r', 'jin' => 'jin',
		'swell' => 'swell', 'sango' => 'sango', 'affinger' => 'affinger',
		'affinger5' => 'affinger', 'affinger6' => 'affinger',
		'wing-affinger5' => 'affinger', 'cocoon' => 'cocoon',
		'cocoon-child' => 'cocoon', 'the-thor' => 'the_thor',
		'the_thor' => 'the_thor', 'stork19' => 'stork19', 'diver' => 'diver',
	);

	/**
	 * 現在のテーマの固定ヘッダーセレクタを取得
	 *
	 * @return array|false PC/SPのセレクタ配列、または false
	 */
	public function get_fixed_header_selector() {
		$theme_key = $this->detect_theme_toc();
		if ( ! $theme_key ) {
			return false;
		}

		$theme_slug = $this->get_current_theme_slug();
		foreach ( $this->header_key_map as $slug => $key ) {
			if ( strpos( $theme_slug, $slug ) !== false && isset( $this->fixed_header_selectors[ $key ] ) ) {
				return $this->fixed_header_selectors[ $key ];
			}
		}

		return false;
	}

	/**
	 * 固定ヘッダー検出データをJS用に生成
	 *
	 * カスタマイザーで設定された値が最優先
	 *
	 * @return array 固定ヘッダー検出データ
	 */
	public function get_fixed_header_data() {
		// カスタマイザーからの設定値を取得
		$pc_selector = get_theme_mod( 'contentpilot_fixed_header_selector_pc', '' );
		$sp_selector = get_theme_mod( 'contentpilot_fixed_header_selector_sp', '' );

		// カスタマイザーで設定されていればそれを優先
		if ( ! empty( $pc_selector ) ) {
			return array(
				'selectors' => array(
					'pc' => $pc_selector,
					'sp' => ! empty( $sp_selector ) ? $sp_selector : $pc_selector,
				),
				'customSelector' => '',
			);
		}

		// カスタマイザー未設定の場合は自動検出
		return array(
			'selectors' => $this->get_fixed_header_selector() ?: array(),
			'customSelector' => get_option( 'contentpilot_fixed_header_selector', '' ),
		);
	}
}
