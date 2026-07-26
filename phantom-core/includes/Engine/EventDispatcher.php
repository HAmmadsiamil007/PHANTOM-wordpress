<?php
declare(strict_types=1);

namespace PhantomCore\Engine;

defined('ABSPATH') || exit;

class EventDispatcher {
    private array $listeners = [];
    private PhpEventStore $store;

    public function __construct(?PhpEventStore $store = null) {
        $this->store = $store ?? new PhpEventStore();
    }

    public function dispatch(string $event, array $payload = []): array {
        $results = [];

        if (isset($this->listeners[$event])) {
            $priorities = array_keys($this->listeners[$event]);
            sort($priorities, SORT_NUMERIC);

            foreach ($priorities as $priority) {
                foreach ($this->listeners[$event][$priority] as $listener) {
                    $results[] = $listener($payload, $event);
                }
            }
        }

        $this->store->capture($event, $payload);

        if (function_exists('do_action')) {
            do_action("phantom_event_{$event}", $payload);
        }

        return $results;
    }

    public function listen(string $event, callable $listener, int $priority = 10): void {
        if (!isset($this->listeners[$event])) {
            $this->listeners[$event] = [];
        }
        if (!isset($this->listeners[$event][$priority])) {
            $this->listeners[$event][$priority] = [];
        }
        $this->listeners[$event][$priority][] = $listener;
    }

    public function listenOnce(string $event, callable $listener): void {
        $once = function ($payload, $eventName) use ($listener, &$once) {
            $id = spl_object_id($once);
            if (isset($this->listeners[$eventName])) {
                foreach ($this->listeners[$eventName] as $priority => &$listeners) {
                    foreach ($listeners as $index => $l) {
                        if (spl_object_id($l) === $id) {
                            array_splice($listeners, $index, 1);
                            if (empty($listeners)) {
                                unset($this->listeners[$eventName][$priority]);
                            }
                            break 2;
                        }
                    }
                }
                if (empty($this->listeners[$eventName])) {
                    unset($this->listeners[$eventName]);
                }
            }
            return $listener($payload, $eventName);
        };

        $this->listen($event, $once);
    }

    public function flush(string $event): int {
        $count = 0;
        if (isset($this->listeners[$event])) {
            foreach ($this->listeners[$event] as $priority => $listeners) {
                $count += count($listeners);
            }
            unset($this->listeners[$event]);
        }
        return $count;
    }

    public function getListeners(?string $event = null): array {
        if ($event !== null) {
            if (!isset($this->listeners[$event])) {
                return [];
            }
            $all = [];
            $priorities = array_keys($this->listeners[$event]);
            sort($priorities, SORT_NUMERIC);
            foreach ($priorities as $p) {
                foreach ($this->listeners[$event][$p] as $listener) {
                    $all[] = $listener;
                }
            }
            return $all;
        }
        return $this->listeners;
    }

    public function getStore(): PhpEventStore {
        return $this->store;
    }
}
