<?php
declare(strict_types=1);

namespace PhantomCore\History;

defined('ABSPATH') || exit;

/**
 * Snapshot — value object representing a saved state of the settings.
 *
 * @package PhantomCore\History
 */
class Snapshot {

    public string $id;
    public int $time;
    public int $user_id;
    public string $action;       // 'manual'|'auto'|'before_publish'|'before_preset'|'before_export'|'before_reset'|'version'
    public string $description;
    public string $theme_version;
    public string $demo;
    public string $preset;
    public string $version_name;  // only for 'version' action
    public array $settings;      // Full settings snapshot
    public array $changed;       // Keys that changed from previous snapshot

    public function __construct(array $data = []) {
        $this->id            = $data['id'] ?? $this->generate_id();
        $this->time          = $data['time'] ?? time();
        $this->user_id       = $data['user_id'] ?? get_current_user_id();
        $this->action        = $data['action'] ?? 'manual';
        $this->description   = $data['description'] ?? '';
        $this->theme_version = $data['theme_version'] ?? PHANTOM_CORE_VERSION;
        $this->demo          = $data['demo'] ?? get_option('phantom_active_demo', '');
        $this->preset        = $data['preset'] ?? '';
        $this->version_name  = $data['version_name'] ?? '';
        $this->settings      = $data['settings'] ?? [];
        $this->changed       = $data['changed'] ?? [];
    }

    public function to_array(): array {
        return [
            'id'            => $this->id,
            'time'          => $this->time,
            'time_formatted' => wp_date(get_option('date_format') . ' ' . get_option('time_format'), $this->time),
            'user_id'       => $this->user_id,
            'user_name'     => get_userdata($this->user_id) ? get_userdata($this->user_id)->display_name : __('Unknown', 'phantom-core'),
            'action'        => $this->action,
            'description'   => $this->description,
            'theme_version' => $this->theme_version,
            'demo'          => $this->demo,
            'preset'        => $this->preset,
            'version_name'  => $this->version_name,
            'settings'      => $this->settings,
            'changed'       => $this->changed,
            'change_count'  => count($this->changed),
        ];
    }

    /**
     * Get a human-readable action label.
     */
    public function get_action_label(): string {
        $labels = [
            'manual'         => __('Manual Save', 'phantom-core'),
            'auto'           => __('Auto-Save', 'phantom-core'),
            'before_publish' => __('Before Publish', 'phantom-core'),
            'before_preset'  => __('Before Preset Change', 'phantom-core'),
            'before_export'  => __('Before Export', 'phantom-core'),
            'before_reset'   => __('Before Reset', 'phantom-core'),
            'version'        => $this->version_name ?: __('Named Version', 'phantom-core'),
        ];
        return $labels[$this->action] ?? __('Unknown', 'phantom-core');
    }

    private function generate_id(): string {
        return wp_generate_uuid4();
    }

    /**
     * Check if this snapshot has a specific setting key.
     */
    public function has_setting(string $key): bool {
        return array_key_exists($key, $this->settings);
    }

    /**
     * Get a setting value from the snapshot.
     */
    public function get_setting(string $key, mixed $default = null): mixed {
        return $this->settings[$key] ?? $default;
    }

    /**
     * Apply snapshot settings to the Settings Registry.
     */
    public function apply(): int {
        $registry = \PhantomCore\Settings_Registry::get_instance();
        $count = 0;
        foreach ($this->settings as $key => $value) {
            if ($registry->has($key)) {
                $registry->set($key, $value);
                $count++;
            }
        }
        return $count;
    }
}
