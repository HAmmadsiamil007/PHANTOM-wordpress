const fs = require('fs');
const path = require('path');

const name = process.argv[2];
if (!name) {
  console.error('Usage: node bin/generate-component.js ComponentName');
  process.exit(1);
}

const baseDir = path.join(__dirname, '..');

function pascalToSnake(str) {
  return str.replace(/([a-z])([A-Z])/g, '$1_$2').toLowerCase();
}

function pascalToSlug(str) {
  return str.replace(/([a-z])([A-Z])/g, '$1-$2').toLowerCase();
}

function pascalToTitle(str) {
  return str.replace(/([A-Z])/g, ' $1').trim();
}

const slug = pascalToSlug(name);
const snake = pascalToSnake(name);
const className = name.replace(/_/g, '').replace(/[^a-zA-Z0-9]/g, '');
const title = pascalToTitle(name);

const rendererPath = path.join(baseDir, 'includes/renderer', 'class-' + slug + '.php');
const templatePath = path.join(baseDir, 'frontend/html/components', slug + '.html');
const testPath = path.join(baseDir, 'tests/renderer', className + 'Test.php');

const rendererContent = `<?php
declare(strict_types=1);

namespace PhantomCore\\Renderer;

defined('ABSPATH') || exit;

class ${className} extends Component_Renderer {

  private string $template;

  public function __construct() {
    $this->template = $this->load_template('${slug}') ?: $this->default_template();
  }

  public function render(array $data): string {
    return $this->inject($this->template, [
      // 'placeholder' => esc_html(\$data['key'] ?? ''),
    ]);
  }

  private function default_template(): string {
    return '<div class="${slug}">
      {{title}}
    </div>';
  }
}
`;

const templateContent = `<div class="${slug}">
  {{title}}
</div>
`;

const testContent = `<?php
declare(strict_types=1);

use PhantomCore\\Renderer\\${className};

class ${className}Test extends Component_Renderer_Test_Base {

  public function test_render_returns_html(): void {
    \$component = new ${className}();
    \$html = \$component->render(['title' => '${title}']);
    \$this->assertStringContainsString('${slug}', \$html);
    \$this->assertStringContainsString('${title}', \$html);
  }

  public function test_render_collection_returns_multiple(): void {
    \$component = new ${className}();
    \$html = \$component->render_collection([
      ['title' => 'One'],
      ['title' => 'Two'],
    ]);
    \$this->assertEquals(2, substr_count(\$html, '${slug}'));
  }
}
`;

[rendererPath, templatePath, testPath].forEach((p) => {
  if (fs.existsSync(p)) {
    console.warn('SKIP: Already exists ' + p);
    return;
  }
});

if (fs.existsSync(rendererPath) || fs.existsSync(templatePath) || fs.existsSync(testPath)) {
  process.exit(0);
}

fs.writeFileSync(rendererPath, rendererContent, 'utf8');
console.log('OK: Created ' + rendererPath);

fs.writeFileSync(templatePath, templateContent, 'utf8');
console.log('OK: Created ' + templatePath);

fs.writeFileSync(testPath, testContent, 'utf8');
console.log('OK: Created ' + testPath);
