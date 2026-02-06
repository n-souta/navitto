<?php
/**
 * ContentPilot 管理画面クラス
 *
 * 投稿編集画面のメタボックスを管理するクラス
 *
 * @package ContentPilot
 * @since   1.0.0
 */

// 直接アクセスを防ぐ
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ContentPilot_Admin
 *
 * 管理画面機能を管理
 *
 * @since 1.0.0
 */
class ContentPilot_Admin {

	/**
	 * シングルトンインスタンス
	 *
	 * @var ContentPilot_Admin|null
	 */
	private static $instance = null;

	/**
	 * コンストラクタ
	 */
	private function __construct() {
		// シングルトン
	}

	/**
	 * シングルトンインスタンスを取得
	 *
	 * @return ContentPilot_Admin
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
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_meta' ) );
	}

	/**
	 * メタボックスを追加
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'contentpilot_settings',
			__( 'ContentPilot', 'contentpilot' ),
			array( $this, 'render_meta_box' ),
			'post',
			'side',
			'default'
		);
	}

	/**
	 * メタボックスの内容を出力
	 *
	 * @param WP_Post $post 投稿オブジェクト
	 */
	public function render_meta_box( $post ) {
		// nonce生成
		wp_nonce_field( 'contentpilot_save_meta', 'contentpilot_meta_nonce' );

		// 現在の値を取得
		$enabled = get_post_meta( $post->ID, '_contentpilot_enabled', true );

		// デフォルトは有効（空の場合は有効）
		if ( '' === $enabled ) {
			$enabled = '1';
		}
		?>
		<p>
			<label>
				<input type="checkbox" 
				       name="contentpilot_enabled" 
				       value="1" 
				       <?php checked( $enabled, '1' ); ?> />
				<?php esc_html_e( 'このページで固定ナビを表示', 'contentpilot' ); ?>
			</label>
		</p>
		<p class="description">
			<?php esc_html_e( '文字数3000文字以上、H2見出し2つ以上の場合に表示されます。', 'contentpilot' ); ?>
		</p>
		<?php
	}

	/**
	 * メタデータを保存
	 *
	 * @param int $post_id 投稿ID
	 */
	public function save_meta( $post_id ) {
		// 自動保存の場合は何もしない
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// nonce検証
		if ( ! isset( $_POST['contentpilot_meta_nonce'] ) ||
		     ! wp_verify_nonce( $_POST['contentpilot_meta_nonce'], 'contentpilot_save_meta' ) ) {
			return;
		}

		// 権限チェック
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// 有効/無効を保存
		$enabled = isset( $_POST['contentpilot_enabled'] ) ? '1' : '0';
		update_post_meta( $post_id, '_contentpilot_enabled', $enabled );
	}
}
