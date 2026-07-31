<?php
/**
 * Component parts metadata — describes the editable parts of each component.
 *
 * A part groups generic properties (from the Property_Registry) under a
 * human label and maps them to component-specific storage keys, so the
 * Color/Typography tools never need component-specific code.
 *
 * Format:
 *   'component-id' => [
 *       'parts' => [
 *           'part-id' => [
 *               'label'      => 'Part label shown in the inspector',
 *               'properties' => [
 *                   ['property' => 'background-color', 'key' => 'hero_bg_color'],
 *                   ['property' => 'font-size',       'key' => 'hero_title_size', 'label' => 'Title Size'],
 *               ],
 *           ],
 *       ],
 *   ],
 *
 * Tools are derived from the properties (union of property tools) plus
 * component capabilities (animation/responsive). Components without a parts
 * entry fall back to their legacy definition tabs.
 *
 * @package PhantomCore\Design
 */

return array(
    'hero' => array(
        'parts' => array(
            'background' => array(
                'label'      => 'Background',
                'properties' => array(
                    array('property' => 'background-color', 'key' => 'hero_bg_color'),
                    array('property' => 'background-image', 'key' => 'hero_bg_image'),
                    array('property' => 'overlay-color',    'key' => 'hero_overlay_color'),
                    array('property' => 'overlay-opacity',  'key' => 'hero_overlay_opacity'),
                ),
            ),
            'heading' => array(
                'label'      => 'Heading',
                'properties' => array(
                    array('property' => 'font-family',    'key' => 'hero_title_font'),
                    array('property' => 'font-size',      'key' => 'hero_title_size', 'label' => 'Title Size'),
                    array('property' => 'font-weight',    'key' => 'hero_title_weight'),
                    array('property' => 'line-height',    'key' => 'hero_title_line_height'),
                    array('property' => 'letter-spacing', 'key' => 'hero_title_letter_spacing'),
                    array('property' => 'text-transform', 'key' => 'hero_title_transform'),
                    array('property' => 'text-align',     'key' => 'hero_title_align'),
                    array('property' => 'text-color',     'key' => 'hero_title_color'),
                ),
            ),
            'description' => array(
                'label'      => 'Description',
                'properties' => array(
                    array('property' => 'font-size',   'key' => 'hero_subtitle_size', 'label' => 'Text Size'),
                    array('property' => 'line-height', 'key' => 'hero_subtitle_line_height'),
                    array('property' => 'text-color',  'key' => 'hero_subtitle_color'),
                ),
            ),
            'button_primary' => array(
                'label'      => 'Primary Button',
                'properties' => array(
                    array('property' => 'background-color', 'key' => 'hero_button_bg_color'),
                    array('property' => 'text-color',       'key' => 'hero_button_text_color'),
                    array('property' => 'border-color',     'key' => 'hero_button_border_color'),
                    array('property' => 'font-size',        'key' => 'hero_button_font_size'),
                    array('property' => 'font-weight',      'key' => 'hero_button_font_weight'),
                    array('property' => 'padding-x',        'key' => 'hero_button_padding_x'),
                    array('property' => 'padding-y',        'key' => 'hero_button_padding_y'),
                    array('property' => 'border-radius',    'key' => 'hero_button_radius'),
                ),
            ),
            'button_secondary' => array(
                'label'      => 'Secondary Button',
                'properties' => array(
                    array('property' => 'background-color', 'key' => 'hero_button2_bg_color'),
                    array('property' => 'text-color',       'key' => 'hero_button2_text_color'),
                    array('property' => 'border-color',     'key' => 'hero_button2_border_color'),
                    array('property' => 'font-size',        'key' => 'hero_button2_font_size'),
                    array('property' => 'padding-x',        'key' => 'hero_button2_padding_x'),
                    array('property' => 'padding-y',        'key' => 'hero_button2_padding_y'),
                ),
            ),
            'image' => array(
                'label'      => 'Image',
                'properties' => array(
                    array('property' => 'logo-image', 'key' => 'hero_image', 'label' => 'Hero Image'),
                ),
            ),
            'animation' => array(
                'label'      => 'Animation',
                'properties' => array(
                    array('property' => 'animation-type',     'key' => 'hero_animation'),
                    array('property' => 'animation-delay',    'key' => 'hero_animation_delay'),
                    array('property' => 'animation-duration', 'key' => 'hero_animation_duration'),
                ),
            ),
        ),
    ),

    'header' => array(
        'parts' => array(
            'background' => array(
                'label'      => 'Background',
                'properties' => array(
                    array('property' => 'background-color', 'key' => 'header_bg_color'),
                ),
            ),
            'links' => array(
                'label'      => 'Navigation Links',
                'properties' => array(
                    array('property' => 'link-color',      'key' => 'header_link_color'),
                    array('property' => 'link-hover-color', 'key' => 'header_link_hover'),
                    array('property' => 'font-size',       'key' => 'header_link_size'),
                    array('property' => 'font-weight',     'key' => 'header_link_weight'),
                    array('property' => 'text-transform',  'key' => 'header_link_transform'),
                ),
            ),
            'logo' => array(
                'label'      => 'Logo',
                'properties' => array(
                    array('property' => 'logo-image', 'key' => 'header_logo_image'),
                ),
            ),
        ),
    ),

    'footer' => array(
        'parts' => array(
            'background' => array(
                'label'      => 'Background',
                'properties' => array(
                    array('property' => 'background-color', 'key' => 'footer_bg_color'),
                ),
            ),
            'text' => array(
                'label'      => 'Text',
                'properties' => array(
                    array('property' => 'text-color',   'key' => 'footer_text_color'),
                    array('property' => 'link-color',   'key' => 'footer_link_color'),
                    array('property' => 'font-size',    'key' => 'footer_text_size'),
                ),
            ),
            'columns' => array(
                'label'      => 'Columns',
                'properties' => array(
                    array('property' => 'gap',        'key' => 'footer_columns_gap'),
                    array('property' => 'padding-y',  'key' => 'footer_padding_y'),
                ),
            ),
        ),
    ),

    'products' => array(
        'parts' => array(
            'card' => array(
                'label'      => 'Product Card',
                'properties' => array(
                    array('property' => 'background-color', 'key' => 'product_card_bg_color'),
                    array('property' => 'border-color',     'key' => 'product_card_border_color'),
                    array('property' => 'border-radius',    'key' => 'product_card_radius'),
                    array('property' => 'padding-x',        'key' => 'product_card_padding_x'),
                    array('property' => 'padding-y',        'key' => 'product_card_padding_y'),
                ),
            ),
            'title' => array(
                'label'      => 'Product Title',
                'properties' => array(
                    array('property' => 'font-size',   'key' => 'products_title_size'),
                    array('property' => 'font-weight', 'key' => 'products_title_weight'),
                    array('property' => 'text-color',  'key' => 'products_title_color'),
                ),
            ),
            'price' => array(
                'label'      => 'Price',
                'properties' => array(
                    array('property' => 'font-size',  'key' => 'products_price_size'),
                    array('property' => 'text-color', 'key' => 'products_price_color'),
                ),
            ),
            'button' => array(
                'label'      => 'Add to Cart Button',
                'properties' => array(
                    array('property' => 'background-color', 'key' => 'product_button_bg_color'),
                    array('property' => 'text-color',       'key' => 'product_button_text_color'),
                    array('property' => 'border-radius',    'key' => 'product_button_radius'),
                    array('property' => 'font-size',        'key' => 'product_button_font_size'),
                ),
            ),
            'grid' => array(
                'label'      => 'Grid',
                'properties' => array(
                    array('property' => 'gap', 'key' => 'products_gap'),
                ),
            ),
        ),
    ),

    'blog' => array(
        'parts' => array(
            'card' => array(
                'label'      => 'Post Card',
                'properties' => array(
                    array('property' => 'background-color', 'key' => 'blog_card_bg_color'),
                    array('property' => 'border-color',     'key' => 'blog_card_border_color'),
                    array('property' => 'border-radius',    'key' => 'blog_card_radius'),
                ),
            ),
            'title' => array(
                'label'      => 'Post Title',
                'properties' => array(
                    array('property' => 'font-size',   'key' => 'blog_title_size'),
                    array('property' => 'font-weight', 'key' => 'blog_title_weight'),
                    array('property' => 'text-color',  'key' => 'blog_title_color'),
                ),
            ),
            'grid' => array(
                'label'      => 'Grid',
                'properties' => array(
                    array('property' => 'gap', 'key' => 'blog_gap'),
                ),
            ),
        ),
    ),

    'navigation' => array(
        'parts' => array(
            'links' => array(
                'label'      => 'Menu Links',
                'properties' => array(
                    array('property' => 'font-size',       'key' => 'nav_font_size'),
                    array('property' => 'font-weight',     'key' => 'nav_font_weight'),
                    array('property' => 'link-color',      'key' => 'nav_link_color'),
                    array('property' => 'link-hover-color', 'key' => 'nav_link_hover_color'),
                    array('property' => 'text-transform',  'key' => 'nav_link_transform'),
                ),
            ),
        ),
    ),

    'collections' => array(
        'parts' => array(
            'card' => array(
                'label'      => 'Collection Card',
                'properties' => array(
                    array('property' => 'background-color', 'key' => 'collection_card_bg_color'),
                    array('property' => 'border-radius',    'key' => 'collection_card_radius'),
                ),
            ),
            'title' => array(
                'label'      => 'Collection Title',
                'properties' => array(
                    array('property' => 'font-size',  'key' => 'collection_title_size'),
                    array('property' => 'text-color', 'key' => 'collection_title_color'),
                ),
            ),
            'grid' => array(
                'label'      => 'Grid',
                'properties' => array(
                    array('property' => 'gap', 'key' => 'collections_gap'),
                ),
            ),
        ),
    ),

    'testimonials' => array(
        'parts' => array(
            'quote' => array(
                'label'      => 'Quote',
                'properties' => array(
                    array('property' => 'font-size',   'key' => 'testimonials_quote_size'),
                    array('property' => 'text-color',  'key' => 'testimonials_quote_color'),
                    array('property' => 'line-height', 'key' => 'testimonials_quote_line_height'),
                ),
            ),
            'author' => array(
                'label'      => 'Author',
                'properties' => array(
                    array('property' => 'font-size',  'key' => 'testimonials_author_size'),
                    array('property' => 'text-color', 'key' => 'testimonials_author_color'),
                ),
            ),
        ),
    ),

    'announcement' => array(
        'parts' => array(
            'background' => array(
                'label'      => 'Background',
                'properties' => array(
                    array('property' => 'background-color', 'key' => 'announcement_bg_color'),
                ),
            ),
            'text' => array(
                'label'      => 'Text',
                'properties' => array(
                    array('property' => 'text-color',  'key' => 'announcement_text_color'),
                    array('property' => 'font-size',   'key' => 'announcement_text_size'),
                    array('property' => 'font-weight', 'key' => 'announcement_text_weight'),
                ),
            ),
        ),
    ),

    'cart-icon' => array(
        'parts' => array(
            'icon' => array(
                'label'      => 'Cart Icon',
                'properties' => array(
                    array('property' => 'icon-color',   'key' => 'cart_icon_color'),
                    array('property' => 'text-color',   'key' => 'cart_badge_bg_color', 'label' => 'Badge Background'),
                ),
            ),
        ),
    ),

    'logo' => array(
        'parts' => array(
            'image' => array(
                'label'      => 'Logo Image',
                'properties' => array(
                    array('property' => 'logo-image', 'key' => 'logo_image'),
                ),
            ),
            'text' => array(
                'label'      => 'Logo Text',
                'properties' => array(
                    array('property' => 'font-size',   'key' => 'logo_text_size'),
                    array('property' => 'font-weight', 'key' => 'logo_text_weight'),
                    array('property' => 'text-color',  'key' => 'logo_text_color'),
                ),
            ),
        ),
    ),

    'blog-preview' => array(
        'parts' => array(
            'heading' => array(
                'label'      => 'Section Heading',
                'properties' => array(
                    array('property' => 'font-size',  'key' => 'blog_preview_title_size'),
                    array('property' => 'text-color', 'key' => 'blog_preview_title_color'),
                ),
            ),
            'grid' => array(
                'label'      => 'Grid',
                'properties' => array(
                    array('property' => 'gap',       'key' => 'blog_preview_gap'),
                    array('property' => 'padding-y', 'key' => 'blog_preview_padding_y'),
                ),
            ),
        ),
    ),

    'copyright' => array(
        'parts' => array(
            'text' => array(
                'label'      => 'Copyright Text',
                'properties' => array(
                    array('property' => 'font-size',  'key' => 'copyright_font_size'),
                    array('property' => 'text-color', 'key' => 'copyright_text_color'),
                ),
            ),
        ),
    ),

    'page' => array(
        'parts' => array(
            'background' => array(
                'label'      => 'Page Background',
                'properties' => array(
                    array('property' => 'background-color', 'key' => 'page_bg_color'),
                ),
            ),
        ),
    ),
);
