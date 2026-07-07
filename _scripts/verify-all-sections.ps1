$sections = @(
  "background-image-text.liquid", "background-video-text.liquid", "blog-posts.liquid",
  "hero-video.liquid", "map.liquid", "product-recommendations.liquid",
  "recently-viewed.liquid", "logo-list.liquid", "featured-collection.liquid",
  "footer-promotions.liquid", "text-and-image.liquid", "text-columns.liquid",
  "testimonials.liquid"
)
$base = "C:\Users\hamma\Downloads\phantom-theme\phantom-theme-v2.2.0\sections"
$allOk = $true
foreach ($s in $sections) {
  $path = Join-Path $base $s
  $content = Get-Content $path -Raw
  $hasSetting = $content -match 'entrance_animation'
  $hasConditional = $content -match "section.settings.entrance_animation" -or $content -match "block.settings.entrance_animation"
  $schemaOk = if ($content -match '(?s){% schema %}(.*?){% endschema %}') {
    $schema = $matches[1]; try { $null = $schema | ConvertFrom-Json; $true } catch { $false }
  } else { $null }
  $status = if ($hasSetting -and $hasConditional -and $schemaOk) { "PASS" } else { "FAIL" }
  if ($status -eq "FAIL") { $allOk = $false }
  Write-Output "$status | $s | setting=$hasSetting conditional=$hasConditional schema=$schemaOk"
}
Write-Output ""
if ($allOk) { Write-Output "ALL 13 SECTIONS PASSED" } else { Write-Output "SOME SECTIONS FAILED" }