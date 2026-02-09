---
description: ContentPilot プラグイン開発時の実装ルール。PHP/JS/CSS ファイルを編集する際に適用。
globs: "**/*.{php,js,css}"
---

# ContentPilot 実装ルール

## セキュリティ（絶対厳守）
1. nonce 検証 → `wp_verify_nonce()`
2. 権限確認 → `current_user_can()`
3. 入力サニタイズ → `sanitize_text_field()`, `absint()`, etc.
4. 出力エスケープ → `esc_html()`, `esc_attr()`, `esc_url()`, etc.
5. SQL → `$wpdb->prepare()` 必須、文字列結合での SQL 構築禁止

## 命名規則
- PHP 関数/フック: `contentpilot_` プレフィックス
- クラス: `ContentPilot_ClassName`
- ポストメタ: `_contentpilot_key_name`
- オプション: `contentpilot_option_name`
- CSS クラス: BEM 記法 `contentpilot-block__element--modifier`
- JS: `contentpilotData` (wp_localize_script)

## PHP コーディング
- WordPress Coding Standards に準拠
- 副作用はフックで実行（ファイル読込時に直接実行しない）
- 管理画面コードは `is_admin()` またはadminフックの中で
- シングルトンパターンでクラスをロード
- `wp_enqueue_scripts` / `admin_enqueue_scripts` でアセット読込

## JavaScript
- jQuery ラッパー `(function($) { ... })(jQuery);`
- `'use strict'` 使用
- グローバル汚染禁止（ContentPilot オブジェクトに集約）

## CSS
- CSS 変数 `--contentpilot-*` でテーマ対応
- `font-family: inherit` でテーマのフォントを尊重
- レスポンシブ: モバイルファースト（768px ブレークポイント）

## 禁止事項
- `eval()`, `base64_encode/decode`
- プレフィックスなしのグローバル関数/変数
- `$_POST` / `$_GET` の直接参照（`wp_unslash()` + サニタイズ必須）
- インラインスタイル/スクリプトの直書き（`wp_add_inline_style` / `wp_localize_script` を使用）
