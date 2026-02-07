<?php
/**
 * ContentPilot 管理画面クラス
 *
 * 投稿編集画面のメタボックスとカスタマイザーを管理
 *
 * @package ContentPilot
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ContentPilot_Admin {

	private static $instance = null;

	private function __construct() {}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * 管理画面の初期化（メタボックス・保存）
	 */
	public function init() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_meta' ) );
	}

	/**
	 * カスタマイザーの初期化
	 */
	public function init_customizer() {
		add_action( 'customize_register', array( $this, 'register_customizer' ) );
	}

	/* =========================================================================
	   メタボックス
	   ========================================================================= */

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
	 */
	public function render_meta_box( $post ) {
		wp_nonce_field( 'contentpilot_save_meta', 'contentpilot_meta_nonce' );

		// 現在の値を取得
		$display_mode = get_post_meta( $post->ID, '_contentpilot_display_mode', true );
		if ( '' === $display_mode ) {
			// 後方互換: 旧 _contentpilot_enabled を確認
			$old_enabled = get_post_meta( $post->ID, '_contentpilot_enabled', true );
			$display_mode = ( '0' === $old_enabled ) ? 'hide' : 'auto';
		}

		$selected_h2  = array();
		$custom_texts  = array();
		if ( 'select' === $display_mode ) {
			$selected_h2 = get_post_meta( $post->ID, '_contentpilot_selected_h2', true );
			$selected_h2 = is_array( $selected_h2 ) ? $selected_h2 : array();
			$custom_texts = get_post_meta( $post->ID, '_contentpilot_h2_custom_texts', true );
			$custom_texts = is_array( $custom_texts ) ? $custom_texts : array();
		}

		$preset = get_post_meta( $post->ID, '_contentpilot_preset', true );

		// 投稿内容からH2を取得
		$content = $post->post_content;
		if ( function_exists( 'do_blocks' ) ) {
			$content = do_blocks( $content );
		}
		preg_match_all( '/<h2[^>]*>(.*?)<\/h2>/is', $content, $matches );
		$h2_list = array();
		if ( ! empty( $matches[1] ) ) {
			foreach ( $matches[1] as $h2_text ) {
				$h2_list[] = wp_strip_all_tags( $h2_text );
			}
		}
		?>
		<style>
			.cp-radio-group { margin-bottom: 8px; }
			.cp-radio-group label { display: block; margin-bottom: 6px; cursor: pointer; }
			.cp-radio-group label:last-child { margin-bottom: 0; }
			#cp-h2-select-area { margin: 8px 0; padding: 8px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px; }
			.cp-h2-item { margin-bottom: 6px; }
			.cp-h2-item label { display: flex; align-items: center; gap: 4px; font-size: 13px; }
			.cp-h2-text-input { width: 100%; margin-top: 4px; font-size: 12px; }
			.cp-h2-text-input:disabled { opacity: 0.4; pointer-events: none; }
			.cp-select-row { margin-top: 10px; }
			.cp-select-row label { font-weight: 600; font-size: 12px; display: block; margin-bottom: 4px; }
			.cp-select-row select { width: 100%; }
		</style>

		<div class="cp-radio-group">
			<label>
				<input type="radio" name="contentpilot_display_mode" value="auto"
					<?php checked( $display_mode, 'auto' ); ?> />
				<?php esc_html_e( '固定ナビを表示（H2タグをそのまま反映）', 'contentpilot' ); ?>
			</label>
			<label>
				<input type="radio" name="contentpilot_display_mode" value="select"
					<?php checked( $display_mode, 'select' ); ?> />
				<?php esc_html_e( '表示する見出しを選択', 'contentpilot' ); ?>
			</label>
			<label>
				<input type="radio" name="contentpilot_display_mode" value="hide"
					<?php checked( $display_mode, 'hide' ); ?> />
				<?php esc_html_e( '固定ナビを非表示', 'contentpilot' ); ?>
			</label>
		</div>

		<div id="cp-h2-select-area" style="<?php echo 'select' === $display_mode ? '' : 'display:none;'; ?>">
			<?php if ( empty( $h2_list ) ) : ?>
				<p class="description"><?php esc_html_e( 'H2見出しが見つかりません。', 'contentpilot' ); ?></p>
			<?php else : ?>
				<?php foreach ( $h2_list as $index => $h2_text ) :
					$is_checked  = in_array( $index, $selected_h2, false );
					$custom_text = isset( $custom_texts[ $index ] ) ? $custom_texts[ $index ] : '';
				?>
					<div class="cp-h2-item">
						<label>
							<input type="checkbox"
								name="contentpilot_selected_h2[]"
								value="<?php echo esc_attr( $index ); ?>"
								class="cp-h2-checkbox"
								data-index="<?php echo esc_attr( $index ); ?>"
								<?php checked( $is_checked ); ?> />
							<?php echo esc_html( $h2_text ); ?>
						</label>
						<input type="text"
							name="contentpilot_h2_text_<?php echo esc_attr( $index ); ?>"
							class="cp-h2-text-input"
							data-index="<?php echo esc_attr( $index ); ?>"
							value="<?php echo esc_attr( $custom_text ); ?>"
							placeholder="<?php echo esc_attr( $h2_text ); ?>"
							<?php echo $is_checked ? '' : 'disabled'; ?> />
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>

		<div class="cp-select-row">
			<label><?php esc_html_e( 'デザインプリセット', 'contentpilot' ); ?></label>
			<select name="contentpilot_preset">
				<option value=""><?php esc_html_e( 'グローバル設定を使用', 'contentpilot' ); ?></option>
				<option value="simple" <?php selected( $preset, 'simple' ); ?>><?php esc_html_e( 'シンプル', 'contentpilot' ); ?></option>
				<option value="modern" <?php selected( $preset, 'modern' ); ?>><?php esc_html_e( 'モダン', 'contentpilot' ); ?></option>
				<option value="flat" <?php selected( $preset, 'flat' ); ?>><?php esc_html_e( 'フラット', 'contentpilot' ); ?></option>
				<option value="dark" <?php selected( $preset, 'dark' ); ?>><?php esc_html_e( 'ダーク', 'contentpilot' ); ?></option>
				<option value="theme" <?php selected( $preset, 'theme' ); ?>><?php esc_html_e( 'テーマ準拠', 'contentpilot' ); ?></option>
			</select>
		</div>

		<p class="description" style="margin-top:8px;">
			<?php esc_html_e( '文字数・H2数の条件を満たす場合に表示されます。', 'contentpilot' ); ?>
		</p>

		<script>
		(function(){
			// ラジオボタンの切り替えでH2選択エリアを表示/非表示
			var radios = document.querySelectorAll('input[name="contentpilot_display_mode"]');
			var area = document.getElementById('cp-h2-select-area');
			radios.forEach(function(r) {
				r.addEventListener('change', function() {
					area.style.display = this.value === 'select' ? '' : 'none';
				});
			});

			// チェックボックスでテキスト入力の有効/無効を切り替え
			document.querySelectorAll('.cp-h2-checkbox').forEach(function(cb) {
				cb.addEventListener('change', function() {
					var idx = this.getAttribute('data-index');
					var input = document.querySelector('.cp-h2-text-input[data-index="' + idx + '"]');
					if (input) {
						input.disabled = !this.checked;
					}
				});
			});
		})();
		</script>
		<?php
	}

	/**
	 * メタデータを保存
	 */
	public function save_meta( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! isset( $_POST['contentpilot_meta_nonce'] ) ||
			! wp_verify_nonce( $_POST['contentpilot_meta_nonce'], 'contentpilot_save_meta' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// 表示モード
		$mode = isset( $_POST['contentpilot_display_mode'] ) ? sanitize_text_field( $_POST['contentpilot_display_mode'] ) : 'auto';
		if ( ! in_array( $mode, array( 'auto', 'select', 'hide' ), true ) ) {
			$mode = 'auto';
		}
		update_post_meta( $post_id, '_contentpilot_display_mode', $mode );

		// 後方互換: _contentpilot_enabled も更新
		update_post_meta( $post_id, '_contentpilot_enabled', 'hide' === $mode ? '0' : '1' );

		// H2選択データ
		if ( 'select' === $mode ) {
			$selected = isset( $_POST['contentpilot_selected_h2'] ) ? array_map( 'intval', $_POST['contentpilot_selected_h2'] ) : array();
			update_post_meta( $post_id, '_contentpilot_selected_h2', $selected );

			$texts = array();
			foreach ( $selected as $idx ) {
				$key = 'contentpilot_h2_text_' . $idx;
				if ( isset( $_POST[ $key ] ) ) {
					$texts[ $idx ] = sanitize_text_field( $_POST[ $key ] );
				}
			}
			update_post_meta( $post_id, '_contentpilot_h2_custom_texts', $texts );
		} else {
			delete_post_meta( $post_id, '_contentpilot_selected_h2' );
			delete_post_meta( $post_id, '_contentpilot_h2_custom_texts' );
		}

		// プリセット
		$preset = isset( $_POST['contentpilot_preset'] ) ? sanitize_text_field( $_POST['contentpilot_preset'] ) : '';
		update_post_meta( $post_id, '_contentpilot_preset', $preset );
	}

	/* =========================================================================
	   カスタマイザー
	   ========================================================================= */

	/**
	 * カスタマイザー設定を登録
	 */
	public function register_customizer( $wp_customize ) {

		// --- セクション: デザイン ---
		$wp_customize->add_section( 'contentpilot_design', array(
			'title'    => __( 'ContentPilot - デザイン', 'contentpilot' ),
			'priority' => 200,
		) );

		// プリセット
		$wp_customize->add_setting( 'contentpilot_preset', array(
			'default'           => 'simple',
			'sanitize_callback' => array( $this, 'sanitize_preset' ),
		) );
		$wp_customize->add_control( 'contentpilot_preset', array(
			'label'   => __( 'デザインプリセット', 'contentpilot' ),
			'section' => 'contentpilot_design',
			'type'    => 'select',
			'choices' => array(
				'simple' => __( 'シンプル', 'contentpilot' ),
				'modern' => __( 'モダン', 'contentpilot' ),
				'flat'   => __( 'フラット', 'contentpilot' ),
				'dark'   => __( 'ダーク', 'contentpilot' ),
				'theme'  => __( 'テーマ準拠', 'contentpilot' ),
			),
		) );

		// 配置位置
		$wp_customize->add_setting( 'contentpilot_position', array(
			'default'           => 'top',
			'sanitize_callback' => array( $this, 'sanitize_position' ),
		) );
		$wp_customize->add_control( 'contentpilot_position', array(
			'label'   => __( '配置位置', 'contentpilot' ),
			'section' => 'contentpilot_design',
			'type'    => 'radio',
			'choices' => array(
				'top'    => __( '上部固定', 'contentpilot' ),
				'bottom' => __( '下部固定', 'contentpilot' ),
			),
		) );

		// 背景色
		$wp_customize->add_setting( 'contentpilot_bg_color', array(
			'default'           => '#ffffff',
			'sanitize_callback' => 'sanitize_hex_color',
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'contentpilot_bg_color', array(
			'label'   => __( '背景色', 'contentpilot' ),
			'section' => 'contentpilot_design',
		) ) );

		// テキスト色
		$wp_customize->add_setting( 'contentpilot_text_color', array(
			'default'           => '#333333',
			'sanitize_callback' => 'sanitize_hex_color',
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'contentpilot_text_color', array(
			'label'   => __( 'テキスト色', 'contentpilot' ),
			'section' => 'contentpilot_design',
		) ) );

		// アクティブ色
		$wp_customize->add_setting( 'contentpilot_active_color', array(
			'default'           => '#0073aa',
			'sanitize_callback' => 'sanitize_hex_color',
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'contentpilot_active_color', array(
			'label'   => __( 'アクティブ色', 'contentpilot' ),
			'section' => 'contentpilot_design',
		) ) );

		// フォントサイズ
		$wp_customize->add_setting( 'contentpilot_font_size', array(
			'default'           => 14,
			'sanitize_callback' => 'absint',
		) );
		$wp_customize->add_control( 'contentpilot_font_size', array(
			'label'       => __( 'フォントサイズ (px)', 'contentpilot' ),
			'section'     => 'contentpilot_design',
			'type'        => 'number',
			'input_attrs' => array( 'min' => 10, 'max' => 20, 'step' => 1 ),
		) );

		// 角丸
		$wp_customize->add_setting( 'contentpilot_border_radius', array(
			'default'           => false,
			'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
		) );
		$wp_customize->add_control( 'contentpilot_border_radius', array(
			'label'   => __( '角丸にする', 'contentpilot' ),
			'section' => 'contentpilot_design',
			'type'    => 'checkbox',
		) );

		// 影
		$wp_customize->add_setting( 'contentpilot_shadow', array(
			'default'           => true,
			'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
		) );
		$wp_customize->add_control( 'contentpilot_shadow', array(
			'label'   => __( '影を表示する', 'contentpilot' ),
			'section' => 'contentpilot_design',
			'type'    => 'checkbox',
		) );

		// --- セクション: 共通設定 ---
		$wp_customize->add_section( 'contentpilot_common', array(
			'title'    => __( 'ContentPilot - 共通設定', 'contentpilot' ),
			'priority' => 201,
		) );

		// 最小文字数
		$wp_customize->add_setting( 'contentpilot_min_word_count', array(
			'default'           => 3000,
			'sanitize_callback' => 'absint',
		) );
		$wp_customize->add_control( 'contentpilot_min_word_count', array(
			'label'       => __( '最小文字数', 'contentpilot' ),
			'section'     => 'contentpilot_common',
			'type'        => 'number',
			'input_attrs' => array( 'min' => 0, 'max' => 20000, 'step' => 100 ),
		) );

		// スクロール表示開始
		$wp_customize->add_setting( 'contentpilot_show_after_scroll', array(
			'default'           => 100,
			'sanitize_callback' => 'absint',
		) );
		$wp_customize->add_control( 'contentpilot_show_after_scroll', array(
			'label'       => __( 'スクロール表示開始位置 (px)', 'contentpilot' ),
			'section'     => 'contentpilot_common',
			'type'        => 'number',
			'input_attrs' => array( 'min' => 0, 'max' => 1000, 'step' => 10 ),
		) );

		// 固定ヘッダーセレクタ PC
		$wp_customize->add_setting( 'contentpilot_fixed_header_selector_pc', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
		) );
		$wp_customize->add_control( 'contentpilot_fixed_header_selector_pc', array(
			'label'       => __( 'テーマ固定ヘッダー セレクタ (PC)', 'contentpilot' ),
			'description' => __( '例: #fix_header, .site-header', 'contentpilot' ),
			'section'     => 'contentpilot_common',
			'type'        => 'text',
		) );

		// 固定ヘッダーセレクタ SP
		$wp_customize->add_setting( 'contentpilot_fixed_header_selector_sp', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
		) );
		$wp_customize->add_control( 'contentpilot_fixed_header_selector_sp', array(
			'label'       => __( 'テーマ固定ヘッダー セレクタ (SP)', 'contentpilot' ),
			'description' => __( '空欄の場合はPC用セレクタを使用', 'contentpilot' ),
			'section'     => 'contentpilot_common',
			'type'        => 'text',
		) );
	}

	/* =========================================================================
	   サニタイズ関数
	   ========================================================================= */

	public function sanitize_preset( $value ) {
		$valid = array( 'simple', 'modern', 'flat', 'dark', 'theme' );
		return in_array( $value, $valid, true ) ? $value : 'simple';
	}

	public function sanitize_position( $value ) {
		return in_array( $value, array( 'top', 'bottom' ), true ) ? $value : 'top';
	}

	public function sanitize_checkbox( $value ) {
		return (bool) $value;
	}
}
