<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_filter( 'amapress_register_entities', 'amapress_register_entities_distribution' );
function amapress_register_entities_distribution( $entities ) {
	$entities['distribution'] = array(
		'singular'         => amapress__( 'Distribution hebdomadaire' ),
		'plural'           => amapress__( 'Distributions hebdomadaires' ),
		'public'           => true,
//                'logged_or_public' => true,
		'show_in_menu'     => false,
		'show_in_nav_menu' => false,
		'editor'           => false,
		'title'            => false,
		'title_format'     => 'amapress_distribution_title_formatter',
		'slug_format'      => 'from_title',
		'slug'             => amapress__( 'distributions' ),
		'redirect_archive' => 'amapress_redirect_agenda',
		'menu_icon'        => 'dashicons-store',
		'row_actions'      => array(
			'emargement'      => [
				'label'  => 'Liste émargement',
				'target' => '_blank',
				'href'   => function ( $dist_id ) {
					return AmapressDistribution::getBy( $dist_id )->getListeEmargementHref();
				},
			],
			'quant_prod'      => [
				'label'  => 'Quantités producteurs',
				'target' => '_blank',
				'href'   => function ( $dist_id ) {
					return add_query_arg( 'date',
						date_i18n( 'Y-m-d', AmapressDistribution::getBy( $dist_id )->getDate() ),
						admin_url( 'admin.php?page=contrats_quantites_next_distrib' ) );
				},
			],
			'mailto_resp'     => [
				'label'     => 'Email aux responsables',
				'target'    => '_blank',
				'confirm'   => true,
				'href'      => function ( $dist_id ) {
					$dist = AmapressDistribution::getBy( $dist_id );

					return $dist->getMailtoResponsables();
				},
				'condition' => function ( $dist_id ) {
					$dist = AmapressDistribution::getBy( $dist_id );

					return ! empty( $dist->getMailtoResponsables() );
				},
				'show_on'   => 'editor',
			],
			'smsto_resp'      => [
				'label'     => 'SMS aux responsables',
				'target'    => '_blank',
				'confirm'   => true,
				'href'      => function ( $dist_id ) {
					$dist = AmapressDistribution::getBy( $dist_id );

					return $dist->getSMStoResponsables();
				},
				'condition' => function ( $dist_id ) {
					$dist = AmapressDistribution::getBy( $dist_id );

					return ! empty( $dist->getSMStoResponsables() );
				},
				'show_on'   => 'editor',
			],
			'mailto_amapiens' => [
				'label'   => 'Email aux amapiens',
				'target'  => '_blank',
				'confirm' => true,
				'href'    => function ( $dist_id ) {
					$dist = AmapressDistribution::getBy( $dist_id );

					return $dist->getMailtoAmapiens();
				},
				'show_on' => 'editor',
			],
			'smsto_amapiens'         => [
				'label'   => 'Sms aux amapiens',
				'target'  => '_blank',
				'confirm' => true,
				'href'    => function ( $dist_id ) {
					$dist = AmapressDistribution::getBy( $dist_id );

					return $dist->getSMStoAmapiens();
				},
				'show_on' => 'editor',
			],
			'resend_liste_to_resp'   => [
				'label'   => 'Renvoyer la liste d\'émargement aux responsables',
				'show_on' => 'editor',
				'confirm' => true,
			],
			'resend_liste_to_verify' => [
				'label'   => 'Envoyer les infos de distribution à vérifier',
				'show_on' => 'editor',
				'confirm' => true,
			],
		),
		'views'            => array(
			'remove' => array( 'mine' ),
			'_dyn_'  => 'amapress_distribution_views',
		),
		'groups'           => array(
			'Infos' => [
				'context' => 'side',
			],
		),
		'default_orderby'  => 'amapress_distribution_date',
		'default_order'    => 'ASC',
		'fields'           => array(
			'date' => array(
				'name'       => amapress__( 'Date de distribution' ),
				'type'       => 'date',
				'time'       => false,
				'top_filter' => array(
					'name'           => 'amapress_date',
					'placeholder'    => 'Toutes les dates',
					'custom_options' => 'amapress_get_active_contrat_month_options'
				),
				'group'      => 'Infos',
				'readonly'   => true,
				'desc'       => 'Date de distribution',
			),
			'lieu' => array(
				'name'       => amapress__( 'Lieu de distribution' ),
				'type'       => 'select-posts',
				'post_type'  => 'amps_lieu',
				'group'      => 'Infos',
				'orderby'    => 'post_title',
				'order'      => 'ASC',
				'top_filter' => array(
					'name'        => 'amapress_lieu',
					'placeholder' => 'Toutes les lieux',
				),
				'readonly'   => true,
				'desc'       => 'Lieu de distribution',
				'searchable' => true,
			),

			'lieu_substitution' => array(
				'name'       => amapress__( 'Lieu de substitution' ),
				'type'       => 'select-posts',
				'post_type'  => 'amps_lieu',
				'group'      => '1/ Partage',
				'desc'       => 'Lieu de substitution',
				'hidden'     => function ( $option ) {
					return count( Amapress::get_lieu_ids() ) <= 1;
				},
				'searchable' => true,
			),
			'heure_debut_spec'  => array(
				'name'  => amapress__( 'Heure de début' ),
				'type'  => 'date',
				'date'  => false,
				'time'  => true,
				'desc'  => 'Heure début particulière pour cette livraison',
				'group' => '1/ Partage',
			),
			'heure_fin_spec'    => array(
				'name'  => amapress__( 'Heure de fin' ),
				'type'  => 'date',
				'date'  => false,
				'time'  => true,
				'desc'  => 'Heure fin particulière pour cette livraison',
				'group' => '1/ Partage',
			),
			'contrats'          => array(
				'name'       => amapress__( 'Contrats' ),
				'type'       => 'multicheck-posts',
				'post_type'  => 'amps_contrat_inst',
				'group'      => '1/ Partage',
				'readonly'   => true,
				'hidden'     => true,
				'desc'       => 'Contrats',
				'orderby'    => 'post_title',
				'order'      => 'ASC',
				'top_filter' => array(
					'name'        => 'amapress_contrat_inst',
					'placeholder' => 'Tous les contrats'
				),
//                'searchable' => true,
			),
			'paniers'           => array(
				'name'              => amapress__( 'Panier(s)' ),
				'group'             => '1/ Partage',
				'desc'              => 'Paniers à cette distribution',
				'show_column'       => false,
//				'bare'              => true,
				'include_columns'   => array(
					'title',
					'amapress_panier_contrat_instance',
					'amapress_panier_status',
					'amapress_panier_date_subst',
				),
				'datatable_options' => array(
					'ordering'  => false,
					'paging'    => false,
					'searching' => false,
				),
				'type'              => 'related-posts',
				'query'             => function ( $postID ) {
					$dist = AmapressDistribution::getBy( $postID );

					return array(
						'post_type'      => AmapressPanier::INTERNAL_POST_TYPE,
						'posts_per_page' => - 1,
						'meta_query'     => array(
							array(
								'relation' => 'OR',
								array(
									'key'     => 'amapress_panier_date',
									'value'   => $dist->getDate(),
									'compare' => '=',
									'type'    => 'NUMERIC'
								),
								array(
									array(
										'key'     => 'amapress_panier_status',
										'value'   => 'delayed',
										'compare' => '=',
									),
									array(
										'key'     => 'amapress_panier_date_subst',
										'value'   => $dist->getDate(),
										'compare' => '=',
										'type'    => 'NUMERIC'
									),
								)
							)
						)
					);
				}
			),

			'nb_resp_supp' => array(
				'name'        => amapress__( 'Nombre' ),
				'type'        => 'number',
				'required'    => true,
				'desc'        => 'Indiquer le nombre de responsables de distributions supplémentaires',
				'group'       => '2/ Responsables',
				'default'     => 0,
				'show_column' => false,
			),
			'responsables' => array(
				'name'         => amapress__( 'Responsables' ),
				'group'        => '2/ Responsables',
				'type'         => 'select-users',
				'autocomplete' => true,
				'multiple'     => true,
				'tags'         => true,
				'desc'         => 'Indiquer tous les responsables de distribution',
//				'before_option' => function ( $o ) {
//					if ( Amapress::hasRespDistribRoles() ) {
//						echo '<p style="color: orange">Lorsqu\'il existe des rôles de responsables de distribution, l\'inscription ne peut se faire que depuis la page d\'inscription par dates.</p>';
//					}
//				},
				'readonly'     => true,
				'after_option' => function ( $option ) {
					/** @var TitanFrameworkOption $option */

//					$href = Amapress::get_inscription_distrib_page_href();
//					if ( ! empty( $href ) ) {
//						echo '<p>Les inscriptions aux distributions se gèrent <a href="' . esc_attr( $href ) . '" target="_blank">ici</a></p>';
//					} else {
//						echo '<p style="color:red">Aucune page du site ne contient le shortcode [inscription-distrib] (qui permet de gérer l\'inscription aux distributions)</p>';
//					}
					$dist = AmapressDistribution::getBy( $option->getPostID() );
					echo amapress_inscription_distrib_shortcode(
						[
							'date'                     => $dist->getDate(),
							'show_for_resp'            => 'true',
							'show_title'               => 'false',
							'max_dates'                => 1,
							'lieu'                     => $dist->getLieuId(),
							'manage_all_subscriptions' => 'true',
						]
					);
				},
//                'searchable' => true,
			),

			'info' => array(
				'name'  => amapress__( 'Informations spécifiques' ),
				'type'  => 'editor',
				'group' => '3/ Informations',
				'desc'  => 'Informations complémentaires',
			),
		),
	);

	return $entities;
}

