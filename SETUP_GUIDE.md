# 別PCでの開発環境セットアップ手順

## 前提条件

- Git がインストールされていること
- Local by Flywheel（または同等のWordPressローカル環境）がインストールされていること
- GitHubアカウントへのアクセス権限があること

---

## 手順

### 1. WordPressローカル環境を作成

1. Local by Flywheel を起動
2. 新しいサイトを作成（例: `contentpilot-dev`）
3. サイトの作成が完了するまで待機

### 2. リポジトリをクローン

プラグインディレクトリに移動してクローンします。

```bash
# Windowsの場合（Local by Flywheel）
cd "C:\Users\[ユーザー名]\Local Sites\[サイト名]\app\public\wp-content\plugins"

# macOSの場合
cd ~/Local Sites/[サイト名]/app/public/wp-content/plugins

# リポジトリをクローン
git clone https://github.com/n-souta/contentpilot.git
```

### 3. developブランチに切り替え

```bash
cd contentpilot
git checkout develop
```

### 4. 最新の変更を取得

```bash
git pull origin develop
```

### 5. プラグインを有効化

1. WordPress管理画面にログイン（通常: http://[サイト名].local/wp-admin）
2. 「プラグイン」→「インストール済みプラグイン」
3. 「ContentPilot」を有効化

---

## テスト用投稿の作成

ContentPilotの動作確認には以下の条件を満たす投稿が必要です：
- **H2見出し**: 2つ以上
- **文字数**: 3000文字以上

### 簡単なテスト投稿サンプル

WordPress管理画面 →「投稿」→「新規追加」で以下の内容をコピー＆ペーストしてください。

```
## はじめに

ここにはじめにの本文を入力します。テスト用なので適当な文章で構いません。
Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.

（このような文章を繰り返して3000文字以上にしてください）

## 機能説明

機能説明の本文をここに入力します。
Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.

## 使い方

使い方の本文をここに入力します。
Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.

## まとめ

まとめの本文をここに入力します。
Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
```

**ヒント**: 文字数が足りない場合は、各セクションの本文を繰り返しコピーして増やしてください。

---

## 動作確認

1. 上記のテスト投稿を作成
2. 投稿編集画面のサイドバーで「ContentPilot」メタボックスを確認
3. 「このページでContentPilotを有効にする」にチェック
4. 投稿を公開
5. フロントエンドで投稿を表示し、固定ナビゲーションが表示されることを確認
6. ナビゲーション項目をクリックして該当見出しにスクロールすることを確認

---

## 現在の開発状況

- **ブランチ**: `develop`
- **実装済み機能**:
  - プラグイン基本構造
  - H2見出し自動検出
  - 固定ナビゲーション表示
  - スムーズスクロール
  - アクティブ項目ハイライト
  - 投稿ごとの有効/無効設定（メタボックス）

---

## 注意事項

- 開発中は `wp-config.php` の `WP_DEBUG` を `true` にすると便利です
- 本番環境では必ず `WP_DEBUG` を `false` に戻してください
