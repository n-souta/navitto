# ContentPilot プラグイン開発フロー

## 📋 プロジェクト全体の流れ

このドキュメントでは、GitHub を使った ContentPilot プラグインの開発から販売までの流れをまとめています。

---

## 1. 初期セットアップ（1日目）

### 1-1. ローカル開発環境の準備

#### 必要なツール
- **WordPress ローカル環境**
  - Local by Flywheel（推奨）
  - XAMPP
  - Docker + WordPress
- **エディタ**
  - Cursor（AI コーディング支援）
  - VS Code
- **Git**
  - Git for Windows / macOS
- **GitHub アカウント**

#### WordPress ローカル環境セットアップ
```bash
# Local by Flywheel を使用する場合
# 1. Local をインストール
# 2. 新しいサイトを作成（例: contentpilot-dev）
# 3. WordPress 6.0 以上を選択
# 4. PHP 7.4 以上を選択

# サイトのプラグインディレクトリに移動
cd ~/Local Sites/contentpilot-dev/app/public/wp-content/plugins/
```

### 1-2. GitHub リポジトリの作成

#### GitHub 上での操作
1. GitHub にログイン
2. 「New repository」をクリック
3. リポジトリ設定：
   - **Repository name:** `contentpilot`
   - **Description:** `長文記事の離脱を防ぐ、目次連携型の固定ナビゲーションプラグイン`
   - **Visibility:** Private（開発中は非公開）
   - **Initialize:** ✅ Add a README file
   - **Add .gitignore:** なし（後で追加）
   - **Choose a license:** GPL v2 or later

#### ローカルでの Git 初期化
```bash
# プラグインディレクトリを作成
mkdir contentpilot
cd contentpilot

# Git を初期化
git init

# GitHub リポジトリと連携
git remote add origin https://github.com/YOUR_USERNAME/contentpilot.git

# メインブランチ名を main に変更（必要に応じて）
git branch -M main
```

### 1-3. プロジェクトファイルの配置

#### ディレクトリ構造を作成
```bash
contentpilot/
├── .gitignore
├── cursorrules.md
├── wordpress-development-guide.md
├── plugin-specification.md
├── README.md
├── LICENSE
└── (後で追加するファイル)
```

#### .gitignore を作成
```bash
# .gitignore の内容
# OS
.DS_Store
Thumbs.db
._*

# IDE
.vscode/
.idea/
*.sublime-project
*.sublime-workspace
.cursor/

# Node
node_modules/
npm-debug.log
package-lock.json

# Composer
vendor/
composer.lock

# Build
*.min.css
*.min.js
*.map

# WordPress
wp-config.php
wp-content/uploads/
wp-content/cache/
wp-content/backup*/
wp-content/updraft/
*.log

# Temporary
*.tmp
*.bak
*.swp
*~

# macOS
.AppleDouble
.LSOverride

# Testing
.phpunit.result.cache
tests/_output/
```

#### ファイルを配置
```bash
# 作成済みのドキュメントをコピー
# - cursorrules.md
# - wordpress-development-guide.md
# - plugin-specification.md

# README.md を作成（GitHub用）
# LICENSE ファイルを作成（GPL v2）
```

#### 初回コミット
```bash
# すべてのファイルをステージング
git add .

# コミット
git commit -m "Initial commit: プロジェクト仕様書とガイドライン"

# GitHub にプッシュ
git push -u origin main
```

---

## 2. 開発準備（1日目）

### 2-1. ブランチ戦略の設定

#### ブランチ構成
```
main (本番リリース用)
├── develop (開発用メインブランチ)
│   ├── feature/basic-structure (基本構造)
│   ├── feature/toc-detection (目次検出機能)
│   ├── feature/h2-selection (H2選択機能)
│   └── feature/design-presets (デザインプリセット)
└── hotfix/* (緊急修正用)
```

#### develop ブランチを作成
```bash
# develop ブランチを作成
git checkout -b develop

# GitHub にプッシュ
git push -u origin develop
```

### 2-2. GitHub Issue の作成

開発タスクを Issue として登録：

