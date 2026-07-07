$files = @(
  "C:\Users\hamma\Downloads\phantom-theme\phantom-theme-v2.2.0\locales\en.default.schema.json"
  "C:\Users\hamma\Downloads\phantom-theme\phantom-theme-v2.2.0\locales\de.schema.json"
  "C:\Users\hamma\Downloads\phantom-theme\phantom-theme-v2.2.0\locales\es.schema.json"
  "C:\Users\hamma\Downloads\phantom-theme\phantom-theme-v2.2.0\locales\fr.schema.json"
  "C:\Users\hamma\Downloads\phantom-theme\phantom-theme-v2.2.0\locales\it.schema.json"
)

$newBlock = @'
    },
    "ph_motion": {
      "name": "PHANTOM Motion",
      "settings": {
        "info": "Enable PHANTOM's enhanced scroll animations, page transitions, and skeleton loaders.",
        "ph_motion_enable": {
          "label": "Enable PHANTOM Motion",
          "info": "Toggles all PHANTOM motion features."
        },
        "header_scroll": "Scroll Animations",
        "ph_motion_entrance_default": {
          "label": "Default entrance animation",
          "options": {
            "existing": { "label": "(Existing theme animation)" },
            "ph-fade-up": { "label": "Fade up" },
            "ph-scale-in": { "label": "Scale in" },
            "ph-blur-in": { "label": "Blur in" },
            "ph-slide-left": { "label": "Slide left" },
            "ph-slide-right": { "label": "Slide right" },
            "ph-rotate-in": { "label": "Rotate in" }
          }
        },
        "ph_motion_entrance_speed": { "label": "Animation speed" },
        "header_page_transitions": "Page Transitions",
        "ph_motion_viewtransitions_enable": {
          "label": "Enable View Transitions",
          "info": "Smooth cross-fade between internal page navigations."
        },
        "ph_motion_viewtransitions_skip_cart": { "label": "Skip cart and checkout links" },
        "header_skeletons": "Skeleton Loaders",
        "ph_motion_skeletons_enable": {
          "label": "Enable skeleton loaders",
          "info": "Show placeholder shimmer while content loads."
        }
      }
    },
    "ph_presets": {
      "name": "PHANTOM Presets",
      "settings": {
        "info": "Switch between style presets to change your store's look instantly.",
        "ph_style_preset": {
          "label": "Style preset",
          "options": {
            "default": { "label": "PHANTOM Default" },
            "minimal": { "label": "PHANTOM Minimal" },
            "editorial": { "label": "PHANTOM Editorial" },
            "bold": { "label": "PHANTOM Bold" },
            "luxury": { "label": "PHANTOM Luxury" }
          }
        }
      }
    }
'@

foreach ($f in $files) {
  $c = Get-Content $f -Raw
  
  # Find the extras section and replace everything from its close to the settings_schema close
  # The pattern is: after "disable_animations" block ends, find the settings_schema closing
  $pattern = '(?s)("extras":\s*\{.*?"disable_animations":\s*\{[^}]*\}\s*\}\s*)\}(,\s*"[a-z])'
  $c = $c -replace $pattern, ('$1' + $newBlock + '$2')
  
  Set-Content -Path $f -Value $c -Encoding UTF8
  Write-Output "Fixed $f"
}

# Verify
Write-Output "---"
foreach ($f in $files) {
  try { $null = Get-Content $f -Raw | ConvertFrom-Json; Write-Output "OK: $f" } catch { Write-Output "FAIL: $f" }
}