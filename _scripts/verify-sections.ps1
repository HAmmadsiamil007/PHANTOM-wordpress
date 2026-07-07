$sections = @(
  "background-image-text.liquid","background-video-text.liquid","blog-posts.liquid",
  "hero-video.liquid","map.liquid","product-recommendations.liquid",
  "recently-viewed.liquid","logo-list.liquid"
)
$base = "C:\Users\hamma\Downloads\phantom-theme\phantom-theme-v2.2.0\sections"
foreach ($s in $sections) {
  $path = Join-Path $base $s
  $content = Get-Content $path -Raw
  $hasSetting = $content -match 'entrance_animation'
  $hasConditional = $content -match "section.settings.entrance_animation"
  $schemaOk = if ($content -match '(?s){% schema %}(.*?){% endschema %}') {
    $schema = $matches[1]; try { $null = $schema | ConvertFrom-Json; $true } catch { $false }
  } else { $null }
  $status = if ($hasSetting -and $hasConditional -and $schemaOk) { "OK" } else { "ISSUE" }
  Write-Output "$status | setting=$hasSetting conditional=$hasConditional schema=$schemaOk | $s"
}