/**
 * ContentPilot フロントエンド JavaScript
 *
 * H2タグから固定ナビゲーションを生成（MVP版）
 *
 * @package ContentPilot
 * @since   1.0.0
 */

(function($) {
	'use strict';

	var ContentPilot = {

		headings: [],
		$nav: null,
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

			console.log('[ContentPilot] 初期化開始');

			// 設定をマージ
			if (typeof contentpilotData !== 'undefined') {
				$.extend(this.settings, contentpilotData);
				console.log('[ContentPilot] 設定読み込み完了', this.settings);
			}

			$(document).ready(function() {
				console.log('[ContentPilot] DOM準備完了');
				
				self.collectHeadings();
				console.log('[ContentPilot] H2検出数:', self.headings.length, self.headings);

				if (self.headings.length >= 2) {
					self.assignIds();
					self.createNav();
					self.bindEvents();
					console.log('[ContentPilot] ナビゲーション作成完了');
				} else {
					console.log('[ContentPilot] H2が2つ未満のためナビ非表示');
				}
			});
		},

		/**
		 * H2見出しを収集
		 */
		collectHeadings: function() {
			var self = this;
			
			// 記事コンテンツのコンテナを探す
			var $container = $('.entry-content, .post-content, .wp-block-post-content, article .content').first();
			
			// コンテナが見つからない場合は article 内を探す
			if ($container.length === 0) {
				$container = $('article').first();
			}
			
			// それでも見つからない場合は main 内を探す
			if ($container.length === 0) {
				$container = $('main').first();
			}

			console.log('[ContentPilot] コンテナ:', $container.length ? $container[0].className : 'なし');

			if ($container.length === 0) {
				console.log('[ContentPilot] コンテナが見つかりません');
				return;
			}

			// コンテナ内のH2を取得
			$container.find('h2').each(function(index) {
				var $h2 = $(this);
				var text = $h2.text().trim();

				console.log('[ContentPilot] H2発見:', index, text, 'offset:', $h2.offset().top);

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
		 * IDを付与
		 */
		assignIds: function() {
			var self = this;

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
		 * イベントをバインド
		 */
		bindEvents: function() {
			var self = this;

			// スクロール
			$(window).on('scroll.contentpilot', function() {
				self.onScroll();
			});

			// クリック
			this.$nav.on('click', '.contentpilot-nav__link', function(e) {
				e.preventDefault();
				var $link = $(this);
				var id = $link.attr('href').slice(1);
				
				// クリックした項目を即座にアクティブに
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

			// 表示/非表示
			if (scrollTop > this.settings.showAfterScroll) {
				this.$nav.addClass('is-visible');
			} else {
				this.$nav.removeClass('is-visible');
			}

			// アクティブ更新
			this.updateActive(scrollTop);
		},

		/**
		 * アクティブ項目を更新
		 */
		updateActive: function(scrollTop) {
			var self = this;
			var activeIndex = 0;
			var offsetVal = parseInt(this.settings.scrollOffset, 10) + 50;

			this.headings.forEach(function(heading, index) {
				var $el = $(heading.element);
				if ($el.length === 0) {
					return;
				}
				var elemTop = $el.offset().top;
				var threshold = elemTop - offsetVal;
				
				if (scrollTop >= threshold) {
					activeIndex = index;
				}
			});

			// 全ての項目からアクティブを削除して、該当項目にアクティブを追加
			this.$nav.find('.contentpilot-nav__item')
				.removeClass('contentpilot-nav__item--active')
				.eq(activeIndex)
				.addClass('contentpilot-nav__item--active');
		},

		/**
		 * 指定位置にスクロール
		 */
		scrollTo: function(id) {
			var $target = $('#' + id);
			if ($target.length === 0) return;

			var top = $target.offset().top - this.settings.scrollOffset;

			$('html, body').animate({
				scrollTop: top
			}, this.settings.animDuration);
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
