<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

//function amapress_generate_map($location_type, $latitude, $longitude, $url, $title,
//                               $mode='map') {
//
//}

function amapress_generate_map( $markers, $mode = 'map' ) {
	if ( count( $markers ) == 0 ) {
		return '<p>Aucune localisation disponible</p>';
	}

	static $amapress_map_instance = 0;
	$amapress_map_instance ++;

	$latitude  = $markers[0]['latitude'];
	$longitude = $markers[0]['longitude'];

	$icons              = array();
	$icons['arrow']     = 'https://maps.google.com/mapfiles/arrow.png';
	$icons['flag']      = 'https://maps.google.com/mapfiles/kml/pal2/icon13.png';
	$icons['home']      = 'https://maps.google.com/mapfiles/kml/pal3/icon31.png';
	$icons['yellow']    = 'https://maps.google.com/mapfiles/ms/micons/yellow-dot.png';
	$icons['blue']      = 'https://maps.google.com/mapfiles/ms/micons/blue-dot.png';
	$icons['green']     = 'https://maps.google.com/mapfiles/ms/micons/green-dot.png';
	$icons['lightblue'] = 'https://maps.google.com/mapfiles/ms/micons/ltblue-dot.png';
	$icons['orange']    = 'https://maps.google.com/mapfiles/ms/micons/orange-dot.png';
	$icons['pink']      = 'https://maps.google.com/mapfiles/ms/micons/pink-dot.png';
	$icons['purple']    = 'https://maps.google.com/mapfiles/ms/micons/purple-dot.png';
	$icons['red']       = 'https://maps.google.com/mapfiles/ms/micons/red-dot.png';
	$icons['lieu']      = 'https://maps.google.com/mapfiles/ms/micons/convienancestore.png';
	$icons['man']       = 'https://maps.google.com/mapfiles/ms/micons/man.png';
	$icons['tree']      = 'https://maps.google.com/mapfiles/ms/micons/tree.png';

	$coords     = [];
	$js_markers = '';
	foreach ( $markers as $marker ) {
		if ( empty( $marker['latitude'] ) || empty( $marker['longitude'] ) ) {
			continue;
		}
		if ( empty( $marker['icon'] ) ) {
			$marker['icon'] = 'red';
		}
//        $content = empty($marker['content']) ? '\'\'' : '\''.esca($marker['content']).'\'';
		if ( empty( $marker['icon'] ) || ! isset( $icons[ $marker['icon'] ] ) ) {
			unset( $marker['icon'] );
		} else {
			$marker['icon'] = $icons[ $marker['icon'] ];
		}
		$js_markers .= json_encode( $marker );
		$js_markers .= ',';
		$coords[]   = [ floatval( $marker['latitude'] ), floatval( $marker['longitude'] ) ];
	}
	$js_markers = trim( $js_markers, ',' );

	$map_provider = Amapress::getOption( 'map_provider' );
	if ( 'google' == $map_provider ) {
		$js_acces = 'var acces = pos;';
		if ( isset( $markers[0]['access'] ) && is_array( $markers[0]['access'] ) ) {
			$js_acces = 'var acces = new google.maps.LatLng(' .
			            $markers[0]['access']['latitude'] .
			            ',' . $markers[0]['access']['longitude'] . ');';
		}
		$sv_js = 'var panorama = new google.maps.StreetViewPanorama(
                      document.getElementById(\'pano' . $amapress_map_instance . '\'), {
                        position: acces,
                      });
                    map.setStreetView(panorama);
                    var street_markers = [' . $js_markers . '];
                    for (var i = 0; i < street_markers.length; i++) {
                        var marker = street_markers[i];
                        var mark_street = new google.maps.Marker({
                                            position: new google.maps.LatLng(parseFloat(marker.latitude),parseFloat(marker.longitude)),
                                            url:marker.url,
                                            title: marker.title,
                                            label: marker.label,
                                            icon: marker.icon
                                        });
                        var infowindow = new google.maps.InfoWindow({
                          content: (mark_street.url ? "<h4><a href="+mark_street.url+" target=\'_blank\'>"+mark_street.title+"<a/></h4>" : "<h4>"+mark_street.title+"</h4>") + (marker.content || "")
                        });
                        mark_street.setMap(panorama);
                        mark_street.infoWnd = infowindow;
                        google.maps.event.addListener(mark_street, \'click\', function() {
                            this.infoWnd.open(panorama, this);
                        });
                    }
                    var service = new google.maps.StreetViewService;
                    // call the "getPanoramaByLocation" function of the Streetview Services to return the closest streetview position for the entered coordinates
                      service.getPanoramaByLocation(panorama.getPosition(), 25, function(panoData) {
                        // if the function returned a result
                        if (panoData != null) {
                          // the GPS coordinates of the streetview camera position
                          var panoCenter = panoData.location.latLng;
                          // this is where the magic happens!
                          // the "computeHeading" function calculates the heading with the two GPS coordinates entered as parameters
                          var heading = google.maps.geometry.spherical.computeHeading(panoCenter, pos);
                          // now we know the heading (camera direction, elevation, zoom, etc) set this as parameters to the panorama object
                          var pov = panorama.getPov();
                          pov.heading = heading;
                          panorama.setPov(pov);
                        }
                      });';
		if ( $mode == 'map+streeview' ) {
			$htm = '<div id="map' . $amapress_map_instance . '" style="height:450px;" class="col-md-6 col-sm-12"></div>
                <div id="pano' . $amapress_map_instance . '" style="height:450px" class="col-md-6 col-sm-12"></div>';
		} else if ( $mode == 'streeview' ) {
			$htm = '<div id="map' . $amapress_map_instance . '" style="display:none"></div>
            <div id="pano' . $amapress_map_instance . '" style="height:450px"></div>';
		} else {
			$sv_js = '';
			$htm   = '<div id="map' . $amapress_map_instance . '" style="height:450px"></div>';
		}

		return $htm . '<script type="text/javascript">
                //<![CDATA[
                var map;
                function initMap' . $amapress_map_instance . '() {
                  var pos = new google.maps.LatLng(' . $latitude . ',' . $longitude . ');
                  ' . $js_acces . '
                  var map = new google.maps.Map(document.getElementById(\'map' . $amapress_map_instance . '\'), {
                    center: pos,
                    zoom: 14
                  });
//                var bikeLayer = new google.maps.BicyclingLayer();
//                bikeLayer.setMap(map);
//                var transitLayer = new google.maps.TransitLayer();
//                transitLayer.setMap(map);
                var markers = [' . $js_markers . '];//some array

                for (var i = 0; i < markers.length; i++) {
                    var mk = markers[i];
                    var marker = new google.maps.Marker({
                                        position: new google.maps.LatLng(parseFloat(mk.latitude), parseFloat(mk.longitude)),
                                        url:mk.url,
                                        icon:mk.icon,
                                        label:mk.label,
                                        title: mk.title
                                    });
                    var infowindow = new google.maps.InfoWindow({
                      content: (marker.url ? "<h4><a href="+marker.url+" target=\'_blank\'>"+marker.title+"<a/></h4>" : "<h4>"+marker.title+"</h4>") + (mk.content || "")
                    });
                    marker.setMap(map);
                    marker.infoWnd = infowindow;
                    google.maps.event.addListener(marker, \'click\', function() {
                        this.infoWnd.open(map, this);
                    });
                }
                var margin = 100;
                var bounds = new google.maps.LatLngBounds();
                for (var i = 0; i < markers.length; i++) {
                    var mk = markers[i];
                    bounds.extend(new google.maps.LatLng(parseFloat(mk.latitude), parseFloat(mk.longitude)));
                }
                // Don\'t zoom in too far on only one marker
                if (bounds.getNorthEast().equals(bounds.getSouthWest())) {
                    var lat = bounds.getNorthEast().lat();
                    var lng = bounds.getNorthEast().lng();
                    var coef_lat = margin * 0.0000089;
                    var coef_long = coef_lat / Math.cos(lat * 0.018);
                   var extendPoint1 = new google.maps.LatLng(lat + coef_lat, lng + coef_long);
                   var extendPoint2 = new google.maps.LatLng(lat - coef_lat, lng - coef_long);
                   bounds.extend(extendPoint1);
                   bounds.extend(extendPoint2);
                }
                map.fitBounds(bounds);

                ' . $sv_js . '
                }
                //]]>
            </script>
            <script async="async" defer="defer"
              src="https://maps.googleapis.com/maps/api/js?key=' . TitanFrameworkOptionAddress::$google_map_api_key . '&callback=initMap' . $amapress_map_instance . '">
            </script>';
	} else if ( 'openstreetmap' == $map_provider ) {
		$htm = '<div id="map' . $amapress_map_instance . '" style="height:450px; width: 100%"></div>';

		return $htm . '<script type="text/javascript">
                //<![CDATA[
var map = L.map(\'map' . $amapress_map_instance . '\').fitBounds(' . json_encode( $coords ) . ');
// add an OpenStreetMap tile layer
L.tileLayer(\'https://{s}.tile.osm.org/{z}/{x}/{y}.png\', {
    attribution: \'&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors\'
}).addTo(map);

                var markers = [' . $js_markers . '];

                for (var i = 0; i < markers.length; i++) {
                    var marker = markers[i];
					// add a marker in the given location, attach some popup content to it and open the popup
					var m = L.marker([parseFloat(marker.latitude), parseFloat(marker.longitude)], {
					    title: marker.title, 
					    icon: marker.icon ? L.icon({iconUrl: marker.icon}) : null}).addTo(map);
					    m.bindPopup((marker.url ? "<h4><a href="+marker.url+" target=\'_blank\'>"+marker.title+"<a/></h4>" : "<h4>"+marker.title+"</h4>") + (marker.content || ""));
                }
                //]]>
</script>';
	}
}