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
		add_action( 'customize_register', array( $this, 'register_customizer' ) );
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
		$enabled       = get_post_meta( $post->ID, '_contentpilot_enabled', true );
		$force_display = get_post_meta( $post->ID, '_contentpilot_force_display', true );

		// デフォルトは有効（空の場合は有効）
		if ( '' === $enabled ) {
			$enabled = '1';
		}

		// 現在の投稿の文字数とH2数を表示（診断用）
		$content          = $post->post_content;
		$rendered_content = do_blocks( $content );
		$text             = wp_strip_all_tags( $rendered_content );
		$text_clean       = preg_replace( '/\s+/', '', $text );
		$word_count       = mb_strlen( $text_clean, 'UTF-8' );
		$min_word_count   = get_option( 'contentpilot_min_word_count', 3000 );

		preg_match_all( '/<h2[^>]*>.*?<\/h2>/is', $rendered_content, $matches );
		$h2_count = count( $matches[0] );
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
		<p>
			<label>
				<input type="checkbox" 
				       name="contentpilot_force_display" 
				       value="1" 
				       <?php checked( $force_display, '1' ); ?> />
				<?php esc_html_e( '条件を無視して強制表示', 'contentpilot' ); ?>
			</label>
		</p>
		<hr />
		<p class="description">
			<strong><?php esc_html_e( '表示条件の状態:', 'contentpilot' ); ?></strong><br />
			<?php
			printf(
				/* translators: %1$d: current word count, %2$d: minimum required */
				esc_html__( '文字数: %1$d / %2$d文字 %3$s', 'contentpilot' ),
				$word_count,
				$min_word_count,
				$word_count >= $min_word_count ? '✅' : '❌'
			);
			?>
			<br />
			<?php
			printf(
				/* translators: %d: H2 heading count */
				esc_html__( 'H2見出し数: %d個（2個以上必要）%s', 'contentpilot' ),
				$h2_count,
				$h2_count >= 2 ? '✅' : '❌'
			);
			?>
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

		// 強制表示を保存
		$force_display = isset( $_POST['contentpilot_force_display'] ) ? '1' : '0';
		update_post_meta( $post_id, '_contentpilot_force_display', $force_display );
	}

	/**
	 * カスタマイザーに設定を追加
	 *
	 * @param WP_Customize_Manager $wp_customize カスタマイザーマネージャー
	 */
	public function register_customizer( $wp_customize ) {
		// セクション追加
		$wp_customize->add_section(
			'contentpilot_common_settings',
			array(
				'title'    => __( 'ContentPilot 共通設定', 'contentpilot' ),
				'priority' => 160,
			)
		);

		// 固定ヘッダーセレクタ（PC）
		$wp_customize->add_setting(
			'contentpilot_fixed_header_selector_pc',
			array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			'contentpilot_fixed_header_selector_pc',
			array(
				'label'       => __( '固定ヘッダーのCSSセレクタ（PC）', 'contentpilot' ),
				'description' => __( 'テーマの固定ヘッダーのCSSセレクタを入力してください。例: #header, .l-header', 'contentpilot' ),
				'section'     => 'contentpilot_common_settings',
				'type'        => 'text',
			)
		);

		// 固定ヘッダーセレクタ（SP）
		$wp_customize->add_setting(
			'contentpilot_fixed_header_selector_sp',
			array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			'contentpilot_fixed_header_selector_sp',
			array(
				'label'       => __( '固定ヘッダーのCSSセレクタ（SP）', 'contentpilot' ),
				'description' => __( 'スマートフォン表示時の固定ヘッダーのCSSセレクタを入力してください。PCと同じ場合は空欄でOKです。', 'contentpilot' ),
				'section'     => 'contentpilot_common_settings',
				'type'        => 'text',
			)
		);
	}
}
