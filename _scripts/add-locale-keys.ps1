$files = @(
  "C:\Users\hamma\Downloads\phantom-theme\phantom-theme-v2.2.0\locales\en.default.schema.json"
  "C:\Users\hamma\Downloads\phantom-theme\phantom-theme-v2.2.0\locales\de.schema.json"
  "C:\Users\hamma\Downloads\phantom-theme\phantom-theme-v2.2.0\locales\es.schema.json"
  "C:\Users\hamma\Downloads\phantom-theme\phantom-theme-v2.2.0\locales\fr.schema.json"
  "C:\Users\hamma\Downloads\phantom-theme\phantom-theme-v2.2.0\locales\it.schema.json"
)

$phMotion = [PSCustomObject]@{
  name = "PHANTOM Motion"
  settings = [PSCustomObject]@{
    info = "Enable PHANTOM's enhanced scroll animations, page transitions, and skeleton loaders."
    ph_motion_enable = [PSCustomObject]@{
      label = "Enable PHANTOM Motion"
      info = "Toggles all PHANTOM motion features. When disabled, the theme uses its original animation system."
    }
    header_scroll = "Scroll Animations"
    ph_motion_entrance_default = [PSCustomObject]@{
      label = "Default entrance animation"
      options = [PSCustomObject]@{
        existing = [PSCustomObject]@{ label = "(Existing theme animation)" }
        "ph-fade-up" = [PSCustomObject]@{ label = "Fade up" }
        "ph-scale-in" = [PSCustomObject]@{ label = "Scale in" }
        "ph-blur-in" = [PSCustomObject]@{ label = "Blur in" }
        "ph-slide-left" = [PSCustomObject]@{ label = "Slide left" }
        "ph-slide-right" = [PSCustomObject]@{ label = "Slide right" }
        "ph-rotate-in" = [PSCustomObject]@{ label = "Rotate in" }
      }
    }
    ph_motion_entrance_speed = [PSCustomObject]@{ label = "Animation speed" }
    header_page_transitions = "Page Transitions"
    ph_motion_viewtransitions_enable = [PSCustomObject]@{
      label = "Enable View Transitions"
      info = "Smooth cross-fade between internal page navigations. Falls back to normal loading on unsupported browsers."
    }
    ph_motion_viewtransitions_skip_cart = [PSCustomObject]@{ label = "Skip cart and checkout links" }
    header_skeletons = "Skeleton Loaders"
    ph_motion_skeletons_enable = [PSCustomObject]@{
      label = "Enable skeleton loaders"
      info = "Show placeholder shimmer while content loads. Only affects AJAX-dependent sections."
    }
  }
}

$phPresets = [PSCustomObject]@{
  name = "PHANTOM Presets"
  settings = [PSCustomObject]@{
    info = "Switch between style presets to change your store's look instantly. Each preset adjusts colors, typography, and button styles."
    ph_style_preset = [PSCustomObject]@{
      label = "Style preset"
      options = [PSCustomObject]@{
        default = [PSCustomObject]@{ label = "PHANTOM Default" }
        minimal = [PSCustomObject]@{ label = "PHANTOM Minimal" }
        editorial = [PSCustomObject]@{ label = "PHANTOM Editorial" }
        bold = [PSCustomObject]@{ label = "PHANTOM Bold" }
        luxury = [PSCustomObject]@{ label = "PHANTOM Luxury" }
      }
    }
  }
}

foreach ($f in $files) {
  $json = Get-Content $f -Raw | ConvertFrom-Json
  # Add the new entries to settings_schema
  $json.settings_schema | Add-Member -NotePropertyName "ph_motion" -NotePropertyValue $phMotion
  $json.settings_schema | Add-Member -NotePropertyName "ph_presets" -NotePropertyValue $phPresets
  
  # Serialize with depth and proper formatting (indent=2 for readability)
  $output = $json | ConvertTo-Json -Depth 10
  # Fix PowerShell's boolean serialization
  $output = $output -replace ': True', ': true' -replace ': False', ': false'
  
  Set-Content -Path $f -Value $output -Encoding UTF8
  Write-Output "Updated $f"
}

# Verify
Write-Output "--- Verification ---"
foreach ($f in $files) {
  try { 
    $null = Get-Content $f -Raw | ConvertFrom-Json
    Write-Output "OK: $(Split-Path $f -Leaf)" 
  } catch { 
    Write-Output "FAIL: $(Split-Path $f -Leaf): $_" 
  }
}