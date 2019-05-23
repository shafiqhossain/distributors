/**
 * @file
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.distributors_map = {
    attach: function (context, settings) {

      var zoom_level = settings.map_zoom_level;
      var google_api_key = settings.api_key;
      var latitude = settings.lat;
      var longitude = settings.long;

	  var bounds = new google.maps.LatLngBounds();
	  var markers = [];

      var mapData = settings.mapData;
      var addressData = JSON.stringify(settings.all_address);

      // Parse JSON.
	  if (typeof mapData === 'undefined') {
        var json_obj = $.parseJSON(addressData);
  	  }
  	  else {
        var json_obj = $.parseJSON(mapData);
	  }

      var locations = [];
      for (var i in json_obj) {
        if (i) {
          var arr = json_obj[i].location_name + json_obj[i].location_address;
          locations.push([arr, json_obj[i].lat, json_obj[i].long]);
        }
      }

      if ($('#map').length > 0) {
        var map = new google.maps.Map(document.getElementById('map'), {
          zoom: parseInt(zoom_level),
          center: new google.maps.LatLng(latitude, longitude),
          minZoom: 2,
          mapTypeId: google.maps.MapTypeId.ROADMAP
        });

        var infowindow = new google.maps.InfoWindow();
        var marker;
        var j;
        for (j = 0; j < locations.length; j++) {
          marker = new google.maps.Marker({
            position: new google.maps.LatLng(locations[j][1], locations[j][2]),
            map: map
          });

		  bounds.extend(marker.position);
  		  markers.push(marker);

          google.maps.event.addListener(marker, 'click', (function (marker, j) {
            return function () {
              infowindow.setContent(locations[j][0]);

			  google.maps.event.addDomListener(infowindow, 'domready', function() {
				$("[id^=distributor-link-]").click(function(ev) {
  					ev.preventDefault();
  					ev.stopPropagation();

	    			var idStr = this.id;
	    			var idArr = idStr.split("-");
	    			if(idArr.length == 0) return;

					var nid = '';
					if (idArr[2] == undefined) {
					  nid = '';
					}
					else {
					  nid = idArr[2];
					}
			  		var item_link = 'distributor-item-'+nid;
					$('#'+item_link).hide().prependTo("#distributors-search-list-grid-wrapper").fadeIn(1200);
				});
			  });

              infowindow.open(map, marker);
            };
          })(marker, j));
        }


		var markerCluster = new MarkerClusterer(map, markers, {imagePath: '/libraries/v3-utility-library/markerclusterer/images/m'});

    	map.fitBounds(bounds);
    	map.panToBounds(bounds);

		$("[id^=distributor-title-]").click(function(ev) {
			ev.preventDefault();
			ev.stopPropagation();

			var idStr = this.id;
			var lat = $(this).attr('data-lat');
			var lng = $(this).attr('data-lng');

			lat = 1*lat;
			lng = 1*lng;

			var idArr = idStr.split("-");
			if(idArr.length == 0) return;

			var nid = '';
			if (idArr[2] == undefined) {
			  nid = '';
			}
			else {
			  nid = idArr[2];
			}
			var item_link = 'distributor-item-'+nid;

	  		var newbounds = new google.maps.LatLngBounds();
			var newLatLng = new google.maps.LatLng(lat, lng);
			newbounds.extend(newLatLng);
			map.setCenter({lat:lat, lng:lng});
			map.fitBounds(newbounds);
			map.panToBounds(newbounds);
		});
  	  }


      //});
    }  //attach
  };  //behaviors



})(jQuery, Drupal, drupalSettings);
