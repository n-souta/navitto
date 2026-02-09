# ContentPilot 開発ルール

## プロジェクト情報
- プラグイン名: ContentPilot
- WordPress: 6.0+ / PHP: 7.4+
- 種類: H2見出しベースの固定ナビゲーション

## 必須ルール

### セキュリティ（絶対厳守）
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
- `eval()`, `base64`, 直接SQL, プレフィックスなし

## Agent Skills（必要時に @ で参照）

WordPress 公式の Agent Skills を `skills/` に配置済み。
トークン節約のため **必要な時だけ @ で参照** すること。

### セキュリティレビュー時
```
@skills/wp-plugin-development/references/security.md
```

### Settings API 実装時
```
@skills/wp-plugin-development/references/settings-api.md
```

### プラグイン構造・アーキテクチャ確認時
```
@skills/wp-plugin-development/references/structure.md
```

### 有効化/無効化/アンインストール実装時
```
@skills/wp-plugin-development/references/lifecycle.md
```

### データ保存・Cron実装時
```
@skills/wp-plugin-development/references/data-and-cron.md
```

### デバッグ・トラブルシューティング時
```
@skills/wp-plugin-development/references/debugging.md
```

### 全手順を確認したい場合
```
@skills/wp-plugin-development/SKILL.md
```

## プロジェクト固有の参照
- `@guides/quick-reference.md` — よく使うパターン集
- `@docs/plugin-specification.md` — プラグイン仕様書
- `@docs/development-flow.md` — 開発フロー記録
