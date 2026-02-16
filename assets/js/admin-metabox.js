/**
 * Navitto - メタボックス管理画面スクリプト
 *
 * アイコンピッカーは画面中央のモーダルで表示。
 *
 * @package Navitto
 * @since   1.0.1
 */
(function(){
	var radios      = document.querySelectorAll('input[name="navitto_display_mode"]');
	var h2Area      = document.getElementById('cp-h2-select-area');
	var triggerArea = document.querySelector('.navitto-trigger-settings');

	// 表示モード切替
	function onModeChange() {
		var mode = document.querySelector('input[name="navitto_display_mode"]:checked');
		var isSelect = mode && mode.value === 'select';
		if ( h2Area ) { h2Area.style.display = isSelect ? '' : 'none'; }
		if ( triggerArea ) { triggerArea.style.display = isSelect ? '' : 'none'; }
	}
	radios.forEach(function(r) { r.addEventListener('change', onModeChange); });
	onModeChange();

	// チェックボックスでテキスト入力・アイコンボタンの有効/無効を切り替え
	document.querySelectorAll('.cp-h2-checkbox').forEach(function(cb) {
		cb.addEventListener('change', function() {
			var idx = this.getAttribute('data-index');
			var input = document.querySelector('.cp-h2-text-input[data-index="' + idx + '"]');
			var iconBtn = document.querySelector('.navitto-icon-picker-btn[data-index="' + idx + '"]');
			if (input) { input.disabled = !this.checked; }
			if (iconBtn) { iconBtn.disabled = !this.checked; }
		});
	});

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

	/* =========================================================================
	   カスタム項目（外部リンク等）追加・削除
	   ========================================================================= */
	var addBtn   = document.getElementById('cp-add-custom-item');
	var listWrap = document.getElementById('cp-custom-items-list');

	if (addBtn && listWrap) {
		addBtn.addEventListener('click', function() {
			var index = listWrap.querySelectorAll('.cp-custom-item').length;
			var div = document.createElement('div');
			div.className = 'cp-custom-item';
			div.setAttribute('data-index', index);
			div.style.cssText = 'background:#f9f9f9; padding:8px; margin-bottom:6px; border:1px solid #ddd; border-radius:4px;';
			div.innerHTML =
				'<div class="cp-h2-item-row">' +
					'<span class="navitto-icon-picker-preview" data-type="custom" data-index="' + index + '"></span>' +
					'<input type="text" name="navitto_custom_item_label[]" value="" placeholder="ラベル（例: お問い合わせ）" style="flex:1; min-width:0; margin-bottom:0;" />' +
				'</div>' +
				'<div class="cp-h2-item-row cp-h2-item-row--icon-btn">' +
					'<button type="button" class="navitto-icon-picker-btn button button-small" data-type="custom" data-index="' + index + '" title="アイコンを追加">アイコンを追加</button>' +
					'<input type="hidden" name="navitto_custom_item_icon[]" class="navitto-icon-picker-value" data-type="custom" data-index="' + index + '" value="" />' +
				'</div>' +
				'<input type="url" name="navitto_custom_item_url[]" value="" placeholder="URL（例: https://example.com）" style="width:100%; margin-bottom:4px;" />' +
				'<label style="font-size:12px;"><input type="checkbox" name="navitto_custom_item_newtab[' + index + ']" value="1" /> 新しいタブで開く</label>' +
				'<button type="button" class="cp-remove-custom-item" style="float:right; color:#a00; background:none; border:none; cursor:pointer; font-size:12px;">削除</button>' +
				'<div style="clear:both;"></div>';
			listWrap.appendChild(div);
			updateIconButtonState('custom', index);
		});
		listWrap.addEventListener('click', function(e) {
			if (e.target && e.target.classList.contains('cp-remove-custom-item')) {
				var item = e.target.closest('.cp-custom-item');
				if (item) item.remove();
			}
		});
	}
})();