#### 例：Issue #1 - 基本プラグイン構造の実装
```markdown
## タスク概要
プラグインの基本構造を実装する

## チェックリスト
- [ ] contentpilot.php（メインファイル）作成
- [ ] includes/class-contentpilot.php 作成
- [ ] assets ディレクトリ作成
- [ ] プラグインヘッダーの実装
- [ ] アクティベーション/ディアクティベーション処理
- [ ] wordpress-development-guide.md のチェックリスト確認

## 参考ドキュメント
- wordpress-development-guide.md の「2. プラグイン基本構造」セクション
```

#### 他の Issue 例
- Issue #2: 目次検出機能の実装
- Issue #3: H2選択UI の実装
- Issue #4: デザインプリセット機能
- Issue #5: フロントエンド表示機能
- Issue #6: テーマ互換性テスト

### 2-3. プロジェクトボードの設定（オプション）

GitHub Projects で進捗管理：

1. GitHub リポジトリの「Projects」タブ
2. 「New project」→「Board」を選択
3. カラムを作成：
   - 📋 Backlog
   - 🔨 In Progress
   - 👀 In Review
   - ✅ Done

---

## 3. 開発フェーズ（2-4週間）

### 3-1. フェーズ1: MVP開発（1-2週間）

#### 作業の流れ

**Step 1: feature ブランチを作成**
```bash
# develop から feature ブランチを作成
git checkout develop
git pull origin develop
git checkout -b feature/basic-structure
```

**Step 2: Cursor で開発**
```bash
# Cursor を起動
cursor .

# cursorrules.md が自動的に読み込まれる
# wordpress-development-guide.md を参照しながら実装
```

**Step 3: AI に指示を出す（Cursor での例）**
```
You: wordpress-development-guide.md を読んで、
     contentpilot.php を作成してください。
     チェックリストをすべて確認してから実装してください。

AI: [wordpress-development-guide.md を読む]
    [チェックリストを確認]
    [コードを生成]
```

**Step 4: コミット**
```bash
# 変更を確認
git status

# ステージング
git add contentpilot.php

# コミット（具体的なメッセージ）
git commit -m "feat: プラグインメインファイルを作成

- プラグインヘッダーを追加
- 直接アクセス防止コードを追加
- アクティベーション/ディアクティベーション処理を実装
- チェックリスト確認済み"

# GitHub にプッシュ
git push -u origin feature/basic-structure
```

**Step 5: プルリクエストを作成**
1. GitHub リポジトリにアクセス
2. 「Pull requests」→「New pull request」
3. `feature/basic-structure` → `develop`
4. タイトル: `feat: プラグイン基本構造の実装`
5. 説明:
```markdown
## 実装内容
- プラグインメインファイル（contentpilot.php）
- メインクラスファイル（includes/class-contentpilot.php）

## チェックリスト
- [x] wordpress-development-guide.md のチェックリスト確認
- [x] セキュリティパターン確認
- [x] NGパターンに該当しないことを確認
- [x] ローカル環境でテスト実施

## 関連 Issue
Closes #1
```
6. 「Create pull request」

**Step 6: レビュー & マージ**
```bash
# 自分でコードレビュー
# 問題なければマージ

# GitHub 上で「Merge pull request」

# ローカルの develop を更新
git checkout develop
git pull origin develop

# feature ブランチを削除
git branch -d feature/basic-structure
git push origin --delete feature/basic-structure
```

#### MVP で実装する機能
- [x] プラグイン基本構造
- [x] H2タグからの目次自動生成
- [x] 固定ナビゲーション表示（シンプルデザイン1種類）
- [x] 投稿編集画面での有効/無効切り替え
- [x] 基本的なスタイリング

### 3-2. フェーズ2: 目次連携機能（1週間）

#### 実装する機能
- [x] テーマ内蔵目次の検出（SWELL, JIN, SANGO）
- [x] 目次プラグインの検出（TOC+, Easy TOC）
- [x] 検出優先順位のロジック

#### 開発の流れ
```bash
git checkout develop
git pull origin develop
git checkout -b feature/toc-detection

# 開発...

git add .
git commit -m "feat: 目次自動連携機能を実装"
git push -u origin feature/toc-detection

# プルリクエスト作成 → レビュー → マージ
```

