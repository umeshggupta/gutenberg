<?php
/**
 * WP_Theme_JSON_Gutenberg class
 *
 * @package gutenberg
 */

/**
 * Class that encapsulates the processing of structures that adhere to the theme.json spec.
 *
 * This class is for internal core usage and is not supposed to be used by extenders (plugins and/or themes).
 * This is a low-level API that may need to do breaking changes. Please,
 * use get_global_settings, get_global_styles, and get_global_stylesheet instead.
 *
 * @access private
 */
class WP_Theme_JSON_Gutenberg extends WP_Theme_JSON_5_9 {
	/**
	 * Presets are a set of values that serve
	 * to bootstrap some styles: colors, font sizes, etc.
	 *
	 * They are a unkeyed array of values such as:
	 *
	 * ```php
	 * array(
	 *   array(
	 *     'slug'      => 'unique-name-within-the-set',
	 *     'name'      => 'Name for the UI',
	 *     <value_key> => 'value'
	 *   ),
	 * )
	 * ```
	 *
	 * This contains the necessary metadata to process them:
	 *
	 * - path       => where to find the preset within the settings section
	 * - override   => whether a theme preset with the same slug as a default preset
	 *                 can override it
	 * - value_key  => the key that represents the value
	 * - value_func => optionally, instead of value_key, a function to generate
	 *                 the value that takes a preset as an argument
	 *                 (either value_key or value_func should be present)
	 * - css_vars   => template string to use in generating the CSS Custom Property.
	 *                 Example output: "--wp--preset--duotone--blue: <value>" will generate as many CSS Custom Properties as presets defined
	 *                 substituting the $slug for the slug's value for each preset value.
	 * - classes    => array containing a structure with the classes to
	 *                 generate for the presets, where for each array item
	 *                 the key is the class name and the value the property name.
	 *                 The "$slug" substring will be replaced by the slug of each preset.
	 *                 For example:
	 *                 'classes' => array(
	 *                   '.has-$slug-color'            => 'color',
	 *                   '.has-$slug-background-color' => 'background-color',
	 *                   '.has-$slug-border-color'     => 'border-color',
	 *                 )
	 * - properties => array of CSS properties to be used by kses to
	 *                 validate the content of each preset
	 *                 by means of the remove_insecure_properties method.
	 */
	const PRESETS_METADATA = array(
		array(
			'path'                => array( 'color', 'palette' ),
			'prevent_override'    => array( 'color', 'defaultPalette' ),
			'use_default_presets' => array( 'color', 'defaultPalette' ),
			'use_default_names'   => false,
			'value_key'           => 'color',
			'css_vars'            => '--wp--preset--color--$slug',
			'classes'             => array(
				'.has-$slug-color'               => 'color',
				'.has-$slug-background-color'    => 'background-color',
				'.has-$slug-border-color'        => 'border-color',
				'.has-$slug-border-top-color'    => 'border-top-color',
				'.has-$slug-border-right-color'  => 'border-right-color',
				'.has-$slug-border-bottom-color' => 'border-bottom-color',
				'.has-$slug-border-left-color'   => 'border-left-color',
			),
			'properties'          => array( 'color', 'background-color', 'border-color' ),
		),
		array(
			'path'                => array( 'color', 'gradients' ),
			'prevent_override'    => array( 'color', 'defaultGradients' ),
			'use_default_presets' => array( 'color', 'defaultGradients' ),
			'use_default_names'   => false,
			'value_key'           => 'gradient',
			'css_vars'            => '--wp--preset--gradient--$slug',
			'classes'             => array( '.has-$slug-gradient-background' => 'background' ),
			'properties'          => array( 'background' ),
		),
		array(
			'path'                => array( 'color', 'duotone' ),
			'prevent_override'    => array( 'color', 'defaultDuotone' ),
			'use_default_presets' => array( 'color', 'defaultDuotone' ),
			'use_default_names'   => false,
			'value_func'          => 'gutenberg_get_duotone_filter_property',
			'css_vars'            => '--wp--preset--duotone--$slug',
			'classes'             => array(),
			'properties'          => array( 'filter' ),
		),
		array(
			'path'                => array( 'typography', 'fontSizes' ),
			'prevent_override'    => false,
			'use_default_presets' => true,
			'use_default_names'   => true,
			'value_key'           => 'size',
			'css_vars'            => '--wp--preset--font-size--$slug',
			'classes'             => array( '.has-$slug-font-size' => 'font-size' ),
			'properties'          => array( 'font-size' ),
		),
		array(
			'path'                => array( 'typography', 'fontFamilies' ),
			'prevent_override'    => false,
			'use_default_presets' => true,
			'use_default_names'   => false,
			'value_key'           => 'fontFamily',
			'css_vars'            => '--wp--preset--font-family--$slug',
			'classes'             => array( '.has-$slug-font-family' => 'font-family' ),
			'properties'          => array( 'font-family' ),
		),
	);

