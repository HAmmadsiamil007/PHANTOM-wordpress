$base = "C:\Users\hamma\Downloads\phantom-theme\phantom-theme-v2.2.0\sections"

$settingEntry = @'
      {
        "type": "select",
        "id": "entrance_animation",
        "label": "t:sections.common.settings.entrance_animation.label",
        "default": "existing",
        "options": [
          {
            "value": "existing",
            "label": "t:sections.common.settings.entrance_animation.options.existing.label"
          },
          {
            "value": "ph-fade-up",
            "label": "ph-fade-up"
          },
          {
            "value": "ph-scale-in",
            "label": "ph-scale-in"
          },
          {
            "value": "ph-blur-in",
            "label": "ph-blur-in"
          },
          {
            "value": "ph-slide-left",
            "label": "ph-slide-left"
          },
          {
            "value": "ph-slide-right",
            "label": "ph-slide-right"
          },
          {
            "value": "ph-rotate-in",
            "label": "ph-rotate-in"
          }
        ]
      }
'@

# Simple sections: single data-aos value, no conditional
$simple = @(
  @{file="background-image-text.liquid"; aosVal="background-media-text__animation"}
  @{file="background-video-text.liquid"; aosVal="background-media-text__animation"}
  @{file="blog-posts.liquid"; aosVal="row-of-3"}
  @{file="hero-video.liquid"; aosVal="hero__animation"}
  @{file="map.liquid"; aosVal="map-section__animation"}
  @{file="product-recommendations.liquid"; aosVal="overflow__animation"}
  @{file="recently-viewed.liquid"; aosVal="overflow__animation"}
  @{file="logo-list.liquid"; aosVal="logo__animation"}
)

foreach ($s in $simple) {
  $path = Join-Path $base $s.file
  $content = Get-Content $path -Raw

  # Add setting entry to schema settings array
  $settingsPattern = '"settings": \[\s*'
  $replacement = '"settings": [' + "`n" + $settingEntry + ",`n"
  $content = $content -replace $settingsPattern, $replacement

  # Replace hardcoded data-aos with conditional
  $oldAos = 'data-aos="' + $s.aosVal + '"'
  $newAos = 'data-aos="{% if section.settings.entrance_animation and section.settings.entrance_animation != ''existing'' %}{{ section.settings.entrance_animation }}{% else %}' + $s.aosVal + '{% endif %}"'
  $content = $content -replace [regex]::Escape($oldAos), $newAos

  Set-Content -Path $path -Value $content -NoNewline
  Write-Output "OK: $($s.file)"
}
Write-output "Done processing $($simple.Count) simple sections"
