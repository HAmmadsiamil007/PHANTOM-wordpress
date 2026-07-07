# Fix BOM issues in locale and config files
$files = @(
  "C:\Users\hamma\Downloads\phantom-theme\phantom-theme-v2.2.0\config\settings_data.json",
  "C:\Users\hamma\Downloads\phantom-theme\phantom-theme-v2.2.0\locales\en.default.schema.json",
  "C:\Users\hamma\Downloads\phantom-theme\phantom-theme-v2.2.0\locales\de.schema.json",
  "C:\Users\hamma\Downloads\phantom-theme\phantom-theme-v2.2.0\locales\es.schema.json",
  "C:\Users\hamma\Downloads\phantom-theme\phantom-theme-v2.2.0\locales\fr.schema.json",
  "C:\Users\hamma\Downloads\phantom-theme\phantom-theme-v2.2.0\locales\it.schema.json",
  "C:\Users\hamma\Downloads\phantom-theme\phantom-theme-v2.2.0\locales\en.default.json",
  "C:\Users\hamma\Downloads\phantom-theme\phantom-theme-v2.2.0\locales\de.json",
  "C:\Users\hamma\Downloads\phantom-theme\phantom-theme-v2.2.0\locales\es.json",
  "C:\Users\hamma\Downloads\phantom-theme\phantom-theme-v2.2.0\locales\fr.json",
  "C:\Users\hamma\Downloads\phantom-theme\phantom-theme-v2.2.0\locales\it.json"
)

foreach ($f in $files) {
  # Read, re-encode without BOM
  $content = [System.IO.File]::ReadAllText($f)
  [System.IO.File]::WriteAllText($f, $content, [System.Text.UTF8Encoding]::new($false))
  Write-Output "Fixed: $f"
}

Write-Output "Done - all files re-encoded without BOM"
