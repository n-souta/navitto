# Navitto プラグイン仕様書

## プロジェクト概要

### プラグイン名
**Navitto**

### バージョン
1.0.0

### キャッチコピー
「設定ゼロで始める固定ナビゲーション」／「ナビっと表示、サクッと移動。」

### ターゲットユーザー
- 長文記事（5000文字以上）を書くブロガー
- ランキング記事やレビュー記事を多く書くアフィリエイター
- SEO対策で文字数の多い記事を作成するWebライター
- 離脱率を下げたいサイト運営者

### 解決する課題
1. 長文記事での読者の離脱
2. 目次に戻る操作の煩雑さ
3. 記事内の目的のセクションへのアクセスの悪さ
4. スマホでの長文記事の読みにくさ

---

## 競合分析と差別化

### 主な競合
- Unify Navigation（https://blogus.jp/unify-navigation/）

### 競合の特徴
1. 複数パターンのナビゲーション（画像/アイコン/テキスト）
2. １ページに複数のナビゲーション配置可能
3. 項目が増えても横スクロール
4. 豊富なデザインと拡張性
5. ブロックエディター・クラシックエディタ両対応

### Navittoの差別化ポイント
| 項目 | Unify Navigation | Navitto |
|------|------------------|--------------|
| **設定の手間** | 手動で項目を設定 | 目次から自動連携 |
| **対象記事** | 全記事 | 長文記事に特化 |
| **ターゲット** | デザイン重視 | UX・離脱防止重視 |
| **コンセプト** | 多機能・高機能 | Navitto：シンプル・実用性 |

---

## コア機能

### 1. 目次自動連携機能
**概要:**  
既存の目次（テーマ内蔵/プラグイン）から見出し構造を自動検出し、固定ナビゲーションを生成する

**検出優先順位:**
1. テーマ内蔵の目次（SWELL, JIN, SANGO, AFFINGER, Cocoon, THE THOR等）
2. 目次プラグイン（Table of Contents Plus, Easy Table of Contents, Rich Table of Contents, LuckyWP Table of Contents等）
3. H2タグから自動生成（上記がない場合）

**対応する見出しレベル:**
- H2のみ（H3以下は対象外）
- 理由: 固定ナビはシンプルに保ち、主要セクションへのアクセスに特化

**技術仕様:**
```
1. DOMContentLoaded後に記事内を解析
2. 既存の目次HTMLを検出（クラス名/ID等で判定）
3. 目次が見つからない場合、H2タグを収集
4. 各見出しにユニークなIDを付与（既存IDがあれば利用）
5. 固定ナビのHTMLを動的生成
6. スムーススクロールでアンカーリンクを実装
```

---

### 2. H2見出しの選択表示機能
**概要:**  
記事内の全H2見出しをユーザーが選択して固定ナビに表示できる

**デフォルト動作:**
- 記事内の全H2を自動検出
- ユーザーが選択しない場合、全H2を表示（横スクロール対応）

**選択UI:**
- 投稿編集画面のサイドバーに「Navitto」メタボックスを配置
- チェックボックスで表示するH2を選択
- 各見出しにカスタムラベル入力・**アイコン設定**（アイコンピッカー）を用意（「表示する見出しを選択」時のみ）
- 選択された見出しのみ固定ナビに表示

**横スクロール:**
- 表示する見出しが画面幅を超える場合、自動で横スクロール可能にする
- スクロールバーは非表示（スワイプ/ドラッグで操作）
- 左右にグラデーションを表示して「続きがある」ことを示唆

**技術仕様:**
```css
.navitto-nav {
  display: flex;
  overflow-x: auto;
  scrollbar-width: none; /* Firefox */
  -ms-overflow-style: none; /* IE/Edge */
}
.navitto-nav::-webkit-scrollbar {
  display: none; /* Chrome/Safari */
}
```

---

### 3. デザインプリセット機能
**概要:**  
主要WordPressテーマに馴染むデザインを複数用意し、簡単に切り替え可能

**プリセット一覧:**
1. **シンプル** - 白背景、黒テキスト、ミニマル
2. **テーマ準拠** - SWELL/JIN等のテーマカラーを自動検出
（追加プリセットは追加コンテンツで提供する場合あり）

