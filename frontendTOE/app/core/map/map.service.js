/**
 * Created by LENOVO-T430 on 2/15/2017.
 */
angular.module('core.map').service('Map', ['Request', 'User', function (Request, User) {

	var mapService = this;
	/**
	 * Corrects the zoom of a LatLngBounds object by adding in two additional points that stretches out the bounds.
	 *
	 * @param {int} zoom The zoom you'd like the map to be at after setting the new bounds
	 * @param {google.maps.LatLngBounds} boundsObj A bounds object with one point in it that is going to be called with fitBounds by the map object
	 */
	this.correctZoomAfterSearch = function (zoom, boundsObj) {
		var neLatOffset = 0;
		var neLngOffset = 0;
		var swLatOffset = 0;
		var swLngOffset = 0;
		switch (zoom) {
			case 1 :
				neLatOffset = 36.89762137462591;
				neLngOffset = 116.54296875;

				swLatOffset = -85.47714446643732;
				swLngOffset = 243.45703125;
				break;
			case 2 :
				neLatOffset = 25.081351240246605;
				neLngOffset = 58.271484375;

				swLatOffset = -42.36865697708525;
				swLngOffset = -58.271484375;

				break;
			case 3 :
				neLatOffset = 14.702262326542282;
				neLngOffset = 29.1357421875;

				swLatOffset = -19.44651901628857;
				swLngOffset = -29.1357421875;

				break;
			case 4 :
				neLatOffset = 7.943678593818063;
				neLngOffset = 14.56787109375;

				swLatOffset = -9.157301765885101;
				swLngOffset = -14.56787109375;

				break;
			case 5 :
				neLatOffset = 4.123836721578229;
				neLngOffset = 7.283935546875;

				swLatOffset = -4.428977571500795;
				swLngOffset = -7.283935546875;

				break;
			case 6 :
				neLatOffset = 2.100135568832691;
				neLngOffset = 3.6419677734375;

				swLatOffset = -2.176529392300381;
				swLngOffset = -3.6419677734375;

				break;
			case 7 :
				neLatOffset = 1.0596292136844454;
				neLngOffset = 1.82098388671875;

				swLatOffset = -1.078734460280522;
				swLngOffset = -1.82098388671875;

				break;
			case 8 :
				neLatOffset = 0.5322044838316344;
				neLngOffset = 0.910491943359375;

				swLatOffset = -0.5369812199405075;
				swLngOffset = -0.910491943359375;

				break;
			case 9 :
				neLatOffset = 0.2666995618327377;
				neLngOffset = 0.4552459716796875;

				swLatOffset = -0.2678937723892929;
				swLngOffset = -0.4552459716796875;

				break;
			case 10 :
				neLatOffset = 0.13349908653706422;
				neLngOffset = 0.22762298583984375;

				swLatOffset = -0.13379764083430246;
				swLngOffset = -0.22762298583984375;

				break;
			case 11 :
				neLatOffset = 0.06678686626965202;
				neLngOffset = 0.11381149291992188;

				swLatOffset = -0.06686150494761023;
				swLngOffset = -0.11381149291992188;

				break;
			case 12 :
				neLatOffset = 0.033402763437031524;
				neLngOffset = 0.05690574645996094;

				swLatOffset = -0.03342142311298346;
				swLngOffset = -0.05690574645996094;

				break;
			case 13 :
				neLatOffset = 0.016703714236655287;
				neLngOffset = 0.02845287322998047;

				swLatOffset = -0.016708379156035846;
				swLngOffset = -0.02845287322998047;

				break;
			case 14 :
				neLatOffset = 0.00835244024059989;
				neLngOffset = 0.014226436614990234;

				swLatOffset = -0.008353606470471675;
				swLngOffset = -0.014226436614990234;

				break;
			case 15 :
				neLatOffset = 0.004176365899951406;
				neLngOffset = 0.007113218307495117;

				swLatOffset = -0.004176657457442445;
				swLngOffset = -0.007113218307495117;

				break;
			case 16 :
				neLatOffset = 0.002088219394757118;
				neLngOffset = 0.0035566091537475586;
				swLatOffset = -0.002088292284149418;
				swLngOffset = -0.0035566091537475586;
				break;
			case 17 :
				neLatOffset = 0.0010441188085579256;
				neLngOffset = 0.0017783045768737793;
				swLatOffset = -0.0010441370309095532;
				swLngOffset = -0.0017783045768737793;
				break;
			case 18 :
				neLatOffset = 0.0005220616820835744;
				neLngOffset = 0.0008891522884368896;
				swLatOffset = -0.0005220662376643759;
				swLngOffset = -0.0008891522884368896;
				break;
			case 19:
			case 20:
			default:
				neLatOffset = 0.0002610314104600775;
				neLngOffset = 0.0004445761442184448;
				swLatOffset = -0.00026103254939613407;
				swLngOffset = -0.0004445761442184448;
				break;
		}

		var extendPoint1 = new google.maps.LatLng(boundsObj.getCenter().lat() + neLatOffset, boundsObj.getCenter().lng() + neLngOffset);
		var extendPoint2 = new google.maps.LatLng(boundsObj.getCenter().lat() + swLatOffset, boundsObj.getCenter().lng() + swLngOffset);
		boundsObj.extend(extendPoint1);
		boundsObj.extend(extendPoint2);
	};

	/**
	 * creates a proper latlng object that is used by the googlemaps api
	 * @param {float} lat
	 * @param {float} lng
	 * @returns {{lat: *, lng: *}}
	 */
	this.newLatLngObj = function (lat, lng) {
		return {lat: lat, lng: lng};
	};

	this.initMap = function (latLng, zoom, mapElementId, markerList, markerUrl) {
		var map = new google.maps.Map(document.getElementById(mapElementId), {
			zoom: zoom,
			center: latLng
		});

		if (markerList) {
			markerList.push(mapService.addMarker(latLng, map, markerUrl));
		}

		return map;
	};

	/**
	 *
	 * @param {google.maps.Map} map A map object
	 * @param {String} searchBoxElementId The element ID of the input that will become the search box.
	 * @param {Array} markerList An initialized array that will have the new markers pushed onto.
	 * @param {String} iconUrl A URL to the icon to be used when searching for a new place
	 */
	this.addSearchBar = function (map, searchBoxElementId, markerList, iconUrl) {
		// Create the search box and link it to the UI element.
		var input = document.getElementById(searchBoxElementId);
		var searchBox = new google.maps.places.SearchBox(input);

		// Bias the SearchBox results towards current map's viewport.
		map.addListener('bounds_changed', function () {
			searchBox.setBounds(map.getBounds());
		});

		// Listen for the event fired when the user selects a prediction and retrieve
		// more details for that place.
		searchBox.addListener('places_changed', function () {
			var places = searchBox.getPlaces();

			if (places.length == 0) {
				return;
			}

			// For each place, get the icon, name and location.
			var bounds = new google.maps.LatLngBounds();
			places.forEach(function (place) {
				if (!place.geometry) {
					return;
				}

				// mapService.addMarker({lat: place.geometry.location.lat(), lng: place.geometry.location.lng()}, $scope.map);
				if (markerList) {
					markerList.forEach(function(marker) {
						mapService.clearMarker(marker);
					});
					markerList.length = 0;
					markerList.push(mapService.addMarker(place.geometry.location, map, iconUrl));
				}

				if (place.geometry.viewport) {
					// Only geocodes have viewport.
					bounds.union(place.geometry.viewport);
				} else {
					bounds.extend(place.geometry.location);

				}
				mapService.correctZoomAfterSearch(map.getZoom(), bounds);

			});
			map.fitBounds(bounds);
		});

	};

	/**
	 * Adds a marker at the location passed in to the map passed in. Icon is the URL passed in. Returns the marker.
	 * @param location
	 * @param map
	 * @param url
	 * @returns {google.maps.Marker}
	 */
	this.addMarker = function (location, map, url) {
		var image = {
			url: url
		};

		return new google.maps.Marker({
			position: location,
			map: map,
			icon: image
		});
	};

	/**
	 * Removes the marker from whatever map it was assigned to.
	 *
	 * @param {google.maps.Marker} marker
	 */
	this.clearMarker = function (marker) {
		marker.setMap(null);
	};


	/**
	 * To view what the options do, go to google's documentation:
	 * https://developers.google.com/maps/documentation/javascript/3.exp/reference#KmlLayerOptions
	 *
	 * @param url required
	 * @param map
	 * @param suppressInfoWindows
	 * @param preserveViewport
	 * @param clickable
	 */
	this.initKmlLayer = function(url, map, suppressInfoWindows, preserveViewport, clickable ) {
		var options = {url: url};
		if (map) {
			options['map'] = map;
		}

		if (suppressInfoWindows !== undefined) {
			options['suppressInfoWindows'] = suppressInfoWindows;
		}

		if (preserveViewport !== undefined) {
			options['preserveViewport'] = preserveViewport;
		}

		if (clickable !== undefined) {
			options['clickable'] = clickable;
		}

		return new google.maps.KmlLayer(options);
	}

}]);
