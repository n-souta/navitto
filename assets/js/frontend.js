/**
 * ContentPilot フロントエンド JavaScript
 *
 * 目次連携・H2フォールバック対応の固定ナビゲーション
 *
 * @package ContentPilot
 * @since   1.1.0
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
		 *
		 * 1. テーマ内蔵目次 → 2. 目次プラグイン → 3. H2フォールバック
		 */
		detectHeadings: function() {
			var self = this;
			var detection = this.settings.detection;

			// detection データがない場合はH2フォールバック
			if (!detection || !detection.detectionOrder) {
				this.collectH2Headings();
				return;
			}

			// 検出順に試行
			var order = detection.detectionOrder;
			for (var i = 0; i < order.length; i++) {
				var entry = order[i];

				if (entry.source === 'h2') {
					// H2フォールバック
					this.collectH2Headings();
					if (this.headings.length > 0) {
						return;
					}
				} else {
					// テーマ/プラグイン/汎用の目次を検出
					var found = this.collectFromToc(entry);
					if (found) {
						return;
					}
				}
			}
		},

		/**
		 * 既存の目次コンテナから見出しを収集
		 *
		 * @param {Object} entry 検出エントリ { container, items, name }
		 * @return {boolean} 検出成功
		 */
		collectFromToc: function(entry) {
			var self = this;
			var $container = $(entry.container);

			if ($container.length === 0) {
				return false;
			}

			var $items = $(entry.items);
			if ($items.length === 0) {
				return false;
			}

			$items.each(function() {
				var $a = $(this);
				var href = $a.attr('href') || '';
				var text = $a.text().trim();

				if (!text) {
					return;
				}

				// アンカーリンクからIDを抽出
				var id = '';
				if (href.indexOf('#') !== -1) {
					id = href.split('#')[1] || '';
				}

				// 対応するH2要素を取得
				var element = null;
				if (id) {
					var $target = $('#' + id);
					if ($target.length > 0 && $target.is('h2')) {
						element = $target[0];
					}
				}

				// H2以外は除外（目次にはH3以下も含まれるため）
				if (!element) {
					return;
				}

				self.headings.push({
					element: element,
					text: text,
					id: id
				});
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

			if ($container.length === 0) {
				$container = $('article').first();
			}
			if ($container.length === 0) {
				$container = $('main').first();
			}
			if ($container.length === 0) {
				return;
			}

			$container.find('h2').each(function() {
				var $h2 = $(this);
				var text = $h2.text().trim();

				if (text) {
					self.headings.push({
						element: this,
						text: text,
						id: $h2.attr('id') || ''
					});
				}
			});
		},

		/**
		 * IDを付与（IDがない見出しに自動付与）
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
			var html = '<nav class="contentpilot-nav" role="navigation">';
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
			$('body').append(this.$nav).addClass('contentpilot-active');
		},

		/**
		 * テーマの固定ヘッダーを検出して高さを取得
		 */
		detectFixedHeader: function() {
			var headerData = this.settings.fixedHeader;
			if (!headerData) {
				return;
			}

			var selector = '';
			var isMobile = window.innerWidth <= 768;

			// カスタムセレクタ優先
			if (headerData.customSelector) {
				selector = headerData.customSelector;
			} else if (headerData.selectors) {
				if (isMobile && headerData.selectors.sp) {
					selector = headerData.selectors.sp;
				} else if (headerData.selectors.pc) {
					selector = headerData.selectors.pc;
				}
			}

			if (!selector) {
				return;
			}

			var $header = $(selector);
			if ($header.length > 0 && $header.is(':visible')) {
				var pos = $header.css('position');
				if (pos === 'fixed' || pos === 'sticky') {
					this.fixedHeaderHeight = $header.outerHeight(true);
				}
			}
		},

		/**
		 * プラグインナビの位置をテーマヘッダーに合わせて調整
		 */
		adjustNavPosition: function() {
			if (!this.$nav) {
				return;
			}

			if (this.fixedHeaderHeight > 0) {
				// テーマヘッダーの下に配置
				this.$nav.css('top', this.fixedHeaderHeight + 'px');

				// z-indexをテーマヘッダーより低く設定
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
						var zIndex = parseInt($hdr.css('z-index'), 10);
						if (!isNaN(zIndex) && zIndex > 0) {
							this.$nav.css('z-index', zIndex - 1);
						}
					}
				}
			}

			// コンテンツのパディング調整
			this.adjustContentPadding();
		},

		/**
		 * コンテンツのパディングを調整
		 */
		adjustContentPadding: function() {
			var navHeight = this.$nav.is(':visible') ? this.$nav.outerHeight(true) : 56;
			var totalOffset = this.fixedHeaderHeight + navHeight;
			$('body.contentpilot-active').css('padding-top', totalOffset + 'px');
		},

		/**
		 * スクロールオフセットを取得（固定ヘッダー + プラグインナビの高さ）
		 */
		getScrollOffset: function() {
			var navHeight = window.innerWidth <= 768 ? 48 : 56;
			return this.fixedHeaderHeight + navHeight + 10;
		},

		/**
		 * イベントをバインド
		 */
		bindEvents: function() {
			var self = this;

			// スクロール
			$(window).on('scroll.contentpilot', function() {
				self.onScroll();
			});

			// リサイズ時に固定ヘッダーを再検出
			$(window).on('resize.contentpilot', function() {
				self.detectFixedHeader();
				self.adjustNavPosition();
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

			// 初回実行
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
				if ($el.length === 0) {
					return;
				}
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
			if ($target.length === 0) {
				return;
			}

			this.isScrolling = true;
			var offset = this.getScrollOffset();
			var targetTop = $target.offset().top - offset;

			$('html, body').stop().animate({
				scrollTop: targetTop
			}, this.settings.animDuration, function() {
				if (self.isScrolling) {
					self.isScrolling = false;

					// 最終位置を補正
					var finalTop = $target.offset().top - offset;
					if (Math.abs($(window).scrollTop() - finalTop) > 2) {
						$(window).scrollTop(finalTop);
					}
				}
			});
		},

		/**
		 * HTMLエスケープ
		 */
		escapeHtml: function(str) {
			var div = document.createElement('div');
			div.textContent = str;
			return div.innerHTML;
		},

		/**
		 * 属性エスケープ
		 */
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
