<?php
/**
 * Navitto 設定画面クラス
 *
 * 管理画面「設定 → Navitto」でデフォルト動作・一括適用を管理
 *
 * @package Navitto
 * @since   1.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Navitto_Settings {

	/**
	 * シングルトンインスタンス
	 *
	 * @var Navitto_Settings|null
	 */
	private static $instance = null;

	/**
	 * オプショングループ名
	 *
	 * @var string
	 */
	private $option_group = 'navitto_settings_group';

	/**
	 * 設定ページスラッグ
	 *
	 * @var string
	 */
	private $page_slug = 'navitto-settings';

	/**
	 * コンストラクタ（private）
	 */
	private function __construct() {}

	/**
	 * シングルトンインスタンスを取得
	 *
	 * @return Navitto_Settings
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

		// Ajax ハンドラ（一括適用）
		add_action( 'wp_ajax_navitto_enable_all',   array( $this, 'ajax_enable_all' ) );
		add_action( 'wp_ajax_navitto_disable_all',  array( $this, 'ajax_disable_all' ) );
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
			__( 'Navitto Settings', 'navitto' ),
			__( 'Navitto', 'navitto' ),
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
		wp_enqueue_style(
			'navitto-admin-settings',
			NAVITTO_PLUGIN_URL . 'assets/css/admin-settings.css',
			array(),
			NAVITTO_VERSION
		);

		wp_enqueue_script(
			'navitto-admin-settings',
			NAVITTO_PLUGIN_URL . 'assets/js/admin-settings.js',
			array( 'jquery' ),
			NAVITTO_VERSION,
			true
		);

		wp_localize_script( 'navitto-admin-settings', 'navittoAdmin', array(
			'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
			'nonce'         => wp_create_nonce( 'navitto_bulk_action' ),
			'i18n'          => array(
				'confirmEnableAll'   => __( 'Enable the fixed nav on all posts?', 'navitto' ),
				'confirmDisableAll'  => __( 'Disable the fixed nav on all posts?', 'navitto' ),
				'processing'         => __( 'Processing...', 'navitto' ),
				'error'              => __( 'An error has occurred.', 'navitto' ),
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
			'navitto_section_default',
			__( 'Default behavior', 'navitto' ),
			array( $this, 'render_section_default' ),
			$this->page_slug
		);

		register_setting( $this->option_group, 'navitto_default_enabled', array(
			'type'              => 'boolean',
			'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
			'default'           => true,
		) );

		add_settings_field(
			'navitto_default_enabled',
			__( 'Enable by default for new posts', 'navitto' ),
			array( $this, 'render_field_default_enabled' ),
			$this->page_slug,
			'navitto_section_default'
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
		echo '<p>' . esc_html__( 'Configure the default behavior when creating new posts.', 'navitto' ) . '</p>';
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
		$value = get_option( 'navitto_default_enabled', true );
		?>
		<label>
			<input type="checkbox"
				name="navitto_default_enabled"
				value="1"
				<?php checked( $value ); ?> />
			<?php esc_html_e( 'Enabled', 'navitto' ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( 'If unchecked, the fixed nav must be enabled manually on each new post.', 'navitto' ); ?>
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
			<h1><?php esc_html_e( 'Navitto Settings', 'navitto' ); ?></h1>

			<form method="post" action="options.php">
				<?php
				settings_fields( $this->option_group );
				do_settings_sections( $this->page_slug );
				?>

				<hr />

				<!-- 一括適用セクション -->
				<h2><?php esc_html_e( 'Bulk apply', 'navitto' ); ?></h2>
				<p class="description">
					<?php esc_html_e( 'Override the fixed nav setting for all posts at once. You can later run the opposite action to revert the global change.', 'navitto' ); ?>
				</p>

				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row"><?php esc_html_e( 'Enable on all posts', 'navitto' ); ?></th>
							<td>
								<button type="button"
									id="navitto_enable_all"
									class="button button-secondary navitto-bulk-btn"
									data-action="navitto_enable_all">
									<?php esc_html_e( 'Enable the fixed nav on all posts', 'navitto' ); ?>
								</button>
								<span class="navitto-bulk-result" data-for="navitto_enable_all"></span>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Disable on all posts', 'navitto' ); ?></th>
							<td>
								<button type="button"
									id="navitto_disable_all"
									class="button button-secondary navitto-bulk-btn"
									data-action="navitto_disable_all">
									<?php esc_html_e( 'Disable the fixed nav on all posts', 'navitto' ); ?>
								</button>
								<span class="navitto-bulk-result" data-for="navitto_disable_all"></span>
							</td>
						</tr>
					</tbody>
				</table>

				<?php submit_button(); ?>
			</form>
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

		// 全 publish 投稿の ID を取得（get_posts でキャッシュ・抽象化を利用）
		$post_ids = get_posts( array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		) );

		$count = 0;
		foreach ( $post_ids as $post_id ) {
			// 一括無効フラグのみ解除（display_mode や選択見出しは変更しない）
			delete_post_meta( (int) $post_id, '_navitto_bulk_disabled' );
			$count++;
		}

		wp_send_json_success( array(
			/* translators: %d: number of posts */
			'message' => sprintf( __( '%d posts enabled.', 'navitto' ), $count ),
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

		$post_ids = get_posts( array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		) );

		$count = 0;
		foreach ( $post_ids as $post_id ) {
			// 一括無効フラグのみ付与（display_mode や選択見出しは変更しない＝再度有効にすると元の設定に戻る）
			update_post_meta( (int) $post_id, '_navitto_bulk_disabled', '1' );
			$count++;
		}

		wp_send_json_success( array(
			/* translators: %d: number of posts */
			'message' => sprintf( __( '%d posts disabled.', 'navitto' ), $count ),
			'count'   => $count,
		) );
	}

	/**
	 * Ajax リクエストの権限と nonce を検証
	 *
	 * @return void 検証失敗時は wp_send_json_error で終了
	 */
	private function verify_ajax_request() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array(
				'message' => __( 'You do not have permission to perform this action.', 'navitto' ),
			) );
		}

		if ( ! check_ajax_referer( 'navitto_bulk_action', 'nonce', false ) ) {
			wp_send_json_error( array(
				'message' => __( 'Security check failed.', 'navitto' ),
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
