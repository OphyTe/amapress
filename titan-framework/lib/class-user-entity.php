<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class TitanUserEntity {
	private $initialized = false;
	private $user_id = null;
	protected $user;
	protected $custom = null;
	public $ID;

	protected function ensure_init() {
		if ( ! $this->initialized ) {
			$this->init_user();
		}
	}

	public function getID() {
		$this->ensure_init();

		return $this->user->ID;
	}

	/**
	 * @return WP_User
	 */
	public function getUser() {
		$this->ensure_init();

		return $this->user;
	}

	public function getPermalink( $relative_url = null ) {
		$this->ensure_init();

		$url = get_permalink( $this->user->ID );
		if ( empty( $relative_url ) ) {
			return $url;
		}

		return trailingslashit( $url ) . $relative_url;
	}

	private function init_user() {
		if ( ! $this->user_id ) {
			return;
		}
		if ( $this->user == null ) {
			$this->user = get_user_by( 'ID', $this->user_id );
		}
		$this->custom      = array_map( function ( $v ) {
			if ( is_array( $v ) ) {
				if ( count( $v ) > 1 ) {
					return $v;
				} else {
					return $v[0];
				}
			} else {
				return $v;
			}
		}, get_user_meta( $this->user_id ) );
		$this->initialized = true;
	}

	protected function __construct( $user_or_id ) {
		if ( is_a( $user_or_id, 'WP_User' ) ) {
			$this->user_id = $user_or_id->ID;
			$this->user    = $user_or_id;
		} else if ( is_a( $user_or_id, 'TitanUserEntity' ) ) {
			/** @var TitanUserEntity $user_or_id */
			$this->user_id = $user_or_id->ID;
			$this->user    = $user_or_id->getUser();
		} else {
			$this->user_id = intval( $user_or_id );
		}
		$this->ID = $this->user_id;
	}
}