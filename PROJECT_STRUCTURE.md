# ContentPilot プロジェクト構造定義

このファイルは ContentPilot プラグインの完全なファイル構造を定義します。
AI エージェント（Cursor等）は、この構造に従ってファイルを作成・編集してください。

---

## 📁 完全なディレクトリ構造

```
contentpilot/
│
├── AGENTS.md                                    # AI エージェントが最初に読むファイル（最重要）
├── README.md                                    # プラグイン説明（GitHub/WordPress.org用）
├── LICENSE                                      # ライセンスファイル（GPL v2 or later）
├── PROJECT_STRUCTURE.md                         # このファイル（プロジェクト構造定義）
│
├── contentpilot.php                             # メインプラグインファイル
├── uninstall.php                                # アンインストール処理
│
├── .gitignore                                   # Git除外設定
├── .cursorignore                                # Cursor除外設定
│
├── skills/                                      # WordPress公式 Agent Skills（読み取り専用）
│   └── wp-plugin-development/
│       ├── SKILL.md                             # メインスキル定義
│       ├── scripts/
│       │   └── detect_plugins.mjs               # プラグイン検出スクリプト
│       └── references/
│           ├── lifecycle.md                     # activation/deactivation/uninstall
│           ├── structure.md                     # プラグイン構造
│           ├── settings-api.md                  # Settings API
│           ├── data-and-cron.md                 # データストレージ/Cron
│           ├── debugging.md                     # デバッグ
│           └── security.md                      # セキュリティ
│
├── docs/                                        # ドキュメント（AI参照用）
│   ├── spec-plugin.md                           # プラグイン基本仕様
│   ├── spec-features.md                         # 機能詳細仕様
│   ├── spec-data-structure.md                   # データ構造定義（カスタムフィールド/オプション）
│   │
│   ├── ai-skills/                               # ContentPilot専用 Agent Skills
│   │   └── skills/
│   │       ├── contentpilot-core.md             # コア機能ルール
│   │       ├── coding-rules.md                  # コーディング規約
│   │       ├── design-rules.md                  # デザインルール
│   │       ├── phpunit.md                       # PHPUnitテストルール
│   │       ├── e2e-test.md                      # E2Eテストルール
│   │       └── security-extra.md                # 追加セキュリティルール
│   │
│   └── ui/                                      # UIドキュメント
│       ├── styleguide.html                      # スタイルガイド（HTML）
│       └── design-tokens.md                     # デザイントークン定義
│
├── guides/                                      # クイックリファレンス（軽量）
│   └── quick-reference.md                       # よく使うパターン集
│
├── includes/                                    # PHPクラス
│   ├── class-contentpilot.php                   # メインクラス（フロントエンド処理）
│   ├── class-contentpilot-admin.php             # 管理クラス（メタボックス/カスタマイザー）
│   ├── class-contentpilot-settings.php          # 設定ページクラス
│   └── class-contentpilot-detector.php          # 検出クラス（テーマ/TOCプラグイン検出）
│
├── assets/                                      # フロントエンドアセット
│   ├── css/
│   │   ├── frontend.css                         # フロントエンドCSS（開発用）
│   │   └── frontend.min.css                     # フロントエンドCSS（本番用・圧縮）
│   │
│   └── js/
│       ├── frontend.js                          # フロントエンドJS（開発用）
│       ├── frontend.min.js                      # フロントエンドJS（本番用・圧縮）
│       ├── admin-settings.js                    # 設定ページJS（開発用）
│       └── admin-settings.min.js               # 設定ページJS（本番用・圧縮）
│
├── tests/                                       # テスト
│   ├── phpunit/                                 # PHPUnit ユニットテスト
│   │   ├── bootstrap.php                        # テストブートストラップ
│   │   ├── test-class-contentpilot.php          # メインクラステスト
│   │   ├── test-class-contentpilot-admin.php    # 管理クラステスト
│   │   ├── test-class-contentpilot-settings.php # 設定クラステスト
│   │   └── test-class-contentpilot-detector.php # 検出クラステスト
│   │
│   └── e2e/                                     # E2Eテスト（Playwright等）
│       ├── playwright.config.js                 # Playwright設定
│       └── tests/
│           ├── basic-display.spec.js            # 基本表示テスト
│           ├── h2-selection.spec.js             # H2選択テスト
│           ├── customizer.spec.js               # カスタマイザーテスト
│           └── settings.spec.js                 # 設定ページテスト
│
├── languages/                                   # 翻訳ファイル
│   ├── contentpilot.pot                         # POTファイル（翻訳テンプレート）
│   ├── contentpilot-ja.po                       # 日本語翻訳（編集用）
│   └── contentpilot-ja.mo                       # 日本語翻訳（コンパイル済）
│
└── build/                                       # ビルド成果物（リリース用）
    └── contentpilot.zip                         # 配布用ZIP
```