### 3-3. フェーズ3: デザイン・カスタマイズ（1週間）

#### 実装する機能
- [x] デザインプリセット5種類
- [x] WordPress カスタマイザー統合
- [x] H2選択UI
- [x] 横スクロール機能

---

## 4. テストフェーズ（1週間）

### 4-1. テスト用ブランチの作成

```bash
git checkout develop
git checkout -b testing/theme-compatibility
```

### 4-2. テスト環境の準備

#### 複数の WordPress サイトを作成
```
Local by Flywheel で以下のサイトを作成：

1. contentpilot-swell（SWELL テーマ）
2. contentpilot-jin（JIN テーマ）
3. contentpilot-sango（SANGO テーマ）
4. contentpilot-affinger（AFFINGER テーマ）
5. contentpilot-cocoon（Cocoon テーマ）
```

#### テストプラグインをインストール
- Table of Contents Plus
- Easy Table of Contents
- Rich Table of Contents

### 4-3. テストケースの実行

#### wordpress-development-guide.md のテスト要件を実行
```
テストケースチェックリスト:

[ ] 基本機能テスト
[ ] 目次検出テスト
[ ] H2選択機能テスト
[ ] デザインプリセットテスト
[ ] レスポンシブテスト
[ ] パフォーマンステスト
[ ] セキュリティテスト
```

#### バグを発見したら Issue 作成
```markdown
## バグ報告
**環境:** SWELL + TOC Plus
**再現手順:**
1. ...
2. ...

**期待される動作:**
...

**実際の動作:**
...
```

#### バグ修正の流れ
```bash
git checkout develop
git checkout -b fix/swell-toc-detection

# 修正...

git add .
git commit -m "fix: SWELL目次の検出が失敗する問題を修正"
git push -u origin fix/swell-toc-detection

# プルリクエスト → マージ
```

---

## 5. リリース準備（3-5日）

### 5-1. リリースブランチの作成

```bash
git checkout develop
git checkout -b release/v1.0.0
```

### 5-2. バージョン番号の更新

#### 更新するファイル
```php
// contentpilot.php
/**
 * Version: 1.0.0
 */
define( 'CONTENTPILOT_VERSION', '1.0.0' );

// README.md
# ContentPilot v1.0.0

// readme.txt (WordPress.org用)
Stable tag: 1.0.0
```

### 5-3. CHANGELOG.md の作成

```markdown
# Changelog

## [1.0.0] - 2025-02-XX

### Added
- 目次自動連携機能（テーマ内蔵/プラグイン対応）
- H2見出し選択機能
- デザインプリセット5種類
- 横スクロール対応
- WordPress カスタマイザー統合

### Fixed
- (なし - 初回リリース)

### Changed
- (なし - 初回リリース)
```

### 5-4. ドキュメントの最終確認

```bash
# 以下のファイルを確認・更新
- README.md（GitHub用）
- readme.txt（WordPress.org用 - 公開する場合）
- LICENSE
- CHANGELOG.md
```

### 5-5. リリースコミット

```bash
git add .
git commit -m "chore: v1.0.0 リリース準備"
git push -u origin release/v1.0.0

# プルリクエスト: release/v1.0.0 → develop
# マージ後、develop → main にもマージ
```

---

## 6. リリース（1日目）

### 6-1. Git タグの作成

```bash
# main ブランチに切り替え
git checkout main
git pull origin main

# タグを作成
git tag -a v1.0.0 -m "ContentPilot v1.0.0

初回リリース
- 目次自動連携機能
- H2選択機能
- デザインプリセット5種類"

# タグをプッシュ
git push origin v1.0.0
```

### 6-2. GitHub Release の作成