**カスタマイズ可能な項目:**
- 配置位置（上部固定 or 下部固定）
- ナビの高さ（小・中・大）
- 最小文字数・スクロール表示開始位置
- テーマ固定ヘッダーセレクタ（PC/SP）
- box-shadow はテーマの `--navitto-nav-shadow` に従う（カスタマイザーでのオン/オフはなし）

**設定UI:**
- WordPress カスタマイザーで設定
- リアルタイムプレビュー対応

---

## 技術仕様

### 対応環境
- **WordPress:** 6.0以上
- **PHP:** 7.4以上
- **ブロックエディタ:** 完全対応
- **クラシックエディタ:** 非対応（ブロックエディタのみ）

### 対応テーマ
- SWELL
- JIN / JIN:R
- SANGO
- AFFINGER
- Cocoon
- THE THOR
- その他の標準的なテーマ（目次プラグイン使用前提）

### ファイル構成
```
navitto/
├── navitto.php                  # メインファイル
├── uninstall.php                # アンインストール処理
├── package.json                 # npm（Font Awesome ビルド用）
├── scripts/
│   └── build-fontawesome-nv.mjs # Font Awesome を nv- プレフィックスでビルド
├── assets/
│   ├── lib/
│   │   └── fontawesome/         # 同梱 Font Awesome（all-nv.min.css, webfonts/）
│   ├── css/
│   │   ├── frontend.css        # フロントエンド用CSS
│   │   └── admin-metabox.css   # メタボックス用CSS
│   └── js/
│       ├── frontend.js         # フロントエンド用JS
│       ├── admin-settings.js   # 設定ページ用JS
│       ├── admin-metabox.js    # メタボックス・アイコンピッカー用JS
│       └── navitto-icons.js    # アイコンレジストリ（Font Awesome クラス名）
├── includes/
│   ├── class-navitto.php        # メインクラス
│   ├── class-navitto-admin.php  # 管理画面・メタボックス・カスタマイザー
│   ├── class-navitto-settings.php # 設定ページ
│   └── class-navitto-detector.php # 目次検出クラス
└── languages/
    └── navitto-ja.po            # 日本語翻訳（任意）
```

### データベース設計
**カスタムフィールド（投稿メタデータ）:**
```php
// 各投稿に保存するメタデータ
_navitto_display_mode     // 表示モード（show_all / select / hide）
_navitto_enabled          // 後方互換用（0/1）
_navitto_selected_h2      // 選択されたH2のインデックス配列
_navitto_h2_custom_texts  // H2のカスタムラベル
_navitto_h2_icons         // H2ごとのアイコン名（インデックス => アイコン名）
_navitto_trigger_type     // 表示開始（immediate / first_selected / nth_selected / scroll_px）
_navitto_trigger_nth, _navitto_trigger_scroll_px
_navitto_nav_width        // scroll / equal
_navitto_custom_items     // カスタム項目（外部リンク等）配列。各要素: label, url, newtab, icon（任意）
```

**オプション・テーマ mod（サイト全体の設定）:**
```php
navitto_default_enabled   // 新規投稿のデフォルト有効化
navitto_db_version        // DBバージョン（アップグレード用）
// カスタマイザー（theme_mod）
navitto_preset            // シンプル / theme
navitto_position          // top / bottom
navitto_nav_height        // small / medium / large
navitto_min_word_count    // 最小文字数（デフォルト3000）
navitto_show_after_scroll
navitto_fixed_header_selector_pc / _sp
```

---

## 機能詳細

### アイコン機能（v1.0.0 以降の機能ブランチ）
- **引用元:** Font Awesome（https://fontawesome.com/）を npm パッケージ `@fortawesome/fontawesome-free` で同梱
- **クラス名:** テーマの `fa-` と競合しないよう `nvfa` / `nvfas` / `nvfar` / `nvfab` + `nvfa-xxx` に変換（`scripts/build-fontawesome-nv.mjs` でビルド）
- **対象:** 表示する見出しを選択した各H2、およびカスタム項目（各項目にアイコン設定可能）
- **管理UI:** アイコンピッカー（モーダル）で選択。選択時は「アイコンを削除」ボタン表示で削除可能

---

### 自動検出ロジック

