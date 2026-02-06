/**
 * ContentPilot フロントエンド JavaScript
 *
 * 目次連携型の固定ナビゲーションを生成
 *
 * 検出優先順位:
 * 1. テーマ内蔵目次（SWELL, JIN, SANGO, AFFINGER, Cocoon, THE THOR）
 * 2. 目次プラグイン（TOC+, Easy TOC, Rich TOC, LuckyWP）
 * 3. H2タグから自動生成（フォールバック）
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
		settings: {
			scrollOffset: 80,
			animDuration: 500,
			showAfterScroll: 100
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
				self.detectAndCollect();
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
		 * 検出優先順位に従って見出しを収集
		 *
		 * PHP側から渡された detectionOrder を順に試行し、
		 * 最初に見つかったソースを採用する
		 */
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

		/**
		 * 既存の目次（テーマ/プラグイン）から見出しを収集
		 *
		 * @param {Object} entry 検出エントリ {source, name, container, items}
		 * @return {boolean} 見出しが2個以上取得できた場合 true
		 */
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

		createNav: function() {
			var s = this, h = '<nav class="contentpilot-nav" role="navigation" aria-label="記事内ナビゲーション" data-source="' + s.escapeAttr(s.detectedSource || 'u') + '"><div class="contentpilot-nav__inner"><ul class="contentpilot-nav__list">';
			this.headings.forEach(function(hd, i) {
				h += '<li class="contentpilot-nav__item' + (i === 0 ? ' contentpilot-nav__item--active' : '') + '"><a href="#' + s.escapeAttr(hd.id) + '" class="contentpilot-nav__link">' + s.escapeHtml(hd.text) + '</a></li>';
			});
			this.$nav = $(h + '</ul></div></nav>');
			$('body').append(this.$nav).addClass('contentpilot-active');
			var adjust = function() {
				s.detectFixedHeader();
				s.adjustNavPosition();
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
			this.detectFixedHeader();
			var nh = this.$nav.outerHeight(), navTop = this.fixedHeaderHeight > 0 ? this.fixedHeaderHeight : 0, hz = 9998;
			if (navTop > 0) {
				var isM = window.innerWidth <= 768, sels = isM ? '#header,#fix_header' : '#fix_header,#header';
				var $h = $(sels).first();
				if ($h.length) {
					var zh = parseInt($h.css('z-index'), 10);
					if (zh > 0) hz = zh - 1;
					else if (navTop === 64) hz = 9997;
				}
			}
			this.$nav.css({ 'top': navTop + 'px', 'z-index': hz });
			this.settings.scrollOffset = navTop + nh;
			this.adjustContentPadding();
		},

		adjustContentPadding: function() {
			if (!this.$nav) return;
			var $c = this.detectContentArea(), nh = this.$nav.outerHeight(), navTop = this.fixedHeaderHeight || 0, total = navTop + nh;
			if (!$c.length) {
				$('body').css('padding-top', total + 'px');
				return;
			}
			var orig = $c.data('cp-orig-pt');
			if (orig === undefined) {
				orig = parseInt($c.css('padding-top'), 10) || 0;
				$c.data('cp-orig-pt', orig);
			}
			$c.css('padding-top', (orig + total) + 'px');
		},

		bindEvents: function() {
			var s = this, st = null;
			$(window).on('scroll.contentpilot', function() {
				if (st) return;
				st = setTimeout(function() { st = null; s.onScroll(); }, 16);
			});
			this.$nav.on('click', '.contentpilot-nav__link', function(e) {
				e.preventDefault();
				var id = $(this).attr('href').slice(1);
				s.$nav.find('.contentpilot-nav__item').removeClass('contentpilot-nav__item--active');
				$(this).parent().addClass('contentpilot-nav__item--active');
				s.scrollTo(id);
			});
			var rt = null;
			$(window).on('resize.contentpilot', function() {
				if (rt) return;
				rt = setTimeout(function() {
					rt = null;
					if (s.$contentArea && s.$contentArea.length) {
						s.$contentArea.css('padding-top', (s.$contentArea.data('cp-orig-pt') || 0) + 'px');
					}
					s.detectFixedHeader();
					s.adjustNavPosition();
				}, 250);
			});
			this.onScroll();
		},

		onScroll: function() {
			var st = $(window).scrollTop();
			if (st > this.settings.showAfterScroll) {
				if (!this.$nav.hasClass('is-visible')) {
					this.adjustNavPosition();
				}
				this.$nav.addClass('is-visible');
			} else {
				this.$nav.removeClass('is-visible');
			}
			this.updateActive(st);
		},

		updateActive: function(st) {
			var ai = 0, ov = parseInt(this.settings.scrollOffset, 10) + 50;
			this.headings.forEach(function(h, i) {
				var $e = $(h.element);
				if ($e.length && st >= ($e.offset().top - ov)) ai = i;
			});
			this.$nav.find('.contentpilot-nav__item').removeClass('contentpilot-nav__item--active').eq(ai).addClass('contentpilot-nav__item--active');
		},

		scrollTo: function(id) {
			var $t = $('#' + id);
			if ($t.length) {
				$('html, body').animate({ scrollTop: $t.offset().top - this.settings.scrollOffset }, this.settings.animDuration);
			}
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
