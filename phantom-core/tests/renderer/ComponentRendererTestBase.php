<?php
declare(strict_types=1);

abstract class Component_Renderer_Test_Base extends \WP_UnitTestCase {

  protected function assert_placeholder_replaced(string $output, string $placeholder): void {
    $this->assertStringNotContainsString('{{' . $placeholder . '}}', $output,
      'Placeholder {{' . $placeholder . '}} should have been replaced in output');
  }

  protected function assert_default_applied(string $output, string $key, $expected): void {
    $this->assertStringContainsString((string) $expected, $output,
      'Expected default value "' . $expected . '" for key "' . $key . '" in output');
  }

  protected function assert_missing_placeholder_throws(string $template, string $placeholder): void {
    $this->assertStringContainsString('{{' . $placeholder . '}}', $template,
      'Placeholder {{' . $placeholder . '}} should be present in original template');
  }

  protected function create_renderer_data(array $overrides = []): array {
    return array_merge([
      'title' => 'Default Title',
      'description' => 'Default description text.',
      'url' => '#',
      'image' => '',
      'price' => '$0.00',
    ], $overrides);
  }
}
