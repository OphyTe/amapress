<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'amapress_register_entities', 'amapress_register_entities_amap_event' );
function amapress_register_entities_amap_event( $entities ) {
	$entities['amap_event'] = array(
		'singular'           => amapress__( 'Evènement' ),
		'plural'             => amapress__( 'Evènements' ),
		'public'             => true,
		'thumb'              => true,
		'editor'             => true,
		'logged_or_public'   => true,
		'special_options'    => array(),
		'show_in_menu'       => false,
		'show_in_nav_menu'   => false,
		'slug'               => 'evenements',
		'menu_icon'          => 'dashicons-groups',
		'redirect_archive'   => 'amapress_redirect_agenda',
		'default_orderby'    => 'amapress_amap_event_date',
		'default_order'      => 'ASC',
		'show_admin_bar_new' => true,
		'groups'             => array(
			'Visibilité' => [
				'context' => 'side',
			],
		),
		'views'              => array(
			'remove'  => array( 'mine' ),
			'_dyn_'   => 'amapress_amap_event_views',
			'exp_csv' => true,
		),
		'edit_header'        => function ( $post ) {
			$event = AmapressAmap_event::getBy( $post, true );
			if ( $event ) {
				if ( 'lieu_externe' == $event->getType() ) {
					if ( ! $event->isLieu_externe_AdresseLocalized() ) {
						amapress_add_admin_notice( 'Adresse du lieu externe non localisée', 'warning', false );
					}
					if ( ! empty( $event->getLieu_externe_adresse_acces() ) && ! $event->isLieu_externe_AdresseAccesLocalized() ) {
						amapress_add_admin_notice( 'Adresse d\'accès du lieu externe non localisée', 'warning', false );
					}
				}
			}
			TitanFrameworkOption::echoFullEditLinkAndWarning();
		},
		'fields'             => array(
			'public'      => array(
				'desc'  => 'Publique ?',
				'group' => 'Visibilité',
				'type'  => 'checkbox',
			),
			'date'        => array(
				'name'       => amapress__( 'Date de l\'évènement' ),
				'type'       => 'date',
				'time'       => false,
				'required'   => true,
				'desc'       => 'Date évènement',
				'group'      => '1/ Horaires',
				'top_filter' => array(
					'name'           => 'amapress_date',
					'placeholder'    => 'Toutes les dates',
					'custom_options' => 'amapress_get_active_contrat_month_options'
				),
			),
			'heure_debut' => array(
				'name'     => amapress__( 'Heure début' ),
				'type'     => 'date',
				'date'     => false,
				'time'     => true,
				'required' => true,
				'desc'     => 'Heure début',
				'group'    => '1/ Horaires',
			),
			'heure_fin'   => array(
				'name'     => amapress__( 'Heure fin' ),
				'type'     => 'date',
				'date'     => false,
				'time'     => true,
				'required' => true,
				'desc'     => 'Heure fin',
				'group'    => '1/ Horaires',
			),
			'type'        => array(
				'name'        => amapress__( 'Emplacement' ),
				'type'        => 'select',
				'options'     => array(
					'lieu'         => 'Lieu de distribution',
					'lieu_externe' => 'Adresse externe',
				),
				'required'    => true,
				'group'       => '2/ Emplacement',
				'conditional' => array(
					'_default_'    => 'lieu',
					'lieu'         => array(
						'lieu' => array(
							'name'              => amapress__( 'Lieu dist.' ),
							'type'              => 'select-posts',
							'post_type'         => 'amps_lieu',
							'desc'              => 'Lieu',
							'group'             => '2/ Emplacement',
							'autoselect_single' => true,
							'searchable'        => true,
							'required'          => true,
						),
					),
					'lieu_externe' => array(
						'lieu_externe_nom'           => array(
							'name'       => amapress__( 'Lieu ext.' ),
							'type'       => 'text',
							'desc'       => 'Lieu externe',
							'group'      => '2/ Emplacement',
							'searchable' => true,
							'required'   => true,
						),
						'lieu_externe_adresse'       => array(
							'name'         => amapress__( 'Adresse ext.' ),
							'type'         => 'address',
							'use_as_field' => true,
							'desc'         => 'Adresse',
							'group'        => '2/ Emplacement',
							'searchable'   => true,
							'required'     => true,
						),
						'lieu_externe_acces'         => array(
							'name'        => amapress__( 'Accès' ),
							'type'        => 'editor',
							'required'    => false,
							'desc'        => 'Accès',
							'group'       => '2/ Emplacement',
							'searchable'  => true,
							'show_column' => false,
						),
						'lieu_externe_adresse_acces' => array(
							'name'        => amapress__( 'Adresse d\'accès' ),
							'type'        => 'address',
							'desc'        => 'Adresse d\'accès',
							'group'       => '2/ Emplacement',
							'searchable'  => true,
							'show_column' => false,
						),
					),
				)
			),
			'participants' => array(
				'name'         => amapress__( 'Participants' ),
				'type'         => 'select-users',
				'readonly'     => true,
				'autocomplete' => true,
				'multiple'     => true,
				'tags'         => true,
				'desc'         => 'Participants',
				'group'        => '3/ Participants',
				'after_option' => function ( $option ) {
					/** @var TitanFrameworkOption $option */
					$visite = new AmapressAmap_event( $option->getPostID() );
					echo '<p>Les inscription se gère <a href="' . esc_attr( $visite->getPermalink() ) . '" target="_blank">ici</a> pour cette évènement</p>';
				},
			),
		),
	);

	return $entities;
}