const fs = require('fs');
const path = require('path');
const sass = require('sass');
const postcss = require('postcss');
const autoprefixer = require('autoprefixer');
const cssnano = require('cssnano');

const isWatch = process.argv.includes('--watch');
const isProd = process.argv.includes('--prod');

const entries = [
  { src: 'frontend/scss/style.scss', dest: 'frontend/assets/css/style.css' },
  { src: 'frontend/scss/responsive.scss', dest: 'frontend/assets/css/responsive.css' },
  { src: 'frontend/scss/motion.scss', dest: 'frontend/assets/css/motion.css' },
  { src: 'frontend/scss/a11y.scss', dest: 'frontend/assets/css/a11y.css' },
  { src: 'phantom-theme/scss/theme.scss', dest: 'phantom-theme/assets/css/theme.css' },
  { src: 'phantom-theme/scss/woocommerce.scss', dest: 'phantom-theme/assets/css/woocommerce.css' },
  { src: 'phantom-theme/scss/customizer.scss', dest: 'phantom-theme/assets/css/customizer.css' },
  // Template pack SCSS
  { src: 'frontend/packs/dark/scss/pack.scss', dest: 'frontend/packs/dark/assets/css/pack.css' },
  { src: 'frontend/packs/minimal/scss/pack.scss', dest: 'frontend/packs/minimal/assets/css/pack.css' },
  { src: 'frontend/packs/bold/scss/pack.scss', dest: 'frontend/packs/bold/assets/css/pack.css' },
];

function compile(entry) {
  const srcPath = path.join(__dirname, entry.src);
  const destPath = path.join(__dirname, entry.dest);

  if (!fs.existsSync(srcPath)) {
    console.warn('WARN: SCSS not found:', entry.src);
    return;
  }

  try {
    const result = sass.compile(srcPath, {
      style: isProd ? 'compressed' : 'expanded',
      sourceMap: !isProd,
      sourceMapIncludeSources: !isProd,
    });

    const plugins = [autoprefixer({ overrideBrowserslist: ['last 2 versions'] })];
    if (isProd) {
      plugins.push(cssnano({ preset: 'default' }));
    }

    postcss(plugins)
      .process(result.css, { from: srcPath, to: destPath, map: result.sourceMap ? { prev: result.sourceMap } : false })
      .then((postResult) => {
        fs.writeFileSync(destPath, postResult.css, 'utf8');
        console.log('OK: Wrote ' + entry.dest + ' (' + (postResult.css.length / 1024).toFixed(1) + ' KB)');

        if (postResult.map) {
          const mapPath = destPath + '.map';
          fs.writeFileSync(mapPath, JSON.stringify(postResult.map), 'utf8');
          console.log('OK: Wrote ' + entry.dest + '.map');
        }
      })
      .catch((err) => {
        console.error('ERROR: PostCSS failed for', entry.src, ':', err.message);
      });
  } catch (err) {
    console.error('ERROR: Sass compile failed for', entry.src, ':', err.message);
  }
}

entries.forEach(compile);

if (isWatch) {
  console.log('Watching for changes...');
  entries.forEach((entry) => {
    const srcPath = path.join(__dirname, entry.src);
    if (fs.existsSync(srcPath)) {
      fs.watch(srcPath, (eventType) => {
        if (eventType === 'change') {
          console.log('Change detected:', entry.src);
          compile(entry);
        }
      });
    }
  });
}
