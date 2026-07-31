<?php
declare(strict_types=1);

namespace PhantomCore\Components;

defined('ABSPATH') || exit;

class ValidationResult {
    public bool $pass;
    public int $score;
    public array $errors;
    public array $warnings;
    public string $component_name;

    public function __construct(
        string $component_name,
        bool $pass = true,
        int $score = 100,
        array $errors = [],
        array $warnings = []
    ) {
        $this->component_name = $component_name;
        $this->pass = $pass;
        $this->score = $score;
        $this->errors = $errors;
        $this->warnings = $warnings;
    }
}
