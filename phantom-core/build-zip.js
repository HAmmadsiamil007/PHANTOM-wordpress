/**
 * Phantom Core Build & Package Script
 * Usage: node build-zip.js
 * Generates phantom-core-v2.0.zip in the parent directory.
 */
const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

const ROOT = __dirname;
const JS_DIR = path.join(ROOT, 'frontend', 'assets', 'js');
const EXCLUDE = [
  'node_modules',
  '.git',
  '.gitignore',
  'build.js',
  'build-zip.js',
  'package.json',
  'package-lock.json',
];

// Step 1: Build JS bundle
console.log('Step 1: Building JS bundle...');
const build = path.join(ROOT, 'build.js');
if (fs.existsSync(build)) {
  try {
    execSync('node ' + build, { cwd: ROOT, stdio: 'inherit' });
  } catch (e) {
    console.error('Build failed:', e.message);
    process.exit(1);
  }
}

// Step 2: Dynamic JS file list for the manifest
function walk(dir, prefix) {
  let files = [];
  const entries = fs.readdirSync(dir, { withFileTypes: true });
  for (const e of entries) {
    const rel = prefix ? prefix + '/' + e.name : e.name;
    const full = path.join(dir, e.name);
    if (EXCLUDE.includes(e.name)) continue;
    if (e.name === '.' || e.name === '..') continue;
    if (e.name.startsWith('.')) continue;
    if (e.isDirectory()) {
      files = files.concat(walk(full, rel));
    } else {
      files.push({ rel: rel.replace(/\\/g, '/'), full: full });
    }
  }
  return files;
}

// Step 3: Write file manifest
const allFiles = walk(ROOT, '');
const manifestPath = path.join(ROOT, 'file-manifest.json');
fs.writeFileSync(manifestPath, JSON.stringify(allFiles.map(f => f.rel), null, 2));
console.log('OK: Written file-manifest.json (' + allFiles.length + ' files)');

// Step 4: Generate ZIP (works on Windows with PowerShell or if 7z/node-archiver available)
const zipName = 'phantom-core-v2.0.zip';
const zipPath = path.join(path.dirname(ROOT), zipName);

// Use archiver if available, else fall back to PowerShell
try {
  require.resolve('archiver');
  const archiver = require('archiver');
  const output = fs.createWriteStream(zipPath);
  const archive = archiver('zip', { zlib: { level: 9 } });

  output.on('close', () => {
    console.log('OK: Created ' + zipPath + ' (' + (archive.pointer() / 1024 / 1024).toFixed(1) + ' MB)');
    fs.unlinkSync(manifestPath);
  });
  archive.on('error', err => { throw err; });
  archive.pipe(output);

  for (const f of allFiles) {
    archive.file(f.full, { name: path.join('phantom-core', f.rel) });
  }
  archive.finalize();
} catch (e) {
  // Fallback: PowerShell Compress-Archive
  console.log('Note: archiver not available, using PowerShell...');
  const cmd = '$compress = @{ Path = (Get-ChildItem -Path "' + ROOT + '" -Exclude ' +
    EXCLUDE.map(x => "'" + x + "'").join(',') +
    ').FullName; DestinationPath = "' + zipPath + '"; CompressionLevel = "Optimal" }; Compress-Archive @compress';
  try {
    execSync('powershell -NoProfile -Command "' + cmd.replace(/"/g, '\\"') + '"', { stdio: 'inherit' });
    console.log('OK: Created ' + zipPath);
    fs.unlinkSync(manifestPath);
  } catch (e2) {
    console.log('OK: File manifest at ' + manifestPath + ' (' + allFiles.length + ' files)');
    console.log('To create ZIP manually: Compress-Archive -Path "' + ROOT + '\\*" -DestinationPath "' + zipPath + '"');
  }
}