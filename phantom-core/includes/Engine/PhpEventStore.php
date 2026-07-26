<?php
declare(strict_types=1);

namespace PhantomCore\Engine;

defined('ABSPATH') || exit;

class PhpEventStore {
    private array $events = [];

    public function capture(string $event, array $payload = []): void {
        $this->events[] = [
            'event'   => $event,
            'payload' => $payload,
            'time'    => microtime(true),
        ];
    }

    public function flush(): array {
        $events = $this->events;
        $this->events = [];
        return $events;
    }

    public function count(): int {
        return count($this->events);
    }

    public function toArray(): array {
        return $this->events;
    }

    public function clear(): void {
        $this->events = [];
    }
}
