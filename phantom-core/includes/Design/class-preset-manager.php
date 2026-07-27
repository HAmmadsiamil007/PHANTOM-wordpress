<?php
declare(strict_types=1);

namespace PhantomCore\Design;

use PhantomCore\Design\Providers\UserProvider;

defined('ABSPATH') || exit;

class PresetManager {
    private static ?self $instance = null;
    private PresetRegistry $registry;
    private TokenRegistry $tokenRegistry;
    private TokenCompiler $compiler;
    private CSSVariableGenerator $cssGenerator;
    private const ACTIVE_PRESET_KEY = 'phantom_active_preset';

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->registry = PresetRegistry::get_instance();
        $this->tokenRegistry = TokenRegistry::get_instance();
        $this->compiler = new TokenCompiler();
        $this->cssGenerator = new CSSVariableGenerator();
    }

    public function apply(string $id): bool {
        $preset = $this->registry->get($id);
        if (null === $preset) return false;

        if (!$preset->isCompatible(PHANTOM_CORE_VERSION)) return false;

        $finalPreset = $preset;
        if (null !== $preset->parent) {
            $parentPreset = $this->registry->get($preset->parent);
            if (null !== $parentPreset) {
                $finalPreset = $preset->merge($parentPreset);
            }
        }

        foreach ($finalPreset->tokens as $tokenName => $value) {
            $def = $this->tokenRegistry->get($tokenName);
            if (null === $def) continue;
            update_option($def['option_key'], $value, false);
        }

        if (!empty($finalPreset->dna)) {
            $dnaEngine = ThemeDNAEngine::get_instance();
            foreach ($finalPreset->dna as $dimension => $value) {
                $dnaEngine->set($dimension, $value);
            }
        }

        update_option(self::ACTIVE_PRESET_KEY, $id, false);
        $this->compiler->invalidateCache();
        $this->cssGenerator->invalidateCache();

        return true;
    }

    public function save(Preset $preset): bool {
        $userProvider = $this->getUserProvider();
        if (null === $userProvider) return false;
        return $userProvider->save($preset);
    }

    public function delete(string $id): bool {
        $userProvider = $this->getUserProvider();
        if (null === $userProvider) return false;
        return $userProvider->delete($id);
    }

    public function duplicate(string $id, string $newId, string $newName): ?Preset {
        $original = $this->registry->get($id);
        if (null === $original) return null;

        $copy = clone $original;
        $copy->id = $newId;
        $copy->name = $newName;
        $copy->source = 'user';

        $saved = $this->save($copy);
        return $saved ? $copy : null;
    }

    public function current(): ?string {
        $id = get_option(self::ACTIVE_PRESET_KEY, null);
        return is_string($id) ? $id : null;
    }

    public function reset(): bool {
        $presets = $this->registry->get_all();
        foreach ($presets as $preset) {
            foreach ($preset->tokens as $tokenName => $value) {
                $def = $this->tokenRegistry->get($tokenName);
                if (null === $def) continue;
                delete_option($def['option_key']);
            }
        }
        delete_option(self::ACTIVE_PRESET_KEY);
        ThemeDNAEngine::get_instance()->reset();
        $this->compiler->invalidateCache();
        $this->cssGenerator->invalidateCache();
        return true;
    }

    private function getUserProvider(): ?UserProvider {
        foreach ($this->registry->get_providers() as $provider) {
            if ($provider instanceof UserProvider) {
                return $provider;
            }
        }
        return null;
    }
}
