<?php

namespace PhantomCore\Contracts;

defined('ABSPATH') || exit;

interface RendererInterface {
    public function render(array $data): string;
    public function render_collection(array $data_set): string;
}