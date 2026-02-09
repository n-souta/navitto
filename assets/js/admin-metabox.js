/**
 * ContentPilot - メタボックス管理画面スクリプト
 *
 * @package ContentPilot
 * @since   1.0.1
 */
(function(){
	var radios      = document.querySelectorAll('input[name="contentpilot_display_mode"]');
	var h2Area      = document.getElementById('cp-h2-select-area');
	var triggerArea  = document.querySelector('.contentpilot-trigger-settings');

	// 表示モード切替
	function onModeChange() {
		var mode = document.querySelector('input[name="contentpilot_display_mode"]:checked');
		var isSelect = mode && mode.value === 'select';
		if ( h2Area ) {
			h2Area.style.display = isSelect ? '' : 'none';
		}
		if ( triggerArea ) {
			triggerArea.style.display = isSelect ? '' : 'none';
		}
	}
	radios.forEach(function(r) { r.addEventListener('change', onModeChange); });
	onModeChange();

	// チェックボックスでテキスト入力の有効/無効を切り替え
	document.querySelectorAll('.cp-h2-checkbox').forEach(function(cb) {
		cb.addEventListener('change', function() {
			var idx = this.getAttribute('data-index');
			var input = document.querySelector('.cp-h2-text-input[data-index="' + idx + '"]');
			if (input) { input.disabled = !this.checked; }
		});
	});
})();
