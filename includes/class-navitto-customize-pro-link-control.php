<?php
/**
 * カスタマイザー用「Pro版へ」リンクコントロール
 *
 * WP_Customize_Control はカスタマイザー読み込み時のみ存在するため、
 * このファイルは register_customizer() 内で追加する直前に require_once すること。
 *
 * @package Navitto
 * @since   1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_Customize_Control' ) ) {
	return;
}

/**
 * Navitto_Customize_Pro_Link_Control
 */
class Navitto_Customize_Pro_Link_Control extends WP_Customize_Control {

	/**
	 * @var string
	 */
	public $type = 'navitto_pro_link';

	/**
	 * コントロール内容を出力
	 */
	public function render_content() {
		if ( ! defined( 'NAVITTO_PRO_URL' ) || get_option( 'navitto_license_status', '' ) === 'valid' ) {
			return;
		}
		?>
		<p style="margin: 0 0 4px;">
			<a href="<?php echo esc_url( NAVITTO_PRO_URL ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Pro版へ', 'navitto' ); ?></a>
		</p>
		<p class="description" style="margin: 0;"><?php esc_html_e( 'Pro版ではアイコン設置やデザインカスタマイズが可能です。', 'navitto' ); ?></p>
		<?php
	}
}
