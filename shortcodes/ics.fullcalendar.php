<?php
/**
 * Created by PhpStorm.
 * User: Guillaume
 * Date: 31/01/2019
 * Time: 08:24
 * Original code from https://github.com/larrybolt/online-ics-feed-viewer
 */


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function amapress_fullcalendar( $atts ) {
	static $amapress_fullcalendar = 1;
	$id   = 'amp_fullcalendar' . $amapress_fullcalendar ++;
	$atts = shortcode_atts(
		[
			'header_left'   => 'prev,next today',
			'header_center' => 'title',
			'header_right'  => 'month,listMonth,listWeek',
			'min_time'      => '08:00:00',
			'max_time'      => '22:00:00',
			'default_view'  => 'listMonth',
			'url'           => '',
		],
		$atts
	);

	if ( empty( $atts['url'] ) ) {
		return 'Aucune source configurée pour le calendrier';
	}

	//'https://cors-anywhere.herokuapp.com/'

	ob_start();
	?>
    <script type="text/javascript">
        jQuery(document).ready(function ($) {
            $('#<?php echo $id; ?>').fullCalendar({
                defaultView: '<?php echo $atts['default_view']; ?>',
                locale: 'fr',
                timezone: 'local',
                header: {
                    left: '<?php echo $atts['header_left']; ?>',
                    center: '<?php echo $atts['header_center']; ?>',
                    right: '<?php echo $atts['header_right']; ?>'
                },
                views: {
                    listDay: {buttonText: 'Par jours'},
                    listWeek: {buttonText: 'Par semaines'},
                    listMonth: {buttonText: 'Par mois'}
                },
                navLinks: true, // can click day/week names to navigate views
                editable: false,
                minTime: "<?php echo $atts['min_time']; ?>",
                maxTime: "<?php echo $atts['max_time']; ?>",
            });
            $.get('<?php echo $atts['url']; ?>', function (res) {
                var events = [];
                var parsed = ICAL.parse(res);
                parsed[2].forEach(function (event) {
                    if (event[0] !== 'vevent') return;
                    var summary, location, start, end, url, description;
                    event[1].forEach(function (event_item) {
                        switch (event_item[0]) {
                            case 'location':
                                location = event_item[3];
                                break;
                            case 'summary':
                                summary = event_item[3];
                                break;
                            case 'description':
                                description = event_item[3];
                                break;
                            case 'url':
                                url = event_item[3];
                                break;
                            case 'dtstart':
                                start = event_item[3];
                                break;
                            case 'dtend':
                                end = event_item[3];
                                break;
                        }
                    });
                    if (summary && location && start && end) {
                        // console.log(summary, 'at', start);
                        var title = summary;
                        if (description)
                            title += ' / ' + description;
                        // if (location)
                        //     title += ' (' + location + ')';
                        events.push({
                            title: title,
                            start: start,
                            end: end,
                            url: url,
                            location: location
                        })
                    }
                });
                $('#<?php echo $id; ?>').fullCalendar('removeEventSources');
                $('#<?php echo $id; ?>').fullCalendar('addEventSource', events);
            })
        });
    </script>
    <div id="<?php echo $id; ?>"></div>
	<?php

	return ob_get_clean();
}