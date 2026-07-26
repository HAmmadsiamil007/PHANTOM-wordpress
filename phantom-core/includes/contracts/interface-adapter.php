<?php
namespace PhantomCore\Contracts;

interface AdapterInterface {
    public function normalize($input = null): array;
    public function normalize_collection(array $inputs): array;
}