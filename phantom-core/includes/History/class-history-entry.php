<?php
declare(strict_types=1);

namespace PhantomCore\History;

defined('ABSPATH') || exit;

class History_Entry {
    public string $id;
    public string $timestamp;
    public string $action;
    public string $instance_id;
    public string $component;
    public string $property;
    public mixed $old_value;
    public mixed $new_value;
    public int $user_id;

    public function __construct(
        string $id = '',
        string $timestamp = '',
        string $action = 'manual',
        string $instance_id = '',
        string $component = '',
        string $property = '',
        mixed $old_value = null,
        mixed $new_value = null,
        int $user_id = 0
    ) {
        $this->id = $id ?: uniqid('h_', true);
        $this->timestamp = $timestamp ?: current_time('mysql');
        $this->action = $action;
        $this->instance_id = $instance_id;
        $this->component = $component;
        $this->property = $property;
        $this->old_value = $old_value;
        $this->new_value = $new_value;
        $this->user_id = $user_id ?: get_current_user_id();
    }

    public function to_array(): array {
        return array(
            'id' => $this->id,
            'timestamp' => $this->timestamp,
            'action' => $this->action,
            'instance_id' => $this->instance_id,
            'component' => $this->component,
            'property' => $this->property,
            'old_value' => $this->old_value,
            'new_value' => $this->new_value,
            'user_id' => $this->user_id,
        );
    }

    public static function from_array(array $data): self {
        return new self(
            id: $data['id'] ?? '',
            timestamp: $data['timestamp'] ?? '',
            action: $data['action'] ?? 'manual',
            instance_id: $data['instance_id'] ?? '',
            component: $data['component'] ?? '',
            property: $data['property'] ?? '',
            old_value: $data['old_value'] ?? null,
            new_value: $data['new_value'] ?? null,
            user_id: (int) ($data['user_id'] ?? 0),
        );
    }
}