#### 1. テーマ内蔵目次の検出
```javascript
// SWELL
document.querySelector('.wp-block-swl-toc')

// JIN
document.querySelector('.jin-toc')

// SANGO
document.querySelector('.sng-toc')

// AFFINGER
document.querySelector('.st-toc')

// Cocoon
document.querySelector('.toc')

// THE THOR
document.querySelector('.ep-toc')
```

#### 2. プラグイン目次の検出
```javascript
// Table of Contents Plus
document.querySelector('#toc_container')

// Easy Table of Contents
document.querySelector('.ez-toc-container')

// Rich Table of Contents
document.querySelector('.rtoc-mokuji')

// LuckyWP Table of Contents
document.querySelector('.lwptoc')
```

#### 3. H2タグからの自動生成
```javascript
// 上記が見つからない場合
const h2Elements = document.querySelectorAll('.entry-content h2');
```

---

### 固定ナビゲーションの表示ロジック

**表示条件:**
1. 記事の文字数が設定値以上（デフォルト3000文字）
2. H2見出しが2個以上存在
3. 投稿メタデータで有効化されている（デフォルトで有効）

**非表示にする場合:**
- 固定ページ
- アーカイブページ
- 検索結果ページ
- 404ページ

**スクロール挙動:**
- ページ読み込み時は非表示
- 100px以上スクロールしたら表示（フェードイン）
- ページトップに戻ったら非表示（フェードアウト）

※ 機能ブランチ `feature/hide-nav-after-last-h2` では、最後の指定H2を過ぎたあとナビを非表示にするオプションを実装。

---

### スムーススクロール実装

```javascript
// アンカーリンククリック時
document.querySelectorAll('.navitto-nav a').forEach(link => {
  link.addEventListener('click', (e) => {
    e.preventDefault();
    const targetId = link.getAttribute('href').slice(1);
    const targetElement = document.getElementById(targetId);
    
    if (targetElement) {
      // 固定ヘッダーの高さを考慮
      const headerOffset = 80;
      const elementPosition = targetElement.getBoundingClientRect().top;
      const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
      
      window.scrollTo({
        top: offsetPosition,
        behavior: 'smooth'
      });
    }
  });
});
```

---

## UI/UXデザイン

### 固定ナビゲーションのデザイン

**配置:**
- デフォルト: 画面上部に固定
- オプション: 画面下部に固定

**サイズ:**
- 高さ: 60px（デスクトップ）/ 50px（モバイル）
- 幅: 画面幅の100%
- z-index: 9999（他の要素より前面）

**アニメーション:**
- 表示/非表示: 300msのフェード
- ホバー時: 色変化（200ms）
- スクロール時: スムーズ（慣性付き）

**レスポンシブ:**
- デスクトップ: 全項目を横並び表示
- タブレット: 横スクロール
- スマホ: 横スクロール、フォントサイズ縮小

---

### 管理画面のUI

#### 1. 投稿編集画面のサイドバー
```
┌─────────────────────────┐
│ Navitto                 │
├─────────────────────────┤
│ ○ 固定ナビを表示（H2そのまま） │
│ ○ 表示する見出しを選択       │
│ ○ 固定ナビを非表示          │
├─────────────────────────┤
│ 表示する見出しを選択時:      │
│ [アイコン] [ラベル入力]      │
│ [アイコンを追加] または      │
│ [アイコンを削除]（赤）       │
│ 表示開始位置・固定ナビの表示方法 │
├─────────────────────────┤
│ カスタム項目を追加（任意）    │
│ 各項目: アイコン・ラベル・URL  │
│ ＋ 項目を追加               │
└─────────────────────────┘
```

※ ブランチ `feature/hide-nav-after-last-h2` では投稿画面から「カスタム項目を追加」を削除した構成あり。

#### 2. カスタマイザー設定
```
外観 > カスタマイズ > Navitto - デザイン / Navitto - 共通設定

- デフォルトプリセット
- 配置位置（上部/下部）
- 背景色
- テキスト色
- アクティブ色
- 最小文字数
- スクロール表示開始位置
```

---

## ビジネス仕様

### 価格設定
- **買い切り価格:** 2,000円（税込）
- **ライセンス:** 購入者のサイトで無制限使用可能
- **アップデート:** 購入後1年間無料

