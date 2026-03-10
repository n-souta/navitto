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
			__( 'Navitto 設定', 'navitto' ),
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
				'confirmEnableAll'   => __( 'すべての投稿で固定ナビを有効にしますか？', 'navitto' ),
				'confirmDisableAll'  => __( 'すべての投稿で固定ナビを無効にしますか？', 'navitto' ),
				'processing'         => __( '処理中...', 'navitto' ),
				'error'              => __( 'エラーが発生しました。', 'navitto' ),
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
			__( 'デフォルト動作', 'navitto' ),
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
			__( '新規投稿でデフォルトで有効にする', 'navitto' ),
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
		echo '<p>' . esc_html__( '新規投稿作成時のデフォルト動作を設定します。', 'navitto' ) . '</p>';
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
			<?php esc_html_e( '有効', 'navitto' ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( 'チェックを外すと、新しく作成する投稿では手動で有効化が必要になります。', 'navitto' ); ?>
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
			<h1><?php esc_html_e( 'Navitto 設定', 'navitto' ); ?></h1>

			<form method="post" action="options.php">
				<?php
				settings_fields( $this->option_group );
				do_settings_sections( $this->page_slug );
				?>

				<hr />

				<!-- 一括適用セクション -->
				<h2><?php esc_html_e( '一括適用', 'navitto' ); ?></h2>
				<p class="description">
					<?php esc_html_e( 'すべての投稿の固定ナビ設定を一括で上書きします。必要に応じて「すべて有効にする」「すべて無効にする」の反対の操作を実行すれば、再度一括で戻せます。', 'navitto' ); ?>
				</p>

				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row"><?php esc_html_e( 'すべて有効にする', 'navitto' ); ?></th>
							<td>
								<button type="button"
									id="navitto_enable_all"
									class="button button-secondary navitto-bulk-btn"
									data-action="navitto_enable_all">
									<?php esc_html_e( 'すべての投稿で有効にする', 'navitto' ); ?>
								</button>
								<span class="navitto-bulk-result" data-for="navitto_enable_all"></span>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'すべて無効にする', 'navitto' ); ?></th>
							<td>
								<button type="button"
									id="navitto_disable_all"
									class="button button-secondary navitto-bulk-btn"
									data-action="navitto_disable_all">
									<?php esc_html_e( 'すべての投稿で無効にする', 'navitto' ); ?>
								</button>
								<span class="navitto-bulk-result" data-for="navitto_disable_all"></span>
							</td>
						</tr>
					</tbody>
				</table>

				<?php submit_button(); ?>
			</form>

			<style>
				.navitto-bulk-result {
					display: inline-block;
					margin-left: 10px;
					font-weight: 600;
				}
				.navitto-bulk-result.success { color: #00a32a; }
				.navitto-bulk-result.error   { color: #d63638; }
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
			'message' => sprintf( __( '%d件の投稿を有効にしました。', 'navitto' ), $count ),
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
			'message' => sprintf( __( '%d件の投稿を無効にしました。', 'navitto' ), $count ),
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
				'message' => __( '権限がありません。', 'navitto' ),
			) );
		}

		if ( ! check_ajax_referer( 'navitto_bulk_action', 'nonce', false ) ) {
			wp_send_json_error( array(
				'message' => __( 'セキュリティ検証に失敗しました。', 'navitto' ),
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