	/**
	 * Metadata for style properties.
	 *
	 * Each element is a direct mapping from the CSS property name to the
	 * path to the value in theme.json & block attributes.
	 */
	const PROPERTIES_METADATA = array(
		'background'                 => array( 'color', 'gradient' ),
		'background-color'           => array( 'color', 'background' ),
		'border-radius'              => array( 'border', 'radius' ),
		'border-top-left-radius'     => array( 'border', 'radius', 'topLeft' ),
		'border-top-right-radius'    => array( 'border', 'radius', 'topRight' ),
		'border-bottom-left-radius'  => array( 'border', 'radius', 'bottomLeft' ),
		'border-bottom-right-radius' => array( 'border', 'radius', 'bottomRight' ),
		'border-color'               => array( 'border', 'color' ),
		'border-width'               => array( 'border', 'width' ),
		'border-style'               => array( 'border', 'style' ),
		'border-top-color'           => array( 'border', 'top', 'color' ),
		'border-top-width'           => array( 'border', 'top', 'width' ),
		'border-top-style'           => array( 'border', 'top', 'style' ),
		'border-right-color'         => array( 'border', 'right', 'color' ),
		'border-right-width'         => array( 'border', 'right', 'width' ),
		'border-right-style'         => array( 'border', 'right', 'style' ),
		'border-bottom-color'        => array( 'border', 'bottom', 'color' ),
		'border-bottom-width'        => array( 'border', 'bottom', 'width' ),
		'border-bottom-style'        => array( 'border', 'bottom', 'style' ),
		'border-left-color'          => array( 'border', 'left', 'color' ),
		'border-left-width'          => array( 'border', 'left', 'width' ),
		'border-left-style'          => array( 'border', 'left', 'style' ),
		'color'                      => array( 'color', 'text' ),
		'font-family'                => array( 'typography', 'fontFamily' ),
		'font-size'                  => array( 'typography', 'fontSize' ),
		'font-style'                 => array( 'typography', 'fontStyle' ),
		'font-weight'                => array( 'typography', 'fontWeight' ),
		'letter-spacing'             => array( 'typography', 'letterSpacing' ),
		'line-height'                => array( 'typography', 'lineHeight' ),
		'margin'                     => array( 'spacing', 'margin' ),
		'margin-top'                 => array( 'spacing', 'margin', 'top' ),
		'margin-right'               => array( 'spacing', 'margin', 'right' ),
		'margin-bottom'              => array( 'spacing', 'margin', 'bottom' ),
		'margin-left'                => array( 'spacing', 'margin', 'left' ),
		'padding'                    => array( 'spacing', 'padding' ),
		'padding-top'                => array( 'spacing', 'padding', 'top' ),
		'padding-right'              => array( 'spacing', 'padding', 'right' ),
		'padding-bottom'             => array( 'spacing', 'padding', 'bottom' ),
		'padding-left'               => array( 'spacing', 'padding', 'left' ),
		'--wp--style--block-gap'     => array( 'spacing', 'blockGap' ),
		'text-decoration'            => array( 'typography', 'textDecoration' ),
		'text-transform'             => array( 'typography', 'textTransform' ),
		'filter'                     => array( 'filter', 'duotone' ),
	);

	/**
	 * The top-level keys a theme.json can have.
	 *
	 * @var string[]
	 */
	const VALID_TOP_LEVEL_KEYS = array(
		'customTemplates',
		'patterns',
		'settings',
		'styles',
		'templateParts',
		'version',
	);

	/**
	 * The valid properties under the styles key.
	 *
	 * @var array
	 */
	const VALID_STYLES = array(
		'border'     => array(
			'color'  => null,
			'radius' => null,
			'style'  => null,
			'width'  => null,
			'top'    => null,
			'right'  => null,
			'bottom' => null,
			'left'   => null,
		),
		'color'      => array(
			'background' => null,
			'gradient'   => null,
			'text'       => null,
		),
		'filter'     => array(
			'duotone' => null,
		),
		'spacing'    => array(
			'margin'   => null,
			'padding'  => null,
			'blockGap' => 'top',
		),
		'typography' => array(
			'fontFamily'     => null,
			'fontSize'       => null,
			'fontStyle'      => null,
			'fontWeight'     => null,
			'letterSpacing'  => null,
			'lineHeight'     => null,
			'textDecoration' => null,
			'textTransform'  => null,
		),
	);

