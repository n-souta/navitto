/**
 * ContentPilot フロントエンド JavaScript
 *
 * テーマのヘッダー構造に挿入する方式
 *
 * @package ContentPilot
 * @since   1.2.0
 */

(function($) {
	'use strict';

	var ContentPilot = {

		headings: [],
		$nav: null,
		$headerParent: null,  // ナビの挿入先ヘッダー要素
		insertMode: 'body',   // 'inside' | 'after' | 'body'
		isScrolling: false,
		lastActiveIndex: -1,  // 前回のアクティブインデックス
		settings: {
			scrollOffset: 80,
			animDuration: 500,
			showAfterScroll: 100,
			preset: 'simple',
			position: 'top',
			displayMode: 'show_all',
			selectedH2: [],
			customTexts: {},
			trigger: { type: 'immediate' },
			detection: null,
			fixedHeader: null
		},

		/**
		 * 初期化
		 */
		init: function() {
			var self = this;

			if (typeof contentpilotData !== 'undefined') {
				$.extend(this.settings, contentpilotData);
			}

			$(document).ready(function() {
				self.detectHeadings();
				self.filterSelectedH2();

				var minHeadings = (self.settings.displayMode === 'select') ? 1 : 2;
				if (self.headings.length >= minHeadings) {
					self.assignIds();
					self.findHeaderParent();
					self.createNav();
					self.bindEvents();
				}
			});
		},

		/* ==================================================================
		   見出し収集
		   ================================================================== */

		detectHeadings: function() {
			var detection = this.settings.detection;
			if (!detection || !detection.detectionOrder) {
				this.collectH2Headings();
				return;
			}
			var order = detection.detectionOrder;
			for (var i = 0; i < order.length; i++) {
				var entry = order[i];
				if (entry.source === 'h2') {
					this.collectH2Headings();
					if (this.headings.length > 0) return;
				} else {
					if (this.collectFromToc(entry)) return;
				}
			}
		},

		collectFromToc: function(entry) {
			var self = this;
			var $container = $(entry.container);
			if ($container.length === 0) return false;
			var $items = $(entry.items);
			if ($items.length === 0) return false;

			$items.each(function() {
				var $a = $(this);
				var href = $a.attr('href') || '';
				var text = $a.text().trim();
				if (!text) return;
				var id = '';
				if (href.indexOf('#') !== -1) {
					id = href.split('#')[1] || '';
				}
				var element = null;
				if (id) {
					var $target = $('#' + id);
					if ($target.length > 0 && $target.is('h2')) {
						element = $target[0];
					}
				}
				if (!element) return;
				self.headings.push({ element: element, text: text, id: id });
			});
			return this.headings.length > 0;
		},

		collectH2Headings: function() {
			var self = this;
			var $container = $(
				'.entry-content, .post-content, .wp-block-post-content, ' +
				'article .content, .post_content, #the_content'
			).first();
			if ($container.length === 0) $container = $('article').first();
			if ($container.length === 0) $container = $('main').first();
			if ($container.length === 0) return;

			$container.find('h2').each(function() {
				var $h2 = $(this);
				var text = $h2.text().trim();
				if (text) {
					self.headings.push({ element: this, text: text, id: $h2.attr('id') || '' });
				}
			});
		},

		filterSelectedH2: function() {
			var s = this.settings;
			if (s.displayMode !== 'select' || !s.selectedH2 || s.selectedH2.length === 0) return;

			var selected = s.selectedH2;
			var texts = s.customTexts || {};
			var filtered = [];
			for (var i = 0; i < this.headings.length; i++) {
				var inSelected = false;
				for (var j = 0; j < selected.length; j++) {
					if (parseInt(selected[j], 10) === i) { inSelected = true; break; }
				}
				if (inSelected) {
					var heading = this.headings[i];
					var customText = texts[String(i)];
					if (customText && customText.length > 0) heading.text = customText;
					filtered.push(heading);
				}
			}
			this.headings = filtered;
		},

		assignIds: function() {
			this.headings.forEach(function(heading, index) {
				if (!heading.id) {
					heading.id = 'contentpilot-h2-' + index;
					heading.element.setAttribute('id', heading.id);
				}
			});
		},

		/* ==================================================================
		   ヘッダー検出 & ナビ挿入
		   ================================================================== */

		/**
		 * テーマの固定ヘッダーを探して挿入先を決定
		 *
		 * SWELL: #header 内に追加（p-spHeadMenuと同じレベル）
		 * Cocoon: ヘッダー直後に追加
		 * その他: bodyに追加（従来方式）
		 */
		findHeaderParent: function() {
			// SWELL: #header が固定ヘッダーの親コンテナ
			var $swellHeader = $('#header');
			if ($swellHeader.length > 0) {
				var pos = $swellHeader.css('position');
				if (pos === 'fixed' || pos === 'sticky') {
					this.$headerParent = $swellHeader;
					this.insertMode = 'inside';
					return;
				}
			}

			// SWELL: #fix_header（PCスクロール時の固定ヘッダー）
			var $fixHeader = $('#fix_header');
			if ($fixHeader.length > 0 && $fixHeader.is(':visible')) {
				var pos2 = $fixHeader.css('position');
				if (pos2 === 'fixed' || pos2 === 'sticky') {
					this.$headerParent = $fixHeader;
					this.insertMode = 'inside';
					return;
				}
			}

			// Cocoon: .sticky-header
			var $cocoon = $('.sticky-header');
			if ($cocoon.length > 0 && $cocoon.is(':visible')) {
				this.$headerParent = $cocoon;
				this.insertMode = 'inside';
				return;
			}

			// 汎用: 画面上部の固定要素を探す
			var genericSelectors = [
				'.l-header', '#masthead', '.site-header', '#site-header'
			];
			for (var i = 0; i < genericSelectors.length; i++) {
				var $el = $(genericSelectors[i]);
				if ($el.length > 0 && $el.is(':visible')) {
					var gPos = $el.css('position');
					if (gPos === 'fixed' || gPos === 'sticky') {
						this.$headerParent = $el;
						this.insertMode = 'inside';
						return;
					}
				}
			}

			// カスタムセレクタ
			var headerData = this.settings.fixedHeader;
			if (headerData && headerData.customSelector) {
				var $custom = $(headerData.customSelector);
				if ($custom.length > 0 && $custom.is(':visible')) {
					this.$headerParent = $custom;
					this.insertMode = 'after';
					return;
				}
			}

			// 見つからない場合はbodyに追加（従来方式）
			this.insertMode = 'body';
		},

		/**
		 * ナビゲーションを生成して挿入
		 */
		createNav: function() {
			var self = this;
			var s = this.settings;
			var presetClass = s.preset ? ' cp-preset-' + s.preset : '';
			var posClass = s.position === 'bottom' ? ' cp-pos-bottom' : '';

			var html = '<nav class="contentpilot-nav' + presetClass + posClass + '" role="navigation">';
			html += '<div class="contentpilot-nav__inner">';
			html += '<ul class="contentpilot-nav__list">';

			this.headings.forEach(function(heading, index) {
				var activeClass = index === 0 ? ' contentpilot-nav__item--active' : '';
				html += '<li class="contentpilot-nav__item' + activeClass + '">';
				html += '<a href="#' + self.escapeAttr(heading.id) + '" class="contentpilot-nav__link">';
				html += self.escapeHtml(heading.text);
				html += '</a></li>';
			});

			html += '</ul></div></nav>';
			this.$nav = $(html);

			// 挿入モードに応じてDOMに配置
			if (this.insertMode === 'inside' && this.$headerParent) {
				// ヘッダー内に追加 → position: fixedは不要（親が固定されている）
				this.$headerParent.append(this.$nav);
				this.$nav.addClass('cp-inside-header');
			} else if (this.insertMode === 'after' && this.$headerParent) {
				// ヘッダーの直後に追加
				this.$headerParent.after(this.$nav);
			} else {
				// bodyに追加（従来方式）
				$('body').append(this.$nav);
			}

			if (s.position === 'bottom') {
				$('body').addClass('contentpilot-bottom');
			}

			this.updateScrollHint();
		},

		/* ==================================================================
		   スクロールオフセット
		   ================================================================== */

		/**
		 * スクロールオフセットを取得
		 * ヘッダー内に挿入されている場合、親ヘッダーの全体高さを使う
		 */
		getScrollOffset: function() {
			if (this.insertMode === 'inside' && this.$headerParent) {
				// ヘッダー全体の高さ（ナビ含む）
				return this.$headerParent[0].offsetHeight + 10;
			}
			// bodyに追加された場合
			var navH = window.innerWidth <= 768 ? 48 : 56;
			return navH + 10;
		},

		/* ==================================================================
		   横スクロールヒント
		   ================================================================== */

		updateScrollHint: function() {
			if (!this.$nav) return;
			var $inner = this.$nav.find('.contentpilot-nav__inner');
			if ($inner.length === 0) return;

			var el = $inner[0];
			var scrollLeft = el.scrollLeft;
			var maxScroll = el.scrollWidth - el.clientWidth;

			$inner.toggleClass('has-scroll-left', scrollLeft > 5);
			$inner.toggleClass('has-scroll-right', maxScroll > 5 && scrollLeft < maxScroll - 5);
		},

		/* ==================================================================
		   イベント
		   ================================================================== */

		/**
		 * リサイズ時にナビの挿入先を再検出・移動
		 * SWELLなどPC/SPでヘッダー要素が変わるテーマに対応
		 */
		relocateNav: function() {
			var oldParent = this.$headerParent;
			var oldMode = this.insertMode;

			// 再検出
			this.findHeaderParent();

			// 挿入先が変わった場合のみ移動
			var parentChanged = (this.insertMode !== oldMode) ||
				(this.$headerParent && oldParent && this.$headerParent[0] !== oldParent[0]) ||
				(this.$headerParent && !oldParent) ||
				(!this.$headerParent && oldParent);

			if (!parentChanged) return;

			// ナビをDOMから一旦外す
			this.$nav.detach();

			if (this.insertMode === 'inside' && this.$headerParent) {
				this.$headerParent.append(this.$nav);
				this.$nav.addClass('cp-inside-header');
			} else if (this.insertMode === 'after' && this.$headerParent) {
				this.$headerParent.after(this.$nav);
				this.$nav.removeClass('cp-inside-header');
			} else {
				$('body').append(this.$nav);
				this.$nav.removeClass('cp-inside-header');
			}
		},

		bindEvents: function() {
			var self = this;
			var resizeTimer = null;

			$(window).on('scroll.contentpilot', function() {
				self.onScroll();
			});

			$(window).on('resize.contentpilot', function() {
				// デバウンス: リサイズ完了後に再配置
				clearTimeout(resizeTimer);
				resizeTimer = setTimeout(function() {
					self.relocateNav();
					self.updateScrollHint();
				}, 150);
			});

			this.$nav.find('.contentpilot-nav__inner').on('scroll', function() {
				self.updateScrollHint();
			});

			this.$nav.on('click', '.contentpilot-nav__link', function(e) {
				e.preventDefault();
				var $link = $(this);
				var id = $link.attr('href').slice(1);
				var $item = $link.parent();

				self.$nav.find('.contentpilot-nav__item').removeClass('contentpilot-nav__item--active');
				$item.addClass('contentpilot-nav__item--active');
				self.lastActiveIndex = $item.index();

				// クリックしたアイテムを中央に配置
				self.centerActiveItem($item);
				self.scrollTo(id);
			});

			this.onScroll();
		},

		/**
		 * 表示開始条件を満たしているか判定
		 */
		shouldShow: function(scrollTop) {
			var trigger = this.settings.trigger || { type: 'immediate' };
			var type = trigger.type || 'immediate';

			// immediate: showAfterScroll ベース（デフォルト動作）
			if (type === 'immediate') {
				return scrollTop > this.settings.showAfterScroll;
			}

			// first_selected: 選択した最初の見出しを通過したら
			if (type === 'first_selected') {
				if (this.headings.length === 0) return false;
				var $first = $(this.headings[0].element);
				if ($first.length === 0) return false;
				return scrollTop >= $first.offset().top - this.getScrollOffset();
			}

			// nth_selected: N番目の見出しを通過したら
			if (type === 'nth_selected') {
				var nth = (trigger.nth || 2) - 1; // 0ベースに変換
				if (nth < 0 || nth >= this.headings.length) return false;
				var $nth = $(this.headings[nth].element);
				if ($nth.length === 0) return false;
				return scrollTop >= $nth.offset().top - this.getScrollOffset();
			}

			// scroll_px: 指定ピクセルスクロール後
			if (type === 'scroll_px') {
				var px = trigger.scrollPx || 300;
				return scrollTop > px;
			}

			// フォールバック
			return scrollTop > this.settings.showAfterScroll;
		},

		onScroll: function() {
			var scrollTop = $(window).scrollTop();

			if (this.shouldShow(scrollTop)) {
				if (!this.$nav.hasClass('is-visible')) {
					this.$nav.addClass('is-visible');
				}
			} else {
				if (this.$nav.hasClass('is-visible')) {
					this.$nav.removeClass('is-visible');
				}
			}

			if (!this.isScrolling) {
				this.updateActive(scrollTop);
			}
		},

		updateActive: function(scrollTop) {
			var activeIndex = 0;
			var offset = this.getScrollOffset();

			this.headings.forEach(function(heading, index) {
				var $el = $(heading.element);
				if ($el.length === 0) return;
				if (scrollTop >= $el.offset().top - offset - 10) {
					activeIndex = index;
				}
			});

			var $items = this.$nav.find('.contentpilot-nav__item');
			$items.removeClass('contentpilot-nav__item--active');
			var $active = $items.eq(activeIndex).addClass('contentpilot-nav__item--active');

			// カレントが変わったらセンタリング
			if (activeIndex !== this.lastActiveIndex) {
				this.lastActiveIndex = activeIndex;
				this.centerActiveItem($active);
			}
		},

		/**
		 * アクティブなナビアイテムを横スクロールで中央に配置
		 */
		centerActiveItem: function($activeItem) {
			if (!$activeItem || $activeItem.length === 0) return;

			var $inner = this.$nav.find('.contentpilot-nav__inner');
			if ($inner.length === 0) return;

			var innerEl = $inner[0];
			var innerRect = innerEl.getBoundingClientRect();
			var itemRect = $activeItem[0].getBoundingClientRect();

			// アイテムの中央とコンテナの中央の差分をスクロール量に加算
			var itemCenter = itemRect.left + itemRect.width / 2;
			var innerCenter = innerRect.left + innerRect.width / 2;
			var scrollTarget = innerEl.scrollLeft + (itemCenter - innerCenter);

			$inner.stop().animate({ scrollLeft: scrollTarget }, 300);
		},

		scrollTo: function(id) {
			var self = this;
			var $target = $('#' + id);
			if ($target.length === 0) return;

			this.isScrolling = true;
			var offset = this.getScrollOffset();
			var targetTop = $target.offset().top - offset;

			$('html, body').stop().animate({
				scrollTop: targetTop
			}, this.settings.animDuration, function() {
				if (self.isScrolling) {
					self.isScrolling = false;
					var finalTop = $target.offset().top - offset;
					if (Math.abs($(window).scrollTop() - finalTop) > 2) {
						$(window).scrollTop(finalTop);
					}
				}
			});
		},

		escapeHtml: function(str) {
			var div = document.createElement('div');
			div.textContent = str;
			return div.innerHTML;
		},

		escapeAttr: function(str) {
			return String(str)
				.replace(/&/g, '&amp;')
				.replace(/"/g, '&quot;')
				.replace(/'/g, '&#39;')
				.replace(/</g, '&lt;')
				.replace(/>/g, '&gt;');
		}
	};

	ContentPilot.init();

})(jQuery);
