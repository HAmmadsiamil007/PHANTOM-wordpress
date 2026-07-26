<?php
use PHPUnit\Framework\TestCase;
use PhantomCore\Engine\EventDispatcher;

class EventDispatcher_Test extends TestCase {
    private EventDispatcher $dispatcher;

    protected function setUp(): void {
        $this->dispatcher = new EventDispatcher();
    }

    public function test_dispatch_calls_listeners(): void {
        $called = false;
        $this->dispatcher->listen('test.event', function ($payload, $event) use (&$called) {
            $called = true;
        });
        $this->dispatcher->dispatch('test.event');
        $this->assertTrue($called);
    }

    public function test_dispatch_passes_payload(): void {
        $received = null;
        $this->dispatcher->listen('test.payload', function ($payload, $event) use (&$received) {
            $received = $payload;
        });
        $this->dispatcher->dispatch('test.payload', ['key' => 'value', 'num' => 42]);
        $this->assertSame(['key' => 'value', 'num' => 42], $received);
    }

    public function test_listenOnce_fires_once(): void {
        $count = 0;
        $this->dispatcher->listenOnce('test.once', function ($payload, $event) use (&$count) {
            $count++;
        });
        $this->dispatcher->dispatch('test.once');
        $this->assertSame(1, $count);
        $this->dispatcher->dispatch('test.once');
        $this->assertSame(1, $count);
    }

    public function test_dispatch_returns_results(): void {
        $this->dispatcher->listen('test.results', function ($payload, $event) {
            return 'first';
        });
        $this->dispatcher->listen('test.results', function ($payload, $event) {
            return 'second';
        });
        $results = $this->dispatcher->dispatch('test.results');
        $this->assertSame(['first', 'second'], $results);
    }

    public function test_flush_removes_listeners(): void {
        $called = false;
        $this->dispatcher->listen('test.flush', function ($payload, $event) use (&$called) {
            $called = true;
        });
        $count = $this->dispatcher->flush('test.flush');
        $this->assertSame(1, $count);
        $this->dispatcher->dispatch('test.flush');
        $this->assertFalse($called);
    }

    public function test_store_captures_events(): void {
        $this->dispatcher->listen('test.store', function ($payload, $event) {
            return 'ok';
        });
        $this->dispatcher->dispatch('test.store', ['msg' => 'hello']);
        $events = $this->dispatcher->getStore()->toArray();
        $this->assertCount(1, $events);
        $this->assertSame('test.store', $events[0]['event']);
        $this->assertSame(['msg' => 'hello'], $events[0]['payload']);
    }

    public function test_priority_order(): void {
        $order = [];
        $this->dispatcher->listen('test.priority', function ($payload, $event) use (&$order) {
            $order[] = 'low';
        }, 20);
        $this->dispatcher->listen('test.priority', function ($payload, $event) use (&$order) {
            $order[] = 'high';
        }, 5);
        $results = $this->dispatcher->dispatch('test.priority');
        $this->assertSame(['high', 'low'], $order);
    }

    public function test_store_flush_clears(): void {
        $this->dispatcher->dispatch('evt.a', ['n' => 1]);
        $this->dispatcher->dispatch('evt.b', ['n' => 2]);
        $this->dispatcher->getStore()->flush();
        $this->assertSame(0, $this->dispatcher->getStore()->count());
    }
}