1. GitHub リポジトリの「Releases」タブ
2. 「Create a new release」
3. タグを選択: `v1.0.0`
4. リリースタイトル: `ContentPilot v1.0.0`
5. 説明:
```markdown
## 🎉 初回リリース

ContentPilot v1.0.0 をリリースしました！

### ✨ 主な機能
- 目次自動連携（SWELL, JIN, SANGO, AFFINGER, Cocoon対応）
- 主要目次プラグイン対応（TOC+, Easy TOC等）
- H2見出し選択機能
- デザインプリセット5種類
- 横スクロール対応

### 📥 インストール
1. 下の「contentpilot-v1.0.0.zip」をダウンロード
2. WordPress 管理画面 → プラグイン → 新規追加 → アップロード
3. 有効化

### 📋 動作要件
- WordPress 6.0以上
- PHP 7.4以上

### 📚 ドキュメント
- [README](https://github.com/YOUR_USERNAME/contentpilot/blob/main/README.md)
- [CHANGELOG](https://github.com/YOUR_USERNAME/contentpilot/blob/main/CHANGELOG.md)
```

6. アセット（zipファイル）を添付

### 6-3. リリース用 ZIP ファイルの作成

```bash
# プラグインファイルのみを含む ZIP を作成
cd /path/to/plugins/
zip -r contentpilot-v1.0.0.zip contentpilot/ \
  -x "contentpilot/.git/*" \
  -x "contentpilot/.gitignore" \
  -x "contentpilot/node_modules/*" \
  -x "contentpilot/*.md" \
  -x "contentpilot/cursorrules.md" \
  -x "contentpilot/wordpress-development-guide.md" \
  -x "contentpilot/plugin-specification.md"
```

または、専用スクリプトを作成：

```bash
# build.sh
#!/bin/bash

VERSION="1.0.0"
PLUGIN_NAME="contentpilot"
BUILD_DIR="build"

# ビルドディレクトリをクリーンアップ
rm -rf $BUILD_DIR
mkdir -p $BUILD_DIR

# 必要なファイルのみコピー
rsync -av \
  --exclude='.git' \
  --exclude='.gitignore' \
  --exclude='node_modules' \
  --exclude='*.md' \
  --exclude='cursorrules.md' \
  --exclude='wordpress-development-guide.md' \
  --exclude='plugin-specification.md' \
  --exclude='build.sh' \
  ./ $BUILD_DIR/$PLUGIN_NAME/

# ZIP作成
cd $BUILD_DIR
zip -r ${PLUGIN_NAME}-v${VERSION}.zip $PLUGIN_NAME/
cd ..

echo "✅ ${PLUGIN_NAME}-v${VERSION}.zip を作成しました"
```

実行：
```bash
chmod +x build.sh
./build.sh
```

---

## 7. 公式サイト作成・販売開始（1-2週間）

### 7-1. 公式サイトのリポジトリを作成

```bash
# 別のリポジトリを作成
mkdir contentpilot-website
cd contentpilot-website
git init
git remote add origin https://github.com/YOUR_USERNAME/contentpilot-website.git
```

### 7-2. サイトの構成

```
contentpilot-website/
├── index.html
├── css/
│   └── style.css
├── js/
│   └── main.js
├── images/
│   ├── logo.png
│   ├── screenshot-1.png
│   └── demo.gif
└── docs/
    ├── manual.pdf
    └── faq.html
```

### 7-3. GitHub Pages でホスティング

1. GitHub リポジトリの「Settings」
2. 「Pages」セクション
3. Source: `main` ブランチ
4. 「Save」
5. サイトURL: `https://YOUR_USERNAME.github.io/contentpilot-website/`

### 7-4. 販売プラットフォームの設定

#### Gumroad を使用する場合
1. Gumroad アカウント作成
2. 新しい商品を作成
   - 商品名: ContentPilot
   - 価格: ¥2,000
   - ファイル: contentpilot-v1.0.0.zip
3. 商品ページのカスタマイズ
4. 公式サイトに購入リンクを設置

---

## 8. 継続的な開発（リリース後）

### 8-1. バグ修正の流れ

