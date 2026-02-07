# ContentPilot 開発ルール

## 🚨 コード生成前に必読
guides/quick-reference.md の全チェックリストを確認すること

## プロジェクト情報
- プラグイン名: ContentPilot
- WordPress: 6.0以上
- PHP: 7.4以上

## 必須ルール

### セキュリティ（絶対厳守）
1. nonce検証 → wp_verify_nonce()
2. 権限確認 → current_user_can()
3. サニタイズ → sanitize_*()
4. エスケープ → esc_*()
5. SQL → $wpdb->prepare()

### 命名
- プレフィックス: contentpilot_
- クラス: ContentPilot_ClassName
- メタキー: _contentpilot_key_name

### 禁止
❌ eval()
❌ base64
❌ 直接SQL
❌ プレフィックスなし

## 詳細ルール
必要な時は以下を参照:
- guides/quick-reference.md（よく使うパターン）
- wordpress-development-guide.md（全ルール）