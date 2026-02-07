/**
 * ContentPilot フロントエンド JavaScript
 *
 * 目次連携型の固定ナビゲーションを生成
 *
 * @package ContentPilot
 * @since   1.1.0
 */

(function($) {
	'use strict';

	var ContentPilot = {

		headings: [],
		$nav: null,
		detectedSource: null,
		fixedHeaderHeight: 0,
		$contentArea: null,
		isScrolling: false,
		settings: {
			scrollOffset: 80,
			animDuration: 500,
			showAfterScroll: 100,
			preset: 'simple',
			position: 'top',
			selectedH2: []
		},

		init: function() {
			var self = this;

			if (typeof contentpilotData !== 'undefined') {
				$.extend(this.settings, contentpilotData);
			}

			$(document).ready(function() {
				self.detectAndCollect();
				self.filterSelectedH2();
				if (self.headings.length >= 2) {
					self.assignIds();
					self.detectFixedHeader();
					self.detectContentArea();
					self.createNav();
					self.bindEvents();
				}
			});
		},

		/**
		 * H2選択フィルタとカスタムテキスト適用
		 *
		 * displayMode:
		 *   'show_all' - 全H2をそのまま表示
		 *   'select'   - 選択したH2のみ、カスタムテキスト適用
		 */
		filterSelectedH2: function() {
			var mode = this.settings.displayMode || 'show_all';
			if (mode !== 'select') return;

			var sel = this.settings.selectedH2;
			var ct  = this.settings.customTexts || {};

			// カスタムテキストを適用
			for (var k in ct) {
				if (ct.hasOwnProperty(k)) {
					var idx = parseInt(k, 10);
					if (idx < this.headings.length && ct[k]) {
						this.headings[idx].text = ct[k];
					}
				}
			}

			// 選択フィルタ
			if (!sel || !sel.length) return;
			var filtered = [];
			for (var i = 0; i < this.headings.length; i++) {
				for (var j = 0; j < sel.length; j++) {
					if (parseInt(sel[j], 10) === i) {
						filtered.push(this.headings[i]);
						break;
					}
				}
			}
			if (filtered.length >= 2) {
				this.headings = filtered;
			}
		},

		detectAndCollect: function() {
			var d = this.settings.detection;
			if (!d || !d.detectionOrder || !d.detectionOrder.length) {
				this.collectFromH2();
				return;
			}
			for (var i = 0; i < d.detectionOrder.length; i++) {
				var e = d.detectionOrder[i];
				if (e.source === 'h2') {
					this.collectFromH2();
					if (this.headings.length >= 2) {
						this.detectedSource = 'H2 Auto Detect';
						return;
					}
				} else if (this.collectFromToc(e)) {
					this.detectedSource = e.name + ' (' + e.source + ')';
					return;
				}
			}
		},

		collectFromToc: function(e) {
			var $c = $(e.container);
			if (!$c.length) return false;
			var $l = $(e.items);
			if (!$l.length) return false;
			var h = [];
			$l.each(function() {
				var $li = $(this), href = $li.attr('href') || '', txt = $li.text().trim();
				if (!txt || !href) return;
				var $pl = $li.closest('li'), $pu = $pl.parent('ul,ol'), nl = 0, $cur = $pu;
				while ($cur.length && !$cur.is($c)) {
					if ($cur.is('ul,ol')) nl++;
					$cur = $cur.parent();
				}
				if (nl > 1) return;
				var tid = href.indexOf('#') !== -1 ? href.split('#')[1] : '';
				if (tid) {
					var $t = document.getElementById(tid);
					if ($t) h.push({ element: $t, text: txt, id: tid });
				}
			});
			if (h.length >= 2) {
				this.headings = h;
				return true;
			}
			return false;
		},

		collectFromH2: function() {
			var self = this, s = '.entry-content,.post-content,.wp-block-post-content,article .content,.the-content,.single-content,.post_content,.article-content,.blog-post-content,.hentry .content,[itemprop="articleBody"]';
			var $c = $(s).first() || $('article').first() || $('main').first() || $('body');
			$c.find('h2').each(function() {
				var t = $(this).text().trim();
				if (t) self.headings.push({ element: this, text: t, id: $(this).attr('id') || '' });
			});
		},

		assignIds: function() {
			this.headings.forEach(function(h, i) {
				if (!h.id) {
					h.id = 'cp-h2-' + i;
					h.element.setAttribute('id', h.id);
				}
			});
		},

		detectFixedHeader: function() {
			var h = this.settings.fixedHeader, isM = window.innerWidth <= 768, sel = '', found = false;
			if (h && h.selectors) {
				sel = h.customSelector || (isM ? h.selectors.sp : h.selectors.pc);
			}
			if (!sel) {
				sel = isM ? '#header' : '#fix_header';
			}
			var $h = $(sel);
			if ($h.length) {
				var ht = Math.max($h.outerHeight(), $h.outerHeight(true), $h.height());
				if (ht > 0) {
					this.fixedHeaderHeight = ht;
					found = true;
				}
			}
			if (!found) {
				var cs = isM ? '#header,#fix_header' : '#fix_header,#header';
				$(cs).each(function() {
					var $e = $(this);
					if ($e.length) {
						var ht = Math.max($e.outerHeight(), $e.outerHeight(true), $e.height());
						if (ht > 0) {
							this.fixedHeaderHeight = ht;
							found = true;
							return false;
						}
					}
				}.bind(this));
			}
			return found;
		},

		/**
		 * ナビゲーション生成
		 */
		createNav: function() {
			var s = this;
			var preset = this.settings.preset || 'simple';
			var pos = this.settings.position || 'top';
			var cls = 'contentpilot-nav cp-preset-' + this.escapeAttr(preset);
			if (pos === 'bottom') cls += ' cp-pos-bottom';

			var html = '<nav class="' + cls + '" role="navigation" aria-label="記事内ナビゲーション" data-source="' + s.escapeAttr(s.detectedSource || 'u') + '">';
			html += '<div class="contentpilot-nav__inner"><ul class="contentpilot-nav__list">';

			this.headings.forEach(function(hd, i) {
				html += '<li class="contentpilot-nav__item' + (i === 0 ? ' contentpilot-nav__item--active' : '') + '">';
				html += '<a href="#' + s.escapeAttr(hd.id) + '" class="contentpilot-nav__link">' + s.escapeHtml(hd.text) + '</a></li>';
			});

			html += '</ul></div></nav>';
			this.$nav = $(html);
			$('body').append(this.$nav).addClass('contentpilot-active');

			var adjust = function() {
				s.detectFixedHeader();
				s.adjustNavPosition();
				s.updateScrollHint();
			};
			setTimeout(adjust, 0);
			setTimeout(adjust, 100);
			setTimeout(adjust, 300);
		},

		detectContentArea: function() {
			if (this.$contentArea) return this.$contentArea;
			var s = '.entry-content,.post-content,.wp-block-post-content,article .content,.the-content,.single-content,.post_content,.article-content,.blog-post-content,.hentry .content,[itemprop="articleBody"],#main,main,.l-mainContent__inner,#postContent,#entry,.content,.single-post-main';
			this.$contentArea = $(s).first() || $('article').first() || $('main').first();
			return this.$contentArea;
		},

		adjustNavPosition: function() {
			if (!this.$nav) return;
			var isBottom = this.settings.position === 'bottom';

			// 下部配置なら固定ヘッダー考慮不要
			if (isBottom) {
				var nh = this.$nav.outerHeight();
				this.$nav.css({ 'z-index': 99 });
				this.settings.scrollOffset = nh + 10;
				this.matchHeaderWidth();
				this.adjustContentPadding();
				return;
			}

			this.detectFixedHeader();
			var nh2 = this.$nav.outerHeight();
			var navTop = this.fixedHeaderHeight > 0 ? this.fixedHeaderHeight : 0;
			var hz = 99;

			if (navTop > 0) {
				var isM = window.innerWidth <= 768;
				var sels = isM ? '#header,#fix_header' : '#fix_header,#header';
				$(sels).each(function() {
					var zh = parseInt($(this).css('z-index'), 10);
					if (!isNaN(zh) && zh > 0) {
						hz = zh - 1;
						return false;
					}
				});
			}

			this.$nav.css({ 'top': navTop + 'px', 'z-index': hz });
			this.settings.scrollOffset = navTop + nh2 + 10;
			this.matchHeaderWidth();
			this.adjustContentPadding();
		},

		/**
		 * テーマ固定ヘッダーのコンテンツ幅を検出して合わせる
		 */
		matchHeaderWidth: function() {
			if (!this.$nav) return;
			var $inner = this.$nav.find('.contentpilot-nav__inner');
			// テーマヘッダーの内部コンテナを検出
			var innerSels = [
				'#fix_header .l-header__inner',
				'#header .l-header__inner',
				'.l-header__inner',
				'#fix_header > div',
				'#header > div',
				'header .l-header__inner',
				'header > div > div'
			];
			for (var i = 0; i < innerSels.length; i++) {
				var $hi = $(innerSels[i]).first();
				if ($hi.length && $hi.width() > 0) {
					var mw = $hi.css('max-width');
					var w = $hi.width();
					if (mw && mw !== 'none') {
						$inner.css({ 'max-width': mw, 'margin': '0 auto' });
					} else if (w < window.innerWidth - 20) {
						$inner.css({ 'max-width': w + 'px', 'margin': '0 auto' });
					}
					return;
				}
			}
		},

		adjustContentPadding: function() {
			if (!this.$nav) return;
			var isBottom = this.settings.position === 'bottom';
			var $c = this.detectContentArea();
			var nh = this.$nav.outerHeight();
			var navTop = isBottom ? 0 : (this.fixedHeaderHeight || 0);
			var total = navTop + nh;
			var prop = isBottom ? 'padding-bottom' : 'padding-top';
			var origKey = isBottom ? 'cp-orig-pb' : 'cp-orig-pt';

			if (!$c || !$c.length) {
				$('body').css(prop, total + 'px');
				return;
			}
			var orig = $c.data(origKey);
			if (orig === undefined) {
				orig = parseInt($c.css(prop), 10) || 0;
				$c.data(origKey, orig);
			}
			$c.css(prop, (orig + total) + 'px');
		},

		/**
		 * 横スクロールヒントの更新
		 */
		updateScrollHint: function() {
			if (!this.$nav) return;
			var $inner = this.$nav.find('.contentpilot-nav__inner');
			var el = $inner[0];
			if (!el) return;
			var sl = el.scrollLeft, sw = el.scrollWidth, cw = el.clientWidth;
			$inner.toggleClass('has-scroll-left', sl > 4);
			$inner.toggleClass('has-scroll-right', sl < sw - cw - 4);
		},

		bindEvents: function() {
			var s = this, st = null;

			// スクロール
			$(window).on('scroll.contentpilot', function() {
				if (st) return;
				st = setTimeout(function() { st = null; s.onScroll(); }, 16);
			});

			// ナビクリック
			this.$nav.on('click', '.contentpilot-nav__link', function(e) {
				e.preventDefault();
				var id = $(this).attr('href').slice(1);
				s.$nav.find('.contentpilot-nav__item').removeClass('contentpilot-nav__item--active');
				$(this).parent().addClass('contentpilot-nav__item--active');
				s.scrollTo(id);
			});

			// リサイズ
			var rt = null;
			$(window).on('resize.contentpilot', function() {
				if (rt) return;
				rt = setTimeout(function() {
					rt = null;
					if (s.$contentArea && s.$contentArea.length) {
						var isB = s.settings.position === 'bottom';
						var key = isB ? 'cp-orig-pb' : 'cp-orig-pt';
						var prop = isB ? 'padding-bottom' : 'padding-top';
						s.$contentArea.css(prop, (s.$contentArea.data(key) || 0) + 'px');
					}
					s.detectFixedHeader();
					s.adjustNavPosition();
					s.updateScrollHint();
				}, 250);
			});

			// 横スクロールヒント
			this.$nav.find('.contentpilot-nav__inner').on('scroll', function() {
				s.updateScrollHint();
			});

			this.onScroll();
		},

		onScroll: function() {
			var st = $(window).scrollTop();
			if (st > this.settings.showAfterScroll) {
				var wasHidden = !this.$nav.hasClass('is-visible');
				this.$nav.addClass('is-visible');
				if (wasHidden) {
					this.adjustNavPosition();
				}
			} else {
				this.$nav.removeClass('is-visible');
			}
			this.updateActive(st);
		},

		updateActive: function(st) {
			if (this.isScrolling) return;
			var ai = 0, ov = this.getScrollOffset() + 50;
			this.headings.forEach(function(h, i) {
				var $e = $(h.element);
				if ($e.length && st >= ($e.offset().top - ov)) ai = i;
			});
			this.$nav.find('.contentpilot-nav__item').removeClass('contentpilot-nav__item--active').eq(ai).addClass('contentpilot-nav__item--active');
		},

		/**
		 * クリック時のオフセットをリアルタイム計算
		 */
		getScrollOffset: function() {
			var headerH = this.fixedHeaderHeight || 0;
			var navH = (window.innerWidth <= 768) ? 48 : 56;
			return headerH + navH + 10;
		},

		scrollTo: function(id) {
			var s = this, $t = $('#' + id), done = false;
			if (!$t.length) return;
			s.isScrolling = true;
			s.detectFixedHeader();
			var offset = s.getScrollOffset();
			var target = $t.offset().top - offset;
			$('html, body').stop().animate(
				{ scrollTop: target },
				s.settings.animDuration,
				function() {
					if (done) return;
					done = true;
					var final_pos = $t.offset().top - s.getScrollOffset();
					if (Math.abs($(window).scrollTop() - final_pos) > 2) {
						$(window).scrollTop(final_pos);
					}
					s.isScrolling = false;
				}
			);
		},

		escapeHtml: function(s) {
			var d = document.createElement('div');
			d.textContent = s;
			return d.innerHTML;
		},

		escapeAttr: function(s) {
			return String(s).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
		}
	};

	ContentPilot.init();

})(jQuery);
