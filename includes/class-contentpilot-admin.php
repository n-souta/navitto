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
	 * 管理画面の初期化（メタボックス・保存・アセット）
	 */
	public function init() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_meta' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * メタボックス用のCSS/JSを投稿編集画面のみでエンキュー（#8 Structure）
	 *
	 * @param string $hook_suffix 現在の管理画面フック名
	 */
	public function enqueue_admin_assets( $hook_suffix ) {
		if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || 'post' !== $screen->post_type ) {
			return;
		}

		wp_enqueue_style(
			'contentpilot-admin-metabox',
			CONTENTPILOT_PLUGIN_URL . 'assets/css/admin-metabox.css',
			array(),
			CONTENTPILOT_VERSION
		);

		wp_enqueue_script(
			'contentpilot-admin-metabox',
			CONTENTPILOT_PLUGIN_URL . 'assets/js/admin-metabox.js',
			array(),
			CONTENTPILOT_VERSION,
			true
		);
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
			$old_enabled = get_post_meta( $post->ID, '_contentpilot_enabled', true );
			$display_mode = ( '0' === $old_enabled ) ? 'hide' : 'show_all';
		}
		// 後方互換: auto → show_all
		if ( 'auto' === $display_mode ) {
			$display_mode = 'show_all';
		}

		$selected_h2   = array();
		$custom_texts  = array();
		if ( 'select' === $display_mode ) {
			$selected_h2 = get_post_meta( $post->ID, '_contentpilot_selected_h2', true );
			$selected_h2 = is_array( $selected_h2 ) ? $selected_h2 : array();
			$custom_texts = get_post_meta( $post->ID, '_contentpilot_h2_custom_texts', true );
			$custom_texts = is_array( $custom_texts ) ? $custom_texts : array();
		}

		// トリガー設定
		$trigger_type      = get_post_meta( $post->ID, '_contentpilot_trigger_type', true );
		$trigger_type      = $trigger_type ? $trigger_type : 'immediate';
		$trigger_nth       = get_post_meta( $post->ID, '_contentpilot_trigger_nth', true );
		$trigger_nth       = $trigger_nth ? absint( $trigger_nth ) : 2;
		$trigger_scroll_px = get_post_meta( $post->ID, '_contentpilot_trigger_scroll_px', true );
		$trigger_scroll_px = $trigger_scroll_px ? absint( $trigger_scroll_px ) : 300;

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

		$is_select = ( 'select' === $display_mode );
		?>
		<div class="contentpilot-meta-box">
			<!-- 表示モード -->
			<div class="cp-radio-group">
				<label>
					<input type="radio" name="contentpilot_display_mode" value="show_all"
						<?php checked( $display_mode, 'show_all' ); ?> />
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

			<!-- 見出し選択（select時のみ表示） -->
			<div id="cp-h2-select-area" style="<?php echo $is_select ? '' : 'display:none;'; ?>">
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

			<!-- 表示開始位置（select時のみ表示） -->
			<div class="cp-trigger-settings contentpilot-trigger-settings" style="<?php echo $is_select ? '' : 'display:none;'; ?>">
				<h4><?php esc_html_e( '表示開始位置', 'contentpilot' ); ?></h4>

				<label>
					<input type="radio" name="_contentpilot_trigger_type" value="immediate"
						<?php checked( $trigger_type, 'immediate' ); ?> />
					<?php esc_html_e( 'ページ上部から', 'contentpilot' ); ?>
				</label>
				<p class="description"><?php esc_html_e( '選択した見出しがページ上部に来たら固定ナビを表示', 'contentpilot' ); ?></p>

				<label>
					<input type="radio" name="_contentpilot_trigger_type" value="first_selected"
						<?php checked( $trigger_type, 'first_selected' ); ?> />
					<?php esc_html_e( '選択した最初の見出しを通過後', 'contentpilot' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'チェックを入れた最初の見出しを通過したら表示', 'contentpilot' ); ?></p>

				<label>
					<input type="radio" name="_contentpilot_trigger_type" value="nth_selected"
						<?php checked( $trigger_type, 'nth_selected' ); ?> />
					<span class="cp-trigger-inline">
						<input type="number" name="_contentpilot_trigger_nth"
							value="<?php echo esc_attr( $trigger_nth ); ?>"
							min="1" max="99" style="width:60px;" />
						<?php esc_html_e( '番目の見出しを通過後', 'contentpilot' ); ?>
					</span>
				</label>
				<p class="description"><?php esc_html_e( 'チェックを入れたN番目の見出しを通過したら表示', 'contentpilot' ); ?></p>

				<label>
					<input type="radio" name="_contentpilot_trigger_type" value="scroll_px"
						<?php checked( $trigger_type, 'scroll_px' ); ?> />
					<span class="cp-trigger-inline">
						<input type="number" name="_contentpilot_trigger_scroll_px"
							value="<?php echo esc_attr( $trigger_scroll_px ); ?>"
							min="0" max="10000" step="50" style="width:80px;" />
						<?php esc_html_e( 'px スクロール後', 'contentpilot' ); ?>
					</span>
				</label>
			</div>

			<!-- デザインプリセット -->
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

			<!-- 固定ナビの表示方法 -->
			<div class="cp-nav-width-setting" style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #ddd;">
				<label style="font-weight: 600; font-size: 12px; display: block; margin-bottom: 6px;">
					<?php esc_html_e( '固定ナビの表示方法', 'contentpilot' ); ?>
				</label>
				<p class="description" style="margin: 0 0 6px; font-size: 12px;">
					<?php esc_html_e( '見出しが多い場合の表示方法を選択します', 'contentpilot' ); ?>
				</p>
				<?php
				$nav_width = get_post_meta( $post->ID, '_contentpilot_nav_width', true );
				$nw_options = array(
					''       => __( 'デフォルト設定を使用（カスタマイザーの設定）', 'contentpilot' ),
					'scroll' => __( '横スクロール可能（全て表示）', 'contentpilot' ),
					'equal'  => __( '均等割（はみ出し非表示）', 'contentpilot' ),
				);
				foreach ( $nw_options as $val => $label ) : ?>
					<label style="display: block; margin-bottom: 4px; font-size: 13px;">
						<input type="radio" name="_contentpilot_nav_width"
							value="<?php echo esc_attr( $val ); ?>"
							<?php checked( $nav_width, $val ); ?> />
						<?php echo esc_html( $label ); ?>
					</label>
				<?php endforeach; ?>
			</div>

			<p class="description" style="margin-top:8px;">
				<?php esc_html_e( '文字数・H2数の条件を満たす場合に表示されます（show_all時）。', 'contentpilot' ); ?>
			</p>
		</div>
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
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['contentpilot_meta_nonce'] ) ), 'contentpilot_save_meta' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// 表示モード
		$mode = isset( $_POST['contentpilot_display_mode'] ) ? sanitize_text_field( wp_unslash( $_POST['contentpilot_display_mode'] ) ) : 'show_all';
		if ( ! in_array( $mode, array( 'show_all', 'select', 'hide' ), true ) ) {
			$mode = 'show_all';
		}
		update_post_meta( $post_id, '_contentpilot_display_mode', $mode );

		// 後方互換: _contentpilot_enabled も更新
		update_post_meta( $post_id, '_contentpilot_enabled', 'hide' === $mode ? '0' : '1' );

		// H2選択データ
		if ( 'select' === $mode ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- array_map('intval') sanitizes
			$selected = isset( $_POST['contentpilot_selected_h2'] ) ? array_map( 'intval', wp_unslash( $_POST['contentpilot_selected_h2'] ) ) : array();
			update_post_meta( $post_id, '_contentpilot_selected_h2', $selected );

			$texts = array();
			foreach ( $selected as $idx ) {
				$key = 'contentpilot_h2_text_' . $idx;
				if ( isset( $_POST[ $key ] ) ) {
					$texts[ $idx ] = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
				}
			}
			update_post_meta( $post_id, '_contentpilot_h2_custom_texts', $texts );

			// 表示開始位置
			$trigger_type = isset( $_POST['_contentpilot_trigger_type'] )
				? sanitize_text_field( wp_unslash( $_POST['_contentpilot_trigger_type'] ) )
				: 'immediate';
			if ( ! in_array( $trigger_type, array( 'immediate', 'first_selected', 'nth_selected', 'scroll_px' ), true ) ) {
				$trigger_type = 'immediate';
			}
			update_post_meta( $post_id, '_contentpilot_trigger_type', $trigger_type );

			if ( 'nth_selected' === $trigger_type ) {
				$nth = isset( $_POST['_contentpilot_trigger_nth'] ) ? absint( wp_unslash( $_POST['_contentpilot_trigger_nth'] ) ) : 2;
				update_post_meta( $post_id, '_contentpilot_trigger_nth', max( 1, $nth ) );
			} else {
				delete_post_meta( $post_id, '_contentpilot_trigger_nth' );
			}

			if ( 'scroll_px' === $trigger_type ) {
				$scroll = isset( $_POST['_contentpilot_trigger_scroll_px'] ) ? absint( wp_unslash( $_POST['_contentpilot_trigger_scroll_px'] ) ) : 300;
				update_post_meta( $post_id, '_contentpilot_trigger_scroll_px', $scroll );
			} else {
				delete_post_meta( $post_id, '_contentpilot_trigger_scroll_px' );
			}
		} else {
			delete_post_meta( $post_id, '_contentpilot_selected_h2' );
			delete_post_meta( $post_id, '_contentpilot_h2_custom_texts' );
			delete_post_meta( $post_id, '_contentpilot_trigger_type' );
			delete_post_meta( $post_id, '_contentpilot_trigger_nth' );
			delete_post_meta( $post_id, '_contentpilot_trigger_scroll_px' );
		}

		// プリセット
		$preset = isset( $_POST['contentpilot_preset'] ) ? sanitize_text_field( wp_unslash( $_POST['contentpilot_preset'] ) ) : '';
		update_post_meta( $post_id, '_contentpilot_preset', $preset );

		// 固定ナビの表示方法
		$nav_width = isset( $_POST['_contentpilot_nav_width'] ) ? sanitize_text_field( wp_unslash( $_POST['_contentpilot_nav_width'] ) ) : '';
		if ( in_array( $nav_width, array( '', 'scroll', 'equal' ), true ) ) {
			update_post_meta( $post_id, '_contentpilot_nav_width', $nav_width );
		}
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

		// 固定ナビの表示方法
		$wp_customize->add_setting( 'contentpilot_nav_width', array(
			'default'           => 'scroll',
			'sanitize_callback' => array( $this, 'sanitize_nav_width' ),
		) );
		$wp_customize->add_control( 'contentpilot_nav_width', array(
			'label'       => __( '固定ナビの表示方法', 'contentpilot' ),
			'description' => __( '見出しが多い場合の表示方法を選択します', 'contentpilot' ),
			'section'     => 'contentpilot_design',
			'type'        => 'radio',
			'choices'     => array(
				'scroll' => __( '横スクロール可能（全て表示）', 'contentpilot' ),
				'equal'  => __( '均等割（はみ出し非表示）', 'contentpilot' ),
			),
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

	public function sanitize_nav_width( $value ) {
		return in_array( $value, array( 'scroll', 'equal' ), true ) ? $value : 'scroll';
	}
}
