/**
 * @file
 * Modal window behaviors.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Attach handlers to resize the modal window
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   */
  Drupal.behaviors.distributors = {
    attach: function (context, settings) {

    }
  };

  if($("input[name^='address_box']").length>0) {
    $("input[name^='address_box']").geocomplete();
  }

  if (!navigator.geolocation) {
    //console.log( 'Track your location is blocked.');
    alert(Drupal.t('Track your location is blocked.'));
  }
  else {
	/* HTML5 Geolocation */
	navigator.geolocation.getCurrentPosition(
		function( position ){ // success cb

			/* Current Coordinate */
			var lat = position.coords.latitude;
			var lng = position.coords.longitude;
			var google_map_pos = new google.maps.LatLng( lat, lng );

			/* Use Geocoder to get address */
			var google_maps_geocoder = new google.maps.Geocoder();
			google_maps_geocoder.geocode(
				{ 'latLng': google_map_pos },
				function( results, status ) {
					if ( status == google.maps.GeocoderStatus.OK && results[0] ) {
						//console.log( results[0].formatted_address );
						$('#edit-address-box').val(results[0].formatted_address);
						$(".distributors-search-box .actions .form-submit" ).trigger( "click" );

						return false;
					}
				}
			);
		},
		function(){ // fail cb
		   console.log( 'Failed to get address' );
		   //alert(Drupal.t('Failed to get address'));
		   return false;
		}
	);


    $( "#distributors-location-icon" ).click( function(e) {
        e.preventDefault();

        /* Chrome need SSL! */
        var is_chrome = /chrom(e|ium)/.test( navigator.userAgent.toLowerCase() );
        var is_ssl    = 'https:' == document.location.protocol;
        if( is_chrome && ! is_ssl ){
    		alert(Drupal.t('https need to enable.'));
            return false;
        }

        /* HTML5 Geolocation */
        navigator.geolocation.getCurrentPosition(
            function( position ){ // success cb

                /* Current Coordinate */
                var lat = position.coords.latitude;
                var lng = position.coords.longitude;
                var google_map_pos = new google.maps.LatLng( lat, lng );

                /* Use Geocoder to get address */
                var google_maps_geocoder = new google.maps.Geocoder();
                google_maps_geocoder.geocode(
                    { 'latLng': google_map_pos },
                    function( results, status ) {
                        if ( status == google.maps.GeocoderStatus.OK && results[0] ) {
                            //console.log( results[0].formatted_address );
                            $('#edit-address-box').val(results[0].formatted_address);
                            return false;
                        }
                    }
                );
            },
            function(){ // fail cb
               console.log( 'Failed to get address' );
               //alert(Drupal.t('Failed to get address'));
               return false;
            }
        );
    });

  }


})(jQuery, Drupal, drupalSettings);