---

## 📋 各ディレクトリの詳細

### ルートディレクトリ

| ファイル | 役割 | 編集頻度 |
|---------|------|---------|
| `AGENTS.md` | AIが最初に読むファイル。開発方針・参照順序を定義 | 低 |
| `README.md` | GitHub/WordPress.org用の説明 | 低 |
| `LICENSE` | ライセンス（GPL v2 or later） | なし |
| `PROJECT_STRUCTURE.md` | このファイル。プロジェクト構造定義 | 低 |
| `contentpilot.php` | プラグインヘッダー、クラス読込、フック登録 | 中 |
| `uninstall.php` | アンインストール時のクリーンアップ | 低 |
| `.gitignore` | Git除外設定 | 低 |
| `.cursorignore` | Cursor除外設定（トークン節約） | 低 |

---

### skills/（WordPress公式 Agent Skills）

**目的:** WordPress開発のベストプラクティスを提供

**特徴:**
- WordPress公式が提供
- 読み取り専用（編集禁止）
- @メンションで明示的に参照

**使用例:**
```
@skills/wp-plugin-development/SKILL.md
@skills/wp-plugin-development/references/security.md
```

---

### docs/（ドキュメント）

#### 仕様書

| ファイル | 内容 | 参照タイミング |
|---------|------|--------------|
| `spec-plugin.md` | プラグイン基本仕様（概要・データ構造） | 常時 |
| `spec-features.md` | 機能詳細仕様（各フェーズの機能定義） | 機能実装時 |
| `spec-data-structure.md` | データ構造定義（カスタムフィールド/オプション） | DB操作時 |

#### ai-skills/skills/（ContentPilot専用ルール）

| ファイル | 内容 | 参照タイミング |
|---------|------|--------------|
| `contentpilot-core.md` | コア機能のルール | 新機能実装時 |
| `coding-rules.md` | コーディング規約 | コード作成時 |
| `design-rules.md` | デザイン・CSS/JSルール | UI実装時 |
| `phpunit.md` | PHPUnitテストルール | テスト作成時 |
| `e2e-test.md` | E2Eテストルール | E2Eテスト作成時 |
| `security-extra.md` | 追加セキュリティルール | セキュリティレビュー時 |

#### ui/（UIドキュメント）

| ファイル | 内容 | 参照タイミング |
|---------|------|--------------|
| `styleguide.html` | スタイルガイド（HTML） | デザイン実装時 |
| `design-tokens.md` | デザイントークン定義 | CSS変数使用時 |

---

### guides/（クイックリファレンス）

| ファイル | 内容 | サイズ | 参照頻度 |
|---------|------|--------|---------|
| `quick-reference.md` | よく使うパターン集 | 5,000トークン以下 | 高 |

**含む内容:**
- セキュリティパターン
- コードスニペット
- よく使う WordPress 関数

---

### includes/（PHPクラス）

| ファイル | クラス名 | 役割 |
|---------|---------|------|
| `class-contentpilot.php` | ContentPilot | メインクラス（フロントエンド処理） |
| `class-contentpilot-admin.php` | ContentPilot_Admin | 管理画面（メタボックス/カスタマイザー） |
| `class-contentpilot-settings.php` | ContentPilot_Settings | 設定ページ |
| `class-contentpilot-detector.php` | ContentPilot_Detector | テーマ/TOCプラグイン検出 |

**設計原則:**
- 1クラス1ファイル
- シングルトンパターン
- `get_instance()` でインスタンス取得
- WordPress Coding Standards準拠

---

### assets/（フロントエンドアセット）

#### CSS

| ファイル | 用途 | 環境 |
|---------|------|------|
| `frontend.css` | フロントエンドCSS | 開発 |
| `frontend.min.css` | フロントエンドCSS（圧縮） | 本番 |

#### JavaScript

| ファイル | 用途 | 環境 |
|---------|------|------|
| `frontend.js` | フロントエンドJS | 開発 |
| `frontend.min.js` | フロントエンドJS（圧縮） | 本番 |
| `admin-settings.js` | 設定ページJS | 開発 |
| `admin-settings.min.js` | 設定ページJS（圧縮） | 本番 |

**ルール:**
- 開発版と圧縮版を両方保持
- 本番では .min.css/.min.js を使用
- 圧縮はビルドプロセスで自動生成

**補足:** 現在は `admin-metabox.css` / `admin-metabox.js` も存在し、投稿編集メタボックスで使用中。

---

### tests/（テスト）