add_filter( 'amapress_can_delete_distribution', 'amapress_can_delete_distribution', 10, 2 );
function amapress_can_delete_distribution( $can, $post_id ) {
	return false;
}

function amapress_get_active_contrat_month_options( $args ) {
	$months    = array();
	$min_month = amapress_time();
	$max_month = amapress_time();
	foreach ( AmapressContrats::get_active_contrat_instances() as $contrat ) {
		$min_month = $contrat->getDate_debut() < $min_month ? $contrat->getDate_debut() : $min_month;
		$max_month = $contrat->getDate_fin() > $max_month ? $contrat->getDate_fin() : $max_month;
	}
	$min_month = Amapress::start_of_month( $min_month );
	$max_month = Amapress::end_of_month( $max_month );
	$month     = $min_month;
	while ( $month <= $max_month ) {
		$months[ date_i18n( 'Y-m', $month ) ] = date_i18n( 'F Y', $month );
		$month                                = Amapress::add_a_month( $month );
	}

	return $months;
}

function amapress_distribution_responsable_roles_options() {
	$ret   = [];
	$lieux = Amapress::get_lieux();
	if ( count( $lieux ) > 1 ) {
		$ret[] = array(
			'type' => 'heading',
			'name' => 'Rôles des responsables de distribution - pour tous les lieux',
		);
	}
	for ( $i = 1; $i < 6; $i ++ ) {
		$ret[] = array(
			'id'   => "resp_role_$i-name",
			'name' => amapress__( "Nom du rôle $i" ),
			'type' => 'text',
			'desc' => 'Nom du rôle de responsable de distribution',
		);
		$ret[] = array(
			'id'   => "resp_role_$i-desc",
			'name' => amapress__( "Description du rôle $i" ),
			'type' => 'editor',
			'desc' => 'Description du rôle de responsable de distribution',
		);
	}
	$ret[] = array(
		'type' => 'save',
	);

	if ( count( $lieux ) > 1 ) {
		foreach ( $lieux as $lieu ) {
			$ret[]   = array(
				'type' => 'heading',
				'name' => 'Rôles des responsables de distribution - pour ' . $lieu->getTitle(),
			);
			$lieu_id = $lieu->ID;
			for ( $i = 1; $i < 6; $i ++ ) {
				$ret[] = array(
					'id'   => "resp_role_{$lieu_id}_$i-name",
					'name' => amapress__( "Nom du rôle $i" ),
					'type' => 'text',
					'desc' => 'Nom du rôle de responsable de distribution',
				);
				$ret[] = array(
					'id'   => "resp_role_{$lieu_id}_$i-desc",
					'name' => amapress__( "Description du rôle $i" ),
					'type' => 'editor',
					'desc' => 'Description du rôle de responsable de distribution',
				);
			}
			$ret[] = array(
				'type' => 'save',
			);
		}
	}

	return $ret;
}

