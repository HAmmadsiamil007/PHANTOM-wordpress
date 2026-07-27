<?php
declare(strict_types=1);

namespace PhantomCore\Design;

defined('ABSPATH') || exit;

class TokenValidator {
    private TokenRegistry $registry;
    private TokenResolver $resolver;

    public function __construct() {
        $this->registry = TokenRegistry::get_instance();
        $this->resolver = new TokenResolver();
    }

    public function validate(?string $name = null): array {
        if (null !== $name) {
            return [$this->validateSingle($name)];
        }
        return $this->validateAll();
    }

    public function validateAll(): array {
        $results = [];
        foreach ($this->registry->get_all() as $name => $def) {
            $results[] = $this->validateSingle($name);
        }
        return $results;
    }

    public function isHealthy(): bool {
        foreach ($this->validateAll() as $result) {
            if ('error' === $result['status']) {
                return false;
            }
        }
        return true;
    }

    private function validateSingle(string $name): array {
        $def = $this->registry->get($name);
        if (null === $def) {
            return ['token' => $name, 'status' => 'error', 'message' => 'Token not found in registry'];
        }

        $value = $this->resolver->resolve($name);
        if (null === $value) {
            return ['token' => $name, 'status' => 'error', 'message' => 'Failed to resolve value'];
        }

        $type = $def['type'] ?? 'string';
        $error = $this->validateByType($value, $type);

        if (null !== $error) {
            return ['token' => $name, 'status' => 'warning', 'message' => $error, 'context' => ['value' => $value]];
        }

        return ['token' => $name, 'status' => 'ok', 'message' => ''];
    }

    private function validateByType(mixed $value, string $type): ?string {
        if (!is_string($value)) {
            return null;
        }
        return match ($type) {
            'color' => $this->validateColor($value),
            'size' => is_numeric(str_replace(['px', 'rem', 'em', '%', 'vh', 'vw'], '', $value)) ? null : "Invalid size: $value",
            'font_size' => is_numeric(str_replace(['px', 'rem', 'em'], '', $value)) ? null : "Invalid font size: $value",
            'duration' => is_numeric(str_replace(['ms', 's'], '', $value)) ? null : "Invalid duration: $value",
            'number' => is_numeric($value) ? null : "Not a number: $value",
            default => null,
        };
    }

    private function validateColor(string $value): ?string {
        if (preg_match('/^#[0-9a-fA-F]{3,8}$/', $value)) return null;
        if (preg_match('/^rgba?\(/', $value)) return null;
        if (preg_match('/^hsla?\(/', $value)) return null;
        return "Invalid color format: $value";
    }
}
