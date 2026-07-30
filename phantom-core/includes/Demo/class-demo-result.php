<?php
declare(strict_types=1);

namespace PhantomCore\Demo;

defined('ABSPATH') || exit;

class Demo_Result {
    public readonly bool $success;
    public readonly string $message;
    public readonly array $data;
    public readonly array $errors;

    private function __construct(
        bool $success,
        string $message,
        array $data = [],
        array $errors = []
    ) {
        $this->success = $success;
        $this->message = $message;
        $this->data = $data;
        $this->errors = $errors;
    }

    public static function ok(string $message, array $data = []): self {
        return new self(true, $message, $data);
    }

    public static function fail(string $message, array $errors = []): self {
        return new self(false, $message, [], $errors);
    }
}
