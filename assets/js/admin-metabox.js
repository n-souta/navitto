/**
 * Navitto - メタボックス管理画面スクリプト
 *
 * アイコンピッカーは画面中央のモーダルで表示。
 * ブロックエディタでは未保存の本文からもH2を取得して一覧表示する。
 *
 * @package Navitto
 * @since   1.0.1
 */
(function(){
	window.navittoMetabox = window.navittoMetabox || {};

	var radios      = document.querySelectorAll('input[name="navitto_display_mode"]');
	var h2Area      = document.getElementById('cp-h2-select-area');
	var triggerArea = document.querySelector('.navitto-trigger-settings');

	// 表示モード切替
	function onModeChange() {
		var mode = document.querySelector('input[name="navitto_display_mode"]:checked');
		var isSelect = mode && mode.value === 'select';
		if ( h2Area ) {
			h2Area.style.display = isSelect ? '' : 'none';
			if ( isSelect ) {
				// ブロックエディタの未保存本文からH2を取得して一覧を更新
				setTimeout(refreshH2FromEditor, 100);
			}
		}
		if ( triggerArea ) { triggerArea.style.display = isSelect ? '' : 'none'; }
	}
	radios.forEach(function(r) { r.addEventListener('change', onModeChange); });
	onModeChange();

	// ブロックエディタ準備後にも未保存本文からH2を反映（既にselect選択時）
	if ( h2Area && h2Area.getAttribute('data-navitto-empty') === '1' && document.querySelector('input[name="navitto_display_mode"][value="select"]:checked') ) {
		window.addEventListener('load', function() { setTimeout(refreshH2FromEditor, 200); });
	}

	// チェックボックスでテキスト入力の有効/無効を切り替え（イベント委譲で動的追加にも対応）
	if ( h2Area ) {
		h2Area.addEventListener('change', function(e) {
			if ( !e.target || !e.target.classList.contains('cp-h2-checkbox') ) return;
			var idx = e.target.getAttribute('data-index');
			var input = document.querySelector('.cp-h2-text-input[data-index="' + idx + '"]');
			var iconBtn = document.querySelector('.navitto-icon-picker-btn[data-index="' + idx + '"]');
			if (input) { input.disabled = !e.target.checked; }
			if (iconBtn) { iconBtn.disabled = !e.target.checked; }
		});
	}

	/**
	 * HTML文字列からH2のテキスト一覧を抽出（ブロック形式・通常HTML両対応）
	 */
	function extractH2FromHtml(html) {
		if ( !html || typeof html !== 'string' ) return [];
		var list = [];
		var re = /<h2[^>]*>(.*?)<\/h2>/gi;
		var m;
		while ( (m = re.exec(html)) !== null ) {
			var text = (m[1] || '').replace(/<[^>]+>/g, '').trim();
			if ( text ) list.push(text);
		}
		return list;
	}

	/**
	 * ブロックエディタの編集中本文を取得し、H2があればメタボックスに一覧を表示する
	 */
	function refreshH2FromEditor() {
		if ( !h2Area || h2Area.getAttribute('data-navitto-empty') !== '1' ) return;
		if ( !window.wp || !wp.data || !wp.data.select('core/editor') ) return;

		var content = '';
		try {
			content = wp.data.select('core/editor').getEditedPostContent();
		} catch (err) {
			return;
		}
		var h2List = extractH2FromHtml(content);
		if ( h2List.length === 0 ) return;

		function defaultH2RowInnerHtml(index, escaped) {
			return (
				'<label>' +
				'<input type="checkbox" name="navitto_selected_h2[]" value="' + index + '" class="cp-h2-checkbox" data-index="' + index + '"> ' +
				escaped +
				'</label>' +
				'<div class="cp-h2-item-row">' +
				'<input type="text" name="navitto_h2_text_' + index + '" class="cp-h2-text-input" data-index="' + index + '" value="" placeholder="' + escaped + '" disabled>' +
				'</div>'
			);
		}

		var fragment = document.createDocumentFragment();
		h2List.forEach(function(text, index) {
			var item = document.createElement('div');
			item.className = 'cp-h2-item';
			var escaped = text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
			var inner = (window.navittoMetabox && typeof window.navittoMetabox.h2RowInnerHtml === 'function')
				? window.navittoMetabox.h2RowInnerHtml(index, text, escaped)
				: defaultH2RowInnerHtml(index, escaped);
			item.innerHTML = inner;
			fragment.appendChild(item);
		});

		h2Area.innerHTML = '';
		h2Area.appendChild(fragment);
		h2Area.setAttribute('data-navitto-empty', '0');
	}
})();
