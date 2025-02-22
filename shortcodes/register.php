<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require_once( AMAPRESS__PLUGIN_DIR . 'shortcodes/generic.map.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'shortcodes/generic.pager.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'shortcodes/ics.fullcalendar.php' );
//require_once(AMAPRESS__PLUGIN_DIR . 'shortcodes/where.to.find.us.php');
//require_once(AMAPRESS__PLUGIN_DIR . 'shortcodes/lieu.map.php');
//require_once(AMAPRESS__PLUGIN_DIR . 'shortcodes/contrat.info.php');
//require_once(AMAPRESS__PLUGIN_DIR . 'shortcodes/contrat.title.php');
//require_once(AMAPRESS__PLUGIN_DIR . 'shortcodes/contrat.header.php');
//require_once(AMAPRESS__PLUGIN_DIR . 'shortcodes/contrat.footer.php');
//require_once(AMAPRESS__PLUGIN_DIR . 'shortcodes/user.info.php');
//require_once(AMAPRESS__PLUGIN_DIR . 'shortcodes/user.map.php');
//require_once(AMAPRESS__PLUGIN_DIR . 'shortcodes/user.avatar.php');
//require_once(AMAPRESS__PLUGIN_DIR . 'shortcodes/recettes.php');
//require_once(AMAPRESS__PLUGIN_DIR . 'shortcodes/produits.php');
require_once( AMAPRESS__PLUGIN_DIR . 'shortcodes/next.events.php' );
require_once( AMAPRESS__PLUGIN_DIR . 'shortcodes/drives.view.php' );

add_action( 'wp_ajax_get_years_from', function () {
	if ( ! isset( $_POST['year'] ) ) {
		return '';
	}
	$diff = intval( date( 'Y' ) ) - intval( $_POST['year'] );
	if ( $diff <= 0 ) {
		$diff = 1;
	}
	printf( _n( '%s an', '%s ans', $diff, 'amapress' ), number_format_i18n( $diff ) );
	die();
} );

