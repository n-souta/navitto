# ContentPilot v1.0.0

長文記事の離脱を防ぐ、目次連携型の固定ナビゲーションプラグイン

## 概要

ContentPilot は WordPress プラグインで、長文記事の読者体験を向上させます。

## 主な機能

- 目次自動連携（テーマ/プラグイン対応）
- H2見出し選択機能
- デザインプリセット5種類
- 横スクロール対応

## 動作要件

- WordPress 6.0以上
- PHP 7.4以上

## ライセンス

GPL v2 or later

---

## 開発環境セットアップ

### 前提条件

- Git がインストールされていること
- Local by Flywheel（または同等のWordPressローカル環境）がインストールされていること
- GitHubアカウントへのアクセス権限があること

### 手順

#### 1. WordPressローカル環境を作成

1. Local by Flywheel を起動
2. 新しいサイトを作成（例: contentpilot-dev）
3. サイトの作成が完了するまで待機

#### 2. リポジトリをクローン

プラグインディレクトリに移動してクローンします。

- Windows: `cd "C:\Users\[ユーザー名]\Local Sites\[サイト名]\app\public\wp-content\plugins"`
- macOS: `cd ~/Local Sites/[サイト名]/app/public/wp-content/plugins`
- 実行: `git clone https://github.com/n-souta/contentpilot.git`

#### 3. ブランチの切り替えと最新取得

`cd contentpilot` のあと `git checkout develop` および `git pull origin develop`

#### 4. プラグインを有効化

管理画面 → プラグイン → インストール済みプラグイン → ContentPilot を有効化

### テスト用投稿の作成

動作確認には **H2見出し2つ以上** かつ **文字数3000文字以上** の投稿が必要です。投稿編集で複数の `## 見出し` と本文を入力してください。

### 動作確認

1. テスト投稿を作成し、投稿編集の「ContentPilot」メタボックスで「このページでContentPilotを有効にする」にチェックして公開
2. フロントで固定ナビが表示され、ナビ項目クリックで該当見出しにスクロールすることを確認

### 注意事項

- 開発中は `wp-config.php` の `WP_DEBUG` を `true` にすると便利です
- 本番環境では必ず `WP_DEBUG` を `false` にしてください
