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
	var radios      = document.querySelectorAll('input[name="navitto_display_mode"]');
	var h2Area      = document.getElementById('cp-h2-select-area');
	var triggerArea = document.querySelector('.navitto-trigger-settings');
	var i18n        = (window.navittoMetabox && window.navittoMetabox.i18n) || {};
	var addIconText = i18n.addIcon || 'アイコンを追加';

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

	// チェックボックスでテキスト入力・アイコンボタンの有効/無効を切り替え（イベント委譲で動的追加にも対応）
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

		var fragment = document.createDocumentFragment();
		h2List.forEach(function(text, index) {
			var item = document.createElement('div');
			item.className = 'cp-h2-item';
			var escaped = text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
			item.innerHTML =
				'<label>' +
				'<input type="checkbox" name="navitto_selected_h2[]" value="' + index + '" class="cp-h2-checkbox" data-index="' + index + '"> ' +
				escaped +
				'</label>' +
				'<div class="cp-h2-item-row">' +
				'<span class="navitto-icon-picker-preview" data-type="h2" data-index="' + index + '"></span>' +
				'<input type="text" name="navitto_h2_text_' + index + '" class="cp-h2-text-input" data-index="' + index + '" value="" placeholder="' + escaped + '" disabled>' +
				'</div>' +
				'<div class="cp-h2-item-row cp-h2-item-row--icon-btn">' +
				'<button type="button" class="navitto-icon-picker-btn button button-small" data-type="h2" data-index="' + index + '" title="' + addIconText + '" disabled>' + addIconText + '</button>' +
				'<input type="hidden" name="navitto_h2_icon_' + index + '" class="navitto-icon-picker-value" data-type="h2" data-index="' + index + '" value="">' +
				'</div>';
			fragment.appendChild(item);
		});

		h2Area.innerHTML = '';
		h2Area.appendChild(fragment);
		h2Area.setAttribute('data-navitto-empty', '0');
	}

	/* =========================================================================
	   アイコンピッカー（画面中央モーダル・タブなし）
	   ========================================================================= */
	var iconRegistry = window.__NAVITTO_ICONS__;
	var pickerOverlay = null;
	var pickerModal = null;
	var currentPickerType = 'h2'; // 'h2' | 'custom'
	var currentPickerIndex = null;

	function getIconNameFromValue(val) {
		if (!val || val === 'none') return '';
		return (val.indexOf(':') !== -1) ? val.split(':')[1] : val;
	}

	function getIconHtmlForValue(val) {
		return iconRegistry && iconRegistry.getIconHtml ? iconRegistry.getIconHtml(val) : (iconRegistry && iconRegistry.getSvg ? iconRegistry.getSvg(val) : '');
	}

	// 保存済みアイコンのプレビューを差し替え＆ボタン表記を同期
	function initIconPreviews() {
		if (!iconRegistry) return;
		document.querySelectorAll('.navitto-icon-picker-placeholder').forEach(function(el) {
			var val = el.getAttribute('data-icon-value');
			if (val) {
				var iconHtml = getIconHtmlForValue(val);
				if (iconHtml) {
					el.innerHTML = iconHtml;
					el.classList.remove('navitto-icon-picker-placeholder');
				}
			}
		});
		document.querySelectorAll('.navitto-icon-picker-value').forEach(function(input) {
			var type = input.getAttribute('data-type') || 'h2';
			var idx = input.getAttribute('data-index');
			if (idx !== null) updateIconButtonState(type, idx);
		});
	}
	initIconPreviews();

	function getIconPickerSelector(type, index) {
		var typeAttr = type ? '[data-type="' + type + '"]' : '';
		return typeAttr + '[data-index="' + index + '"]';
	}

	function buildPickerModal() {
		if (pickerModal || !iconRegistry || !iconRegistry.iconNames) return;

		pickerOverlay = document.createElement('div');
		pickerOverlay.className = 'navitto-icon-picker-overlay';
		pickerOverlay.setAttribute('aria-hidden', 'true');
		pickerOverlay.addEventListener('click', closeIconPicker);

		pickerModal = document.createElement('div');
		pickerModal.className = 'navitto-icon-picker-modal';
		pickerModal.setAttribute('role', 'dialog');
		pickerModal.setAttribute('aria-modal', 'true');
		pickerModal.setAttribute('aria-label', 'アイコンを選択');

		var header = document.createElement('div');
		header.className = 'navitto-icon-picker-header';
		header.innerHTML = '<span class="navitto-icon-picker-title">アイコンを選択</span>';
		var closeBtn = document.createElement('button');
		closeBtn.type = 'button';
		closeBtn.className = 'navitto-icon-picker-close';
		closeBtn.innerHTML = '&times;';
		closeBtn.setAttribute('aria-label', '閉じる');
		closeBtn.addEventListener('click', closeIconPicker);
		header.appendChild(closeBtn);
		pickerModal.appendChild(header);

		var gridContainer = document.createElement('div');
		gridContainer.className = 'navitto-icon-picker-grid-container';
		var grid = document.createElement('div');
		grid.className = 'navitto-icon-picker-grid';

		iconRegistry.iconNames.forEach(function(iconName) {
			var cell = document.createElement('button');
			cell.type = 'button';
			cell.className = 'navitto-icon-picker-cell';
			cell.setAttribute('data-icon-name', iconName);
			cell.setAttribute('title', iconName);
			cell.setAttribute('aria-label', iconName);
			var iconHtml = iconRegistry.getIconHtml ? iconRegistry.getIconHtml(iconName) : iconRegistry.getSvg(iconName);
			cell.innerHTML = iconHtml || '';
			cell.addEventListener('click', function() { selectIcon(iconName); });
			grid.appendChild(cell);
		});
		gridContainer.appendChild(grid);
		pickerModal.appendChild(gridContainer);

		document.body.appendChild(pickerOverlay);
		document.body.appendChild(pickerModal);
	}

	function openIconPicker(type, index) {
		if (index === undefined) { index = type; type = 'h2'; }
		buildPickerModal();
		if (!pickerModal) return;
		currentPickerType = type || 'h2';
		currentPickerIndex = index;
		var sel = getIconPickerSelector(currentPickerType, currentPickerIndex);
		var hiddenInput = document.querySelector('.navitto-icon-picker-value' + sel);
		var currentVal = hiddenInput ? hiddenInput.value : '';
		var currentName = getIconNameFromValue(currentVal) || '';

		pickerModal.querySelectorAll('.navitto-icon-picker-cell').forEach(function(cell) {
			cell.classList.toggle('navitto-icon-picker-cell-selected', cell.getAttribute('data-icon-name') === currentName);
		});
		pickerOverlay.classList.add('navitto-icon-picker-overlay-visible');
		pickerModal.classList.add('navitto-icon-picker-modal-visible');
	}

	function closeIconPicker() {
		if (pickerOverlay) pickerOverlay.classList.remove('navitto-icon-picker-overlay-visible');
		if (pickerModal) pickerModal.classList.remove('navitto-icon-picker-modal-visible');
		currentPickerIndex = null;
		currentPickerType = 'h2';
	}

	function updateIconButtonState(type, index) {
		var sel = getIconPickerSelector(type, index);
		var btn = document.querySelector('.navitto-icon-picker-btn' + sel);
		var hiddenInput = document.querySelector('.navitto-icon-picker-value' + sel);
		if (!btn || !hiddenInput) return;
		var hasIcon = !!(hiddenInput.value && hiddenInput.value !== 'none' && hiddenInput.value.indexOf(':none') === -1);
		btn.textContent = hasIcon ? 'アイコンを削除' : 'アイコンを追加';
		btn.title = hasIcon ? 'アイコンを削除' : 'アイコンを追加';
		btn.classList.toggle('navitto-icon-picker-btn--remove', hasIcon);
	}

	function selectIcon(iconName) {
		if (currentPickerIndex === null) return;
		var value = (!iconName || iconName === 'none') ? '' : iconName;
		var sel = getIconPickerSelector(currentPickerType, currentPickerIndex);
		var hiddenInput = document.querySelector('.navitto-icon-picker-value' + sel);
		var preview = document.querySelector('.navitto-icon-picker-preview' + sel);
		if (hiddenInput) hiddenInput.value = value;
		if (preview) {
			if (value && iconName !== 'none' && iconRegistry) {
				var iconHtml = iconRegistry.getIconHtml ? iconRegistry.getIconHtml(iconName) : iconRegistry.getSvg(iconName);
				preview.innerHTML = iconHtml || '';
			} else {
				preview.innerHTML = '';
			}
		}
		updateIconButtonState(currentPickerType, currentPickerIndex);
		closeIconPicker();
	}

	document.addEventListener('click', function(e) {
		var btn = e.target.closest('.navitto-icon-picker-btn');
		if (!btn || btn.disabled) return;
		e.preventDefault();
		var type = btn.getAttribute('data-type') || 'h2';
		var index = btn.getAttribute('data-index');
		var sel = getIconPickerSelector(type, index);
		var hiddenInput = document.querySelector('.navitto-icon-picker-value' + sel);
		var hasIcon = hiddenInput && hiddenInput.value && hiddenInput.value !== 'none' && hiddenInput.value.indexOf(':none') === -1;
		if (hasIcon) {
			currentPickerType = type;
			currentPickerIndex = index;
			selectIcon('none');
		} else {
			openIconPicker(type, index);
		}
	});
})();