add_filter( 'amapress_init', 'amapress_register_shortcodes' );
function amapress_register_shortcodes() {
	if ( 'active' == amapress_is_plugin_active( 'latest-post-shortcode' ) ) {
		amapress_register_shortcode( 'amapress-latest-posts', function ( $atts ) {
			$atts = shortcode_atts(
				[
					'limit'    => '5',
					'chrlimit' => '120',
				],
				$atts
			);

			return do_shortcode( '[latest-selected-content 
		limit=' . $atts['limit'] . ' display="title,date,excerpt-small" 
		chrlimit=' . $atts['chrlimit'] . ' url="yes_blank" image="thumbnail" 
		elements="3" type="post" status="publish" 
		orderby="dateD" show_extra="date_diff"]' );
		},
			[
				'desc' => 'Affiche une grille des articles récents',
				'args' => [
					'limit'    => '(5 par défaut) Nombre maximum d\'articles à afficher',
					'chrlimit' => '(120 par défaut) Nombre maximum de caractères du résumé de chaque article à afficher',
				]
			] );
	}
	amapress_register_shortcode( 'years-since', function ( $atts ) {
		$atts = shortcode_atts(
			[ 'year' => '' ],
			$atts
		);
		$year = esc_attr( intval( $atts['year'] ) );

		return "<span class='amp-years-since' data-year='$year'></span>";
	},
		[
			'desc' => 'Affiche le nombre d\'années écoulée depuis une autre année',
			'args' => [
				'year' => 'Année de départ du décompte d\'années'
			]
		] );
	amapress_register_shortcode( 'amapress-panel', function ( $atts, $content ) {
		$atts = shortcode_atts(
			[
				'title'    => '',
				'esc_html' => true,
			],
			$atts
		);
		if ( Amapress::toBool( $atts['esc_html'] ) ) {
			return amapress_get_panel_start( $atts['title'] ) . $content . amapress_get_panel_end();
		} else {
			return amapress_get_panel_start_no_esc( $atts['title'] ) . $content . amapress_get_panel_end();
		}
	},
		[
			'desc' => 'Affiche un encadré avec titre',
			'args' => [
				'title'    => 'Titre de l\'encadré',
				'esc_html' => '(true par défaut) encoder le titre'
			]
		] );
	amapress_register_shortcode( 'paged_gallery', 'amapress_generic_paged_gallery_shortcode',
		[
			'desc' => '',
			'args' => [
			]
		] );
	amapress_register_shortcode( 'nous-trouver', 'amapress_where_to_find_us_shortcode',
		[
			'desc' => 'Carte des lieux de distributions',
			'args' => [
			]
		] );
	amapress_register_shortcode( 'recettes', 'amapress_recettes_shortcode',
		[
			'desc' => 'Gallerie des recettes',
			'args' => [
				'produits'    => 'Filtre de produits',
				'cat'         => 'Filtre de catégories',
				'cat__not_in' => 'Inverse filtre de catégories',
				'if_empty'    => '(Par défaut “Pas encore de recette”) Texte à afficher quand il n\’y a pas de recettes à afficher',
				'size'        => '(Par défaut “thumbnail”) Taille de l\’aperçu',
				'searchbox'   => '(Par défaut “true”) Afficher une barre de recherche',
			]
		] );
	amapress_register_shortcode( 'produits', 'amapress_produits_shortcode',
		[
			'desc' => 'Gallerie de produits',
			'args' => [
				'producteur'  => 'Filtre producteurs',
				'recette'     => 'Filtre recettes',
				'cat'         => 'Filtre catégories',
				'cat__not_in' => 'Inverse filtre catégories',
				'if_empty'    => '(Par défaut “Pas encore de produits”) Texte à afficher quand il n\’y a pas de recettes à afficher',
				'size'        => '(Par défaut “thumbnail”) Taille de l\’aperçu',
				'searchbox'   => '(Par défaut “true”) Afficher une barre de recherche',
			]
		] );
	amapress_register_shortcode( 'lieu-map', 'amapress_lieu_map_shortcode',
		[
			'desc' => 'Emplacement d\'un lieu (carte et StreetView)',
			'args' => [
				'lieu' => 'Afficher la carte du lieu indiqué',
				'mode' => '(Par défaut “map”) Mode d’affichage. Si Gooogle est votre afficheur de carte, alors vous pouvez choisir : map, map+streetview ou streetview',
			]
		] );
	amapress_register_shortcode( 'user-map', 'amapress_user_map_shortcode',
		[
			'desc' => 'Emplacement d\'un amapien',
			'args' => [
			]
		] );
	amapress_register_shortcode( 'producteur-map', 'amapress_producteur_map_shortcode',
		[
			'desc' => 'Emplacement d\'un producteur',
			'args' => [
				'lieu' => 'Afficher la carte du lieu indiqué',
				'mode' => '(Par défaut “map”) Mode d’affichage. Si Gooogle est votre afficheur de carte, alors vous pouvez choisir : map, map+streetview ou streetview',
			]
		] );
	amapress_register_shortcode( 'amapien-avatar', 'amapress_amapien_avatar_shortcode',
		[
			'desc' => '',
			'args' => [
			]
		] );
	amapress_register_shortcode( 'histo-inscription-distrib', 'amapress_histo_inscription_distrib_shortcode',
		[
			'desc' => 'Historique d\'inscription des responsables aux distributions',
			'args' => [
				'show_email'      => '(Par défaut “default”) Afficher les emails',
				'show_tel'        => '(Par défaut “default”) Afficher les numéros de téléphones',
				'show_tel_fixe'   => '(Par défaut “default”) Afficher les numéros de téléphones fixes',
				'show_tel_mobile' => '(Par défaut “default”) Afficher les numéros de téléphones mobiles',
				'show_adresse'    => '(Par défaut “default”) Afficher les adresses',
				'show_avatar'     => '(Par défaut “default”) Afficher les avatars des amapiens',
				'show_roles'      => '(Par défaut “default”) Afficher les rôles des membres du collectif',
				'show_title'      => '(Par défaut “true”) Afficher les noms des lieux',
				'past_weeks'      => '(Par défaut “5”) Nombre de semaines d’historique des distributions',
				'lieu'            => 'Filtre de lieu',
			]
		] );
	amapress_register_shortcode( 'liste-inscription-distrib', function ( $args ) {
		$args         = shortcode_atts(
			[
				'lieu'       => 0,
				'max_dates'  => 52,
				'show_title' => 'false',
			],
			$args
		);
		$dist_lieu_id = 0;
		if ( ! empty( $args['lieu'] ) ) {
			$dist_lieu_id = Amapress::resolve_post_id( $dist_lieu_id, AmapressLieu_distribution::INTERNAL_POST_TYPE );

			return do_shortcode( '[inscription-distrib for_pdf=true show_title=' . $args['show_title'] . ' for_emargement=true show_past=false show_adresse=false show_roles=false show_for_resp=true show_avatar=true max_dates=' . $args['max_dates'] . ' lieu=' . $dist_lieu_id . ']' );
		} else {
			$ret = '';
			foreach ( Amapress::get_lieu_ids() as $lieu_id ) {
				$ret .= do_shortcode( '[inscription-distrib for_pdf=true show_title=' . $args['show_title'] . ' for_emargement=true show_past=false show_adresse=false show_roles=false show_for_resp=true show_avatar=true max_dates=' . $args['max_dates'] . ' lieu=' . $lieu_id . ']' );
			}

			return $ret;
		}
	},
		[
			'desc' => 'Liste statique des inscrits des responsables aux distributions',
			'args' => [
				'show_title' => '(Par défaut “true”) Afficher les noms des lieux',
				'max_dates'  => '(Par défaut “-1”) Nombre maximum de distributions à venir à afficher',
				'lieu'       => 'Filtre de lieu',
			]
		] );
	amapress_register_shortcode( 'inscription-distrib', 'amapress_inscription_distrib_shortcode',
		[
			'desc' => 'Inscriptions comme responsable de distributions',
			'args' => [
				'show_past'         => '(Par défaut “false”) Afficher les distributions passées',
				'show_next'         => '(Par défaut “true”) Afficher les distributions à venir',
				'show_email'        => '(Par défaut “default”) Afficher les emails',
				'show_tel'          => '(Par défaut “default”) Afficher les numéros de téléphones',
				'show_tel_fixe'     => '(Par défaut “default”) Afficher les numéros de téléphones fixes',
				'show_tel_mobile'   => '(Par défaut “default”) Afficher les numéros de téléphones mobiles',
				'show_adresse'      => '(Par défaut “default”) Afficher les adresses',
				'show_avatar'       => '(Par défaut “default”) Afficher les avatars des amapiens',
				'show_roles'        => '(Par défaut “default”) Afficher les rôles des membres du collectif',
				'show_title'        => '(Par défaut “true”) Afficher les noms des lieux',
				'past_weeks'        => '(Par défaut “5”) Nombre de semaines d’historique des distributions',
				'max_dates'         => '(Par défaut “-1”) Nombre maximum de distributions à venir à afficher',
				'lieu'              => 'Filtre de lieu',
				'inscr_all_distrib' => '(Par défaut “false”) Autoriser tous les amapiens à s’inscrire même sur les lieux pour lesquels ils n’ont pas de contrat',
			]
		] );
	amapress_register_shortcode( 'anon-inscription-distrib', 'amapress_inscription_distrib_shortcode',
		[
			'desc' => 'Inscriptions comme responsable de distributions',
			'args' => [
				'key'               => '(Par exemple : ' . uniqid() . uniqid() . ') Clé de sécurisation de l\'accès à cet assistant d\'inscription aux distributions sans connexion',
				'show_past'         => '(Par défaut “false”) Afficher les distributions passées',
				'show_next'         => '(Par défaut “true”) Afficher les distributions à venir',
				'show_email'        => '(Par défaut “default”) Afficher les emails',
				'show_tel'          => '(Par défaut “default”) Afficher les numéros de téléphones',
				'show_tel_fixe'     => '(Par défaut “default”) Afficher les numéros de téléphones fixes',
				'show_tel_mobile'   => '(Par défaut “default”) Afficher les numéros de téléphones mobiles',
				'show_adresse'      => '(Par défaut “default”) Afficher les adresses',
				'show_avatar'       => '(Par défaut “default”) Afficher les avatars des amapiens',
				'show_roles'        => '(Par défaut “default”) Afficher les rôles des membres du collectif',
				'show_title'        => '(Par défaut “true”) Afficher les noms des lieux',
				'past_weeks'        => '(Par défaut “5”) Nombre de semaines d’historique des distributions',
				'max_dates'         => '(Par défaut “-1”) Nombre maximum de distributions à venir à afficher',
				'lieu'              => 'Filtre de lieu',
				'inscr_all_distrib' => '(Par défaut “false”) Autoriser tous les amapiens à s’inscrire même sur les lieux pour lesquels ils n’ont pas de contrat',
			]
		] );
	amapress_register_shortcode( 'resp-distrib-contacts', 'amapress_responsables_distrib_shortcode',
		[
			'desc' => 'Contacts des responsables de distribution',
			'args' => [
				'distrib' => '(Par défaut "2") Afficher les responsables pour ce nombre de distributions à venir'
			]
		] );
	amapress_register_shortcode( 'inscription-visite', 'amapress_inscription_visite_shortcode',
		[
			'desc' => 'Inscripions aux visites à la ferme',
			'args' => [
				'show_email'      => '(Par défaut “default”) Afficher les emails',
				'show_tel'        => '(Par défaut “default”) Afficher les numéros de téléphones',
				'show_tel_fixe'   => '(Par défaut “default”) Afficher les numéros de téléphones fixes',
				'show_tel_mobile' => '(Par défaut “default”) Afficher les numéros de téléphones mobiles',
				'show_adresse'    => '(Par défaut “default”) Afficher les adresses',
				'show_avatar'     => '(Par défaut “default”) Afficher les avatars des amapiens',
			]
		] );
	amapress_register_shortcode( 'inscription-amap-event', 'amapress_inscription_amap_event_shortcode',
		[
			'desc' => 'Inscriptions aux évènements AMAP',
			'args' => [
				'show_email'      => '(Par défaut “default”) Afficher les emails',
				'show_tel'        => '(Par défaut “default”) Afficher les numéros de téléphones',
				'show_tel_fixe'   => '(Par défaut “default”) Afficher les numéros de téléphones fixes',
				'show_tel_mobile' => '(Par défaut “default”) Afficher les numéros de téléphones mobiles',
				'show_adresse'    => '(Par défaut “default”) Afficher les adresses',
				'show_avatar'     => '(Par défaut “default”) Afficher les avatars des amapiens',
			]
		] );

//    amapress_register_shortcode('paniers-intermittents-list', 'amapress_intermittents_paniers_list_shortcode');
	amapress_register_shortcode( 'echanger-paniers-list', 'amapress_echanger_panier_shortcode',
		[
			'desc' => 'Liste d\'échange de paniers',
			'args' => [
			]
		] );
//
	amapress_register_shortcode( 'intermittents-inscription', 'amapress_intermittence_inscription_shortcode',
		[
			'desc' => 'Inscription d\'un amapien à la liste des intermittents',
			'args' => [
				'show_info' => '(Par défaut “yes”) Afficher les informations d\’inscription à la liste des intermittents',
			]
		] );
	amapress_register_shortcode( 'intermittents-desinscription', 'amapress_intermittence_desinscription_shortcode',
		[
			'desc' => 'Désinscription d\'un amapien à la liste des intermittents',
			'args' => [
			]
		] );


	amapress_register_shortcode( 'adhesion-request-count', 'amapress_adhesion_request_count_shortcode',
		[
			'desc' => '',
			'args' => [
			]
		] );

	amapress_register_shortcode( 'amapress-post-its', 'amapress_postits_shortcode',
		[
			'desc' => 'Post-its des tâches courantes (listes émargement..)',
			'args' => [
			]
		] );
	amapress_register_shortcode( 'amapress-ics-viewer', 'amapress_fullcalendar',
		[
			'desc' => 'Afficheur de calendrier ICAL/ICS',
			'args' => [
				'header_left'   => '(Par défaut “prev,next today”) Option de personnalisation de l\’entête partie gauche, voir <a href=”https://fullcalendar.io/docs/header” target=”_blank”>Options de fullcalendar</a>',
				'header_center' => '(Par défaut “title”) Option de personnalisation de l\’entête partie centrale, voir <a href=”https://fullcalendar.io/docs/header” target=”_blank”>Options de fullcalendar</a>',
				'header_right'  => '(Par défaut “month,listMonth,listWeek”) Option de personnalisation de l\’entête partie droite, voir <a href=”https://fullcalendar.io/docs/header” target=”_blank”>Options de fullcalendar</a>',
				'min_time'      => '(Par défaut “08:00:00”) Heure minimale affichée',
				'max_time'      => '(Par défaut “22:00:00”) Heure maximale affichée',
				'default_view'  => '(Par défaut “listMonth”) Type d’affichage <a href=”https://fullcalendar.io/docs#main”>Option Views de fullcalendar</a>',
				'url'           => 'Url du calendrier à afficher (ICS)',
			]
		] );
	amapress_register_shortcode( 'amapress-amapien-agenda-viewer', function ( $atts ) {
		$atts        = shortcode_atts(
			[ 'since_days' => 30 ],
			$atts );
		$atts['url'] = Amapress_Agenda_ICAL_Export::get_link_href( false, intval( $atts['since_days'] ) );

		return amapress_fullcalendar( $atts );
	},
		[
			'desc' => 'Calendrier de l\'amapien',
			'args' => [
				'since_days'    => '(Par défaut 30) Nombre de jours d\'historique de l\'agenda',
				'header_left'   => '(Par défaut “prev,next today”) Option de personnalisation de l\’entête partie gauche, voir <a href=”https://fullcalendar.io/docs/header” target=”_blank”>Options de fullcalendar</a>',
				'header_center' => '(Par défaut “title”) Option de personnalisation de l\’entête partie centrale, voir <a href=”https://fullcalendar.io/docs/header” target=”_blank”>Options de fullcalendar</a>',
				'header_right'  => '(Par défaut “month,listMonth,listWeek”) Option de personnalisation de l\’entête partie droite, voir <a href=”https://fullcalendar.io/docs/header” target=”_blank”>Options de fullcalendar</a>',
				'min_time'      => '(Par défaut “08:00:00”) Heure minimale affichée',
				'max_time'      => '(Par défaut “22:00:00”) Heure maximale affichée',
				'default_view'  => '(Par défaut “listMonth”) Type d’affichage <a href=”https://fullcalendar.io/docs#main”>Option Views de fullcalendar</a>',
			]
		] );
	amapress_register_shortcode( 'amapress-public-agenda-viewer', function ( $atts ) {
		$atts        = shortcode_atts(
			[ 'since_days' => 30, 'url' => '' ],
			$atts );
		$atts['url'] = Amapress_Agenda_ICAL_Export::get_link_href( true, intval( $atts['since_days'] ) );
		amapress_consider_logged( false );
		$ret = amapress_fullcalendar( $atts );
		amapress_consider_logged( true );

		return $ret;
	},
		[
			'desc' => 'Calendrier publique de l\'AMAP',
			'args' => [
				'since_days'    => '(Par défaut 30) Nombre de jours d\'historique de l\'agenda',
				'header_left'   => '(Par défaut “prev,next today”) Option de personnalisation de l\’entête partie gauche, voir <a href=”https://fullcalendar.io/docs/header” target=”_blank”>Options de fullcalendar</a>',
				'header_center' => '(Par défaut “title”) Option de personnalisation de l\’entête partie centrale, voir <a href=”https://fullcalendar.io/docs/header” target=”_blank”>Options de fullcalendar</a>',
				'header_right'  => '(Par défaut “month,listMonth,listWeek”) Option de personnalisation de l\’entête partie droite, voir <a href=”https://fullcalendar.io/docs/header” target=”_blank”>Options de fullcalendar</a>',
				'min_time'      => '(Par défaut “08:00:00”) Heure minimale affichée',
				'max_time'      => '(Par défaut “22:00:00”) Heure maximale affichée',
				'default_view'  => '(Par défaut “listMonth”) Type d’affichage <a href=”https://fullcalendar.io/docs#main”>Option Views de fullcalendar</a>',
			]
		] );

	amapress_register_shortcode( 'amapien-adhesions', 'amapress_display_user_adhesions_shortcode',
		[
			'desc' => 'Liste des inscriptions aux contrats pour un amapien',
			'args' => [
			]
		] );
	amapress_register_shortcode( 'amapien-edit-infos', 'amapress_edit_user_info_shortcode',
		[
			'desc' => 'Permet à un amapien de modifier ses coordonnées',
			'args' => [
			]
		] );
	amapress_register_shortcode( 'amapien-messages', 'amapress_user_messages_shortcode' );
	amapress_register_shortcode( 'amapien-messages-count', 'amapress_user_messages_count_shortcode' );
	amapress_register_shortcode( 'amapien-paniers-intermittents', 'amapress_user_paniers_intermittents_shortcode',
		[
			'desc' => 'Paniers proposés/échangés par un amapien',
			'args' => [
				'show_history' => '(Par défaut “false”) Afficher l\’historique des échanges de paniers de l\’amapien/intermittent',
				'history_days' => '(Par défaut “180”) Nombre de jour de l\’historique',
				'show_futur'   => '(Par défaut “true”) Afficher les échanges à venir',
			]
		] );
	amapress_register_shortcode( 'amapien-paniers-intermittents-count', 'amapress_user_paniers_intermittents_count_shortcode',
		[
			'desc' => 'Nombre de paniers proposés/échangés par un amapien',
			'args' => [
			]
		] );
	amapress_register_shortcode( 'les-paniers-intermittents', 'amapress_all_paniers_intermittents_shortcode',
		[
			'desc' => 'Paniers disponibles sur la liste des intermittents',
			'args' => [
				'contrat'        => 'Permet de filtrer les contrats pour lesquels les paneirs à échanger sont affichés',
				'allow_amapiens' => '(Par défaut “true”) Autoriser les amapiens à réserver des paniers',
			]
		] );
	amapress_register_shortcode( 'les-paniers-intermittents-count', 'amapress_all_paniers_intermittents_count_shortcode',
		[
			'desc' => 'Nombre de paniers disponibles sur la liste des intermittents',
			'args' => [
			]
		] );

	amapress_register_shortcode( 'mes-contrats', 'amapress_mes_contrats',
		[
			'desc' => 'Permet l\'inscription aux contrats complémentaires en cours d\'année',
			'args' => [
			]
		] );
	amapress_register_shortcode( 'inscription-en-ligne', 'amapress_self_inscription',
		[
			'desc' => 'Permet les inscriptions en ligne',
			'args' => [
				'key'                           => 'Clé de sécurisation de l\'accès à l\'Assistant de Préinscription en ligne',
				'filter_multi_contrat'          => '(booléen, false par défaut) : en cas de variante de contrat Semaine A/B/AB, ne pas autoriser un amapien à s\'inscrire à plusieurs variantes',
				'agreement'                     => '(booléen, false par défaut) : afficher une étape de réglement intérieur de l\'AMAP (configurable dans ' . Amapress::makeLink( admin_url( 'admin.php?page=amapress_gest_contrat_conf_opt_page&tab=config_online_inscriptions_messages' ), 'Tableau de bord > Gestion Contrats > onglet Assistant - Pré-inscription en ligne' ) . ')',
				'check_principal'               => '(booléen, true par défaut) : vérifier qu\'un contrat principal est actif',
				'adhesion'                      => '(booléen, true par défaut) : afficher une étape Adhésion à l\'AMAP',
				'send_referents'                => '(booléen, true par défaut) : envoyer une notification pour les nouvelles inscriptions aux référents',
				'send_tresoriers'               => '(booléen, true par défaut) : envoyer une notification pour les nouvelles inscriptions aux trésoriers',
				'edit_names'                    => '(booléen, true par défaut) : autoriser l\'édition des noms pour une réinscription',
				'only_contrats'                 => 'Filtrage des contrats affichés (par ID). Permet de faire une page dédiée à l\'inscription à un contrat donné avec une autre clé',
				'shorturl'                      => 'Url raccourcie de la page sur laquelle se trouve cet Assistant de Préinscription en ligne',
				'adhesion_shift_weeks'          => '(0 par défaut) Nombre de semaines de décalage entre le début des contrats et la période d\'Adhésion',
				'max_coadherents'               => '(3 par défaut) Nombre maximum de co-adhérents',
				'mob_phone_required'            => '(false par défaut) Téléphones (mobiles) requis',
				'allow_remove_coadhs'           => '(false par défaut) Autoriser la suppression des co-adhérents',
				'track_no_renews'               => '(false par défaut) Afficher une case "Je ne souhaite pas renouveler" et une zone Motif à l\'étape 1',
				'track_no_renews_email'         => '(email administrateir par défaut) Envoyer l\'email de notification de non renouvellement à cette adresse',
				'notify_email'                  => '(vide par défaut) Envoyer les emails de notification (Changement co-adhérents, Non renouvellement, Adhésion, Inscription) en copie à cette/ces adresse(s)',
				'show_adherents_infos'          => '(true par défaut) Afficher les infos sur l\'ahdérent et ses co-adhérents',
				'allow_coadherents_inscription' => '(true par défaut) Autoriser l\'inscription aux contrats par les co-adhérents',
				'allow_coadherents_access'      => '(true par défaut) Autoriser l\accès aux co-adhérents',
				'allow_coadherents_adhesion'    => '(true par défaut) Autoriser l\'adhésion à l\'AMAP par les co-adhérents',
				'show_coadherents_address'      => '(false par défaut) Afficher la saisie d\'adresse pour les co-adhérents',
				'contact_referents'             => '(true par défaut) Affiche un lien de contact des référents dans la liste des contrats déjà souscrit (étape 4/8)',
				'before_close_hours'            => '(24 par défaut) Clôturer la possibilité d\'inscription pour la prochaine distribution X heures avant',
				'email'                         => '(adresse email de l\'administrateur par défaut)Email de contact pour demander l\'accès à l\'Assistant ou en cas de problème',
			]
		] );

	amapress_register_shortcode( 'intermittent-paniers', 'amapress_intermittent_paniers_shortcode',
		[
			'desc' => 'Paniers réservés par un intermittent',
			'args' => [
				'show_history' => '(Par défaut “false”)  Afficher l\’historique des échanges de paniers de l\’amapien/intermittent',
				'history_days' => '(Par défaut “30”) Nombre de jour de l\’historique',
				'show_futur'   => '(Par défaut “true”) Afficher les échanges à venir',
			]
		] );


	amapress_register_shortcode( 'amapiens-map', 'amapress_amapiens_map_shortcode',
		[
			'desc' => 'Carte des amapiens',
			'args' => [
				'lieu'            => 'Afficher les amapiens ayant un contrat dans le lieu de distribution indiqué',
				'show_email'      => '(Par défaut “default”) Afficher les emails des amapiens',
				'show_tel'        => '(Par défaut “default”) Afficher les numéros de téléphones des amapiens',
				'show_tel_fixe'   => '(Par défaut “default”) Afficher les numéros de fixes des amapiens',
				'show_tel_mobile' => '(Par défaut “default”) Afficher les numéros de portables des amapiens',
				'show_adresse'    => '(Par défaut “default”) Afficher les adresses des amapiens',
				'show_avatar'     => '(Par défaut “default”) Afficher les photos des amapiens',
				'show_lieu'       => '(Par défaut “default”) Afficher le nom du lieu de distribution',
			]
		] );
	amapress_register_shortcode( 'amapiens-role-list', 'amapress_amapiens_role_list_shortcode',
		[
			'desc' => 'Liste des membres du collectif de l\'AMAP',
			'args' => [
				'lieu'            => 'Afficher les membres du collectif du lieu de distribution indiqué',
				'show_prod'       => '(Par défaut “false”) Afficher les producteurs',
				'show_email'      => '(Par défaut “force”) Afficher les emails des membres du collectif',
				'show_tel'        => '(Par défaut “default”) Afficher les numéros de téléphones des membres du collectif',
				'show_tel_fixe'   => '(Par défaut “default”) Afficher les numéros de fixes des membres du collectif',
				'show_tel_mobile' => '(Par défaut “force”) Afficher les numéros de portables des membres du collectif',
				'show_adresse'    => '(Par défaut “default”) Afficher les adresses des membres du collectif',
				'show_avatar'     => '(Par défaut “default”) Afficher les photos des membres du collectif',
			]
		] );
	amapress_register_shortcode( 'user-info', 'amapress_user_info_shortcode' );
	amapress_register_shortcode( 'next_events', 'amapress_next_events_shortcode',
		[
			'desc' => 'Calendrier des prochains évènements (Slider)',
			'args' => [
			]
		] );

	if ( amapress_is_user_logged_in() ) {
		amapress_register_shortcode( 'intermittent-desinscription-href', 'amapress_intermittence_desinscription_link',
			[
				'desc' => 'Lien de désinscription des intermittents',
				'args' => [
				]
			] );
	}

	amapress_register_shortcode( 'next-distrib-href', 'amapress_next_distrib_shortcode',
		[
			'desc' => 'Url de la page de la prochaine distributions',
			'args' => [
			]
		] );
	amapress_register_shortcode( 'next-distrib-link', 'amapress_next_distrib_shortcode',
		[
			'desc' => 'Lien vers la page de la prochaine distributions',
			'args' => [
			]
		] );
	amapress_register_shortcode( 'next-distrib-date', 'amapress_next_distrib_shortcode',
		[
			'desc' => 'Date de la prochaine distribution',
			'args' => [
			]
		] );
	amapress_register_shortcode( 'next-emargement-href', 'amapress_next_distrib_shortcode',
		[
			'desc' => 'Url de la page de la liste d\'émargement de la prochaine distributions',
			'args' => [
			]
		] );
	amapress_register_shortcode( 'next-emargement-link', 'amapress_next_distrib_shortcode',
		[
			'desc' => 'Lien vers la page de la liste d\'émargement de la prochaine distributions',
			'args' => [
			]
		] );
	amapress_register_shortcode( 'amapress-redirect-next-distrib', 'amapress_next_distrib_shortcode',
		[
			'desc' => 'Redirige vers la page de la prochaine distributions',
			'args' => [
			]
		] );
	amapress_register_shortcode( 'amapress-redirect-next-emargement', 'amapress_next_distrib_shortcode',
		[
			'desc' => 'Redirige vers la page de la liste d\'émargement de la prochaine distributions',
			'args' => [
			]
		] );

	amapress_register_shortcode( 'liste-emargement-button', function ( $atts, $content = null ) {
		if ( is_singular( AmapressDistribution::INTERNAL_POST_TYPE ) ) {
			$dist_id = get_the_ID();
			if ( empty( $content ) ) {
				$content = 'Imprimer la liste d\'émargement';
			}

			return amapress_get_button_no_esc( $content,
				amapress_action_link( $dist_id, 'liste-emargement' ), 'fa-fa',
				true, null, 'btn-print-liste' );
		}

		//TODO : for other place, next distrib
		return '';
	} );

	amapress_register_shortcode( 'display-if', function ( $atts, $content = null ) {
		$atts = shortcode_atts(
			array(
				'role' => 'logged',
			), $atts
		);
		$show = false;
		foreach ( explode( ',', $atts['role'] ) as $role ) {
			switch ( $role ) {
				case 'logged':
					$show = $show || amapress_is_user_logged_in();
					break;
				case 'not_logged':
					$show = $show || ! amapress_is_user_logged_in();
					break;
				case 'intermittent':
					$show = $show || AmapressContrats::is_user_active_intermittent();
					break;
				case 'no_contrat':
					$show = $show || count( AmapressContrat_instance::getContratInstanceIdsForUser() ) == 0;
					break;
				case 'responsable_distrib':
					$show = $show || AmapressDistributions::isCurrentUserResponsableThisWeek();
					break;
				case 'responsable_amap':
					$show = $show || amapress_can_access_admin();
					break;
				default:
					if ( strpos( $role, 'contrat_' ) === 0 ) {
						$contrat_id = Amapress::resolve_post_id( substr( $role, 8 ), AmapressContrat::INTERNAL_POST_TYPE );
						$show       = $show || count( AmapressContrat_instance::getContratInstanceIdsForUser( null, $contrat_id ) ) > 0;
					} else {
						$show = $show || amapress_current_user_can( $role );
					}
			}
		}

		return $show ? $content : '';
	},
		[
			'desc' => 'Affiche le contenu du shortcode suivant une condition (connecté, non connecté, membre du collectif, intermittent, responsable de distribution)',
			'args' => [
				'role' => '(Par défaut "logged") Afficher le contenu de ce shortcode uniquement si l\'amapien est dans un des rôles suivants : logged, not_logged, intermittent, no_contrat, responsable_distrib (est responsable de distribution cette semaine), responsable_amap (peut accéder au Tableau de Bord), contrat_xxx (a un contrat xxx)'
			]
		] );

	amapress_register_shortcode( 'nous-contacter', function ( $atts ) {
		amapress_ensure_no_cache();

		return Amapress::getContactInfos();
	},
		[
			'desc' => 'Contenu du formulaire de contact',
			'args' => [
			]
		] );

	amapress_register_shortcode( 'agenda-url', function ( $atts ) {
		$atts = shortcode_atts(
			[ 'since_days' => 30 ],
			$atts );
		$id   = 'agenda-url-' . md5( uniqid() );
		$url  = esc_attr( Amapress_Agenda_ICAL_Export::get_link_href(), intval( $atts['since_days'] ) );

		return "<div class='input-group'><input id='$id' type='text' value='$url' class='form-control' style='max-width: 80%' /><span class='input-group-addon'><button class='btn btn-secondary copy-agenda-url' type='button' data-clipboard-target='#{$id}'><span class='fa fa-copy' /></button></span><script type='text/javascript'>jQuery(function() { new Clipboard('.copy-agenda-url'); });</script></div>";
	},
		[
			'desc' => 'Copieur de lien de configuration de la synchronisation d\'un calendrier ICAL dans l\'agenda de l\'amapien',
			'args' => [
				'since_days' => '(Par défaut 30) Nombre de jours d\'historique de l\'agenda',
			]
		] );

	amapress_register_shortcode( 'front_next_events', function ( $atts ) {
		amapress_ensure_no_cache();

		$atts = shortcode_atts(
			array(
				'title' => 'yes',
			),
			$atts );

		$max            = amapress_is_user_logged_in() ? Amapress::getOption( 'agenda_max_dates', 5 ) : Amapress::getOption( 'agenda_max_public_dates', 5 );
		$agenda_content = do_shortcode( '[next_events max=' . $max . ']' );
		if ( ! Amapress::toBool( $atts['title'] ) ) {
			return $agenda_content;
		}
//    $agenda = '';
//    if (trim(wp_strip_all_tags($agenda_content, true)) != '') {
//    (amapress_is_user_logged_in() ? ' (<a href="' . Amapress_Agenda_ICAL_Export::get_link_href() . '"><i class="fa fa-calendar" aria-hidden="true"></i> iCal</a>)' : '') . '</h2>' .
		$agenda = '<h3 id="front-adgenda-title">' . ( amapress_is_user_logged_in() ? Amapress::getOption( 'front_agenda_title', 'Cette semaine dans mon panier...' ) : Amapress::getOption( 'front_agenda_public_title', 'Agenda' ) ) . '</h3>' .
		          $agenda_content;

//    }
		return $agenda;
	},
		[
			'desc' => 'Affiche le calendrier de la page d\'accueil',
			'args' => [
				'title' => '(Par défaut “yes”) Afficher le titre de la section',
			]
		] );
	amapress_register_shortcode( 'front_produits', function ( $atts ) {
		amapress_ensure_no_cache();

		$atts = shortcode_atts(
			array(
				'title' => 'yes',
			),
			$atts );

		$produits_content = Amapress::get_contrats_list();
		$produits         = '';
		if ( Amapress::toBool( $atts['title'] ) ) {
			$produits = '<h3 id="front-produits-title">' . Amapress::getOption( 'front_produits_title', 'Les produits de l\'Amap...' ) . '</h3>';
		}
		if ( trim( wp_strip_all_tags( $produits_content, true ) ) != '' ) {
			$interm = '';
//        if (Amapress::isIntermittenceEnabled() && Amapress::userCanRegister()) {
//            $interm = amapress_get_button('Devenir intermittent', Amapress::getMesInfosSublink('adhesions/intermittence/inscription'));
//        }
//			if ( Amapress::isIntermittenceEnabled() ) {
//				$interm = do_shortcode( '[intermittents-inscription view=me show_info=no]' );
//			}

			$produits .= $produits_content . $interm;
		}

		return $produits;
	},
		[
			'desc' => 'Affiche la liste des producteurs/productions pour la page d\'acceuil',
			'args' => [
				'title' => '(Par défaut “yes”) Afficher le titre de la section',
			]
		] );
	amapress_register_shortcode( 'front_nous_trouver', function ( $atts ) {
		amapress_ensure_no_cache();

		$atts = shortcode_atts(
			array(
				'title' => 'yes',
			),
			$atts );

		$map_content = do_shortcode( '[nous-trouver]' );
		if ( ! Amapress::toBool( $atts['title'] ) ) {
			return $map_content;
		}
		$map = '<h3 id="front-map-title">' . Amapress::getOption( 'front_map_title', 'Où nous trouver ?' ) . '</h3>' . $map_content;

		return $map;
	},
		[
			'desc' => 'Affiche la carte des lieux de distributions pour la page d\'accueil',
			'args' => [
				'title' => '(Par défaut “yes”) Afficher le titre de la section',
			]
		] );
	amapress_register_shortcode( 'front_default_grid', function ( $atts ) {
		amapress_ensure_no_cache();

		$atts = shortcode_atts(
			array(
				'title'            => 'yes',
				'agenda-classes'   => 'col-lg-4 col-md-6 col-sm-6 col-xs-12',
				'produits-classes' => 'col-lg-4 col-md-6 col-sm-6 col-xs-12',
				'map-classes'      => 'col-lg-4 col-md-12 col-sm-12 col-xs-12',
			),
			$atts );

		$agenda   = do_shortcode( '[front_next_events title=yes]' );
		$produits = do_shortcode( '[front_produits title=yes]' );
		$map      = do_shortcode( '[front_nous_trouver title=yes]' );

		return '<div class="front-page">
                    <div class="row">
                        <div class="' . $atts['agenda-classes'] . '">' . $agenda . '</div>
                        <div class="' . $atts['produits-classes'] . '">' . $produits . '</div>
                        <div class="' . $atts['map-classes'] . '">' . $map . '</div>
                    </div>
                </div>';
	},
		[
			'desc' => 'Affiche les infos de la page d\'accueil (calendrier, productions, carte)',
			'args' => [
				'title'            => '(Par défaut “yes”) Afficher le titre des trois sections (Agenda/Produits/Carte) de la grille par défaut',
				'agenda-classes'   => '(Par défaut “col-lg-4 col-md-6 col-sm-6 col-xs-12”) Nom des classes CSS appliquées pour le formatage de la grille',
				'produits-classes' => '(Par défaut “col-lg-4 col-md-6 col-sm-6 col-xs-12”) Nom des classes CSS appliquées pour le formatage de la grille',
				'map-classes'      => '(Par défaut “col-lg-4 col-md-12 col-sm-12 col-xs-12”) Nom des classes CSS appliquées pour le formatage de la grille',
			]
		] );

	amapress_register_shortcode( 'listes-diffusions', function ( $atts ) {
		if ( ! amapress_is_user_logged_in() ) {
			return '';
		}

		$atts = shortcode_atts(
			array(
				'sms' => 'yes',
			),
			$atts );

		ob_start();

		$do_sms_link = Amapress::toBool( $atts['sms'] ) && amapress_can_access_admin();
		$entries     = [];
		foreach ( Amapress_MailingListConfiguration::getAll() as $mailing_list_configuration ) {
			$li   = '<li>';
			$name = $mailing_list_configuration->getAddress();
			$desc = $mailing_list_configuration->getDescription();
			$li   .= Amapress::makeLink( "mailto:$name", $name );
			if ( $do_sms_link ) {
				$li .= ' ; ' . Amapress::makeLink( $mailing_list_configuration->getMembersSMSTo(), 'Envoyer un SMS aux membres' );
			}
			if ( ! empty( $desc ) ) {
				$li .= "<br/><em>$desc</em>";
			}
			$li        .= '</li>';
			$entries[] = $li;
		}
		foreach ( AmapressMailingGroup::getAll() as $ml ) {
			$li   = '<li>';
			$name = $ml->getName();
			$desc = $ml->getDescription();
			$li   .= Amapress::makeLink( "mailto:$name", $name );
			if ( $do_sms_link ) {
				$li .= ' ; ' . Amapress::makeLink( $ml->getMembersSMSTo(), 'Envoyer un SMS aux membres' );
			}
			if ( ! empty( $desc ) ) {
				$li .= "<br/><em>$desc</em>";
			}
			$li        .= '</li>';
			$entries[] = $li;
		}
		sort( $entries );
		echo '<ul>';
		echo implode( '', $entries );
		echo '</ul>';

		return ob_get_clean();
	},
		[
			'desc' => 'Liste des liste de diffusions (SYMPA/SudOuest/Emails groupés) configurées sur le site',
			'args' => [
				'sms' => '(Par défaut “yes”) Afficher un lien SMS-To contenant tous les membres de chaque liste de diffusion',
			]
		] );
}