	/**
	 * Returns the current theme's wanted patterns(slugs) to be
	 * registered from Pattern Directory.
	 *
	 * @return array
	 */
	public function get_patterns() {
		if ( isset( $this->theme_json['patterns'] ) && is_array( $this->theme_json['patterns'] ) ) {
			return $this->theme_json['patterns'];
		}
		return array();
	}

	/**
	 * Converts each style section into a list of rulesets
	 * containing the block styles to be appended to the stylesheet.
	 *
	 * See glossary at https://developer.mozilla.org/en-US/docs/Web/CSS/Syntax
	 *
	 * For each section this creates a new ruleset such as:
	 *
	 *   block-selector {
	 *     style-property-one: value;
	 *   }
	 *
	 * @param array $style_nodes Nodes with styles.
	 * @return string The new stylesheet.
	 */
	protected function get_block_classes( $style_nodes ) {
		$block_rules = '';

		foreach ( $style_nodes as $metadata ) {
			if ( null === $metadata['selector'] ) {
				continue;
			}

			$node         = _wp_array_get( $this->theme_json, $metadata['path'], array() );
			$selector     = $metadata['selector'];
			$settings     = _wp_array_get( $this->theme_json, array( 'settings' ) );
			$declarations = static::compute_style_properties( $node, $settings );

			// 1. Separate the ones who use the general selector
			// and the ones who use the duotone selector.
			$declarations_duotone = array();
			foreach ( $declarations as $index => $declaration ) {
				if ( 'filter' === $declaration['name'] ) {
					unset( $declarations[ $index ] );
					$declarations_duotone[] = $declaration;
				}
			}

			/*
			 * Reset default browser margin on the root body element.
			 * This is set on the root selector **before** generating the ruleset
			 * from the `theme.json`. This is to ensure that if the `theme.json` declares
			 * `margin` in its `spacing` declaration for the `body` element then these
			 * user-generated values take precedence in the CSS cascade.
			 * @link https://github.com/WordPress/gutenberg/issues/36147.
			 */
			if ( static::ROOT_BLOCK_SELECTOR === $selector ) {
				$block_rules .= 'body { margin: 0; }';
			}

			// 2. Generate the rules that use the general selector.
			$block_rules .= static::to_ruleset( $selector, $declarations );

			// 3. Generate the rules that use the duotone selector.
			if ( isset( $metadata['duotone'] ) && ! empty( $declarations_duotone ) ) {
				$selector_duotone = static::scope_selector( $metadata['selector'], $metadata['duotone'] );
				$block_rules     .= static::to_ruleset( $selector_duotone, $declarations_duotone );
			}

			if ( self::ROOT_BLOCK_SELECTOR === $selector ) {
				$block_rules .= '.wp-site-blocks > .alignleft { float: left; margin-right: 2em; }';
				$block_rules .= '.wp-site-blocks > .alignright { float: right; margin-left: 2em; }';
				$block_rules .= '.wp-site-blocks > .aligncenter { justify-content: center; margin-left: auto; margin-right: auto; }';

				$has_block_gap_support = _wp_array_get( $this->theme_json, array( 'settings', 'spacing', 'blockGap' ) ) !== null;
				if ( $has_block_gap_support ) {
					$block_rules .= '.wp-site-blocks > * { margin-block-start: 0; margin-block-end: 0; }';
					$block_rules .= '.wp-site-blocks > * + * { margin-block-start: var( --wp--style--block-gap ); }';
				}
			}
		}

		return $block_rules;
	}

	/**
	 * Returns a valid theme.json for a theme.
	 * Essentially, it flattens the preset data.
	 *
	 * @return array
	 */
	public function get_data() {
		$flattened_theme_json = $this->theme_json;
		$nodes                = static::get_setting_nodes( $this->theme_json );
		foreach ( $nodes as $node ) {
			foreach ( static::PRESETS_METADATA as $preset_metadata ) {
				$path   = array_merge( $node['path'], $preset_metadata['path'] );
				$preset = _wp_array_get( $flattened_theme_json, $path, null );
				if ( null === $preset ) {
					continue;
				}

				$items = array();
				if ( isset( $preset['theme'] ) ) {
					foreach ( $preset['theme'] as $item ) {
						$slug = $item['slug'];
						unset( $item['slug'] );
						$items[ $slug ] = $item;
					}
				}
				if ( isset( $preset['custom'] ) ) {
					foreach ( $preset['custom'] as $item ) {
						$slug = $item['slug'];
						unset( $item['slug'] );
						$items[ $slug ] = $item;
					}
				}
				$flattened_preset = array();
				foreach ( $items as $slug => $value ) {
					$flattened_preset[] = array_merge( array( 'slug' => $slug ), $value );
				}
				_wp_array_set( $flattened_theme_json, $path, $flattened_preset );
			}
		}

		return $flattened_theme_json;
	}

}
