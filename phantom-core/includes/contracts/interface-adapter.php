<?php

namespace PhantomCore\Contracts;

defined('ABSPATH') || exit;

interface AdapterInterface {
    public function normalize($input = null): array;
    public function normalize_collection(array $inputs): array;
}