#### phpunit/（PHPUnit ユニットテスト）

| ファイル | テスト対象 |
|---------|-----------|
| `bootstrap.php` | テストブートストラップ |
| `test-class-contentpilot.php` | `ContentPilot` クラス |
| `test-class-contentpilot-admin.php` | `ContentPilot_Admin` クラス |
| `test-class-contentpilot-settings.php` | `ContentPilot_Settings` クラス |
| `test-class-contentpilot-detector.php` | `ContentPilot_Detector` クラス |

**テストパターン:**
- Given-When-Then
- 日本語コメント必須
- クラス・メソッド単位

#### e2e/（E2Eテスト）

| ファイル | テスト内容 |
|---------|-----------|
| `playwright.config.js` | Playwright設定 |
| `basic-display.spec.js` | 基本表示テスト |
| `h2-selection.spec.js` | H2選択テスト |
| `customizer.spec.js` | カスタマイザーテスト |
| `settings.spec.js` | 設定ページテスト |

**テストツール:** Playwright（推奨）または Puppeteer

---

### languages/（翻訳）

| ファイル | 形式 | 用途 |
|---------|------|------|
| `contentpilot.pot` | POT | 翻訳テンプレート |
| `contentpilot-ja.po` | PO | 日本語翻訳（編集用） |
| `contentpilot-ja.mo` | MO | 日本語翻訳（コンパイル済） |

---

### build/（ビルド成果物）

| ファイル | 内容 |
|---------|------|
| `contentpilot.zip` | 配布用ZIP（WordPress.org/GitHub Releases用） |

**含むファイル:**
- 圧縮済みCSS/JS
- コンパイル済み翻訳ファイル
- 必要最小限のファイルのみ

**含まないファイル:**
- tests/
- docs/
- node_modules/
- .git/

---

## 🎯 AI エージェントへの指示

### ファイル作成時のルール

1. **必ず PROJECT_STRUCTURE.md を確認**
2. **適切なディレクトリに配置**
3. **命名規則に従う**
4. **既存の構造を壊さない**

### 新規ファイル追加時

1. このファイル（PROJECT_STRUCTURE.md）に追記
2. AGENTS.md に参照方法を追記（必要に応じて）
3. Git コミット

### ファイル削除時

1. このファイル（PROJECT_STRUCTURE.md）から削除
2. AGENTS.md から参照を削除（必要に応じて）
3. Git コミット

---

## 📝 命名規則

### PHP

```
クラス: ContentPilot_ClassName
関数: contentpilot_function_name()
定数: CONTENTPILOT_CONSTANT_NAME
```

### JavaScript

```
変数: contentPilotVariableName（キャメルケース）
グローバル: contentpilotData
関数: contentPilotFunctionName()
```

### CSS

```
クラス: .contentpilot-class-name（BEM方式）
Block: .contentpilot-nav
Element: .contentpilot-nav__item
Modifier: .contentpilot-nav__item--active
```

### ファイル

```
PHPクラス: class-contentpilot-classname.php
PHPテスト: test-class-contentpilot-classname.php
JavaScript: contentpilot-filename.js
CSS: contentpilot-filename.css
```

---

## 🔒 編集禁止ファイル

以下のファイル・ディレクトリは **編集禁止**：

```
skills/                    # WordPress公式（読み取り専用）
build/                     # ビルド成果物（自動生成）
*.min.css                  # 圧縮CSS（自動生成）
*.min.js                   # 圧縮JS（自動生成）
languages/*.mo             # コンパイル済翻訳（自動生成）
```

---

## ✅ この構造のメリット

1. **明確な役割分担** - どこに何があるか一目瞭然
2. **スケーラビリティ** - 機能追加時も迷わない
3. **保守性** - テスト・ドキュメント完備
4. **AI 開発効率** - AI が迷わず実装できる
5. **トークン節約** - 必要なファイルのみ参照

---

## 📌 参照順序（AI向け）

### Level 0: 最初に必ず読む
```
@AGENTS.md
@PROJECT_STRUCTURE.md
```

### Level 1: 基本実装
```
@docs/spec-plugin.md
@guides/quick-reference.md
@skills/wp-plugin-development/SKILL.md
```

### Level 2: 詳細実装
```
@docs/spec-features.md
@docs/ai-skills/skills/coding-rules.md
@docs/ai-skills/skills/design-rules.md
```

### Level 3: 専門実装
```
@skills/wp-plugin-development/references/security.md
@docs/ai-skills/skills/security-extra.md
@docs/ai-skills/skills/phpunit.md
```

---

**このファイルは ContentPilot プロジェクトの設計図です。**
**すべてのファイル作成・編集はこの構造に従ってください。**
