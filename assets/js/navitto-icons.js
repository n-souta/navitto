/**
 * Navitto - アイコンレジストリ（Font Awesome クラス名）
 *
 * 引用元: Font Awesome (https://fontawesome.com/)
 *        npm: @fortawesome/fontawesome-free
 * クラス名を nvfa / nvfas / nvfar / nvfab + nvfa-xxx にし、テーマの fa- と競合しないようにする。
 *
 * @package Navitto
 * @since   1.1.0
 */
(function() {
	'use strict';

	// アイコン名 → Font Awesome クラス文字列（nv- プレフィックス）
	// FA6 Free の solid/regular に合わせた名前（表示崩れする outline 系は除外）
	var iconClasses = {
		none: '',
		home: 'nvfa nvfas nvfa-house',
		star: 'nvfa nvfas nvfa-star',
		heart: 'nvfa nvfas nvfa-heart',
		bookmark: 'nvfa nvfas nvfa-bookmark',
		flag: 'nvfa nvfas nvfa-flag',
		check: 'nvfa nvfas nvfa-check',
		'check-circle': 'nvfa nvfas nvfa-circle-check',
		circle: 'nvfa nvfas nvfa-circle',
		list: 'nvfa nvfas nvfa-list',
		clipboard: 'nvfa nvfas nvfa-clipboard',
		file: 'nvfa nvfas nvfa-file',
		'file-text': 'nvfa nvfas nvfa-file-lines',
		book: 'nvfa nvfas nvfa-book',
		'book-open': 'nvfa nvfas nvfa-book-open',
		pen: 'nvfa nvfas nvfa-pen',
		'message-circle': 'nvfa nvfas nvfa-comment',
		mail: 'nvfa nvfas nvfa-envelope',
		phone: 'nvfa nvfas nvfa-phone',
		bell: 'nvfa nvfas nvfa-bell',
		'arrow-right': 'nvfa nvfas nvfa-arrow-right',
		'arrow-left': 'nvfa nvfas nvfa-arrow-left',
		'arrow-up': 'nvfa nvfas nvfa-arrow-up',
		'arrow-down': 'nvfa nvfas nvfa-arrow-down',
		'chevron-right': 'nvfa nvfas nvfa-chevron-right',
		'chevron-left': 'nvfa nvfas nvfa-chevron-left',
		'chevron-up': 'nvfa nvfas nvfa-chevron-up',
		'chevron-down': 'nvfa nvfas nvfa-chevron-down',
		'external-link': 'nvfa nvfas nvfa-arrow-up-right-from-square',
		compass: 'nvfa nvfas nvfa-compass',
		info: 'nvfa nvfas nvfa-circle-info',
		'alert-circle': 'nvfa nvfas nvfa-circle-exclamation',
		'help-circle': 'nvfa nvfas nvfa-circle-question',
		zap: 'nvfa nvfas nvfa-bolt',
		search: 'nvfa nvfas nvfa-magnifying-glass',
		lock: 'nvfa nvfas nvfa-lock',
		unlock: 'nvfa nvfas nvfa-lock-open',
		shield: 'nvfa nvfas nvfa-shield-halved',
		settings: 'nvfa nvfas nvfa-gear',
		image: 'nvfa nvfas nvfa-image',
		camera: 'nvfa nvfas nvfa-camera',
		play: 'nvfa nvfas nvfa-play',
		pause: 'nvfa nvfas nvfa-pause',
		music: 'nvfa nvfas nvfa-music',
		user: 'nvfa nvfas nvfa-user',
		users: 'nvfa nvfas nvfa-users',
		globe: 'nvfa nvfas nvfa-globe',
		'map-pin': 'nvfa nvfas nvfa-location-dot',
		clock: 'nvfa nvfas nvfa-clock',
		calendar: 'nvfa nvfas nvfa-calendar',
		coffee: 'nvfa nvfas nvfa-mug-saucer',
		'thumbs-up': 'nvfa nvfas nvfa-thumbs-up',
		'thumbs-down': 'nvfa nvfas nvfa-thumbs-down',
		award: 'nvfa nvfas nvfa-trophy',
		target: 'nvfa nvfas nvfa-bullseye',
		smile: 'nvfa nvfas nvfa-face-smile',
		sun: 'nvfa nvfas nvfa-sun',
		moon: 'nvfa nvfas nvfa-moon',
		lightbulb: 'nvfa nvfas nvfa-lightbulb',
		'trending-up': 'nvfa nvfas nvfa-arrow-trend-up',
		'trending-down': 'nvfa nvfas nvfa-arrow-trend-down',
		layers: 'nvfa nvfas nvfa-layer-group',
		database: 'nvfa nvfas nvfa-database',
		hash: 'nvfa nvfas nvfa-hashtag',
		link: 'nvfa nvfas nvfa-link',
		'link-off': 'nvfa nvfas nvfa-link-slash',
		key: 'nvfa nvfas nvfa-key',
		'shopping-cart': 'nvfa nvfas nvfa-cart-shopping',
		gift: 'nvfa nvfas nvfa-gift',
		'dollar-sign': 'nvfa nvfas nvfa-dollar-sign',
		tag: 'nvfa nvfas nvfa-tag',
		folder: 'nvfa nvfas nvfa-folder',
		'folder-open': 'nvfa nvfas nvfa-folder-open',
		download: 'nvfa nvfas nvfa-download',
		upload: 'nvfa nvfas nvfa-upload',
		share: 'nvfa nvfas nvfa-share-nodes',
		printer: 'nvfa nvfas nvfa-print',
		scissors: 'nvfa nvfas nvfa-scissors',
		package: 'nvfa nvfas nvfa-box',
		grid: 'nvfa nvfas nvfa-grip',
		menu: 'nvfa nvfas nvfa-bars',
		'more-horizontal': 'nvfa nvfas nvfa-ellipsis',
		'more-vertical': 'nvfa nvfas nvfa-ellipsis-vertical',
		minus: 'nvfa nvfas nvfa-minus',
		plus: 'nvfa nvfas nvfa-plus',
		x: 'nvfa nvfas nvfa-xmark',
		'x-circle': 'nvfa nvfas nvfa-circle-xmark',
		refresh: 'nvfa nvfas nvfa-arrows-rotate',
		repeat: 'nvfa nvfas nvfa-repeat',
		filter: 'nvfa nvfas nvfa-filter',
		eye: 'nvfa nvfas nvfa-eye',
		'eye-off': 'nvfa nvfas nvfa-eye-slash',
		feather: 'nvfa nvfas nvfa-feather-pointed',
		anchor: 'nvfa nvfas nvfa-anchor',
		box: 'nvfa nvfas nvfa-cube',
		code: 'nvfa nvfas nvfa-code',
		cpu: 'nvfa nvfas nvfa-microchip',
		'ranking-star': 'nvfa nvfas nvfa-ranking-star',
		crown: 'nvfa nvfas nvfa-crown',
		'wand-magic-sparkles': 'nvfa nvfas nvfa-wand-magic-sparkles',
		'web-awesome': 'nvfa nvfas nvfa-web-awesome'
	};

	// ピッカーに表示するアイコン名（'none' は含めない＝初期は未選択）
	var iconNames = [
		'home', 'star', 'heart', 'bookmark', 'flag', 'check', 'check-circle', 'circle', 'list', 'clipboard',
		'file', 'file-text', 'book', 'book-open', 'pen', 'message-circle', 'mail', 'phone', 'bell',
		'arrow-right', 'arrow-left', 'arrow-up', 'arrow-down', 'chevron-right', 'chevron-left', 'chevron-up', 'chevron-down', 'external-link', 'compass',
		'info', 'alert-circle', 'help-circle', 'zap', 'search', 'lock', 'unlock', 'shield', 'settings',
		'image', 'camera', 'play', 'pause', 'music', 'user', 'users', 'globe', 'map-pin', 'clock', 'calendar', 'coffee',
		'thumbs-up', 'thumbs-down', 'award', 'target', 'smile', 'sun', 'moon', 'lightbulb', 'trending-up', 'trending-down', 'layers', 'database', 'hash', 'link', 'link-off', 'key',
		'shopping-cart', 'gift', 'dollar-sign', 'tag', 'folder', 'folder-open', 'download', 'upload', 'share', 'printer', 'scissors', 'package',
		'grid', 'menu', 'more-horizontal', 'more-vertical', 'minus', 'plus', 'x', 'x-circle', 'refresh', 'repeat', 'filter', 'eye', 'eye-off', 'feather', 'anchor', 'box', 'code', 'cpu',
		'ranking-star', 'crown', 'wand-magic-sparkles', 'web-awesome'
	];

	/**
	 * アイコン名からクラス文字列を取得（後方互換: "setId:iconName" も受け付ける）
	 * @returns {string} 例: "nvfa nvfas nvfa-house" または ""
	 */
	function getClass(iconNameOrValue) {
		if (!iconNameOrValue || iconNameOrValue === 'none') return '';
		var name = (iconNameOrValue.indexOf(':') !== -1) ? iconNameOrValue.split(':')[1] : iconNameOrValue;
		return iconClasses[name] || '';
	}

	/**
	 * クラス文字列から <i> の HTML を返す（描画用）
	 */
	function getIconHtml(iconNameOrValue) {
		var cls = getClass(iconNameOrValue);
		if (!cls) return '';
		return '<i class="' + cls + '" aria-hidden="true"></i>';
	}

	window.__NAVITTO_ICONS__ = {
		iconClasses: iconClasses,
		iconNames: iconNames,
		getClass: getClass,
		getIconHtml: getIconHtml,
		// 後方互換: getSvg は getIconHtml を返す（SVG→Font Awesome 移行後）
		getSvg: function() { return getIconHtml.apply(null, arguments); }
	};
})();
