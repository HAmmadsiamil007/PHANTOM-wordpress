/**
 * Phantom Core — JS Build Pipeline
 *
 * Minifies frontend JS files using Terser.
 * Run: node build.js
 */
const fs = require('fs');
const path = require('path');
const { minify } = require('terser');

const JS_DIR = path.join(__dirname, 'frontend', 'assets', 'js');
const ADMIN_JS_DIR = path.join(__dirname, 'admin', 'js');

const files = [
  path.join(JS_DIR, 'phantom-data.js'),
  path.join(JS_DIR, 'phantom-bridge.js'),

  path.join(JS_DIR, 'contact-form.js'),
];

async function build() {
  for (const filepath of files) {
    if (!fs.existsSync(filepath)) {
      console.log('SKIP (not found):', path.basename(filepath));
      continue;
    }
    const code = fs.readFileSync(filepath, 'utf8');
    const ext = path.extname(filepath);
    const basename = path.basename(filepath, ext);
    const outpath = path.join(path.dirname(filepath), basename + '.min' + ext);

    try {
      const result = await minify(code, {
        sourceMap: false,
        compress: { drop_console: false },
        output: { comments: false },
      });
      fs.writeFileSync(outpath, result.code, 'utf8');
      const inSize = (code.length / 1024).toFixed(1);
      const outSize = (result.code.length / 1024).toFixed(1);
      const pct = ((1 - result.code.length / code.length) * 100).toFixed(0);
      console.log(`MINIFY ${path.basename(filepath)}: ${inSize}K → ${outSize}K (${pct}% savings)`);
    } catch (err) {
      console.error(`ERROR ${path.basename(filepath)}:`, err.message);
    }
  }
}

build();
