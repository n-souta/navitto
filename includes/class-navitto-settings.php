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

		// Ajax ハンドラ
		add_action( 'wp_ajax_navitto_enable_all',   array( $this, 'ajax_enable_all' ) );
		add_action( 'wp_ajax_navitto_disable_all',  array( $this, 'ajax_disable_all' ) );
		add_action( 'wp_ajax_navitto_activate_license', array( $this, 'ajax_activate_license' ) );
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
			'licenseNonce'  => wp_create_nonce( 'navitto_license_activate' ),
			'i18n'          => array(
				'confirmEnableAll'   => __( 'すべての投稿で固定ナビを有効にしますか？', 'navitto' ),
				'confirmDisableAll'  => __( 'すべての投稿で固定ナビを無効にしますか？', 'navitto' ),
				'processing'        => __( '処理中...', 'navitto' ),
				'error'             => __( 'エラーが発生しました。', 'navitto' ),
				'licenseActivate'   => __( '有効化', 'navitto' ),
				'licenseActivating' => __( '確認中...', 'navitto' ),
				'licenseValid'      => __( 'ライセンスは有効です。', 'navitto' ),
				'licenseInvalid'    => __( 'ライセンスが無効です。', 'navitto' ),
				'licenseEmpty'      => __( 'ライセンスキーを入力してください。', 'navitto' ),
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

	/**
	 * ライセンスセクションの説明と入力欄
	 *
	 * @return void
	 */
	public function render_section_license() {
		$license_key   = get_option( 'navitto_license_key', '' );
		$license_status = get_option( 'navitto_license_status', '' );
		$is_valid      = ( $license_status === 'valid' );
		?>
		<p><?php esc_html_e( 'Navitto Pro のライセンスキーを入力し「有効化」をクリックすると、有料版機能が利用可能になります。', 'navitto' ); ?></p>
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><label for="navitto_license_key"><?php esc_html_e( 'ライセンスキー', 'navitto' ); ?></label></th>
					<td>
						<input type="text"
							id="navitto_license_key"
							class="regular-text"
							name="navitto_license_key"
							value="<?php echo esc_attr( $license_key ); ?>"
							placeholder="XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX"
							autocomplete="off" />
						<button type="button"
							id="navitto_activate_license"
							class="button button-secondary">
							<?php esc_html_e( '有効化', 'navitto' ); ?>
						</button>
						<span id="navitto_license_result" class="navitto-license-result" aria-live="polite"></span>
						<?php if ( $is_valid ) : ?>
							<p class="description navitto-license-status navitto-license-status-valid">
								<?php esc_html_e( 'ライセンスは有効です。', 'navitto' ); ?>
							</p>
						<?php elseif ( $license_key && ! $is_valid ) : ?>
							<p class="description navitto-license-status navitto-license-status-invalid">
								<?php esc_html_e( 'ライセンスが無効です。キーを確認するか、再度有効化してください。', 'navitto' ); ?>
							</p>
						<?php endif; ?>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
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

				<hr />

				<!-- ライセンスセクション（最後に配置） -->
				<h2><?php esc_html_e( 'ライセンス', 'navitto' ); ?></h2>
				<?php $this->render_section_license(); ?>

				<?php submit_button(); ?>
			</form>

			<style>
				.navitto-bulk-result,
				.navitto-license-result {
					display: inline-block;
					margin-left: 10px;
					font-weight: 600;
				}
				.navitto-bulk-result.success,
				.navitto-license-result.success { color: #00a32a; }
				.navitto-bulk-result.error,
				.navitto-license-result.error   { color: #d63638; }
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
	 * Ajax: ライセンスキーを有効化（Lemon Squeezy で検証）
	 *
	 * @return void
	 */
	public function ajax_activate_license() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( '権限がありません。', 'navitto' ) ) );
		}
		if ( ! check_ajax_referer( 'navitto_license_activate', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'セキュリティ検証に失敗しました。', 'navitto' ) ) );
		}

		$license_key = isset( $_POST['license_key'] ) ? sanitize_text_field( wp_unslash( $_POST['license_key'] ) ) : '';
		if ( '' === $license_key ) {
			wp_send_json_error( array( 'message' => __( 'ライセンスキーを入力してください。', 'navitto' ) ) );
		}

		$result = $this->validate_license_with_lemonsqueezy( $license_key );

		if ( is_wp_error( $result ) ) {
			update_option( 'navitto_license_status', '' );
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		if ( empty( $result['valid'] ) ) {
			update_option( 'navitto_license_status', '' );
			$error_msg = ! empty( $result['error'] ) ? $result['error'] : __( 'ライセンスが無効です。', 'navitto' );
			wp_send_json_error( array( 'message' => $error_msg ) );
		}

		// 有効な場合のみキーとステータスを保存
		update_option( 'navitto_license_key', $license_key );
		update_option( 'navitto_license_status', 'valid' );
		if ( ! empty( $result['meta']['customer_email'] ) ) {
			update_option( 'navitto_license_email', $result['meta']['customer_email'] );
		} else {
			delete_option( 'navitto_license_email' );
		}

		wp_send_json_success( array( 'message' => __( 'ライセンスは有効です。', 'navitto' ) ) );
	}

	/* =========================================================================
	   ヘルパー
	   ========================================================================= */

	/**
	 * Lemon Squeezy API でライセンスキーを検証
	 *
	 * @param string $license_key ライセンスキー
	 * @return array|WP_Error 成功時は API レスポンス配列、失敗時は WP_Error
	 */
	private function validate_license_with_lemonsqueezy( $license_key ) {
		$url = 'https://api.lemonsqueezy.com/v1/licenses/validate';
		$body = array( 'license_key' => $license_key );

		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 15,
				'headers' => array(
					'Accept'       => 'application/json',
					'Content-Type' => 'application/x-www-form-urlencoded',
				),
				'body'    => $body,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body_raw = wp_remote_retrieve_body( $response );
		$data = json_decode( $body_raw, true );

		if ( $code !== 200 ) {
			$msg = isset( $data['error'] ) ? $data['error'] : sprintf(
				/* translators: %d: HTTP status code */
				__( 'ライセンスの確認に失敗しました。（HTTP %d）', 'navitto' ),
				$code
			);
			return new WP_Error( 'navitto_license_http_error', $msg );
		}

		if ( ! is_array( $data ) ) {
			return new WP_Error( 'navitto_license_invalid_response', __( 'ライセンスの確認に失敗しました。', 'navitto' ) );
		}

		return $data;
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
