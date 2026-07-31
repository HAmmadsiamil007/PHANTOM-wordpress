<?php
declare(strict_types=1);

namespace PhantomCore\Search;

defined('ABSPATH') || exit;

class SearchResult {
    public string $type;
    public string $id;
    public string $label;
    public string $description;
    public float $relevance;

    public function __construct(
        string $type,
        string $id,
        string $label,
        string $description = '',
        float $relevance = 0.5
    ) {
        $this->type = $type;
        $this->id = $id;
        $this->label = $label;
        $this->description = $description;
        $this->relevance = max(0.0, min(1.0, $relevance));
    }

    public function to_array(): array {
        return [
            'type' => $this->type,
            'id' => $this->id,
            'label' => $this->label,
            'description' => $this->description,
            'relevance' => $this->relevance,
        ];
    }
}
