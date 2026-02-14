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

	/* =========================================================================
	   カスタム項目（外部リンク等）追加・削除
	   ========================================================================= */
	var addBtn    = document.getElementById('cp-add-custom-item');
	var listWrap  = document.getElementById('cp-custom-items-list');

	if (addBtn && listWrap) {
		// 項目を追加
		addBtn.addEventListener('click', function() {
			var index = listWrap.querySelectorAll('.cp-custom-item').length;
			var div = document.createElement('div');
			div.className = 'cp-custom-item';
			div.setAttribute('data-index', index);
			div.style.cssText = 'background:#f9f9f9; padding:8px; margin-bottom:6px; border:1px solid #ddd; border-radius:4px;';
			div.innerHTML =
				'<input type="text" name="contentpilot_custom_item_label[]"' +
				' value="" placeholder="ラベル（例: お問い合わせ）"' +
				' style="width:100%; margin-bottom:4px;" />' +
				'<input type="url" name="contentpilot_custom_item_url[]"' +
				' value="" placeholder="URL（例: https://example.com）"' +
				' style="width:100%; margin-bottom:4px;" />' +
				'<label style="font-size:12px;">' +
				'<input type="checkbox" name="contentpilot_custom_item_newtab[' + index + ']" value="1" />' +
				' 新しいタブで開く</label>' +
				'<button type="button" class="cp-remove-custom-item"' +
				' style="float:right; color:#a00; background:none; border:none; cursor:pointer; font-size:12px;">削除</button>' +
				'<div style="clear:both;"></div>';
			listWrap.appendChild(div);
		});

		// 項目を削除（イベント委任）
		listWrap.addEventListener('click', function(e) {
			if (e.target && e.target.classList.contains('cp-remove-custom-item')) {
				var item = e.target.closest('.cp-custom-item');
				if (item) { item.remove(); }
			}
		});
	}
})();
