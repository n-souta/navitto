#!/usr/bin/env node
/**
 * Font Awesome を nv- プレフィックスに変換して assets/lib/fontawesome/ に出力する
 *
 * 引用元:
 *   - Font Awesome (https://fontawesome.com/)
 *   - npm: @fortawesome/fontawesome-free (https://www.npmjs.com/package/@fortawesome/fontawesome-free)
 * ライセンス: Font Awesome Free License (https://fontawesome.com/license/free)
 *
 * 使い方:
 *   npm install @fortawesome/fontawesome-free
 *   node scripts/build-fontawesome-nv.mjs
 */

import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const rootDir = path.resolve(__dirname, '..');
const nodeModules = path.join(rootDir, 'node_modules');
const faCssPath = path.join(nodeModules, '@fortawesome/fontawesome-free', 'css', 'all.min.css');
const faWebfontsDir = path.join(nodeModules, '@fortawesome/fontawesome-free', 'webfonts');
const outDir = path.join(rootDir, 'assets', 'lib', 'fontawesome');
const outWebfontsDir = path.join(outDir, 'webfonts');

// node_modules が無い場合は説明して終了
if (!fs.existsSync(faCssPath)) {
	console.error('@fortawesome/fontawesome-free がインストールされていません。');
	console.error('プラグインルートで実行: npm install @fortawesome/fontawesome-free');
	process.exit(1);
}

if (!fs.existsSync(outDir)) {
	fs.mkdirSync(outDir, { recursive: true });
}
if (!fs.existsSync(outWebfontsDir)) {
	fs.mkdirSync(outWebfontsDir, { recursive: true });
}

let css = fs.readFileSync(faCssPath, 'utf8');

// クラス名を nv- プレフィックスに変更（テーマの fa- と競合しない）
// 順序に注意: 長いパターンから置換
css = css.replace(/\.fa-solid\b/g, '.nvfas');
css = css.replace(/\.fa-regular\b/g, '.nvfar');
css = css.replace(/\.fa-brands\b/g, '.nvfab');
css = css.replace(/\.fas(?![a-zA-Z0-9-])/g, '.nvfas');
css = css.replace(/\.far(?![a-zA-Z0-9-])/g, '.nvfar');
css = css.replace(/\.fab(?![a-zA-Z0-9-])/g, '.nvfab');
css = css.replace(/\.fa-light\b/g, '.nvfal');
css = css.replace(/\.fa-duotone\b/g, '.nvfad');
css = css.replace(/\.fa-thin\b/g, '.nvfat');
css = css.replace(/\.fa-([a-zA-Z0-9-]+)/g, '.nvfa-$1');  // .fa-xxx → .nvfa-xxx
// .fa 単体（.fa の直後が - でない場合）を .nvfa に
css = css.replace(/\.fa(?![a-zA-Z0-9-])/g, '.nvfa');

// @font-face 内の webfonts パス（CSS と同じ fontawesome 直下の webfonts/ を参照）
const fontFileMap = {};
css = css.replace(/url\([^)]*webfonts\/([^)]+)\)/g, (_, file) => {
	const newName = file.replace(/^fa-/, 'nv-');
	fontFileMap[file] = newName;
	return 'url(webfonts/' + newName + ')';
});

// webfonts をコピー＆リネーム
const webfontFiles = fs.readdirSync(faWebfontsDir);
for (const f of webfontFiles) {
	const src = path.join(faWebfontsDir, f);
	const newName = f.startsWith('fa-') ? 'nv-' + f.slice(3) : 'nv-' + f;
	const dest = path.join(outWebfontsDir, newName);
	fs.copyFileSync(src, dest);
}

fs.writeFileSync(path.join(outDir, 'all-nv.min.css'), css, 'utf8');
console.log('Generated: assets/lib/fontawesome/all-nv.min.css');
console.log('Webfonts:  assets/lib/fontawesome/webfonts/');
console.log('Font Awesome を nvfa/nvfas/nvfar/nvfab + nvfa-xxx に変換しました。');
