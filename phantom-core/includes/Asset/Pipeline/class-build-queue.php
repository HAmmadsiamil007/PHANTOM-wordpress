<?php
declare(strict_types=1);

namespace PhantomCore\Asset\Pipeline;

defined('ABSPATH') || exit;

class Build_Queue {
    private static ?self $instance = null;
    private const QUEUE_OPTION = 'phantom_build_queue';

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function request(string $type, array $params = []): void {
        $queue = $this->get_queue();

        foreach ($queue as &$item) {
            if ($item['type'] === $type) {
                $item['params'] = array_merge($item['params'], $params);
                $this->save_queue($queue);
                return;
            }
        }

        $queue[] = [
            'type'      => $type,
            'params'    => $params,
            'requested' => current_time('mysql'),
        ];

        $this->save_queue($queue);
    }

    public function next(): ?array {
        $queue = $this->get_queue();
        if (empty($queue)) {
            return null;
        }
        return $queue[0];
    }

    public function dequeue(): ?array {
        $queue = $this->get_queue();
        if (empty($queue)) {
            return null;
        }
        $item = array_shift($queue);
        $this->save_queue($queue);
        return $item;
    }

    public function clear(): void {
        delete_option(self::QUEUE_OPTION);
    }

    private function get_queue(): array {
        $queue = get_option(self::QUEUE_OPTION, []);
        return is_array($queue) ? $queue : [];
    }

    private function save_queue(array $queue): void {
        update_option(self::QUEUE_OPTION, $queue, false);
    }
}
