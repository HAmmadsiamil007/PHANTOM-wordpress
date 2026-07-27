<?php
declare(strict_types=1);

namespace PhantomCore\Design;

defined('ABSPATH') || exit;

class DesignExporter {
    private TokenRegistry $registry;
    private TokenResolver $resolver;
    private ThemeDNAEngine $dnaEngine;

    public function __construct() {
        $this->registry = TokenRegistry::get_instance();
        $this->resolver = new TokenResolver();
        $this->dnaEngine = ThemeDNAEngine::get_instance();
    }

    public function exportCurrent(): string {
        $tokens = [];
        foreach ($this->registry->get_all() as $name => $def) {
            $value = $this->resolver->resolve($name);
            if (null !== $value) {
                $tokens[$name] = $value;
            }
        }

        $export = [
            'id' => 'user:exported-' . gmdate('Ymd-His'),
            'name' => 'Exported Preset ' . gmdate('Y-m-d H:i'),
            'source' => 'user',
            'version' => '1.0.0',
            'framework' => '>=' . PHANTOM_CORE_VERSION,
            'author' => 'Exported from Phantom Core',
            'tokens' => $tokens,
            'dna' => $this->dnaEngine->getCurrent(),
            'metadata' => [
                'description' => 'Exported design preset',
                'exported_at' => gmdate('Y-m-d H:i:s'),
                'exported_version' => PHANTOM_CORE_VERSION,
            ],
        ];

        return wp_json_encode($export, JSON_PRETTY_PRINT);
    }

    public function exportPreset(string $id): string {
        $registry = PresetRegistry::get_instance();
        $preset = $registry->get($id);
        if (null === $preset) {
            return '{}';
        }
        return $preset->to_json();
    }
}
