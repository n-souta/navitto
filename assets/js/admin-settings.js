/**
 * Navitto 設定画面 JavaScript
 *
 * 一括適用ボタンの Ajax 処理
 *
 * @package Navitto
 * @since   1.3.0
 */

(function($) {
	'use strict';

	$(document).ready(function() {

		var config = window.navittoAdmin || {};
		var i18n   = config.i18n || {};

		// 確認メッセージのマッピング
		var confirmMessages = {
			navitto_enable_all:  i18n.confirmEnableAll  || 'Are you sure?',
			navitto_disable_all: i18n.confirmDisableAll || 'Are you sure?'
		};

		// 一括適用ボタンのクリック処理
		$('.navitto-bulk-btn').on('click', function() {

			var $btn    = $(this);
			var action  = $btn.data('action');
			var $result = $('.navitto-bulk-result[data-for="' + action + '"]');

			// 確認ダイアログ
			if ( ! confirm( confirmMessages[action] ) ) {
				return;
			}

			// ボタンを無効化してローディング表示
			var originalText = $btn.text();
			$btn.prop('disabled', true).text( i18n.processing || '処理中...' );
			$result.removeClass('success error').text('');

			// Ajax リクエスト
			$.ajax({
				url:  config.ajaxUrl,
				type: 'POST',
				data: {
					action: action,
					nonce:  config.nonce
				},
				dataType: 'json'
			})
			.done(function(response) {
				if ( response.success && response.data ) {
					$result.addClass('success').text( response.data.message );
				} else {
					var msg = ( response.data && response.data.message ) ? response.data.message : ( i18n.error || 'エラーが発生しました。' );
					$result.addClass('error').text( msg );
				}
			})
			.fail(function() {
				$result.addClass('error').text( i18n.error || 'エラーが発生しました。' );
			})
			.always(function() {
				$btn.prop('disabled', false).text( originalText );
			});
		});
	});

})(jQuery);
