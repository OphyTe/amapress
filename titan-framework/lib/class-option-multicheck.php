<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class TitanFrameworkOptionMulticheck extends TitanFrameworkOption {

	public $defaultSecondarySettings = array(
		'options'           => array(),
		'placeholder'       => '',
		'cache'             => true,
		'custom_csv_sample' => null,
		'wrap_edit'         => true,
		'autoselect_single' => false,
	);

	public function getSamplesForCSV( $arg = null ) {
		if ( is_callable( $this->settings['custom_csv_sample'], false ) ) {
			return call_user_func( $this->settings['custom_csv_sample'], $this, $arg );
		} else if ( is_array( $this->settings['custom_csv_sample'] ) ) {
			return $this->settings['custom_csv_sample'];
		} else {
			return $this->fetchOptionsWithCache();
		}
	}

	private $optionsCache = null;

	public function fetchOptionsWithCache() {
		if ( $this->optionsCache == null || ( isset( $this->settings['cache'] ) && ! $this->settings['cache'] ) ) {
			$this->optionsCache = $this->fetchOptions();
		}

		return $this->optionsCache;
	}

	public function fetchOptions() {
		$options = $this->settings['options'];
		if ( is_callable( $options, false ) ) {
			return call_user_func( $options, $this );
		} else {
			return $options;
		}
	}

	protected function getEditLink( $value ) {
		return null;
	}

	protected function wrapEditLink( $value, $label ) {
		$href = $this->getEditLink( $value );
		if ( empty( $href ) || false === $this->settings['wrap_edit'] ) {
			return $label;
		}

		return "<a class='option-link' href='$href' target='_blank'>$label</a>";
	}

	/*
	 * Display for options and meta
	 */
	public function display() {
		if ( $this->isReadonly() ) {
			$this->echoOptionHeader();
			$this->columnDisplayValue( $this->getPostID() );
			$this->echoOptionFooter( true );
		} else {
			$this->echoOptionHeader( true );
			echo '<fieldset>';

			$savedValue = $this->getValue();

			$options         = $this->fetchOptions();
			$default_checked = false;
			if ( TitanFrameworkOption::isOnNewScreen() ) {
				$default_checked = ( isset( $this->settings['autoselect_single'] ) && $this->settings['autoselect_single'] && count( $options ) == 1 );
				$default_checked |= ( isset( $this->settings['select_all'] ) && $this->settings['select_all'] );
			}

			foreach ( $options as $value => $label ) {
				printf( '<label for="%s"><input class="%s" id="%s" type="checkbox" name="%s[]" value="%s" %s/> %s</label><br>',
					$this->getID() . $value,
					( $this->settings['required'] ? 'multicheckReq' : '' ),
					$this->getID() . $value,
					$this->getID(),
					esc_attr( $value ),
					checked( $default_checked || in_array( $value, $savedValue ), true, false ),
					$this->wrapEditLink( $value, $label )
				);
			}

			echo '</fieldset>';
			$this->echoOptionFooter( false );
		}
	}


	public function columnDisplayValue( $post_id ) {
		$savedValue = $this->getValue( $post_id );
		if ( ! $savedValue ) {
			return;
		}

		$titles = array();

		foreach ( $this->fetchOptionsWithCache() as $value => $label ) {
			if ( in_array( $value, $savedValue ) ) {
				$titles[] = $this->wrapEditLink( $value, $label );
			}
		}

		echo implode( ', ', $titles );
	}

	public function columnExportValue( $post_id ) {
		$savedValue = $this->getValue( $post_id );
		if ( ! $savedValue ) {
			return;
		}

		$titles = array();

		foreach ( $this->fetchOptionsWithCache() as $value => $label ) {
			if ( in_array( $value, $savedValue ) ) {
				$titles[] = $label;
			}
		}

		echo implode( ', ', $titles );
	}

	public function cleanValueForSaving( $value ) {
		if ( empty( $value ) ) {
			return array();
		}
		if ( is_serialized( $value ) ) {
			return $value;
		}
		// CSV
		if ( is_string( $value ) ) {
			$value = explode( ',', $value );
		}

		return serialize( $value );
	}

	public function cleanValueForGetting( $value ) {
		if ( empty( $value ) ) {
			return array();
		}
		if ( is_array( $value ) ) {
			return $value;
		}
		if ( is_serialized( $value ) ) {
			return unserialize( $value );
		}
		if ( is_string( $value ) ) {
			return explode( ',', $value );
		}
	}

	public
	function echoFilter(
		$args
	) {
		$placeholder = empty( $args['placeholder'] ) ? '— ' . __( 'Tous', TF_I18NDOMAIN ) . ' —' : $args['placeholder'];
		$name        = $args['name'];

		if ( is_callable( $args['custom_options'], false ) ) {
			$options = call_user_func( $args['custom_options'], $args );
		} else {
			$old_placeholder               = ! empty( $this->settings['placeholder'] ) ? $this->settings['placeholder'] : '';
			$this->settings['placeholder'] = $placeholder;
			$options                       = $this->fetchOptionsWithCache();
			$this->settings['placeholder'] = $old_placeholder;
		}

		if ( ! isset( $options[''] ) ) {
			$new_options     = [];
			$new_options[''] = $placeholder;
			foreach ( $options as $k => $v ) {
				$new_options[ $k ] = $v;
			}
			$options = $new_options;
		}
		if ( count( $options ) <= 1 ) {
			return;
		}

		?><select id="<?php echo $name ?>"
                  name="<?php echo $name; ?>"
                  data-placeholder="<?php echo esc_attr( $placeholder ) ?>"
        ><?php
		tf_parse_select_options( $options, isset( $_REQUEST[ $name ] ) ? $_REQUEST[ $name ] : null );
		?></select><?php
	}

	public function generateMember() {
		$mn    = $this->getMemberName();
		$cases = '';
		foreach ( $this->settings['options'] as $k => $v ) {
			if ( is_array( $v ) ) {
				foreach ( $v as $kk => $vv ) {
					$cases .= "\ncase '$kk':\n\treturn '$vv';";
				}
			} else {
				$cases .= "\ncase '$k':\n\treturn '$v';";
			}
		}

		return '
		public function get' . $mn . 'Display() {
			$this->ensure_init();
			return array_map($this->custom[\'' . $this->getID() . '\'], function($v) {
				switch ($v) {
					' . $cases . '
					default:
						return $v;
				}
			});
		}
		public function get' . $mn . '() {
			$this->ensure_init();
			return $this->custom[\'' . $this->getID() . '\'];
		}
		public function set' . $mn . '($value) {
			update_post_meta($this->post->ID, \'' . $this->getID() . '\', $value);
		}
		';
	}

	/*
	 * Display for theme customizer
	 */
	public function registerCustomizerControl( $wp_customize, $section, $priority = 1 ) {
		$wp_customize->add_control( new TitanFrameworkOptionMulticheckControl( $wp_customize, $this->getID(), array(
			'label'       => $this->settings['name'],
			'section'     => $section->settings['id'],
			'settings'    => $this->getID(),
			'description' => $this->settings['desc'],
			'required'    => $this->settings['required'],
			'options'     => $this->fetchOptions( $this->settings['options'] ),
			'priority'    => $priority,
		) ) );
	}
}


/*
 * WP_Customize_Control with description
 */
add_action( 'customize_register', 'registerTitanFrameworkOptionMulticheckControl', 1 );
function registerTitanFrameworkOptionMulticheckControl() {
	class TitanFrameworkOptionMulticheckControl extends WP_Customize_Control {
		public $description;
		public $options;
		public $required;

		private static $firstLoad = true;

		// Since theme_mod cannot handle multichecks, we will do it with some JS
		public function render_content() {
			// the saved value is an array. convert it to csv
			if ( is_array( $this->value() ) ) {
				$savedValueCSV = implode( ',', $this->value() );
				$values        = $this->value();
			} else {
				$savedValueCSV = $this->value();
				$values        = explode( ',', $this->value() );
			}

			if ( self::$firstLoad ) {
				self::$firstLoad = false;

				?>
                <script>
                    jQuery(document).ready(function ($) {
                        "use strict";

                        $('input.tf-multicheck').change(function (event) {
                            event.preventDefault();
                            var csv = '';

                            $(this).parents('li:eq(0)').find('input[type=checkbox]').each(function () {
                                if ($(this).is(':checked')) {
                                    csv += $(this).attr('value') + ',';
                                }
                            });

                            csv = csv.replace(/,+$/, "");

                            $(this).parents('li:eq(0)').find('input[type=hidden]').val(csv)
                            // we need to trigger the field afterwards to enable the save button
                                .trigger('change');
                            return true;
                        });
                    });
                </script>
				<?php
			}

			$description = '';
			if ( ! empty( $this->description ) ) {
				$description = "<p class='description'>" . $this->description . '</p>';
			}
			?>
            <label class='tf-multicheck-container'>
                <span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
				<?php
				echo $description;
				foreach ( $this->options as $value => $label ) {
					printf( '<label for="%s"><input class="tf-multicheck %s" id="%s" type="checkbox" value="%s" %s/> %s</label><br>',
						$this->id . $value,
						( $this->required ? 'required' : '' ),
						$this->id . $value,
						esc_attr( $value ),
						checked( in_array( $value, $values ), true, false ),
						$label
					);
				}
				?>
                <input type="hidden" value="<?php echo esc_attr( $savedValueCSV ); ?>" <?php $this->link(); ?> />
            </label>
			<?php
		}
	}
}
