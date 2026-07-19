<?php
declare(strict_types=1);

namespace PhantomCore\Admin;

use PhantomCore\Settings_Registry;

defined( 'ABSPATH' ) || exit;

class Settings_Page {

	private static ?Settings_Page $instance = null;

	private array $entries   = array();
	private array $tabs      = array();
	private array $grouped   = array();

	final public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function init(): void {
		$this->entries = Settings_Registry::get_instance()->get_entries();
		$this->tabs    = $this->define_tabs();

		foreach ( $this->tabs as $tab_id => &$tab ) {
			$tab['fields'] = array();
		}
		unset( $tab );

		foreach ( $this->entries as $key => $entry ) {
			$section = $entry['section'] ?? '';
			foreach ( $this->tabs as $tab_id => &$tab ) {
				if ( in_array( $section, $tab['sections'], true ) ) {
					$tab['fields'][ $key ] = $entry;
					break;
				}
			}
		}
		unset( $tab );

		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	public function add_admin_menu(): void {
		$hook = add_theme_page(
			__( 'Phantom Core Settings', 'phantom-core' ),
			__( 'Phantom Core', 'phantom-core' ),
			'manage_options',
			'phantom-core',
			array( $this, 'render_page' )
		);
	}

	public function enqueue_assets( string $hook ): void {
		if ( 'appearance_page_phantom-core' !== $hook ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_media();
		wp_enqueue_code_editor( array( 'type' => 'text/html' ) );

		wp_enqueue_style(
			'phantom-core-admin',
			PHANTOM_CORE_URL . 'admin/css/admin.css',
			array( 'wp-color-picker' ),
			PHANTOM_CORE_VERSION
		);

		wp_enqueue_script(
			'phantom-core-admin',
			PHANTOM_CORE_URL . 'admin/js/admin.js',
			array( 'jquery', 'wp-color-picker', 'jquery-ui-sortable' ),
			PHANTOM_CORE_VERSION,
			true
		);

		wp_localize_script(
			'phantom-core-admin',
			'phantomCoreAdmin',
			array(
				'mediaTitle'  => __( 'Choose Image', 'phantom-core' ),
				'mediaButton' => __( 'Use Image', 'phantom-core' ),
				'addRow'      => __( 'Add Row', 'phantom-core' ),
				'removeRow'   => __( 'Remove', 'phantom-core' ),
			)
		);
	}

	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions.', 'phantom-core' ) );
		}

		$this->handle_submission();

		$active_tab = sanitize_key( wp_unslash( $_GET['tab'] ?? 'branding' ) );
		if ( ! isset( $this->tabs[ $active_tab ] ) ) {
			$active_tab = array_key_first( $this->tabs );
		}

		?>
		<div class="wrap phantom-core-wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<?php settings_errors( 'phantom_core_messages' ); ?>
			<h2 class="nav-tab-wrapper">
				<?php foreach ( $this->tabs as $tab_id => $tab ) : ?>
					<a href="?page=phantom-core&tab=<?php echo esc_attr( $tab_id ); ?>"
					   class="nav-tab<?php echo $active_tab === $tab_id ? ' nav-tab-active' : ''; ?>">
						<?php echo esc_html( $tab['label'] ); ?>
					</a>
				<?php endforeach; ?>
			</h2>
			<form method="post" action="" class="phantom-core-form">
				<?php wp_nonce_field( 'phantom_core_save', 'phantom_core_nonce' ); ?>
				<input type="hidden" name="action" value="phantom_core_save" />
				<input type="hidden" name="active_tab" value="<?php echo esc_attr( $active_tab ); ?>" />
				<table class="form-table">
					<tbody>
						<?php $this->render_tab_fields( $active_tab ); ?>
					</tbody>
				</table>
				<p class="submit">
					<button type="submit" class="button button-primary">
						<?php esc_html_e( 'Save Settings', 'phantom-core' ); ?>
					</button>
				</p>
			</form>
		</div>
		<?php
	}

	private function render_tab_fields( string $tab_id ): void {
		$fields = $this->tabs[ $tab_id ]['fields'] ?? array();
		if ( empty( $fields ) ) {
			echo '<tr><td colspan="2"><p><em>';
			esc_html_e( 'No settings available for this section.', 'phantom-core' );
			echo '</em></p></td></tr>';
			return;
		}

		$registry = Settings_Registry::get_instance();

		foreach ( $fields as $key => $entry ) {
			$value = $registry->has( $key ) ? $registry->get( $key ) : ( $entry['default'] ?? '' );
			$this->render_field_row( $key, $entry, $value );
		}
	}

