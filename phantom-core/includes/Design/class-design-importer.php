<?php
declare(strict_types=1);

namespace PhantomCore\Design;

defined('ABSPATH') || exit;

class DesignImporter {
    private PresetManager $presetManager;
    private PresetRegistry $presetRegistry;

    public function __construct() {
        $this->presetManager = PresetManager::get_instance();
        $this->presetRegistry = PresetRegistry::get_instance();
    }

    public function import(string $json): array {
        $data = json_decode($json, true);
        if (!is_array($data)) {
            return ['success' => false, 'message' => 'Invalid JSON'];
        }

        if (!isset($data['id']) || !isset($data['tokens'])) {
            return ['success' => false, 'message' => 'Missing required fields: id, tokens'];
        }

        $framework = $data['framework'] ?? '>=' . PHANTOM_CORE_VERSION;
        $tempPreset = Preset::from_array([
            'id' => $data['id'],
            'name' => $data['name'] ?? 'Imported Preset',
            'source' => 'user',
            'version' => $data['version'] ?? '1.0.0',
            'framework' => $framework,
            'author' => $data['author'] ?? 'Imported',
            'tokens' => $data['tokens'],
            'dna' => $data['dna'] ?? [],
            'metadata' => $data['metadata'] ?? [],
        ]);

        if (!$tempPreset->isCompatible(PHANTOM_CORE_VERSION)) {
            return [
                'success' => false,
                'message' => 'Preset requires framework ' . $framework . ', current version is ' . PHANTOM_CORE_VERSION,
            ];
        }

        $this->presetManager->save($tempPreset);

        $applied = $this->presetManager->apply($data['id']);

        return [
            'success' => true,
            'message' => 'Preset imported and ' . ($applied ? 'applied' : 'saved (apply skipped)'),
            'preset_id' => $data['id'],
        ];
    }
}
