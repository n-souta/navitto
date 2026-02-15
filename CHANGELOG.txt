# Changelog

All notable changes to Navitto will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/ja/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/lang/ja/).

## [1.0.0] - 2026-02-15

### Added

- 固定ナビゲーション表示（H2 見出しベース）
- テーマ内蔵目次・目次プラグインとの連携（SWELL, JIN, SANGO, TOC+, Easy TOC 等）
- 投稿ごとの表示モード（すべて表示 / 見出しを選択 / 非表示）
- H2 選択 UI・カスタムラベル・表示開始位置（即時 / N 番目通過後 / px スクロール後）
- デザインプリセット：シンプル・テーマ準拠
- WordPress カスタマイザー統合（配置位置・ナビの高さ・最小文字数・スクロール表示開始・固定ヘッダーセレクタ）
- 横スクロール・均等割り表示
- テーマヘッダー内挿入 / 直後挿入 / body 追加の自動検出
- ヘッダーが固定でないテーマでの body padding 条件付き適用（余白防止）
- 設定ページ（デフォルト有効化・最小文字数・一括有効/無効/長文のみ有効）
- カスタム項目（外部リンク等をナビに追加）
- box-shadow はテーマの --navitto-nav-shadow に従う仕様

### Security

- 投稿メタ保存：nonce・権限・サニタイズ・ホワイトリスト・esc_url_raw
- 一括操作：manage_options・check_ajax_referer・$wpdb->prepare
- 出力のエスケープ（esc_html / esc_attr / esc_url）、JS 側 escapeHtml / escapeAttr

---

[1.0.0]: https://github.com/n-souta/navitto/releases/tag/v1.0.0