### 販売方法
1. 専用公式サイトでの直販
2. Gumroad等のプラットフォーム利用も検討

### サポート体制
- メールサポート（購入後1年間）
- 公式サイトにFAQページ設置
- 使い方マニュアル（PDF）提供

### マーケティング戦略
**SEOキーワード:**
- 固定ヘッダー カスタマイズ
- WordPress 固定ナビゲーション
- 長文記事 離脱防止
- ブログ クリック率 向上
- ランキング記事 プラグイン

**参考サイト:**
- Pochipp（https://pochipp.com/）
- Useful Blocks（https://ponhiro.com/useful-blocks/）

**コンテンツマーケティング:**
- 導入事例の紹介
- クリック率改善の実績データ公開
- 長文記事の書き方とセットで情報発信

---

## WordPress公式ガイドライン準拠

### 準拠すべき項目
以下のWordPress公式ドキュメントに従って開発:
https://ja.wordpress.org/team/handbook/plugin-development

**必須要件:**
1. GPLライセンス準拠
2. セキュリティベストプラクティス
   - nonce検証
   - データのサニタイズ/エスケープ
   - prepared statementsの使用
3. コーディング規約準拠
   - WordPress Coding Standards
   - PHPDocブロックの記述
4. 翻訳対応（i18n）
5. アクセシビリティ対応（WCAG 2.0 AA）

### セキュリティ対策
```php
// nonce検証の例
if (!wp_verify_nonce($_POST['navitto_meta_nonce'], 'navitto_save_meta')) {
    wp_die('Security check failed');
}

// データのサニタイズ
$mode = sanitize_text_field($_POST['navitto_display_mode']);

// データのエスケープ
echo esc_html($heading_text);
```

---

## 開発フェーズ

### フェーズ1: MVP開発（最小機能版）
**期間:** 2-3週間

**実装機能:**
- H2タグからの自動検出
- 基本的な固定ナビゲーション表示
- シンプルデザインプリセット1種類
- 投稿編集画面での有効/無効切り替え

**成果物:**
- 動作するプラグイン（v0.1.0）
- 基本的なドキュメント

---

### フェーズ2: 目次連携機能追加
**期間:** 1-2週間

**実装機能:**
- テーマ内蔵目次の検出（SWELL, JIN, SANGO）
- 目次プラグインの検出（TOC+, Easy TOC）
- 検出優先順位のロジック実装

**成果物:**
- プラグイン v0.5.0
- 目次連携のテストケース

---

### フェーズ3: デザイン・カスタマイズ機能
**期間:** 1-2週間

**実装機能:**
- デザインプリセット2種類（シンプル・テーマ準拠）
- カスタマイザー統合
- H2選択UI
- 横スクロール機能

**成果物:**
- プラグイン v1.0.0
- 完全なドキュメント

---

### フェーズ4: 公式サイト・マーケティング
**期間:** 2-3週間

**実装内容:**
- 公式サイト制作
- SEO対策
- デモサイト構築
- マニュアル・FAQ作成
- プロモーション動画作成

**成果物:**
- 公式サイト公開
- 販売開始

---

## cursorrules.md用の開発ルール

以下の内容を`cursorrules.md`に記載してAIエージェントに渡す:

