# WordPress.org SVN 反映（Windows / PowerShell）
#
# 使い方（navitto-github フォルダで）:
#   .\scripts\svn-wordpress-org.ps1 -Mode readme
#   .\scripts\svn-wordpress-org.ps1 -Mode release
#
# 環境変数（任意）:
#   $env:WPORG_SVN_DIR = "C:\path\to\navitto.svn-wporg"
#
# 認証: https://profiles.wordpress.org/nsouta/profile/edit/group/3/?screen=svn-password

[CmdletBinding()]
param(
	[ValidateSet('readme', 'release')]
	[string]$Mode = 'readme'
)

$ErrorActionPreference = 'Stop'

$Root = Split-Path -Parent $PSScriptRoot
$Slug = 'navitto'
$SvnUrl = "https://plugins.svn.wordpress.org/$Slug/"
$SvnDir = if ($env:WPORG_SVN_DIR) { $env:WPORG_SVN_DIR } else { Join-Path (Split-Path -Parent $Root) "$Slug.svn-wporg" }
$Readme = Join-Path $Root 'readme.txt'

if (-not (Get-Command svn -ErrorAction SilentlyContinue)) {
	throw 'svn が見つかりません。Slik Subversion をインストールしてください。'
}
if (-not (Test-Path $Readme)) {
	throw "readme.txt がありません: $Readme"
}

function Ensure-SvnCheckout {
	if (-not (Test-Path (Join-Path $SvnDir '.svn'))) {
		Write-Host "SVN checkout: $SvnUrl -> $SvnDir"
		New-Item -ItemType Directory -Force -Path $SvnDir | Out-Null
		svn checkout --depth immediates $SvnUrl $SvnDir
		Push-Location $SvnDir
		svn update --set-depth infinity trunk
		svn update --set-depth infinity tags
		Pop-Location
	} else {
		Push-Location $SvnDir
		svn update
		Pop-Location
	}
}

function Get-StableTag {
	$line = Select-String -Path $Readme -Pattern '^Stable tag:' | Select-Object -First 1
	if (-not $line) { return '' }
	return ($line.Line -replace '^Stable tag:\s*', '').Trim()
}

function Copy-PluginToTrunk {
	$exclude = @('.git', '.github', 'docs', 'scripts', 'box', 'node_modules', 'package.json', 'README.md', 'wordpress-org-assets.txt', 'glotpress-stable-readme-ja.txt', 'glotpress-stable-ui-ja.txt')
	$trunk = Join-Path $SvnDir 'trunk'
	Get-ChildItem -Path $trunk -Force | Where-Object { $_.Name -ne '.svn' } | Remove-Item -Recurse -Force
	robocopy $Root $trunk /E /XD $exclude /XF README.md package.json wordpress-org-assets.txt glotpress-stable-readme-ja.txt glotpress-stable-ui-ja.txt /NFL /NDL /NJH /NJS | Out-Null
	if ($LASTEXITCODE -ge 8) {
		throw "robocopy failed with exit code $LASTEXITCODE"
	}
}

Ensure-SvnCheckout

$stableTag = Get-StableTag
if (-not $stableTag) {
	throw 'readme.txt から Stable tag を読み取れません。'
}

Write-Host "Stable tag: $stableTag"
Write-Host "Mode: $Mode"

if ($Mode -eq 'release') {
	Copy-PluginToTrunk
} else {
	Copy-Item -Force $Readme (Join-Path $SvnDir 'trunk\readme.txt')
}

$tagDir = Join-Path $SvnDir "tags\$stableTag"
if ($Mode -eq 'release') {
	if (Test-Path $tagDir) {
		throw "tags/$stableTag は既に存在します。新バージョンの場合は readme の Stable tag を上げてください。"
	}
	svn copy (Join-Path $SvnDir 'trunk') $tagDir -m "Adding tag $stableTag for $Slug."
	Write-Host "Created tags/$stableTag from trunk."
} else {
	if (-not (Test-Path $tagDir)) {
		throw "tags/$stableTag がありません。先に -Mode release で公開してください。"
	}
	Copy-Item -Force $Readme (Join-Path $tagDir 'readme.txt')
}

Push-Location $SvnDir
svn add . --force 2>$null | Out-Null
svn status
Write-Host ''
Write-Host '上記を確認し、問題なければ次を実行:'
Write-Host "  cd `"$SvnDir`""
Write-Host '  svn commit -m "Fix readme tags line break."'
Pop-Location
