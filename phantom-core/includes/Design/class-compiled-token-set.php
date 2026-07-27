<?php
declare(strict_types=1);

namespace PhantomCore\Design;

defined('ABSPATH') || exit;

class CompiledTokenSet {
    public array $tokens = [];
    public array $cssVars = [];
    public array $components = [];
    public array $responsive = [];
    public string $css = '';
}