```bash
# ユーザーからバグ報告
# → GitHub Issue 作成

# hotfix ブランチを作成
git checkout main
git checkout -b hotfix/fix-swell-compatibility

# 修正...

git add .
git commit -m "fix: SWELL 3.0 との互換性問題を修正"

# main と develop の両方にマージ
git checkout main
git merge hotfix/fix-swell-compatibility
git push origin main

git checkout develop
git merge hotfix/fix-swell-compatibility
git push origin develop

# タグ作成（パッチバージョンアップ）
git tag -a v1.0.1 -m "v1.0.1: SWELL互換性修正"
git push origin v1.0.1

# GitHub Release 作成
# Gumroad のファイルを更新
```

### 8-2. 新機能追加の流れ

```bash
# feature ブランチ作成
git checkout develop
git checkout -b feature/auto-hide-on-scroll

# 開発...

# プルリクエスト → レビュー → マージ

# 次のマイナーバージョンリリースに含める
```

### 8-3. リリースサイクル

```
パッチバージョン（v1.0.x）
→ バグ修正のみ
→ 即座にリリース

マイナーバージョン（v1.x.0）
→ 新機能追加
→ 1-2ヶ月ごとにリリース

メジャーバージョン（v2.0.0）
→ 大規模な変更
→ 半年〜1年ごと
```

---

## 9. チートシート

### よく使う Git コマンド

```bash
# ブランチ一覧
git branch -a

# ブランチ切り替え
git checkout BRANCH_NAME

# 新しいブランチ作成
git checkout -b NEW_BRANCH

# 変更を確認
git status
git diff

# コミット
git add .
git commit -m "コミットメッセージ"

# プッシュ
git push origin BRANCH_NAME

# プル
git pull origin BRANCH_NAME

# マージ
git checkout TARGET_BRANCH
git merge SOURCE_BRANCH

# タグ
git tag -a v1.0.0 -m "メッセージ"
git push origin v1.0.0

# ブランチ削除
git branch -d BRANCH_NAME
git push origin --delete BRANCH_NAME
```

### コミットメッセージのプレフィックス

```
feat:     新機能
fix:      バグ修正
docs:     ドキュメント変更
style:    コードフォーマット（動作に影響なし）
refactor: リファクタリング
test:     テスト追加
chore:    ビルド設定等
```

### 開発中の確認事項

```
コード書く前：
[ ] wordpress-development-guide.md を読む
[ ] 該当セクションのチェックリストを確認

コミット前：
[ ] セキュリティチェックリスト確認
[ ] NGパターンに該当しないか確認
[ ] ローカル環境で動作確認

プルリクエスト前：
[ ] コードレビュー
[ ] テストケース実行
[ ] ドキュメント更新
```

---

## 10. トラブルシューティング

### Q1: Git でコンフリクトが発生した

```bash
# コンフリクトを確認
git status

# ファイルを手動で編集してコンフリクトを解消
# <<<<<<< HEAD と >>>>>>> の間を修正

# 解消後
git add .
git commit -m "fix: コンフリクトを解消"
```

### Q2: 間違ったコミットをした

```bash
# 最新のコミットを取り消し（変更は保持）
git reset --soft HEAD^

# 最新のコミットを完全に削除
git reset --hard HEAD^

# プッシュ済みの場合は force push（注意！）
git push -f origin BRANCH_NAME
```

### Q3: WordPress で Fatal Error が出た

```bash
# エラーログを確認
tail -f /path/to/wordpress/wp-content/debug.log

# プラグインを無効化（FTP経由）
# wp-content/plugins/contentpilot を一時的にリネーム

# エラー箇所を特定して修正
```

---

## 📚 参考リンク

### ドキュメント
- [plugin-specification.md](./plugin-specification.md) - プラグイン仕様書
- [cursorrules.md](./cursorrules.md) - AI開発ルール
- [wordpress-development-guide.md](./wordpress-development-guide.md) - WordPress開発ガイド

### WordPress 公式
- [Plugin Handbook](https://developer.wordpress.org/plugins/)
- [Coding Standards](https://developer.wordpress.org/coding-standards/)

### Git/GitHub
- [Git 公式ドキュメント](https://git-scm.com/doc)
- [GitHub Guides](https://guides.github.com/)

---

**このフローに沿って開発を進めれば、品質の高いプラグインを効率的にリリースできます！**
