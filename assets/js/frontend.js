/**
 * ContentPilot フロントエンド JavaScript
 *
 * 目次連携・プリセット・H2選択・横スクロール対応
 *
 * @package ContentPilot
 * @since   1.2.0
 */

(function($) {
	'use strict';

	var ContentPilot = {

		headings: [],
		$nav: null,
		fixedHeaderHeight: 0,
		isScrolling: false,
		settings: {
			scrollOffset: 80,
			animDuration: 500,
			showAfterScroll: 100,
			preset: 'simple',
			position: 'top',
			displayMode: 'auto',
			selectedH2: [],
			customTexts: {},
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

				if (self.headings.length >= 2) {
					self.assignIds();
					self.createNav();
					self.detectFixedHeader();
					self.adjustNavPosition();
					self.bindEvents();
				}
			});
		},

		/**
		 * 検出優先順位に基づいて見出しを収集
		 */
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

		/**
		 * 既存の目次から見出しを収集
		 */
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

		/**
		 * H2タグから見出しを収集（フォールバック）
		 */
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

		/**
		 * H2選択モードの場合、選択された見出しのみに絞り込む
		 */
		filterSelectedH2: function() {
			var s = this.settings;
			if (s.displayMode !== 'select' || !s.selectedH2 || s.selectedH2.length === 0) {
				return;
			}

			var selected = s.selectedH2;
			var texts = s.customTexts || {};
			var filtered = [];

			for (var i = 0; i < this.headings.length; i++) {
				var inSelected = false;
				for (var j = 0; j < selected.length; j++) {
					if (parseInt(selected[j], 10) === i) {
						inSelected = true;
						break;
					}
				}
				if (inSelected) {
					var heading = this.headings[i];
					// カスタムテキストがあれば適用
					var customText = texts[String(i)];
					if (customText && customText.length > 0) {
						heading.text = customText;
					}
					filtered.push(heading);
				}
			}

			this.headings = filtered;
		},

		/**
		 * IDを付与
		 */
		assignIds: function() {
			this.headings.forEach(function(heading, index) {
				if (!heading.id) {
					heading.id = 'contentpilot-h2-' + index;
					heading.element.setAttribute('id', heading.id);
				}
			});
		},

		/**
		 * ナビゲーションを生成
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
			var bodyClass = 'contentpilot-active';
			if (s.position === 'bottom') {
				bodyClass += ' contentpilot-bottom';
			}
			$('body').append(this.$nav).addClass(bodyClass);

			// 横スクロールヒントを初期化
			this.updateScrollHint();
		},

		/**
		 * テーマの固定ヘッダーを検出
		 */
		detectFixedHeader: function() {
			var headerData = this.settings.fixedHeader;
			if (!headerData) return;

			var selector = '';
			var isMobile = window.innerWidth <= 768;

			if (headerData.customSelector) {
				selector = headerData.customSelector;
			} else if (headerData.selectors) {
				if (isMobile && headerData.selectors.sp) {
					selector = headerData.selectors.sp;
				} else if (headerData.selectors.pc) {
					selector = headerData.selectors.pc;
				}
			}

			if (!selector) return;

			var $header = $(selector);
			if ($header.length > 0 && $header.is(':visible')) {
				var pos = $header.css('position');
				if (pos === 'fixed' || pos === 'sticky') {
					this.fixedHeaderHeight = $header.outerHeight(true);
				}
			}
		},

		/**
		 * ナビの位置を調整
		 */
		adjustNavPosition: function() {
			if (!this.$nav) return;
			var isBottom = this.settings.position === 'bottom';

			if (this.fixedHeaderHeight > 0 && !isBottom) {
				this.$nav.css('top', this.fixedHeaderHeight + 'px');

				// z-indexをテーマヘッダーより低く
				var headerData = this.settings.fixedHeader;
				var selector = '';
				if (headerData && headerData.customSelector) {
					selector = headerData.customSelector;
				} else if (headerData && headerData.selectors && headerData.selectors.pc) {
					selector = headerData.selectors.pc;
				}
				if (selector) {
					var $hdr = $(selector);
					if ($hdr.length > 0) {
						var zIdx = parseInt($hdr.css('z-index'), 10);
						if (!isNaN(zIdx) && zIdx > 0) {
							this.$nav.css('z-index', zIdx - 1);
						}
					}
				}
			}

			this.adjustContentPadding();
		},

		/**
		 * コンテンツのパディングを調整
		 */
		adjustContentPadding: function() {
			var navH = this.$nav.is(':visible') ? this.$nav.outerHeight(true) : 56;
			var isBottom = this.settings.position === 'bottom';

			if (isBottom) {
				$('body.contentpilot-active').css({ 'padding-top': '', 'padding-bottom': navH + 'px' });
			} else {
				var total = this.fixedHeaderHeight + navH;
				$('body.contentpilot-active').css({ 'padding-top': total + 'px', 'padding-bottom': '' });
			}
		},

		/**
		 * スクロールオフセットを取得
		 */
		getScrollOffset: function() {
			var navH = window.innerWidth <= 768 ? 48 : 56;
			return this.fixedHeaderHeight + navH + 10;
		},

		/**
		 * 横スクロールヒントを更新
		 */
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

		/**
		 * イベントをバインド
		 */
		bindEvents: function() {
			var self = this;

			$(window).on('scroll.contentpilot', function() {
				self.onScroll();
			});

			$(window).on('resize.contentpilot', function() {
				self.detectFixedHeader();
				self.adjustNavPosition();
				self.updateScrollHint();
			});

			// 横スクロール時にヒントを更新
			this.$nav.find('.contentpilot-nav__inner').on('scroll', function() {
				self.updateScrollHint();
			});

			// クリック
			this.$nav.on('click', '.contentpilot-nav__link', function(e) {
				e.preventDefault();
				var $link = $(this);
				var id = $link.attr('href').slice(1);

				self.$nav.find('.contentpilot-nav__item').removeClass('contentpilot-nav__item--active');
				$link.parent().addClass('contentpilot-nav__item--active');

				self.scrollTo(id);
			});

			this.onScroll();
		},

		/**
		 * スクロール時の処理
		 */
		onScroll: function() {
			var scrollTop = $(window).scrollTop();

			if (scrollTop > this.settings.showAfterScroll) {
				if (!this.$nav.hasClass('is-visible')) {
					this.$nav.addClass('is-visible');
					this.adjustNavPosition();
				}
			} else {
				this.$nav.removeClass('is-visible');
			}

			if (!this.isScrolling) {
				this.updateActive(scrollTop);
			}
		},

		/**
		 * アクティブ項目を更新
		 */
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

			this.$nav.find('.contentpilot-nav__item')
				.removeClass('contentpilot-nav__item--active')
				.eq(activeIndex)
				.addClass('contentpilot-nav__item--active');
		},

		/**
		 * 指定位置にスクロール
		 */
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
