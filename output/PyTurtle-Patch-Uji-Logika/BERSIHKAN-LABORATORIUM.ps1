$ErrorActionPreference = 'Stop'
$projectRoot = [IO.Path]::GetFullPath($PSScriptRoot)
if (!(Test-Path -LiteralPath (Join-Path $projectRoot 'artisan'))) { throw 'Salin script ini ke folder proyek yang berisi artisan terlebih dahulu.' }
$obsoleteFiles = @('resources/views/components/maze-learning.blade.php','resources/js/game/turtle/learning.js','tests/game/learning.test.mjs','tests/browser/learning.spec.js','docs/LOGIC-LAB.md')
foreach ($relative in $obsoleteFiles) {
 $target = [IO.Path]::GetFullPath((Join-Path $projectRoot $relative))
 if (!$target.StartsWith($projectRoot + [IO.Path]::DirectorySeparatorChar, [StringComparison]::OrdinalIgnoreCase)) { throw 'Path di luar proyek ditolak.' }
 if (Test-Path -LiteralPath $target -PathType Leaf) { Remove-Item -LiteralPath $target }
}
Write-Output 'File Laboratorium Logika lama sudah dibersihkan. Data akun dan database tidak diubah.'
