<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'amapress_register_entities', 'amapress_register_entities_amap_event' );
function amapress_register_entities_amap_event( $entities ) {
	$entities['amap_event'] = array(
		'singular'         => amapress__( 'Evènement' ),
		'plural'           => amapress__( 'Evènements' ),
		'public'           => true,
		'thumb'            => true,
		'editor'           => true,
		'logged_or_public' => true,
		'special_options'  => array(),
		'show_in_menu'     => false,
		'show_in_nav_menu' => false,
		'slug'             => 'evenements',
		'menu_icon'        => 'dashicons-groups',
		'redirect_archive' => 'amapress_redirect_agenda',
		'views'            => array(
			'remove'  => array( 'mine' ),
			'_dyn_'   => 'amapress_amap_event_views',
			'exp_csv' => true,
		),
		'fields'           => array(
			'date'         => array(
				'name'     => amapress__( 'Date de l\'évènement' ),
				'type'     => 'date',
				'time'     => true,
				'required' => true,
				'desc'     => 'Date évènement',
				'group'    => 'Horaires',
			),
			'heure_debut'  => array(
				'name'     => amapress__( 'Heure début' ),
				'type'     => 'date',
				'date'     => false,
				'time'     => true,
				'required' => true,
				'desc'     => 'Heure début',
				'group'    => 'Horaires',
			),
			'heure_fin'    => array(
				'name'     => amapress__( 'Heure fin' ),
				'type'     => 'date',
				'date'     => false,
				'time'     => true,
				'required' => true,
				'desc'     => 'Heure fin',
				'group'    => 'Horaires',
			),
			'type'         => array(
				'name'        => amapress__( 'Emplacement' ),
				'type'        => 'select',
				'options'     => array(
					'lieu'         => 'Lieu de distribution',
					'lieu_externe' => 'Adresse externe',
				),
				'required'    => true,
				'group'       => 'Gestion',
				'desc'        => 'Type de de contrat',
				'conditional' => array(
					'_default_'    => 'lieu',
					'lieu'         => array(
						'lieu' => array(
							'name'              => amapress__( 'Lieu' ),
							'type'              => 'select-posts',
							'post_type'         => 'amps_lieu',
							'desc'              => 'Lieu',
							'group'             => 'Emplacement',
							'autoselect_single' => true,
							'searchable'        => true,
						),
					),
					'lieu_externe' => array(
						'lieu_externe_nom'           => array(
							'name'       => amapress__( 'Lieu' ),
							'type'       => 'text',
							'desc'       => 'Lieu externe',
							'group'      => 'Emplacement',
							'searchable' => true,
						),
						'lieu_externe_adresse'       => array(
							'name'         => amapress__( 'Adresse d\'accès' ),
							'type'         => 'address',
							'use_as_field' => true,
							'desc'         => 'Adresse d\'accès',
							'group'        => 'Emplacement',
							'searchable'   => true,
						),
						'lieu_externe_acces'         => array(
							'name'       => amapress__( 'Accès' ),
							'type'       => 'editor',
							'required'   => false,
							'desc'       => 'Accès',
							'group'      => 'Emplacement',
							'searchable' => true,
						),
						'lieu_externe_adresse_acces' => array(
							'name'       => amapress__( 'Adresse d\'accès' ),
							'type'       => 'address',
							'desc'       => 'Adresse d\'accès',
							'group'      => 'Emplacement',
							'searchable' => true,
						),
					),
				)
			),
			'participants' => array(
				'name'         => amapress__( 'Participants' ),
				'type'         => 'select-users',
				'autocomplete' => true,
				'multiple'     => true,
				'tags'         => true,
				'desc'         => 'Participants',
				'group'        => 'Gestion',
			),
		),
	);

	return $entities;
}