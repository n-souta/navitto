<?php
/**
 * ContentPilot 管理画面クラス
 *
 * メタボックス・カスタマイザーを管理
 *
 * @package ContentPilot
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ContentPilot_Admin {

	private static $instance = null;

	/**
	 * プリセット一覧
	 */
	private static $presets = array(
		'simple' => 'シンプル',
		'modern' => 'モダン',
		'flat'   => 'フラット',
		'dark'   => 'ダーク',
		'theme'  => 'テーマ準拠',
	);

	private function __construct() {}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * 管理画面フック初期化
	 */
	public function init() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_meta' ) );
	}

	/**
	 * カスタマイザーフック初期化（管理画面・プレビュー両方で必要）
	 */
	public function init_customizer() {
		add_action( 'customize_register', array( $this, 'register_customizer' ) );
	}

	/* ======================================================================
	   メタボックス
	   ====================================================================== */

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
	 * メタボックス出力
	 */
	public function render_meta_box( $post ) {
		wp_nonce_field( 'contentpilot_save_meta', 'contentpilot_meta_nonce' );

		$raw_mode      = get_post_meta( $post->ID, '_contentpilot_display_mode', true );
		$post_preset   = get_post_meta( $post->ID, '_contentpilot_preset', true );
		$custom_texts  = get_post_meta( $post->ID, '_contentpilot_h2_custom_texts', true );

		// 旧データからの移行互換
		if ( '' === $raw_mode ) {
			$old_enabled  = get_post_meta( $post->ID, '_contentpilot_enabled', true );
			$display_mode = ( '0' === $old_enabled ) ? 'hide' : 'show_all';
		} else {
			$display_mode = $raw_mode;
		}

		// H2選択データ：selectモードとして明示保存済みの場合のみ読み込む
		$selected_h2 = array();
		if ( 'select' === $raw_mode ) {
			$saved = get_post_meta( $post->ID, '_contentpilot_selected_h2', true );
			if ( is_array( $saved ) ) {
				$selected_h2 = $saved;
			}
		}
		if ( ! is_array( $custom_texts ) ) {
			$custom_texts = array();
		}

		// 投稿のH2を取得
		$rendered = do_blocks( $post->post_content );
		$text     = wp_strip_all_tags( $rendered );
		$wc       = mb_strlen( preg_replace( '/\s+/', '', $text ), 'UTF-8' );
		$min_wc   = get_option( 'contentpilot_min_word_count', 3000 );

		preg_match_all( '/<h2[^>]*>(.*?)<\/h2>/is', $rendered, $matches );
		$h2_texts = array();
		foreach ( $matches[1] as $m ) {
			$h2_texts[] = wp_strip_all_tags( $m );
		}
		$h2_count = count( $h2_texts );

		$default_preset = get_theme_mod( 'contentpilot_preset', 'simple' );
		?>

		<!-- 表示モード（ラジオボタン3択） -->
		<p style="margin-bottom:4px;"><strong><?php esc_html_e( '固定ナビの表示', 'contentpilot' ); ?></strong></p>

		<p style="margin:6px 0;">
			<label>
				<input type="radio" name="contentpilot_display_mode" value="show_all"
				       <?php checked( $display_mode, 'show_all' ); ?> />
				<?php esc_html_e( '固定ナビを表示（H2タグをそのまま反映）', 'contentpilot' ); ?>
			</label>
		</p>

		<p style="margin:6px 0;">
			<label>
				<input type="radio" name="contentpilot_display_mode" value="select"
				       <?php checked( $display_mode, 'select' ); ?> />
				<?php esc_html_e( '表示する見出しを選択', 'contentpilot' ); ?>
			</label>
			<br /><span class="description" style="margin-left:22px;font-size:11px;"><?php esc_html_e( '見出しの選択・テキスト編集が可能', 'contentpilot' ); ?></span>
		</p>

		<p style="margin:6px 0;">
			<label>
				<input type="radio" name="contentpilot_display_mode" value="hide"
				       <?php checked( $display_mode, 'hide' ); ?> />
				<?php esc_html_e( '固定ナビを非表示', 'contentpilot' ); ?>
			</label>
		</p>

		<!-- H2選択・テキスト編集エリア（selectモード時のみ表示） -->
		<div id="cp-h2-select-area" style="<?php echo ( 'select' !== $display_mode ) ? 'display:none;' : ''; ?>margin-top:8px;padding:8px;background:#f9f9f9;border:1px solid #ddd;">
			<?php if ( $h2_count > 0 ) : ?>
				<?php foreach ( $h2_texts as $i => $txt ) :
					$is_checked  = ! empty( $selected_h2 ) && in_array( $i, $selected_h2, false );
					$custom_text = isset( $custom_texts[ $i ] ) ? $custom_texts[ $i ] : '';
				?>
					<div class="cp-h2-row" style="margin-bottom:8px;padding-bottom:8px;border-bottom:1px solid #eee;">
						<label style="display:flex;align-items:center;gap:4px;">
							<input type="checkbox"
							       class="cp-h2-checkbox"
							       name="contentpilot_selected_h2[]"
							       value="<?php echo esc_attr( $i ); ?>"
							       <?php checked( $is_checked ); ?> />
							<span style="font-size:12px;color:#666;"><?php echo esc_html( mb_strimwidth( $txt, 0, 50, '…' ) ); ?></span>
						</label>
						<input type="text"
						       class="cp-h2-text"
						       name="contentpilot_h2_text_<?php echo esc_attr( $i ); ?>"
						       value="<?php echo esc_attr( $custom_text ); ?>"
						       placeholder="<?php echo esc_attr( $txt ); ?>"
						       style="width:100%;margin-top:4px;font-size:12px;<?php echo $is_checked ? '' : 'opacity:0.4;pointer-events:none;'; ?>"
						       <?php echo $is_checked ? '' : 'disabled'; ?> />
					</div>
				<?php endforeach; ?>
				<input type="hidden" name="contentpilot_h2_total" value="<?php echo esc_attr( $h2_count ); ?>" />
			<?php else : ?>
				<p class="description"><?php esc_html_e( 'H2見出しが見つかりません。', 'contentpilot' ); ?></p>
			<?php endif; ?>
		</div>

		<hr />

		<!-- デザインプリセット（投稿別） -->
		<p>
			<label><strong><?php esc_html_e( 'デザインプリセット', 'contentpilot' ); ?></strong></label><br />
			<select name="contentpilot_preset" style="width:100%;">
				<option value=""><?php printf( esc_html__( 'サイト共通（%s）', 'contentpilot' ), esc_html( self::$presets[ $default_preset ] ?? $default_preset ) ); ?></option>
				<?php foreach ( self::$presets as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $post_preset, $key ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</p>

		<hr />

		<!-- 診断情報 -->
		<p class="description">
			<strong><?php esc_html_e( '表示条件:', 'contentpilot' ); ?></strong><br />
			<?php
			printf(
				esc_html__( '文字数: %1$d / %2$d文字 %3$s', 'contentpilot' ),
				$wc,
				$min_wc,
				$wc >= $min_wc ? '✅' : '❌'
			);
			?>
			<br />
			<?php
			printf(
				esc_html__( 'H2見出し数: %d個（2個以上必要）%s', 'contentpilot' ),
				$h2_count,
				$h2_count >= 2 ? '✅' : '❌'
			);
			?>
		</p>

		<!-- 表示モード切替 + チェックボックス連動スクリプト -->
		<script>
		(function(){
			var radios = document.querySelectorAll('input[name="contentpilot_display_mode"]');
			var area = document.getElementById('cp-h2-select-area');
			if (!radios.length || !area) return;
			function toggleArea() {
				var val = document.querySelector('input[name="contentpilot_display_mode"]:checked');
				area.style.display = (val && val.value === 'select') ? '' : 'none';
			}
			for (var i = 0; i < radios.length; i++) {
				radios[i].addEventListener('change', toggleArea);
			}
			/* チェックボックスON/OFFでテキスト入力を有効/無効切替 */
			var cbs = area.querySelectorAll('.cp-h2-checkbox');
			for (var j = 0; j < cbs.length; j++) {
				cbs[j].addEventListener('change', function() {
					var txt = this.closest('.cp-h2-row').querySelector('.cp-h2-text');
					if (!txt) return;
					if (this.checked) {
						txt.disabled = false;
						txt.style.opacity = '1';
						txt.style.pointerEvents = '';
					} else {
						txt.disabled = true;
						txt.style.opacity = '0.4';
						txt.style.pointerEvents = 'none';
					}
				});
			}
		})();
		</script>
		<?php
	}

	/**
	 * メタデータ保存
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
		$mode = isset( $_POST['contentpilot_display_mode'] ) ? sanitize_text_field( $_POST['contentpilot_display_mode'] ) : 'show_all';
		if ( ! in_array( $mode, array( 'show_all', 'select', 'hide' ), true ) ) {
			$mode = 'show_all';
		}
		update_post_meta( $post_id, '_contentpilot_display_mode', $mode );

		// プリセット
		$preset = isset( $_POST['contentpilot_preset'] ) ? sanitize_text_field( $_POST['contentpilot_preset'] ) : '';
		if ( $preset && ! array_key_exists( $preset, self::$presets ) ) {
			$preset = '';
		}
		update_post_meta( $post_id, '_contentpilot_preset', $preset );

		// H2選択・カスタムテキスト（selectモード時のみ保存、それ以外はクリア）
		if ( 'select' === $mode ) {
			$selected = array();
			if ( isset( $_POST['contentpilot_selected_h2'] ) && is_array( $_POST['contentpilot_selected_h2'] ) ) {
				$selected = array_map( 'absint', $_POST['contentpilot_selected_h2'] );
			}
			update_post_meta( $post_id, '_contentpilot_selected_h2', $selected );

			$total = isset( $_POST['contentpilot_h2_total'] ) ? absint( $_POST['contentpilot_h2_total'] ) : 0;
			$custom_texts = array();
			for ( $i = 0; $i < $total; $i++ ) {
				$key = 'contentpilot_h2_text_' . $i;
				if ( isset( $_POST[ $key ] ) ) {
					$val = sanitize_text_field( $_POST[ $key ] );
					if ( '' !== $val ) {
						$custom_texts[ $i ] = $val;
					}
				}
			}
			update_post_meta( $post_id, '_contentpilot_h2_custom_texts', $custom_texts );
		} else {
			delete_post_meta( $post_id, '_contentpilot_selected_h2' );
			delete_post_meta( $post_id, '_contentpilot_h2_custom_texts' );
		}
	}

	/* ======================================================================
	   カスタマイザー
	   ====================================================================== */

	public function register_customizer( $wp_customize ) {

		// --- セクション ---
		$wp_customize->add_section( 'contentpilot_design', array(
			'title'    => __( 'ContentPilot デザイン設定', 'contentpilot' ),
			'priority' => 160,
		) );

		$wp_customize->add_section( 'contentpilot_common_settings', array(
			'title'    => __( 'ContentPilot 共通設定', 'contentpilot' ),
			'priority' => 161,
		) );

		// ============================
		// デザインセクション
		// ============================

		// プリセット
		$wp_customize->add_setting( 'contentpilot_preset', array(
			'default'           => 'simple',
			'sanitize_callback' => array( $this, 'sanitize_preset' ),
			'transport'         => 'refresh',
		) );
		$wp_customize->add_control( 'contentpilot_preset', array(
			'label'   => __( 'デザインプリセット', 'contentpilot' ),
			'section' => 'contentpilot_design',
			'type'    => 'select',
			'choices' => self::$presets,
		) );

		// 配置位置
		$wp_customize->add_setting( 'contentpilot_position', array(
			'default'           => 'top',
			'sanitize_callback' => array( $this, 'sanitize_position' ),
			'transport'         => 'refresh',
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
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color',
			'transport'         => 'refresh',
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'contentpilot_bg_color', array(
			'label'       => __( '背景色（カスタム）', 'contentpilot' ),
			'description' => __( '設定するとプリセットの背景色を上書きします。', 'contentpilot' ),
			'section'     => 'contentpilot_design',
		) ) );

		// テキスト色
		$wp_customize->add_setting( 'contentpilot_text_color', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color',
			'transport'         => 'refresh',
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'contentpilot_text_color', array(
			'label'   => __( 'テキスト色（カスタム）', 'contentpilot' ),
			'section' => 'contentpilot_design',
		) ) );

		// アクティブ色
		$wp_customize->add_setting( 'contentpilot_active_color', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color',
			'transport'         => 'refresh',
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'contentpilot_active_color', array(
			'label'   => __( 'アクティブ項目の色（カスタム）', 'contentpilot' ),
			'section' => 'contentpilot_design',
		) ) );

		// フォントサイズ
		$wp_customize->add_setting( 'contentpilot_font_size', array(
			'default'           => 14,
			'sanitize_callback' => 'absint',
			'transport'         => 'refresh',
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
			'transport'         => 'refresh',
		) );
		$wp_customize->add_control( 'contentpilot_border_radius', array(
			'label'   => __( '角丸を有効にする', 'contentpilot' ),
			'section' => 'contentpilot_design',
			'type'    => 'checkbox',
		) );

		// 影
		$wp_customize->add_setting( 'contentpilot_shadow', array(
			'default'           => false,
			'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
			'transport'         => 'refresh',
		) );
		$wp_customize->add_control( 'contentpilot_shadow', array(
			'label'   => __( '影を表示する', 'contentpilot' ),
			'section' => 'contentpilot_design',
			'type'    => 'checkbox',
		) );

		// ============================
		// 共通設定セクション
		// ============================

		// 最小文字数
		$wp_customize->add_setting( 'contentpilot_min_word_count', array(
			'default'           => 3000,
			'sanitize_callback' => 'absint',
			'transport'         => 'refresh',
		) );
		$wp_customize->add_control( 'contentpilot_min_word_count', array(
			'label'       => __( '表示する最小文字数', 'contentpilot' ),
			'section'     => 'contentpilot_common_settings',
			'type'        => 'number',
			'input_attrs' => array( 'min' => 0, 'max' => 50000, 'step' => 500 ),
		) );

		// スクロール表示開始位置
		$wp_customize->add_setting( 'contentpilot_show_after_scroll', array(
			'default'           => 100,
			'sanitize_callback' => 'absint',
			'transport'         => 'refresh',
		) );
		$wp_customize->add_control( 'contentpilot_show_after_scroll', array(
			'label'       => __( 'スクロール表示開始位置 (px)', 'contentpilot' ),
			'description' => __( 'この値以上スクロールしたらナビを表示します。', 'contentpilot' ),
			'section'     => 'contentpilot_common_settings',
			'type'        => 'number',
			'input_attrs' => array( 'min' => 0, 'max' => 1000, 'step' => 10 ),
		) );

		// 固定ヘッダーセレクタ（PC）
		$wp_customize->add_setting( 'contentpilot_fixed_header_selector_pc', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'refresh',
		) );
		$wp_customize->add_control( 'contentpilot_fixed_header_selector_pc', array(
			'label'       => __( '固定ヘッダーのCSSセレクタ（PC）', 'contentpilot' ),
			'description' => __( '例: #header, .l-header', 'contentpilot' ),
			'section'     => 'contentpilot_common_settings',
			'type'        => 'text',
		) );

		// 固定ヘッダーセレクタ（SP）
		$wp_customize->add_setting( 'contentpilot_fixed_header_selector_sp', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'refresh',
		) );
		$wp_customize->add_control( 'contentpilot_fixed_header_selector_sp', array(
			'label'       => __( '固定ヘッダーのCSSセレクタ（SP）', 'contentpilot' ),
			'description' => __( 'PCと同じ場合は空欄でOKです。', 'contentpilot' ),
			'section'     => 'contentpilot_common_settings',
			'type'        => 'text',
		) );
	}

	/* ======================================================================
	   サニタイズ関数
	   ====================================================================== */

	public function sanitize_preset( $value ) {
		return array_key_exists( $value, self::$presets ) ? $value : 'simple';
	}

	public function sanitize_position( $value ) {
		return in_array( $value, array( 'top', 'bottom' ), true ) ? $value : 'top';
	}

	public function sanitize_checkbox( $value ) {
		return (bool) $value;
	}
}