```markdown
# Navitto プラグイン開発ルール

## 必須準拠事項
- WordPress Plugin Handbook に完全準拠: https://ja.wordpress.org/team/handbook/plugin-development
- WordPress Coding Standards に準拠
- セキュリティベストプラクティスの遵守

## コーディング規約
- PHPコーディング規約: WordPress PHP Coding Standards
- JavaScriptコーディング規約: WordPress JavaScript Coding Standards
- 全ての関数・クラスにPHPDocブロックを記述
- 変数名: snake_case（PHP）、camelCase（JavaScript）
- プレフィックス: `navitto_` を全てのグローバル関数・変数に付与

## セキュリティ
- 全てのフォーム送信にnonce検証を実装
- ユーザー入力は全てサニタイズ
- 出力は全てエスケープ（esc_html, esc_attr, esc_url等）
- SQLクエリは prepared statements を使用
- 権限チェック（current_user_can）を必ず実装

## ファイル構成
- メインファイル: navitto.php
- クラスファイルは includes/ ディレクトリに配置
- アセットファイルは assets/ ディレクトリに配置
- 言語ファイルは languages/ ディレクトリに配置

## 命名規則
- プラグインプレフィックス: `navitto_`
- フック名: `navitto_{action/filter}_name`
- クラス名: `Navitto_ClassName`
- 関数名: `navitto_function_name`
- CSS/JSハンドル名: `navitto-{name}`

## WordPress関数の使用
- 直接SQLクエリを書かない（wpdbを使用）
- wp_enqueue_style / wp_enqueue_script でアセット読み込み
- add_action / add_filter でフック追加
- register_post_meta でカスタムフィールド登録
- add_option / get_option / update_option で設定管理

## 翻訳対応
- 全ての表示テキストは翻訳関数を使用（__, _e, esc_html__等）
- テキストドメイン: 'navitto'
- load_plugin_textdomain( 'navitto', ... ) でテキストドメイン読み込み

## テスト
- 主要WordPressテーマでの動作確認必須
  - SWELL, JIN, SANGO, AFFINGER, Cocoon
- 主要目次プラグインでの動作確認必須
  - Table of Contents Plus, Easy Table of Contents
- ブラウザテスト: Chrome, Firefox, Safari, Edge
- レスポンシブテスト: デスクトップ、タブレット、スマホ

## パフォーマンス
- 必要なページでのみスクリプト/スタイル読み込み
- インラインスタイル/スクリプトは最小限に
- 画像は最適化済みのものを使用
- キャッシュ可能な部分は wp_cache_* 関数を使用

## Git管理
- ブランチ戦略: main, develop, feature/xxx
- コミットメッセージは日本語または英語で明確に
- 機能ごとに小さくコミット

## 禁止事項
- eval()の使用禁止
- base64エンコードされたコードの使用禁止
- 外部サーバーへの無断通信禁止
- WordPress Core, テーマ, 他プラグインのファイルの直接編集禁止
- グローバルスコープの汚染を最小限に
```

---

## 付録

### 想定FAQ

**Q: 目次プラグインを使っていない場合も動作しますか？**  
A: はい。H2タグから自動的にナビゲーションを生成します。

**Q: テーマを変更した場合、設定はどうなりますか？**  
A: デザインプリセットは自動的にテーマに合わせて調整されます。個別設定は維持されます。

**Q: ショートコードで任意の場所に表示できますか？**  
A: 現在は固定位置のみですが、今後のアップデートで対応予定です。

**Q: クラシックエディタには対応していますか？**  
A: 申し訳ございませんが、ブロックエディタのみの対応となります。

**Q: ページの読み込み速度に影響はありますか？**  
A: 最小限のJavaScript/CSSのみを読み込むため、ほとんど影響ありません。

---

### 参考リンク

**WordPress公式:**
- Plugin Handbook: https://ja.wordpress.org/team/handbook/plugin-development
- Coding Standards: https://developer.wordpress.org/coding-standards/

**競合プラグイン:**
- Unify Navigation: https://blogus.jp/unify-navigation/

**マーケティング参考:**
- Pochipp: https://pochipp.com/
- Useful Blocks: https://ponhiro.com/useful-blocks/

**目次プラグイン:**
- Table of Contents Plus: https://wordpress.org/plugins/table-of-contents-plus/
- Easy Table of Contents: https://wordpress.org/plugins/easy-table-of-contents/

**アイコン（Font Awesome）:**
- Font Awesome: https://fontawesome.com/
- Font Awesome Free License: https://fontawesome.com/license/free

---

## 改訂履歴

- v1.0.0 (2025-02-05): 初版作成
- v1.0.0 (2026-02-15): Navitto に名称変更、プリセット2種・カスタマイザー整理・データ構造を現行仕様に合わせて更新
- v1.0.0 (2026-02-16): ファイル構成に package.json / scripts / navitto-icons.js / fontawesome を追加。DB に _navitto_h2_icons を追加、_navitto_custom_items に icon を追記。アイコン機能・管理UI・改訂履歴を更新。
```
- v1.0.0 (2026-02-16): 今後の流れを整理。feature/icon-picker と feature/hide-nav-after-last-h2 のマージ方針確定・実施を次のタスクとして spec-features に明記。マージ前にカスタム項目の扱い（残す／削除）を決定する旨を整理。
