# ContentPilot - AI Agent Instructions

## 基本
- **プラグイン名**: ContentPilot（H2見出しベースの固定ナビゲーション）
- **WordPress**: 6.0+ / **PHP**: 7.4+
- 実装時は本ファイルのルールと `@PROJECT_STRUCTURE.md` に従うこと

## 必須ルール（開発時厳守）

### セキュリティ
1. nonce検証 → `wp_verify_nonce()`
2. 権限確認 → `current_user_can()`
3. サニタイズ → `sanitize_*()`
4. エスケープ → `esc_*()`
5. SQL → `$wpdb->prepare()`

### 命名規則
- プレフィックス: `contentpilot_`
- クラス: `ContentPilot_ClassName`
- メタキー: `_contentpilot_key_name`
- CSS: BEM `contentpilot-nav__item--active`

### 禁止
- `eval()`, `base64`, 直接SQL, プレフィックスなしのグローバル

## 仕様書（必要時に @ 参照）
- `@docs/spec-plugin.md` — プラグイン基本仕様
- `@docs/spec-features.md` — 機能詳細・開発フロー

## Agent Skills（必要時に @ 参照）
- `@skills/wp-plugin-development/SKILL.md` — WordPress 公式ベストプラクティス
- セキュリティ: `@skills/wp-plugin-development/references/security.md`
- Settings API: `@skills/wp-plugin-development/references/settings-api.md`
- 構造: `@skills/wp-plugin-development/references/structure.md`
- ライフサイクル: `@skills/wp-plugin-development/references/lifecycle.md`
- データ/Cron: `@skills/wp-plugin-development/references/data-and-cron.md`
- デバッグ: `@skills/wp-plugin-development/references/debugging.md`

## プロジェクト固有
- `@guides/quick-reference.md` — よく使うパターン集