	private function render_field_row( string $key, array $entry, mixed $value ): void {
		$type        = $entry['type'] ?? 'string';
		$label       = $entry['label'] ?? $key;
		$desc        = $entry['desc'] ?? '';
		$depend      = $entry['dependencies'] ?? array();
		$data_attr   = '';

		if ( ! empty( $depend ) && is_array( $depend ) ) {
			$parts = array();
			foreach ( $depend as $dep_entry ) {
				if ( is_array( $dep_entry ) && isset( $dep_entry['key'] ) ) {
					$parts[] = 'data-depend-on="' . esc_attr( $dep_entry['key'] ) . '"';
					$parts[] = 'data-depend-value="' . esc_attr( is_array( $dep_entry['value'] ) ? implode( ',', $dep_entry['value'] ) : (string) $dep_entry['value'] ) . '"';
				}
			}
			$data_attr = implode( ' ', $parts );
		}

		$hidden_class = ! empty( $depend ) ? 'phantom-core-dependent' : '';
		?>
		<tr class="phantom-core-field-row <?php echo esc_attr( $hidden_class ); ?>" <?php echo $data_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<th scope="row">
				<label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>
			</th>
			<td>
				<?php $this->render_field_input( $type, $key, $entry, $value ); ?>
				<?php if ( $desc ) : ?>
					<p class="description"><?php echo wp_kses_post( $desc ); ?></p>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	private function render_field_input( string $type, string $key, array $entry, mixed $value ): void {
		switch ( $type ) {
			case 'text':
				$this->render_textarea( $key, $entry, $value );
				break;
			case 'bool':
				$this->render_checkbox( $key, $entry, $value );
				break;
			case 'int':
			case 'number':
				$this->render_number( $key, $entry, $value );
				break;
			case 'float':
				$this->render_float( $key, $entry, $value );
				break;
			case 'color':
				$this->render_color( $key, $entry, $value );
				break;
			case 'select':
				$this->render_select( $key, $entry, $value );
				break;
			case 'multiselect':
				$this->render_multiselect( $key, $entry, $value );
				break;
			case 'image':
				$this->render_image( $key, $entry, $value );
				break;
			case 'code':
				$this->render_code( $key, $entry, $value );
				break;
			case 'repeater':
				$this->render_repeater( $key, $entry, $value );
				break;
			case 'array':
				$this->render_array_field( $key, $entry, $value );
				break;
			case 'string':
			default:
				$this->render_string( $key, $entry, $value );
				break;
		}
	}

	private function render_string( string $key, array $entry, mixed $value ): void {
		$placeholder = $entry['placeholder'] ?? '';
		?>
		<input type="text"
			   id="<?php echo esc_attr( $key ); ?>"
			   name="phantom_core[<?php echo esc_attr( $key ); ?>]"
			   value="<?php echo esc_attr( (string) $value ); ?>"
			   placeholder="<?php echo esc_attr( $placeholder ); ?>"
			   class="regular-text" />
		<?php
	}

	private function render_textarea( string $key, array $entry, mixed $value ): void {
		$placeholder = $entry['placeholder'] ?? '';
		$rows        = $entry['rows'] ?? 5;
		?>
		<textarea id="<?php echo esc_attr( $key ); ?>"
				  name="phantom_core[<?php echo esc_attr( $key ); ?>]"
				  placeholder="<?php echo esc_attr( $placeholder ); ?>"
				  class="large-text"
				  rows="<?php echo absint( $rows ); ?>"><?php echo esc_textarea( (string) $value ); ?></textarea>
		<?php
	}

	private function render_checkbox( string $key, array $entry, mixed $value ): void {
		?>
		<label>
			<input type="hidden" name="phantom_core[<?php echo esc_attr( $key ); ?>]" value="0" />
			<input type="checkbox"
				   id="<?php echo esc_attr( $key ); ?>"
				   name="phantom_core[<?php echo esc_attr( $key ); ?>]"
				   value="1" <?php checked( 1, (int) $value ); ?> />
			<?php echo esc_html( $entry['checkbox_label'] ?? __( 'Enable', 'phantom-core' ) ); ?>
		</label>
		<?php
	}

	private function render_number( string $key, array $entry, mixed $value ): void {
		$min  = $entry['min'] ?? 0;
		$max  = $entry['max'] ?? 999999;
		$step = $entry['step'] ?? 1;
		?>
		<input type="number"
			   id="<?php echo esc_attr( $key ); ?>"
			   name="phantom_core[<?php echo esc_attr( $key ); ?>]"
			   value="<?php echo esc_attr( (string) $value ); ?>"
			   min="<?php echo esc_attr( (string) $min ); ?>"
			   max="<?php echo esc_attr( (string) $max ); ?>"
			   step="<?php echo esc_attr( (string) $step ); ?>"
			   class="small-text" />
		<?php
	}

	private function render_float( string $key, array $entry, mixed $value ): void {
		$min  = $entry['min'] ?? 0;
		$max  = $entry['max'] ?? 999999;
		$step = $entry['step'] ?? '0.01';
		?>
		<input type="number"
			   id="<?php echo esc_attr( $key ); ?>"
			   name="phantom_core[<?php echo esc_attr( $key ); ?>]"
			   value="<?php echo esc_attr( (string) $value ); ?>"
			   min="<?php echo esc_attr( (string) $min ); ?>"
			   max="<?php echo esc_attr( (string) $max ); ?>"
			   step="<?php echo esc_attr( $step ); ?>"
			   class="small-text" />
		<?php
	}

	private function render_color( string $key, array $entry, mixed $value ): void {
		?>
		<input type="text"
			   id="<?php echo esc_attr( $key ); ?>"
			   name="phantom_core[<?php echo esc_attr( $key ); ?>]"
			   value="<?php echo esc_attr( (string) $value ); ?>"
			   class="phantom-core-color-picker"
			   data-default-color="<?php echo esc_attr( $entry['default'] ?? '#000000' ); ?>" />
		<?php
	}

	private function render_select( string $key, array $entry, mixed $value ): void {
		$options = $entry['options'] ?? array();
		?>
		<select id="<?php echo esc_attr( $key ); ?>"
				name="phantom_core[<?php echo esc_attr( $key ); ?>]">
			<?php foreach ( $options as $opt_val => $opt_label ) : ?>
				<option value="<?php echo esc_attr( $opt_val ); ?>" <?php selected( $value, $opt_val ); ?>>
					<?php echo esc_html( $opt_label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	private function render_multiselect( string $key, array $entry, mixed $value ): void {
		$options = $entry['options'] ?? array();
		$value   = is_array( $value ) ? $value : ( is_string( $value ) ? explode( ',', $value ) : array() );
		$value   = array_map( 'strval', $value );
		?>
		<select id="<?php echo esc_attr( $key ); ?>"
				name="phantom_core[<?php echo esc_attr( $key ); ?>][]"
				multiple="multiple"
				class="phantom-core-multiselect"
				size="<?php echo esc_attr( (string) max( 4, count( $options ) ) ); ?>">
			<?php foreach ( $options as $opt_val => $opt_label ) : ?>
				<option value="<?php echo esc_attr( $opt_val ); ?>" <?php echo in_array( (string) $opt_val, $value, true ) ? 'selected' : ''; ?>>
					<?php echo esc_html( $opt_label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	private function render_image( string $key, array $entry, mixed $value ): void {
		$attachment_id = is_numeric( $value ) ? (int) $value : 0;
		$preview_url   = '';

		if ( $attachment_id > 0 ) {
			$preview_url = wp_get_attachment_image_url( $attachment_id, 'medium' );
		} elseif ( is_string( $value ) && $value ) {
			$preview_url = $value;
		}
		?>
		<div class="phantom-core-image-field">
			<input type="hidden"
				   id="<?php echo esc_attr( $key ); ?>"
				   name="phantom_core[<?php echo esc_attr( $key ); ?>]"
				   value="<?php echo esc_attr( (string) $value ); ?>"
				   class="phantom-core-image-input" />
			<div class="phantom-core-image-preview">
				<?php if ( $preview_url ) : ?>
					<img src="<?php echo esc_url( $preview_url ); ?>" alt="" style="max-width:200px;max-height:150px;" />
				<?php endif; ?>
			</div>
			<div class="phantom-core-image-actions">
				<button type="button" class="button phantom-core-image-upload">
					<?php esc_html_e( 'Choose Image', 'phantom-core' ); ?>
				</button>
				<button type="button" class="button phantom-core-image-remove" <?php echo empty( $value ) ? 'style="display:none"' : ''; ?>>
					<?php esc_html_e( 'Remove', 'phantom-core' ); ?>
				</button>
			</div>
		</div>
		<?php
	}

	private function render_code( string $key, array $entry, mixed $value ): void {
		$mode = $entry['mode'] ?? 'text/html';
		?>
		<textarea id="<?php echo esc_attr( $key ); ?>"
				  name="phantom_core[<?php echo esc_attr( $key ); ?>]"
				  class="phantom-core-code-editor large-text"
				  data-editor-mode="<?php echo esc_attr( $mode ); ?>"
				  rows="10"><?php echo esc_textarea( (string) $value ); ?></textarea>
		<?php
	}

	private function render_repeater( string $key, array $entry, mixed $value ): void {
		$value      = is_array( $value ) ? $value : array();
		$sub_fields = $entry['sub_fields'] ?? $this->infer_repeater_fields( $value );
		$index      = 0;
		?>
		<div class="phantom-core-repeater" data-key="<?php echo esc_attr( $key ); ?>">
			<div class="phantom-core-repeater-rows">
				<?php foreach ( $value as $row ) : ?>
					<?php $this->render_repeater_row( $key, $index, $row, $sub_fields ); ?>
					<?php ++$index; ?>
				<?php endforeach; ?>
			</div>
			<script type="text/html" class="phantom-core-repeater-template">
				<?php $this->render_repeater_row( $key, '{{INDEX}}', array(), $sub_fields ); ?>
			</script>
			<button type="button" class="button phantom-core-repeater-add">
				<?php esc_html_e( 'Add Row', 'phantom-core' ); ?>
			</button>
		</div>
		<?php
	}

	private function render_repeater_row( string $key, int|string $index, array $row, array $sub_fields ): void {
		?>
		<div class="phantom-core-repeater-row">
			<div class="phantom-core-repeater-fields">
				<?php foreach ( $sub_fields as $sf_key => $sf_config ) : ?>
					<?php
					$sf_type  = $sf_config['type'] ?? 'string';
					$sf_label = $sf_config['label'] ?? $sf_key;
					$sf_value = $row[ $sf_key ] ?? $sf_config['default'] ?? '';
					$sf_name  = "phantom_core[{$key}][{$index}][{$sf_key}]";
					$sf_id    = "{$key}_{$index}_{$sf_key}";
					?>
					<div class="phantom-core-repeater-field">
						<label for="<?php echo esc_attr( $sf_id ); ?>" class="phantom-core-repeater-field-label">
							<?php echo esc_html( $sf_label ); ?>
						</label>
						<?php $this->render_repeater_subfield( $sf_type, $sf_name, $sf_id, $sf_value, $sf_config ); ?>
					</div>
				<?php endforeach; ?>
			</div>
			<button type="button" class="button phantom-core-repeater-remove">
				<span class="dashicons dashicons-no"></span>
				<?php esc_html_e( 'Remove', 'phantom-core' ); ?>
			</button>
		</div>
		<?php
	}

	private function render_repeater_subfield( string $type, string $name, string $id, mixed $value, array $config ): void {
		switch ( $type ) {
			case 'bool':
				?>
				<label>
					<input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="0" />
					<input type="checkbox"
						   id="<?php echo esc_attr( $id ); ?>"
						   name="<?php echo esc_attr( $name ); ?>"
						   value="1" <?php checked( 1, (int) $value ); ?> />
				</label>
				<?php
				break;
			case 'select':
				$options = $config['options'] ?? array();
				?>
				<select id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>">
					<?php foreach ( $options as $opt_val => $opt_label ) : ?>
						<option value="<?php echo esc_attr( $opt_val ); ?>" <?php selected( $value, $opt_val ); ?>>
							<?php echo esc_html( $opt_label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<?php
				break;
			case 'color':
				?>
				<input type="text"
					   id="<?php echo esc_attr( $id ); ?>"
					   name="<?php echo esc_attr( $name ); ?>"
					   value="<?php echo esc_attr( (string) $value ); ?>"
					   class="phantom-core-color-picker" />
				<?php
				break;
			case 'text':
				?>
				<textarea id="<?php echo esc_attr( $id ); ?>"
						  name="<?php echo esc_attr( $name ); ?>"
						  class="large-text"
						  rows="3"><?php echo esc_textarea( (string) $value ); ?></textarea>
				<?php
				break;
			case 'image':
				?>
				<div class="phantom-core-repeater-image">
					<input type="text"
						   id="<?php echo esc_attr( $id ); ?>"
						   name="<?php echo esc_attr( $name ); ?>"
						   value="<?php echo esc_attr( (string) $value ); ?>"
						   class="regular-text" />
					<button type="button" class="button phantom-core-image-upload-small">
						<?php esc_html_e( 'Upload', 'phantom-core' ); ?>
					</button>
				</div>
				<?php
				break;
			default:
				?>
				<input type="text"
					   id="<?php echo esc_attr( $id ); ?>"
					   name="<?php echo esc_attr( $name ); ?>"
					   value="<?php echo esc_attr( (string) $value ); ?>"
					   class="regular-text" />
				<?php
				break;
		}
	}

	private function infer_repeater_fields( array $rows ): array {
		if ( empty( $rows ) ) {
			return array(
				'field' => array(
					'type'    => 'string',
					'label'   => __( 'Value', 'phantom-core' ),
					'default' => '',
				),
			);
		}
		$fields   = array();
		$template = $rows[0] ?? array();
		foreach ( $template as $tk => $tv ) {
			$type = 'string';
			if ( is_bool( $tv ) || ( is_numeric( $tv ) && in_array( (string) $tv, array( '0', '1' ), true ) ) ) {
				$type = 'bool';
			} elseif ( is_numeric( $tv ) && is_float( $tv ) ) {
				$type = 'float';
			} elseif ( is_numeric( $tv ) ) {
				$type = 'int';
			}
			$fields[ $tk ] = array(
				'type'    => $type,
				'label'   => ucwords( str_replace( array( '_', '-' ), ' ', $tk ) ),
				'default' => $tv,
			);
		}
		return $fields;
	}

	private function render_array_field( string $key, array $entry, mixed $value ): void {
		$json = is_array( $value ) ? wp_json_encode( $value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) : (string) $value;
		?>
		<textarea id="<?php echo esc_attr( $key ); ?>"
				  name="phantom_core[<?php echo esc_attr( $key ); ?>]"
				  class="phantom-core-code-editor large-text"
				  data-editor-mode="application/json"
				  rows="8"><?php echo esc_textarea( $json ); ?></textarea>
		<?php
	}

	private function handle_submission(): void {
		if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
			return;
		}

		if ( ! isset( $_POST['phantom_core_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['phantom_core_nonce'] ), 'phantom_core_save' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'phantom-core' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'phantom-core' ) );
		}

		if ( ! isset( $_POST['phantom_core'] ) || ! is_array( $_POST['phantom_core'] ) ) {
			return;
		}

		$raw      = wp_unslash( $_POST['phantom_core'] );
		$registry = Settings_Registry::get_instance();
		$changed  = false;

		foreach ( $this->entries as $key => $entry ) {
			$sanitize = $entry['sanitize'] ?? 'sanitize_text_field';
			$type     = $entry['type'] ?? 'string';

			if ( 'bool' === $type ) {
				$new_value = isset( $raw[ $key ] ) ? 1 : 0;
				$registry->set( $key, $new_value );
				$changed = true;
				continue;
			}

			if ( 'multiselect' === $type ) {
				$new_value = isset( $raw[ $key ] ) && is_array( $raw[ $key ] )
					? array_map( 'sanitize_text_field', $raw[ $key ] )
					: array();
				$registry->set( $key, $new_value );
				$changed = true;
				continue;
			}

			if ( 'repeater' === $type ) {
				if ( isset( $raw[ $key ] ) && is_array( $raw[ $key ] ) ) {
					$new_value = $this->sanitize_repeater( $raw[ $key ], $entry );
				} else {
					$new_value = array();
				}
				$registry->set( $key, $new_value );
				$changed = true;
				continue;
			}

			if ( 'array' === $type ) {
				if ( isset( $raw[ $key ] ) && is_string( $raw[ $key ] ) ) {
					$decoded = json_decode( $raw[ $key ], true );
					$new_value = is_array( $decoded ) ? $decoded : array();
				} else {
					$new_value = array();
				}
				$registry->set( $key, $new_value );
				$changed = true;
				continue;
			}

			if ( ! isset( $raw[ $key ] ) ) {
				continue;
			}

			$raw_value = $raw[ $key ];

			if ( is_string( $sanitize ) && function_exists( $sanitize ) ) {
				if ( 'wp_kses_post' === $sanitize ) {
					$new_value = wp_kses_post( (string) $raw_value );
				} elseif ( 'esc_url_raw' === $sanitize ) {
					$new_value = esc_url_raw( (string) $raw_value );
				} elseif ( 'sanitize_hex_color' === $sanitize ) {
					$new_value = sanitize_hex_color( (string) $raw_value ) ? sanitize_hex_color( (string) $raw_value ) : ( $entry['default'] ?? '' );
				} elseif ( 'absint' === $sanitize ) {
					$new_value = absint( $raw_value );
				} elseif ( 'floatval' === $sanitize || 'float' === $type ) {
					$new_value = floatval( $raw_value );
				} else {
					$new_value = is_string( $raw_value ) ? call_user_func( $sanitize, $raw_value ) : sanitize_text_field( (string) $raw_value );
				}
			} elseif ( is_callable( $sanitize ) ) {
				$new_value = is_string( $raw_value ) ? call_user_func( $sanitize, $raw_value ) : sanitize_text_field( (string) $raw_value );
			} else {
				$new_value = sanitize_text_field( (string) $raw_value );
			}

			$registry->set( $key, $new_value );
			$changed = true;
		}

		if ( $changed ) {
			\PhantomCore\Customizer::get_instance()->sync_options();
			add_settings_error(
				'phantom_core_messages',
				'settings_updated',
				__( 'Settings saved.', 'phantom-core' ),
				'success'
			);
		}
	}

	private function sanitize_repeater( array $rows, array $entry ): array {
		$sanitized = array();
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$clean = array();
			foreach ( $row as $rk => $rv ) {
				if ( is_string( $rv ) ) {
					$clean[ $rk ] = sanitize_text_field( $rv );
				} elseif ( is_numeric( $rv ) ) {
					$clean[ $rk ] = $rv + 0;
				} else {
					$clean[ $rk ] = sanitize_text_field( (string) $rv );
				}
			}
			$sanitized[] = $clean;
		}
		return $sanitized;
	}

	private function define_tabs(): array {
		return array(
			'branding'      => array(
				'label'    => __( 'Branding', 'phantom-core' ),
				'sections' => array( 'branding' ),
			),
			'header'        => array(
				'label'    => __( 'Header', 'phantom-core' ),
				'sections' => array( 'header', 'topbar', 'navigation', 'announcement_bar' ),
			),
			'hero'          => array(
				'label'    => __( 'Hero & Home', 'phantom-core' ),
				'sections' => array( 'hero', 'home_sections', 'collections' ),
			),
			'product_cards' => array(
				'label'    => __( 'Product Cards', 'phantom-core' ),
				'sections' => array( 'product_cards' ),
			),
			'shop'          => array(
				'label'    => __( 'Shop', 'phantom-core' ),
				'sections' => array( 'shop_page', 'product_page' ),
			),
			'blog'          => array(
				'label'    => __( 'Blog', 'phantom-core' ),
				'sections' => array( 'blog' ),
			),
			'footer'        => array(
				'label'    => __( 'Footer', 'phantom-core' ),
				'sections' => array( 'footer' ),
			),
			'typography'    => array(
				'label'    => __( 'Typography', 'phantom-core' ),
				'sections' => array( 'typography' ),
			),
			'colors'        => array(
				'label'    => __( 'Colors', 'phantom-core' ),
				'sections' => array( 'colors', 'buttons', 'forms', 'spacing' ),
			),
			'layout'        => array(
				'label'    => __( 'Layout', 'phantom-core' ),
				'sections' => array( 'layout', 'responsive', 'animations', 'effects_3d' ),
			),
			'search'        => array(
				'label'    => __( 'Search', 'phantom-core' ),
				'sections' => array( 'search' ),
			),
			'performance'   => array(
				'label'    => __( 'Performance', 'phantom-core' ),
				'sections' => array( 'performance', 'seo' ),
			),
			'accessibility' => array(
				'label'    => __( 'Accessibility', 'phantom-core' ),
				'sections' => array( 'accessibility' ),
			),
			'advanced'      => array(
				'label'    => __( 'Advanced', 'phantom-core' ),
				'sections' => array( 'integrations', 'custom_code', 'import_export' ),
			),
			'pages'         => array(
				'label'    => __( 'Pages', 'phantom-core' ),
				'sections' => array(
					'about_page',
					'contact_page',
					'faq_page',
					'coming_soon',
					'error_404',
					'login_page',
					'register_page',
					'portfolio',
					'thank_you',
					'load_more',
					'privacy',
					'terms',
					'team',
					'testimonials',
				),
			),
		);
	}
}
