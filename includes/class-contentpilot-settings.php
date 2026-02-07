<?php
/**
 * ContentPilot 設定画面クラス
 *
 * 管理画面「設定 → ContentPilot」でデフォルト動作・表示条件・一括適用を管理
 *
 * @package ContentPilot
 * @since   1.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ContentPilot_Settings {

	/**
	 * シングルトンインスタンス
	 *
	 * @var ContentPilot_Settings|null
	 */
	private static $instance = null;

	/**
	 * オプショングループ名
	 *
	 * @var string
	 */
	private $option_group = 'contentpilot_settings_group';

	/**
	 * 設定ページスラッグ
	 *
	 * @var string
	 */
	private $page_slug = 'contentpilot-settings';

	/**
	 * コンストラクタ（private）
	 */
	private function __construct() {}

	/**
	 * シングルトンインスタンスを取得
	 *
	 * @return ContentPilot_Settings
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * 初期化 — フックを登録
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Ajax ハンドラ
		add_action( 'wp_ajax_contentpilot_enable_all',  array( $this, 'ajax_enable_all' ) );
		add_action( 'wp_ajax_contentpilot_disable_all', array( $this, 'ajax_disable_all' ) );
		add_action( 'wp_ajax_contentpilot_enable_long', array( $this, 'ajax_enable_long' ) );
	}

	/* =========================================================================
	   設定ページの登録
	   ========================================================================= */

	/**
	 * 管理メニューに設定ページを追加
	 *
	 * @return void
	 */
	public function add_settings_page() {
		$hook = add_options_page(
			__( 'ContentPilot 設定', 'contentpilot' ),
			__( 'ContentPilot', 'contentpilot' ),
			'manage_options',
			$this->page_slug,
			array( $this, 'render_settings_page' )
		);

		// 設定ページでのみJSを読み込む
		add_action( 'load-' . $hook, array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * 管理画面用アセットをキューに追加
	 *
	 * @return void
	 */
	public function enqueue_admin_assets() {
		add_action( 'admin_enqueue_scripts', array( $this, 'load_admin_scripts' ) );
	}

	/**
	 * 管理画面用スクリプトを読み込む
	 *
	 * @return void
	 */
	public function load_admin_scripts() {
		wp_enqueue_script(
			'contentpilot-admin-settings',
			CONTENTPILOT_PLUGIN_URL . 'assets/js/admin-settings.js',
			array( 'jquery' ),
			CONTENTPILOT_VERSION,
			true
		);

		wp_localize_script( 'contentpilot-admin-settings', 'contentpilotAdmin', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'contentpilot_bulk_action' ),
			'i18n'    => array(
				'confirmEnableAll'  => __( 'すべての投稿で固定ナビを有効にしますか？', 'contentpilot' ),
				'confirmDisableAll' => __( 'すべての投稿で固定ナビを無効にしますか？', 'contentpilot' ),
				'confirmEnableLong' => __( '長文記事のみ有効にしますか？（短文記事は無効になります）', 'contentpilot' ),
				'processing'        => __( '処理中...', 'contentpilot' ),
				'error'             => __( 'エラーが発生しました。', 'contentpilot' ),
			),
		) );
	}

	/* =========================================================================
	   Settings API 登録
	   ========================================================================= */

	/**
	 * 設定を登録
	 *
	 * @return void
	 */
	public function register_settings() {

		// --- セクション1: デフォルト動作 ---
		add_settings_section(
			'contentpilot_section_default',
			__( 'デフォルト動作', 'contentpilot' ),
			array( $this, 'render_section_default' ),
			$this->page_slug
		);

		register_setting( $this->option_group, 'contentpilot_default_enabled', array(
			'type'              => 'boolean',
			'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
			'default'           => true,
		) );

		add_settings_field(
			'contentpilot_default_enabled',
			__( '新規投稿でデフォルトで有効にする', 'contentpilot' ),
			array( $this, 'render_field_default_enabled' ),
			$this->page_slug,
			'contentpilot_section_default'
		);

		// --- セクション2: 表示条件 ---
		add_settings_section(
			'contentpilot_section_conditions',
			__( '表示条件', 'contentpilot' ),
			array( $this, 'render_section_conditions' ),
			$this->page_slug
		);

		register_setting( $this->option_group, 'contentpilot_min_word_count', array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 3000,
		) );

		add_settings_field(
			'contentpilot_min_word_count',
			__( '最小文字数', 'contentpilot' ),
			array( $this, 'render_field_min_word_count' ),
			$this->page_slug,
			'contentpilot_section_conditions'
		);
	}

	/* =========================================================================
	   セクション説明の出力
	   ========================================================================= */

	/**
	 * デフォルト動作セクションの説明
	 *
	 * @return void
	 */
	public function render_section_default() {
		echo '<p>' . esc_html__( '新規投稿作成時のデフォルト動作を設定します。', 'contentpilot' ) . '</p>';
	}

	/**
	 * 表示条件セクションの説明
	 *
	 * @return void
	 */
	public function render_section_conditions() {
		echo '<p>' . esc_html__( '固定ナビの表示条件を設定します。', 'contentpilot' ) . '</p>';
	}

	/* =========================================================================
	   フィールドの出力
	   ========================================================================= */

	/**
	 * デフォルト有効チェックボックスを出力
	 *
	 * @return void
	 */
	public function render_field_default_enabled() {
		$value = get_option( 'contentpilot_default_enabled', true );
		?>
		<label>
			<input type="checkbox"
				name="contentpilot_default_enabled"
				value="1"
				<?php checked( $value ); ?> />
			<?php esc_html_e( '有効', 'contentpilot' ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( 'チェックを外すと、新しく作成する投稿では手動で有効化が必要になります。', 'contentpilot' ); ?>
		</p>
		<?php
	}

	/**
	 * 最小文字数フィールドを出力
	 *
	 * @return void
	 */
	public function render_field_min_word_count() {
		$value = get_option( 'contentpilot_min_word_count', 3000 );
		?>
		<input type="number"
			name="contentpilot_min_word_count"
			value="<?php echo esc_attr( $value ); ?>"
			min="0"
			max="50000"
			step="100"
			class="small-text" />
		<p class="description">
			<?php esc_html_e( 'この文字数以上の記事のみ固定ナビを表示します。', 'contentpilot' ); ?>
		</p>
		<?php
	}

	/* =========================================================================
	   設定ページの出力
	   ========================================================================= */

	/**
	 * 設定ページをレンダリング
	 *
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'ContentPilot 設定', 'contentpilot' ); ?></h1>

			<form method="post" action="options.php">
				<?php
				settings_fields( $this->option_group );
				do_settings_sections( $this->page_slug );
				submit_button();
				?>
			</form>

			<hr />

			<!-- 一括適用セクション -->
			<h2><?php esc_html_e( '一括適用', 'contentpilot' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'すべての投稿の固定ナビ設定を一括で変更します。この操作は取り消せません。', 'contentpilot' ); ?>
			</p>

			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row"><?php esc_html_e( 'すべて有効にする', 'contentpilot' ); ?></th>
						<td>
							<button type="button"
								id="contentpilot_enable_all"
								class="button button-secondary contentpilot-bulk-btn"
								data-action="contentpilot_enable_all">
								<?php esc_html_e( 'すべての投稿で有効にする', 'contentpilot' ); ?>
							</button>
							<span class="contentpilot-bulk-result" data-for="contentpilot_enable_all"></span>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'すべて無効にする', 'contentpilot' ); ?></th>
						<td>
							<button type="button"
								id="contentpilot_disable_all"
								class="button button-secondary contentpilot-bulk-btn"
								data-action="contentpilot_disable_all">
								<?php esc_html_e( 'すべての投稿で無効にする', 'contentpilot' ); ?>
							</button>
							<span class="contentpilot-bulk-result" data-for="contentpilot_disable_all"></span>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( '条件付きで有効にする', 'contentpilot' ); ?></th>
						<td>
							<button type="button"
								id="contentpilot_enable_long"
								class="button button-secondary contentpilot-bulk-btn"
								data-action="contentpilot_enable_long">
								<?php
								printf(
									/* translators: %s: minimum word count */
									esc_html__( '長文記事のみ有効にする（%s文字以上）', 'contentpilot' ),
									number_format_i18n( get_option( 'contentpilot_min_word_count', 3000 ) )
								);
								?>
							</button>
							<span class="contentpilot-bulk-result" data-for="contentpilot_enable_long"></span>
						</td>
					</tr>
				</tbody>
			</table>

			<style>
				.contentpilot-bulk-result {
					display: inline-block;
					margin-left: 10px;
					font-weight: 600;
				}
				.contentpilot-bulk-result.success { color: #00a32a; }
				.contentpilot-bulk-result.error   { color: #d63638; }
			</style>
		</div>
		<?php
	}

	/* =========================================================================
	   Ajax ハンドラ
	   ========================================================================= */

	/**
	 * Ajax: すべての投稿で有効にする
	 *
	 * @return void
	 */
	public function ajax_enable_all() {
		$this->verify_ajax_request();

		global $wpdb;

		// 全 publish 投稿の ID を取得
		$post_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s",
				'post',
				'publish'
			)
		);

		$count = 0;
		foreach ( $post_ids as $post_id ) {
			update_post_meta( (int) $post_id, '_contentpilot_display_mode', 'show_all' );
			update_post_meta( (int) $post_id, '_contentpilot_enabled', '1' );
			$count++;
		}

		wp_send_json_success( array(
			/* translators: %d: number of posts */
			'message' => sprintf( __( '%d件の投稿を有効にしました。', 'contentpilot' ), $count ),
			'count'   => $count,
		) );
	}

	/**
	 * Ajax: すべての投稿で無効にする
	 *
	 * @return void
	 */
	public function ajax_disable_all() {
		$this->verify_ajax_request();

		global $wpdb;

		$post_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s",
				'post',
				'publish'
			)
		);

		$count = 0;
		foreach ( $post_ids as $post_id ) {
			update_post_meta( (int) $post_id, '_contentpilot_display_mode', 'hide' );
			update_post_meta( (int) $post_id, '_contentpilot_enabled', '0' );
			$count++;
		}

		wp_send_json_success( array(
			/* translators: %d: number of posts */
			'message' => sprintf( __( '%d件の投稿を無効にしました。', 'contentpilot' ), $count ),
			'count'   => $count,
		) );
	}

	/**
	 * Ajax: 長文記事のみ有効にする
	 *
	 * @return void
	 */
	public function ajax_enable_long() {
		$this->verify_ajax_request();

		global $wpdb;

		$min_count = absint( get_option( 'contentpilot_min_word_count', 3000 ) );

		$posts = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID, post_content FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s",
				'post',
				'publish'
			)
		);

		$enabled  = 0;
		$disabled = 0;

		foreach ( $posts as $post ) {
			$text       = wp_strip_all_tags( $post->post_content );
			$char_count = mb_strlen( $text, 'UTF-8' );
			$post_id    = (int) $post->ID;

			if ( $char_count >= $min_count ) {
				update_post_meta( $post_id, '_contentpilot_display_mode', 'show_all' );
				update_post_meta( $post_id, '_contentpilot_enabled', '1' );
				$enabled++;
			} else {
				update_post_meta( $post_id, '_contentpilot_display_mode', 'hide' );
				update_post_meta( $post_id, '_contentpilot_enabled', '0' );
				$disabled++;
			}
		}

		wp_send_json_success( array(
			'message' => sprintf(
				/* translators: 1: enabled count, 2: disabled count */
				__( '有効: %1$d件、無効: %2$d件', 'contentpilot' ),
				$enabled,
				$disabled
			),
			'enabled'  => $enabled,
			'disabled' => $disabled,
		) );
	}

	/* =========================================================================
	   ヘルパー
	   ========================================================================= */

	/**
	 * Ajax リクエストの権限と nonce を検証
	 *
	 * @return void 検証失敗時は wp_send_json_error で終了
	 */
	private function verify_ajax_request() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array(
				'message' => __( '権限がありません。', 'contentpilot' ),
			) );
		}

		if ( ! check_ajax_referer( 'contentpilot_bulk_action', 'nonce', false ) ) {
			wp_send_json_error( array(
				'message' => __( 'セキュリティ検証に失敗しました。', 'contentpilot' ),
			) );
		}
	}

	/**
	 * チェックボックスのサニタイズ
	 *
	 * @param mixed $value 入力値
	 * @return bool
	 */
	public function sanitize_checkbox( $value ) {
		return (bool) $value;
	}
}
