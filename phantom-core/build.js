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
