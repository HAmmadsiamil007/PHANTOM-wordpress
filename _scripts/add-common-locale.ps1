$files = @(
  "C:\Users\hamma\Downloads\phantom-theme\phantom-theme-v2.2.0\locales\en.default.json"
  "C:\Users\hamma\Downloads\phantom-theme\phantom-theme-v2.2.0\locales\de.json"
  "C:\Users\hamma\Downloads\phantom-theme\phantom-theme-v2.2.0\locales\es.json"
  "C:\Users\hamma\Downloads\phantom-theme\phantom-theme-v2.2.0\locales\fr.json"
  "C:\Users\hamma\Downloads\phantom-theme\phantom-theme-v2.2.0\locales\it.json"
)

$commonSection = [PSCustomObject]@{
  settings = [PSCustomObject]@{
    entrance_animation = [PSCustomObject]@{
      label = "Entrance animation"
      options = [PSCustomObject]@{
        existing = [PSCustomObject]@{ label = "Existing" }
        "ph-fade-up" = [PSCustomObject]@{ label = "Fade up" }
        "ph-scale-in" = [PSCustomObject]@{ label = "Scale in" }
        "ph-blur-in" = [PSCustomObject]@{ label = "Blur in" }
        "ph-slide-left" = [PSCustomObject]@{ label = "Slide left" }
        "ph-slide-right" = [PSCustomObject]@{ label = "Slide right" }
        "ph-rotate-in" = [PSCustomObject]@{ label = "Rotate in" }
      }
    }
  }
}

foreach ($f in $files) {
  $json = Get-Content $f -Raw | ConvertFrom-Json
  $json.sections | Add-Member -NotePropertyName "common" -NotePropertyValue $commonSection -Force
  
  $output = $json | ConvertTo-Json -Depth 10
  $output = $output -replace ': True', ': true' -replace ': False', ': false'
  
  Set-Content -Path $f -Value $output -Encoding UTF8
  Write-Output "Updated $(Split-Path $f -Leaf)"
}

Write-Output "--- Verification ---"
foreach ($f in $files) {
  try { $null = Get-Content $f -Raw | ConvertFrom-Json; Write-Output "OK: $(Split-Path $f -Leaf)" }
  catch { Write-Output "FAIL: $(Split-Path $f -Leaf): $_" }
}