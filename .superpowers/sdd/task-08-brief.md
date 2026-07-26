# T0.8 — Build pipeline with terser + source maps

**Goal:** Upgrade build.js to produce minified output with source maps using terser.

## Context

Current `build.js` concatenates files into `phantom-data.js` and just copies to `phantom-core.min.js`. Replace with proper minification using terser.

## Files to Modify

### `phantom-core/build.js`

READ the existing file first. Replace entire contents:

```javascript
/**
 * Phantom Core Build Script
 * Concatenates modular JS files, minifies with terser, produces source maps.
 *
 * Usage: node build.js
 */

const fs = require('fs');
const path = require('path');
const Terser = require('terser');

const JS_DIR = path.join(__dirname, 'frontend', 'assets', 'js');

const files = [
  // Services
  'services/event-services.js',
  'services/api-service.js',
  'services/cart-service.js',
  'services/auth-service.js',
  // Adapters
  'adapters/product-adapter.js',
  'adapters/category-adapter.js',
  'adapters/post-adapter.js',
  // Renderer
  'renderer/component-renderer.js',
  'renderer/product-card.js',
  'renderer/category-card.js',
  'renderer/blog-card.js',
  'renderer/navigation.js',
  'renderer/hero.js',
  // Entry point
  'phantom-core.js',
];

let bundle = '';

files.forEach(function(relPath) {
  var fullPath = path.join(JS_DIR, relPath);
  if (fs.existsSync(fullPath)) {
    bundle += '/* ' + relPath + ' */\n';
    bundle += fs.readFileSync(fullPath, 'utf8');
    bundle += '\n\n';
  } else {
    console.warn('WARN: File not found:', relPath);
  }
});

// Write unminified bundle (phantom-data.js — backward compat)
var outputPath = path.join(JS_DIR, 'phantom-data.js');
fs.writeFileSync(outputPath, bundle, 'utf8');
console.log('OK: Wrote ' + outputPath + ' (' + (bundle.length / 1024).toFixed(1) + ' KB)');

// Minify with terser + source map
var minPath = path.join(JS_DIR, 'phantom-core.min.js');
var mapPath = path.join(JS_DIR, 'phantom-core.min.js.map');

Terser.minify(bundle, {
  sourceMap: {
    url: 'phantom-core.min.js.map',
  },
  output: {
    comments: false,
  },
}).then(function(result) {
  fs.writeFileSync(minPath, result.code, 'utf8');
  console.log('OK: Wrote ' + minPath + ' (' + (result.code.length / 1024).toFixed(1) + ' KB)');

  if (result.map) {
    fs.writeFileSync(mapPath, result.map, 'utf8');
    console.log('OK: Wrote ' + mapPath + ' (' + (result.map.length / 1024).toFixed(1) + ' KB)');
  }
}).catch(function(err) {
  console.error('ERROR: Minification failed:', err);
  process.exit(1);
});
```

The manifest order matters — services first (event, api, cart, auth — no dependencies), then adapters, then renderers, then phantom-core.js last.

## Verification
```bash
cd C:\Users\hamma\Downloads\wordpress\phantom-core
npm ls terser 2>$null; if ($LASTEXITCODE -ne 0) { npm install terser --save-dev }
node build.js
```

Expected output:
```
OK: Wrote .../phantom-data.js (... KB)
OK: Wrote .../phantom-core.min.js (... KB)
OK: Wrote .../phantom-core.min.js.map (... KB)
```
The minified file should be noticeably smaller than the unminified one.

## Commit
```bash
cd C:\Users\hamma\Downloads\wordpress
git add phantom-core/build.js phantom-core/package.json 2>$null; git add phantom-core/frontend/assets/js/phantom-data.js phantom-core/frontend/assets/js/phantom-core.min.js phantom-core/frontend/assets/js/phantom-core.min.js.map
git commit -m "feat(phase0): upgrade build.js with terser minification + source maps"
```
