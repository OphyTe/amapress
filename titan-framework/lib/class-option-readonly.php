<?php/** * Text Option * * @package Titan Framework */if ( ! defined( 'ABSPATH' ) ) {	exit; // Exit if accessed directly.}/** * Text Option * * Creates a text option * * <strong>Creating a text option:</strong> * <pre>$panel->createOption( array( *     'name' => 'My Text Option', *     'id' => 'my_text_option', *     'type' => 'text', *     'desc' => 'This is our option', * ) );</pre> * * @since 1.0 * @type readonly * @availability Admin Pages|Meta Boxes|Customizer */class TitanFrameworkOptionReadonly extends TitanFrameworkOption {	/**	 * Default settings specific for this option	 * @var array	 */	public $defaultSecondarySettings = array(		/**		 * (Optional) An additional label, located immediately after the form field. Accepts alphanumerics and symbols. Potential applications include indication of the unit, especially if the field is used with numbers.		 *		 * @since 1.5.2		 * @var string		 * @example 'px' or '%'		 */		'unit' => '',	);	/**	 * Display for options and meta	 */	public function display() {		$this->echoOptionHeader();		printf( '<span id="%s" class="readonly-text" >%s</span> %s',			$this->getID(),			$this->getValue(),			$this->settings['unit']		);		$this->echoOptionFooter();	}	/**	 * Display for options and meta	 */	public function columnDisplayValue( $post_id ) {		printf( '<span class="readonly-text" >%s</span> %s',			$this->getValue( $post_id ),			$this->settings['unit']		);	}	/**	 * Cleans the value before saving the option	 *	 * @param string $value The value of the option.	 */	public function cleanValueForSaving( $value ) {		return $value;	}	public function customSave( $postID ) {		return true;	}	/**	 * Display for theme customizer	 *	 * @param WP_Customize $wp_customize The customizer object.	 * @param TitanFrameworkCustomizer $section The customizer section.	 * @param int $priority The display priority of the control.	 */	public function registerCustomizerControl( $wp_customize, $section, $priority = 1 ) {		$wp_customize->add_control( new TitanFrameworkCustomizeControl( $wp_customize, $this->getID(), array(			'label'       => $this->settings['name'],			'section'     => $section->settings['id'],			'settings'    => $this->getID(),			'description' => $this->settings['desc'],			'required'    => $this->settings['required'],			'priority'    => $priority,		) ) );	}}