add_action( 'amapress_row_action_distribution_resend_liste_to_resp', 'amapress_row_action_distribution_resend_liste_to_resp' );
function amapress_row_action_distribution_resend_liste_to_resp( $post_id ) {
	do_action( 'amapress_recall_resp_distrib', [
		'id' => $post_id
	] );
	wp_redirect_and_exit( wp_get_referer() );
}

add_action( 'amapress_row_action_distribution_resend_liste_to_verify', 'amapress_row_action_distribution_resend_liste_to_verify' );
function amapress_row_action_distribution_resend_liste_to_verify( $post_id ) {
	do_action( 'amapress_recall_verify_distrib', [
		'id' => $post_id
	] );
	wp_redirect_and_exit( wp_get_referer() );
}

function amapress_distribution_hours_setter() {
	$start_date = empty( $_POST['start_date'] ) ? date_i18n( 'd/m/Y', amapress_time() ) : $_POST['start_date'];
	$end_date   = empty( $_POST['end_date'] ) ? date_i18n( 'd/m/Y', Amapress::add_a_month( amapress_time(), 12 ) ) : $_POST['end_date'];
	$lieu_id    = empty( $_POST['lieu'] ) ? null : $_POST['lieu'];
	$incr_date  = empty( $_POST['incr_date'] ) ? 2 : $_POST['incr_date'];
	$start_hour = empty( $_POST['start_hour'] ) ? null : $_POST['start_hour'];
	$end_hour   = empty( $_POST['end_hour'] ) ? null : $_POST['end_hour'];

	$lieux_options = [];
	foreach ( Amapress::get_lieux() as $lieu ) {
		$lieux_options[ strval( $lieu->ID ) ] = $lieu->getTitle();
	}
	?>
    <h4>Cet outil permet de définir les horaires alternatifs des distributions.</h4>

	<?php

	if ( isset( $_POST['set_hours'] ) || isset( $_POST['reset_hours'] ) ) {
		$set = isset( $_POST['set_hours'] );
		if ( ! empty( $_POST['dist'] ) ) {
			global $wpdb;
			$wpdb->query( 'START TRANSACTION' );

			foreach ( $_POST['dist'] as $k => $v ) {
				$dist_id   = intval( $v );
				$dist_post = AmapressDistribution::getBy( $dist_id, true );
				if ( $set ) {
					if ( ! empty( $start_hour ) ) {
						$start_hour_date = DateTime::createFromFormat( 'd#m#Y H:i', date_i18n( 'd/m/Y', $dist_post->getDate() ) . ' ' . trim( $start_hour ) );
						if ( $start_hour_date ) {
							$dist_post->setSpecialHeure_debut( $start_hour_date->getTimestamp() );
						}
					}
					if ( ! empty( $end_hour ) ) {
						$end_hour_date = DateTime::createFromFormat( 'd#m#Y H:i', date_i18n( 'd/m/Y', $dist_post->getDate() ) . ' ' . trim( $end_date ) );
						if ( $end_hour_date ) {
							$dist_post->setSpecialHeure_fin( $end_hour_date->getTimestamp() );
						}
					}
				} else {
					$dist_post->setSpecialHeure_debut( null );
					$dist_post->setSpecialHeure_fin( null );
				}
				amapress_compute_post_slug_and_title( $dist_post->getPost() );
			}
			$wpdb->query( 'COMMIT' );
			AmapressDistribution::clearCache();
		}
	}

	?>
    <label for="start_date" class="tf-date">Date de début :<input type="text" id="start_date" name="start_date"
                                                                  class="input-date date required"
                                                                  value="<?php echo esc_attr( $start_date ); ?>"/></label>
    <br/>
    <label for="end_date" class="tf-date">Date de fin :<input type="text" id="end_date" name="end_date"
                                                              class="input-date date required"
                                                              value="<?php echo esc_attr( $end_date ); ?>"/></label>
    <br/>
    <label for="lieu">Lieu:</label><select id="lieu"
                                           name="lieu"
                                           class="required"><?php tf_parse_select_options( $lieux_options, $lieu_id ); ?></select>
    <br/>
    <label for="incr_date">Prendre une distribution toute les </label><input type="text" id="incr_date" name="incr_date"
                                                                             class="number required"
                                                                             value="<?php echo esc_attr( $incr_date ); ?>"/> dates
    <br/>
    <input type="submit" class="button button-primary" name="select_dists" value="Afficher les distributions"/>
    <br/>
    <label for="start_hour" class="tf-date">Heure de début : <input type="text" id="start_hour"
                                                                    name="start_hour"
                                                                    class="input-date time"
                                                                    value="<?php echo esc_attr( $start_hour ); ?>"/></label>
    <br/>
    <label for="end_hour" class="tf-date">Heure de fin : <input type="text" id="end_hour" name="end_hour"
                                                                class="input-date time"
                                                                value="<?php echo esc_attr( $end_hour ); ?>"/></label>
    <br/>
    <input type="submit" class="button button-primary" name="set_hours" value="Définir"/>
    <input type="submit" class="button button-secondary" name="reset_hours" value="Effacer"/>
	<?php

	$distributions        = AmapressDistribution::get_distributions( TitanEntity::to_date( $start_date ), TitanEntity::to_date( $end_date ), 'ASC' );
	$distributions        = array_filter( $distributions, function ( $d ) use ( $lieu_id ) {
		/** @var AmapressDistribution $d */
		return $d->getLieuId() == $lieu_id;
	} );
	$filter_distributions = [];
	$i                    = 0;
	echo '<ul>';
	foreach ( $distributions as $d ) {
		$hours = ' (' . date_i18n( 'H:i', $d->getStartDateAndHour() ) . ' à ' . date_i18n( 'H:i', $d->getEndDateAndHour() ) . ')';
		if ( ( $i % $incr_date ) == 0 ) {
			$filter_distributions[] = $d;
			echo '<li><input type="checkbox" class="checkbox" id="dist_' . $d->ID . '" name="dist[' . $d->ID . ']" value="' . $d->ID . '" ' . checked( isset( $_POST['dist'][ $d->ID ] ) || isset( $_POST['select_dists'] ), true, false ) . ' /><label for="dist_' . $d->ID . '">' . esc_html( $d->getTitle() ) . $hours . ' (' . Amapress::makeLink( $d->getAdminEditLink(), 'Voir', true, true ) . ')' . '</label></li>';
		} else {
			echo '<li style="text-decoration: line-through">' . esc_html( $d->getTitle() ) . $hours . ' (' . Amapress::makeLink( $d->getAdminEditLink(), 'Voir', true, true ) . ')' . '</li>';
		}
		$i += 1;
	}
	echo '</ul>';

	TitanFrameworkOptionDate::createCalendarScript();
}