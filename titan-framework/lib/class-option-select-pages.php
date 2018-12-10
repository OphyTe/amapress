<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class TitanFrameworkOptionSelectPages extends TitanFrameworkOptionSelect {

	public $defaultSecondarySettings = array(
		'default'           => '', // show this when blank
		'autocomplete'      => false,
		'tags'              => false,
		'autoselect_single' => false,
		'placeholder'       => '',
		'wrap_edit'         => true,
		'custom_csv_sample' => null,
		'refresh_button'    => true,
	);

	protected function getEditLink( $value ) {
		if ( empty( $value ) ) {
			return null;
		}

		return admin_url( "post.php?post=$value&action=edit" );
	}

	private static $allPages;

	public function fetchOptions() {
		// Remember the pages so as not to perform any more lookups
		if ( ! isset( self::$allPages ) ) {
			self::$allPages = get_pages();
		}

		$placeholder = empty( $this->settings['placeholder'] ) ? '— ' . __( 'Select', TF_I18NDOMAIN ) . ' —' : $this->settings['placeholder'];

		$ret     = array();
		$ret[''] = $placeholder;

		// Print all the other pages
		foreach ( self::$allPages as $page ) {

			$title = $page->post_title;
			if ( empty( $title ) ) {
				$title = sprintf( __( 'Untitled %s', TF_I18NDOMAIN ), '(ID #' . $page->ID . ')' );
			}

			$ret[ $page->ID ] = $title;
		}

		return $ret;
	}

	public function generateMember() {
		$mn        = strtolower( $this->getMemberName() );
		$post_type = ucfirst( amapress_simplify_post_type( $this->settings['post_type'] ) );

		return '
		private $' . $mn . ' = null;
		public function get' . $this->getMemberName() . '() {
			$this->ensure_init();
			$v = $this->custom[\'' . $this->getID() . '\'];
			if (empty($v)) return null;
			if ($this->' . $mn . ' == null) $this->' . $mn . ' = new Amapress' . $post_type . '($v);
			return $this->' . $mn . ';
		}
		public function set' . $this->getMemberName() . '($value) {
			update_post_meta($this->post->ID, \'' . $this->getID() . '\', $value);
			$this->' . $mn . ' = null;
		}
		';
	}

	/*
	 * Display for theme customizer
	 */
	public function registerCustomizerControl( $wp_customize, $section, $priority = 1 ) {
		$wp_customize->add_control( new TitanFrameworkCustomizeControl( $wp_customize, $this->getID(), array(
			'label'       => $this->settings['name'],
			'section'     => $section->settings['id'],
			'settings'    => $this->getID(),
			'type'        => 'dropdown-pages',
			'description' => $this->settings['desc'],
			'required'    => $this->settings['required'],
			'priority'    => $priority,
		) ) );
	}
}
