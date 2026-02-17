<?php
/**
 * Navitto 管理画面クラス
 *
 * 投稿編集画面のメタボックスとカスタマイザーを管理
 *
 * @package Navitto
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Navitto_Admin {

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
			'navitto-admin-metabox',
			NAVITTO_PLUGIN_URL . 'assets/css/admin-metabox.css',
			array(),
			NAVITTO_VERSION
		);

		// Font Awesome（nv- プレフィックス・テーマの fa- と競合しない）
		$fa_css = NAVITTO_PLUGIN_DIR . 'assets/lib/fontawesome/all-nv.min.css';
		if ( file_exists( $fa_css ) ) {
			wp_enqueue_style(
				'navitto-fontawesome',
				NAVITTO_PLUGIN_URL . 'assets/lib/fontawesome/all-nv.min.css',
				array(),
				NAVITTO_VERSION
			);
		}

		// アイコンレジストリ（メタボックスJSより前に読み込み・テーマ干渉防止）
		wp_enqueue_script(
			'navitto-icons',
			NAVITTO_PLUGIN_URL . 'assets/js/navitto-icons.js',
			array(),
			NAVITTO_VERSION,
			true
		);

		wp_enqueue_script(
			'navitto-admin-metabox',
			NAVITTO_PLUGIN_URL . 'assets/js/admin-metabox.js',
			array( 'navitto-icons' ),
			NAVITTO_VERSION,
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
			'navitto_settings',
			__( 'Navitto', 'navitto' ),
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
		wp_nonce_field( 'navitto_save_meta', 'navitto_meta_nonce' );

		// 現在の値を取得
		$display_mode = get_post_meta( $post->ID, '_navitto_display_mode', true );
		if ( '' === $display_mode ) {
			$old_enabled = get_post_meta( $post->ID, '_navitto_enabled', true );
			$display_mode = ( '0' === $old_enabled ) ? 'hide' : 'show_all';
		}
		// 後方互換: auto → show_all
		if ( 'auto' === $display_mode ) {
			$display_mode = 'show_all';
		}

		$selected_h2   = array();
		$custom_texts  = array();
		$h2_icons      = array();
		if ( 'select' === $display_mode ) {
			$selected_h2 = get_post_meta( $post->ID, '_navitto_selected_h2', true );
			$selected_h2 = is_array( $selected_h2 ) ? $selected_h2 : array();
			$custom_texts = get_post_meta( $post->ID, '_navitto_h2_custom_texts', true );
			$custom_texts = is_array( $custom_texts ) ? $custom_texts : array();
			$h2_icons = get_post_meta( $post->ID, '_navitto_h2_icons', true );
			$h2_icons = is_array( $h2_icons ) ? $h2_icons : array();
		}

		// トリガー設定
		$trigger_type      = get_post_meta( $post->ID, '_navitto_trigger_type', true );
		$trigger_type      = $trigger_type ? $trigger_type : 'immediate';
		$trigger_nth       = get_post_meta( $post->ID, '_navitto_trigger_nth', true );
		$trigger_nth       = $trigger_nth ? absint( $trigger_nth ) : 2;
		$trigger_scroll_px = get_post_meta( $post->ID, '_navitto_trigger_scroll_px', true );
		$trigger_scroll_px = $trigger_scroll_px ? absint( $trigger_scroll_px ) : 300;

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
		<div class="navitto-meta-box">
			<!-- 表示モード -->
			<div class="cp-radio-group">
				<label>
					<input type="radio" name="navitto_display_mode" value="show_all"
						<?php checked( $display_mode, 'show_all' ); ?> />
					<?php esc_html_e( '固定ナビを表示（H2タグをそのまま反映）', 'navitto' ); ?>
				</label>
				<label>
					<input type="radio" name="navitto_display_mode" value="select"
						<?php checked( $display_mode, 'select' ); ?> />
					<?php esc_html_e( '表示する見出しを選択', 'navitto' ); ?>
				</label>
				<label>
					<input type="radio" name="navitto_display_mode" value="hide"
						<?php checked( $display_mode, 'hide' ); ?> />
					<?php esc_html_e( '固定ナビを非表示', 'navitto' ); ?>
				</label>
			</div>

			<!-- 見出し選択（select時のみ表示） -->
			<div id="cp-h2-select-area" style="<?php echo $is_select ? '' : 'display:none;'; ?>">
				<?php if ( empty( $h2_list ) ) : ?>
					<p class="description"><?php esc_html_e( 'H2見出しが見つかりません。', 'navitto' ); ?></p>
				<?php else : ?>
				<?php foreach ( $h2_list as $index => $h2_text ) :
					$is_checked  = in_array( $index, $selected_h2, false );
					$custom_text = isset( $custom_texts[ $index ] ) ? $custom_texts[ $index ] : '';
					$icon_value  = isset( $h2_icons[ $index ] ) ? $h2_icons[ $index ] : ''; // "setId:iconName" または ""
				?>
					<div class="cp-h2-item">
						<label>
							<input type="checkbox"
								name="navitto_selected_h2[]"
								value="<?php echo esc_attr( $index ); ?>"
								class="cp-h2-checkbox"
								data-index="<?php echo esc_attr( $index ); ?>"
								<?php checked( $is_checked ); ?> />
							<?php echo esc_html( $h2_text ); ?>
						</label>
						<div class="cp-h2-item-row">
							<span class="navitto-icon-picker-preview" data-type="h2" data-index="<?php echo esc_attr( $index ); ?>"><?php
								if ( $icon_value && $icon_value !== 'none' && substr( $icon_value, -4 ) !== ':none' ) {
									echo '<span class="navitto-icon-picker-placeholder" data-icon-value="' . esc_attr( $icon_value ) . '"></span>';
								}
							?></span>
							<input type="text"
								name="navitto_h2_text_<?php echo esc_attr( $index ); ?>"
								class="cp-h2-text-input"
								data-index="<?php echo esc_attr( $index ); ?>"
								value="<?php echo esc_attr( $custom_text ); ?>"
								placeholder="<?php echo esc_attr( $h2_text ); ?>"
								<?php echo $is_checked ? '' : 'disabled'; ?> />
						</div>
						<div class="cp-h2-item-row cp-h2-item-row--icon-btn">
							<button type="button"
								class="navitto-icon-picker-btn button button-small"
								data-type="h2"
								data-index="<?php echo esc_attr( $index ); ?>"
								title="<?php esc_attr_e( 'アイコンを追加', 'navitto' ); ?>"
								<?php echo $is_checked ? '' : 'disabled'; ?>><?php esc_html_e( 'アイコンを追加', 'navitto' ); ?></button>
							<input type="hidden"
								name="navitto_h2_icon_<?php echo esc_attr( $index ); ?>"
								class="navitto-icon-picker-value"
								data-type="h2"
								data-index="<?php echo esc_attr( $index ); ?>"
								value="<?php echo esc_attr( $icon_value ); ?>" />
						</div>
					</div>
				<?php endforeach; ?>
				<?php endif; ?>
			</div>

			<!-- 表示開始位置（select時のみ表示） -->
			<div class="cp-trigger-settings navitto-trigger-settings" style="<?php echo $is_select ? '' : 'display:none;'; ?>">
				<h4><?php esc_html_e( '表示開始位置', 'navitto' ); ?></h4>

				<label>
					<input type="radio" name="_navitto_trigger_type" value="immediate"
						<?php checked( $trigger_type, 'immediate' ); ?> />
					<?php esc_html_e( 'ページ上部から', 'navitto' ); ?>
				</label>
				<p class="description"><?php esc_html_e( '選択した見出しがページ上部に来たら固定ナビを表示', 'navitto' ); ?></p>

				<label>
					<input type="radio" name="_navitto_trigger_type" value="first_selected"
						<?php checked( $trigger_type, 'first_selected' ); ?> />
					<?php esc_html_e( '選択した最初の見出しを通過後', 'navitto' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'チェックを入れた最初の見出しを通過したら表示', 'navitto' ); ?></p>

				<label>
					<input type="radio" name="_navitto_trigger_type" value="nth_selected"
						<?php checked( $trigger_type, 'nth_selected' ); ?> />
					<span class="cp-trigger-inline">
						<input type="number" name="_navitto_trigger_nth"
							value="<?php echo esc_attr( $trigger_nth ); ?>"
							min="1" max="99" style="width:60px;" />
						<?php esc_html_e( '番目の見出しを通過後', 'navitto' ); ?>
					</span>
				</label>
				<p class="description"><?php esc_html_e( 'チェックを入れたN番目の見出しを通過したら表示', 'navitto' ); ?></p>

				<label>
					<input type="radio" name="_navitto_trigger_type" value="scroll_px"
						<?php checked( $trigger_type, 'scroll_px' ); ?> />
					<span class="cp-trigger-inline">
						<input type="number" name="_navitto_trigger_scroll_px"
							value="<?php echo esc_attr( $trigger_scroll_px ); ?>"
							min="0" max="10000" step="50" style="width:80px;" />
						<?php esc_html_e( 'px スクロール後', 'navitto' ); ?>
					</span>
				</label>
			</div>

			<!-- 固定ナビの表示方法 -->
			<div class="cp-nav-width-setting" style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #ddd;">
				<label style="font-weight: 600; font-size: 12px; display: block; margin-bottom: 6px;">
					<?php esc_html_e( '固定ナビの表示方法', 'navitto' ); ?>
				</label>
				<p class="description" style="margin: 0 0 6px; font-size: 12px;">
					<?php esc_html_e( '見出しが多い場合の表示方法を選択します', 'navitto' ); ?>
				</p>
				<?php
				$nav_width = get_post_meta( $post->ID, '_navitto_nav_width', true );
				$nw_options = array(
					''       => __( 'デフォルト設定を使用（カスタマイザーの設定）', 'navitto' ),
					'scroll' => __( '横スクロール可能（全て表示）', 'navitto' ),
					'equal'  => __( '均等割（はみ出し非表示）', 'navitto' ),
				);
				foreach ( $nw_options as $val => $label ) : ?>
					<label style="display: block; margin-bottom: 4px; font-size: 13px;">
						<input type="radio" name="_navitto_nav_width"
							value="<?php echo esc_attr( $val ); ?>"
							<?php checked( $nav_width, $val ); ?> />
						<?php echo esc_html( $label ); ?>
					</label>
				<?php endforeach; ?>
			</div>

			<p class="description" style="margin-top:8px;">
				<?php esc_html_e( '文字数・H2数の条件を満たす場合に表示されます（show_all時）。', 'navitto' ); ?>
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
		if ( ! isset( $_POST['navitto_meta_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['navitto_meta_nonce'] ) ), 'navitto_save_meta' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// 表示モード
		$mode = isset( $_POST['navitto_display_mode'] ) ? sanitize_text_field( wp_unslash( $_POST['navitto_display_mode'] ) ) : 'show_all';
		if ( ! in_array( $mode, array( 'show_all', 'select', 'hide' ), true ) ) {
			$mode = 'show_all';
		}
		update_post_meta( $post_id, '_navitto_display_mode', $mode );

		// 後方互換: _navitto_enabled も更新
		update_post_meta( $post_id, '_navitto_enabled', 'hide' === $mode ? '0' : '1' );

		// H2選択データ
		if ( 'select' === $mode ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- array_map('intval') sanitizes
			$selected = isset( $_POST['navitto_selected_h2'] ) ? array_map( 'intval', wp_unslash( $_POST['navitto_selected_h2'] ) ) : array();
			update_post_meta( $post_id, '_navitto_selected_h2', $selected );

			$texts = array();
			$icons = array();
			foreach ( $selected as $idx ) {
				$key = 'navitto_h2_text_' . $idx;
				if ( isset( $_POST[ $key ] ) ) {
					$texts[ $idx ] = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
				}
				$icon_key = 'navitto_h2_icon_' . $idx;
				if ( isset( $_POST[ $icon_key ] ) ) {
					$val = sanitize_text_field( wp_unslash( $_POST[ $icon_key ] ) );
					if ( $val !== '' ) {
						$icons[ $idx ] = $val;
					}
				}
			}
			update_post_meta( $post_id, '_navitto_h2_custom_texts', $texts );
			update_post_meta( $post_id, '_navitto_h2_icons', $icons );

			// 表示開始位置
			$trigger_type = isset( $_POST['_navitto_trigger_type'] )
				? sanitize_text_field( wp_unslash( $_POST['_navitto_trigger_type'] ) )
				: 'immediate';
			if ( ! in_array( $trigger_type, array( 'immediate', 'first_selected', 'nth_selected', 'scroll_px' ), true ) ) {
				$trigger_type = 'immediate';
			}
			update_post_meta( $post_id, '_navitto_trigger_type', $trigger_type );

			if ( 'nth_selected' === $trigger_type ) {
				$nth = isset( $_POST['_navitto_trigger_nth'] ) ? absint( wp_unslash( $_POST['_navitto_trigger_nth'] ) ) : 2;
				update_post_meta( $post_id, '_navitto_trigger_nth', max( 1, $nth ) );
			} else {
				delete_post_meta( $post_id, '_navitto_trigger_nth' );
			}

			if ( 'scroll_px' === $trigger_type ) {
				$scroll = isset( $_POST['_navitto_trigger_scroll_px'] ) ? absint( wp_unslash( $_POST['_navitto_trigger_scroll_px'] ) ) : 300;
				update_post_meta( $post_id, '_navitto_trigger_scroll_px', $scroll );
			} else {
				delete_post_meta( $post_id, '_navitto_trigger_scroll_px' );
			}
		} else {
			delete_post_meta( $post_id, '_navitto_selected_h2' );
			delete_post_meta( $post_id, '_navitto_h2_custom_texts' );
			delete_post_meta( $post_id, '_navitto_h2_icons' );
			delete_post_meta( $post_id, '_navitto_trigger_type' );
			delete_post_meta( $post_id, '_navitto_trigger_nth' );
			delete_post_meta( $post_id, '_navitto_trigger_scroll_px' );
		}

		// 固定ナビの表示方法
		$nav_width = isset( $_POST['_navitto_nav_width'] ) ? sanitize_text_field( wp_unslash( $_POST['_navitto_nav_width'] ) ) : '';
		if ( in_array( $nav_width, array( '', 'scroll', 'equal' ), true ) ) {
			update_post_meta( $post_id, '_navitto_nav_width', $nav_width );
		}

		// カスタム項目は削除したため、過去データがあれば保存時に削除する
		delete_post_meta( $post_id, '_navitto_custom_items' );

	}

	/* =========================================================================
	   カスタマイザー
	   ========================================================================= */

	/**
	 * カスタマイザー設定を登録
	 */
	public function register_customizer( $wp_customize ) {

		// --- セクション: デザイン ---
		$wp_customize->add_section( 'navitto_design', array(
			'title'    => __( 'Navitto - デザイン', 'navitto' ),
			'priority' => 200,
		) );

		// プリセット
		$wp_customize->add_setting( 'navitto_preset', array(
			'default'           => 'simple',
			'sanitize_callback' => array( $this, 'sanitize_preset' ),
		) );
		$wp_customize->add_control( 'navitto_preset', array(
			'label'   => __( 'デザインプリセット', 'navitto' ),
			'section' => 'navitto_design',
			'type'    => 'select',
			'choices' => array(
				'simple' => __( 'シンプル', 'navitto' ),
				'theme'  => __( 'テーマ準拠', 'navitto' ),
			),
		) );

		// 配置位置
		$wp_customize->add_setting( 'navitto_position', array(
			'default'           => 'top',
			'sanitize_callback' => array( $this, 'sanitize_position' ),
		) );
		$wp_customize->add_control( 'navitto_position', array(
			'label'   => __( '配置位置', 'navitto' ),
			'section' => 'navitto_design',
			'type'    => 'radio',
			'choices' => array(
				'top'    => __( '上部固定', 'navitto' ),
				'bottom' => __( '下部固定', 'navitto' ),
			),
		) );

		// ナビの高さ
		$wp_customize->add_setting( 'navitto_nav_height', array(
			'default'           => 'medium',
			'sanitize_callback' => array( $this, 'sanitize_nav_height' ),
		) );
		$wp_customize->add_control( 'navitto_nav_height', array(
			'label'   => __( 'ナビの高さ', 'navitto' ),
			'section' => 'navitto_design',
			'type'    => 'radio',
			'choices' => array(
				'small'  => __( '小', 'navitto' ),
				'medium' => __( '中※デフォルト', 'navitto' ),
				'large'  => __( '大', 'navitto' ),
			),
		) );

		// 文字の太さ
		$wp_customize->add_setting( 'navitto_font_weight', array(
			'default'           => 'default',
			'sanitize_callback' => array( $this, 'sanitize_font_weight' ),
		) );
		$wp_customize->add_control( 'navitto_font_weight', array(
			'label'   => __( '文字の太さ', 'navitto' ),
			'section' => 'navitto_design',
			'type'    => 'radio',
			'choices' => array(
				'default' => __( 'デフォルト', 'navitto' ),
				'bold'    => __( '太字', 'navitto' ),
			),
		) );

		// 背景を透明にする（テーマ準拠時）
		$wp_customize->add_setting( 'navitto_theme_bg_transparent', array(
			'default'           => false,
			'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
		) );
		$wp_customize->add_control( 'navitto_theme_bg_transparent', array(
			'label'       => __( '背景を透明にする', 'navitto' ),
			'description' => __( 'テーマ準拠のとき、ナビの背景を透明にします。', 'navitto' ),
			'section'     => 'navitto_design',
			'type'        => 'checkbox',
		) );

		// --- セクション: 共通設定 ---
		$wp_customize->add_section( 'navitto_common', array(
			'title'    => __( 'Navitto - 共通設定', 'navitto' ),
			'priority' => 201,
		) );

		// 最小文字数
		$wp_customize->add_setting( 'navitto_min_word_count', array(
			'default'           => 3000,
			'sanitize_callback' => 'absint',
		) );
		$wp_customize->add_control( 'navitto_min_word_count', array(
			'label'       => __( '最小文字数', 'navitto' ),
			'section'     => 'navitto_common',
			'type'        => 'number',
			'input_attrs' => array( 'min' => 0, 'max' => 20000, 'step' => 100 ),
		) );

		// スクロール表示開始
		$wp_customize->add_setting( 'navitto_show_after_scroll', array(
			'default'           => 100,
			'sanitize_callback' => 'absint',
		) );
		$wp_customize->add_control( 'navitto_show_after_scroll', array(
			'label'       => __( 'スクロール表示開始位置 (px)', 'navitto' ),
			'section'     => 'navitto_common',
			'type'        => 'number',
			'input_attrs' => array( 'min' => 0, 'max' => 1000, 'step' => 10 ),
		) );

		// 固定ヘッダーセレクタ PC
		$wp_customize->add_setting( 'navitto_fixed_header_selector_pc', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
		) );
		$wp_customize->add_control( 'navitto_fixed_header_selector_pc', array(
			'label'       => __( 'テーマ固定ヘッダー セレクタ (PC)', 'navitto' ),
			'description' => __( '例: #fix_header, .site-header', 'navitto' ),
			'section'     => 'navitto_common',
			'type'        => 'text',
		) );

		// 固定ヘッダーセレクタ SP
		$wp_customize->add_setting( 'navitto_fixed_header_selector_sp', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
		) );
		$wp_customize->add_control( 'navitto_fixed_header_selector_sp', array(
			'label'       => __( 'テーマ固定ヘッダー セレクタ (SP)', 'navitto' ),
			'description' => __( '空欄の場合はPC用セレクタを使用', 'navitto' ),
			'section'     => 'navitto_common',
			'type'        => 'text',
		) );
	}

	/* =========================================================================
	   サニタイズ関数
	   ========================================================================= */

	public function sanitize_preset( $value ) {
		$valid = array( 'simple', 'theme' );
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

	public function sanitize_nav_height( $value ) {
		return in_array( $value, array( 'small', 'medium', 'large' ), true ) ? $value : 'medium';
	}

	public function sanitize_font_weight( $value ) {
		return in_array( $value, array( 'default', 'bold' ), true ) ? $value : 'default';
	}
}
