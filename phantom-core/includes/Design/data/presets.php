<?php
declare(strict_types=1);

namespace PhantomCore\Design;

defined('ABSPATH') || exit;

return [
    'core:light' => [
        'id' => 'core:light',
        'name' => 'Light',
        'source' => 'core',
        'version' => '1.0.0',
        'framework' => '>=1.5.0',
        'author' => 'Phantom Core',
        'dna' => [
            'design_style' => 'classic', 'motion_style' => 'subtle',
            'shape_style' => 'sharp', 'typography_style' => 'sans',
            'elevation_style' => 'soft', 'color_style' => 'neutral',
        ],
        'tokens' => [
            'color.primary' => '#C1121F', 'color.background' => '#FFFFFF',
            'color.text.primary' => '#333333',
            'typography.heading.font' => "'Playfair Display', serif",
            'typography.body.font' => "'Inter', sans-serif",
            'shadow.sm' => '0 1px 3px rgba(0,0,0,0.1)',
            'radius.md' => '8px',
        ],
    ],
    'core:dark' => [
        'id' => 'core:dark',
        'name' => 'Dark',
        'source' => 'core',
        'version' => '1.0.0',
        'framework' => '>=1.5.0',
        'author' => 'Phantom Core',
        'dna' => [
            'design_style' => 'modern', 'motion_style' => 'dynamic',
            'shape_style' => 'rounded', 'typography_style' => 'sans',
            'elevation_style' => 'floating', 'color_style' => 'vibrant',
        ],
        'tokens' => [
            'color.primary' => '#E53935', 'color.background' => '#121212',
            'color.surface' => '#1E1E1E', 'color.text.primary' => '#FFFFFF',
            'color.border' => '#333333',
        ],
    ],
    'core:minimal' => [
        'id' => 'core:minimal',
        'name' => 'Minimal',
        'source' => 'core',
        'version' => '1.0.0',
        'framework' => '>=1.5.0',
        'author' => 'Phantom Core',
        'dna' => [
            'design_style' => 'minimal', 'motion_style' => 'subtle',
            'shape_style' => 'sharp', 'typography_style' => 'sans',
            'elevation_style' => 'flat', 'color_style' => 'monochrome',
        ],
        'tokens' => [
            'color.primary' => '#000000', 'color.background' => '#FFFFFF',
            'color.surface' => '#F8F8F8', 'color.text.primary' => '#111111',
            'space.lg' => '48px', 'space.xl' => '96px',
            'shadow.xs' => 'none', 'shadow.sm' => 'none',
            'shadow.md' => 'none', 'shadow.lg' => 'none',
            'shadow.xl' => 'none',
        ],
    ],
    'core:modern' => [
        'id' => 'core:modern',
        'name' => 'Modern',
        'source' => 'core',
        'version' => '1.0.0',
        'framework' => '>=1.5.0',
        'author' => 'Phantom Core',
        'dna' => [
            'design_style' => 'modern', 'motion_style' => 'dynamic',
            'shape_style' => 'sharp', 'typography_style' => 'sans',
            'elevation_style' => 'floating', 'color_style' => 'vibrant',
        ],
        'tokens' => [
            'color.primary' => '#6C63FF', 'color.secondary' => '#FF6584',
            'typography.heading.font' => "'Inter', sans-serif",
            'typography.h1.size' => '64px',
            'shadow.lg' => '0 20px 40px rgba(108,99,255,0.15)',
        ],
    ],
    'core:luxury' => [
        'id' => 'core:luxury',
        'name' => 'Luxury',
        'source' => 'core',
        'version' => '1.0.0',
        'framework' => '>=1.5.0',
        'author' => 'Phantom Core',
        'dna' => [
            'design_style' => 'luxury', 'motion_style' => 'elegant',
            'shape_style' => 'rounded', 'typography_style' => 'serif',
            'elevation_style' => 'soft', 'color_style' => 'vibrant',
        ],
        'tokens' => [
            'color.primary' => '#000000', 'color.secondary' => '#D4AF37',
            'color.accent' => '#8B7355',
            'typography.heading.font' => "'Playfair Display', serif",
            'typography.body.font' => "'Inter', sans-serif",
            'radius.md' => '12px', 'radius.lg' => '24px',
            'space.md' => '24px', 'space.lg' => '48px', 'space.xl' => '96px',
            'motion.duration.normal' => '400ms',
            'shadow.md' => '0 8px 24px rgba(0,0,0,0.12)',
        ],
    ],
    'core:classic' => [
        'id' => 'core:classic',
        'name' => 'Classic',
        'source' => 'core',
        'version' => '1.0.0',
        'framework' => '>=1.5.0',
        'author' => 'Phantom Core',
        'dna' => [
            'design_style' => 'classic', 'motion_style' => 'subtle',
            'shape_style' => 'sharp', 'typography_style' => 'serif',
            'elevation_style' => 'soft', 'color_style' => 'neutral',
        ],
        'tokens' => [
            'color.primary' => '#1A365D', 'color.secondary' => '#2D3748',
            'typography.heading.font' => "'Playfair Display', serif",
            'typography.body.font' => "'Source Serif 4', serif",
            'radius.sm' => '2px', 'radius.md' => '4px',
        ],
    ],
    'core:glass' => [
        'id' => 'core:glass',
        'name' => 'Glass',
        'source' => 'core',
        'version' => '1.0.0',
        'framework' => '>=1.5.0',
        'author' => 'Phantom Core',
        'dna' => [
            'design_style' => 'modern', 'motion_style' => 'smooth',
            'shape_style' => 'rounded', 'typography_style' => 'sans',
            'elevation_style' => 'glass', 'color_style' => 'vibrant',
        ],
        'tokens' => [
            'color.primary' => '#7C3AED', 'color.secondary' => '#EC4899',
            'color.surface' => 'rgba(255,255,255,0.1)',
            'color.border' => 'rgba(255,255,255,0.2)',
            'effect.glass.reflection' => 'rgba(255,255,255,0.15)',
            'effect.blur.md' => '12px',
            'shadow.card' => '0 8px 32px rgba(0,0,0,0.1)',
        ],
    ],
];
