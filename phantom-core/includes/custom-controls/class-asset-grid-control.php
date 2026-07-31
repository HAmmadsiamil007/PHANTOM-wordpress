<?php
declare(strict_types=1);

namespace PhantomCore\Customizer\Controls;

defined( 'ABSPATH' ) || exit;

/**
 * Asset_Grid_Control — "Visual Assets" grid in the Customizer sidebar.
 *
 * Renders one row per display asset (from Media_Asset_Registry) with a live
 * preview plus Upload / Reset buttons. Changes are persisted immediately via
 * the /phantom/v1/assets REST routes, so no Customizer setting is attached.
 */
class Asset_Grid_Control extends Control_Base {

	public $type = 'ast-asset-grid';

	public static function get_type(): string {
		return 'ast-asset-grid';
	}

	public static function get_sanitize_callback(): callable {
		return static function ( $value ) {
			return sanitize_text_field( (string) $value );
		};
	}

	public function render_content(): void {
		$items = $this->collect_assets();

		if ( empty( $items ) ) {
			echo '<p class="description">' . esc_html__( 'No assets registered.', 'phantom-core' ) . '</p>';
			return;
		}

		echo '<div class="phantom-asset-grid">';
		foreach ( $items as $item ) {
			$key = $item['key'];
			echo '<div class="vc-asset-row" data-asset="' . esc_attr( $key ) . '">';
			echo '<div class="vc-asset-info">';
			echo '<span class="vc-asset-label">' . esc_html( $item['label'] ) . '</span>';
			if ( $item['url'] ) {
				echo '<img class="vc-asset-preview" src="' . esc_url( $item['url'] ) . '" alt="' . esc_attr( $item['label'] ) . '" data-default="' . esc_url( $item['default'] ) . '" onerror="this.onerror=null;this.src=this.getAttribute(\'data-default\');">';
			} else {
				echo '<span class="vc-asset-preview vc-asset-preview-empty">' . esc_html__( 'Default', 'phantom-core' ) . '</span>';
			}
			echo '</div>';
			echo '<div class="vc-asset-actions">';
			echo '<button type="button" class="button button-small vc-btn-upload" data-asset="' . esc_attr( $key ) . '">' . esc_html__( 'Upload', 'phantom-core' ) . '</button>';
			echo '<button type="button" class="button button-small vc-btn-reset" data-asset="' . esc_attr( $key ) . '"' . ( $item['has_custom'] ? '' : ' disabled' ) . '>' . esc_html__( 'Reset', 'phantom-core' ) . '</button>';
			echo '</div>';
			echo '</div>';
		}
		echo '</div>';
	}

	public function to_json(): void {
		parent::to_json();
		$this->json['assets'] = $this->collect_assets();
	}

	public function content_template(): void {
		?>
		<div class="phantom-asset-grid">
			<# if ( data.assets && data.assets.length ) { #>
				<# _.each( data.assets, function( asset ) { #>
					<div class="vc-asset-row" data-asset="{{ asset.key }}">
						<div class="vc-asset-info">
							<span class="vc-asset-label">{{ asset.label }}</span>
							<# if ( asset.url ) { #>
								<img class="vc-asset-preview" src="{{ asset.url }}" alt="{{ asset.label }}" data-default="{{ asset.default }}" onerror="this.onerror=null;this.src=this.getAttribute('data-default');">
							<# } else { #>
								<span class="vc-asset-preview vc-asset-preview-empty">Default</span>
							<# } #>
						</div>
						<div class="vc-asset-actions">
							<button type="button" class="button button-small vc-btn-upload" data-asset="{{ asset.key }}">Upload</button>
							<button type="button" class="button button-small vc-btn-reset" data-asset="{{ asset.key }}"<# if ( ! asset.has_custom ) { #> disabled<# } #>>Reset</button>
						</div>
					</div>
				<# } ); #>
			<# } else { #>
				<p class="description">No assets registered.</p>
			<# } #>
		</div>
		<?php
	}

	private function collect_assets(): array {
		if ( ! class_exists( '\PhantomCore\Components\Media_Asset_Registry' ) ) {
			return array();
		}

		$registry = \PhantomCore\Components\Media_Asset_Registry::get_instance();
		$registry->register_defaults();
		$assets   = $registry->get_display_assets();
		$uploaded = get_option( 'phantom_assets', array() );
		$items    = array();

		foreach ( $assets as $key => $label ) {
			$asset   = $registry->get( $key );
			$default = $asset ? $asset->default : '';
			$items[] = array(
				'key'        => $key,
				'label'      => $label,
				'url'        => $registry->get_url( $key ),
				'default'    => $default,
				'has_custom' => ! empty( $uploaded[ $key ] ),
			);
		}

		return $items;
	}
}
