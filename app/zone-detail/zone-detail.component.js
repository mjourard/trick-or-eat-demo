/**
 * Created by LENOVO-T430 on 1/30/2017.
 */
function ZoneDetailController($scope, $routeParams, $location, $mdDialog, ICON_PATHS, LOCATION_PATHS, Location, Map, User, Zone) {
	//ensure to write this in such a way that it can be used for both creation and editing
	var self = this;

	this.addressMarker = [];
	this.mapZoom = 7;
	$scope.saveText = isNaN($routeParams.zoneId) ? "Create" : "Save";

	this.initMap = function (lat, long, zoom) {
		$scope.map = Map.initMap(Map.newLatLngObj(lat, long), zoom, 'zone-map', self.addressMarker, ICON_PATHS.parking);
		var geocoder = new google.maps.Geocoder;

		google.maps.event.addListener($scope.map, 'click', function (event) {
			self.addMarker(event.latLng, $scope.map);
			self.updateSearchBox(geocoder, event.latLng);
		});

		self.mapZoom = zoom;

		Map.addSearchBar($scope.map, 'zone-address', self.addressMarker, ICON_PATHS.parking);
		self.updateSearchBox(geocoder, Map.newLatLngObj(lat, long));
	};

	this.updateSearchBox = function(geocoder, latLng) {
		geocoder.geocode({'location': latLng}, function (results, status) {
			if (status === 'OK') {
				if (results[0]) {
					self.address = results[0].formatted_address;
					$scope.$digest();
				} else {
					window.alert('No results found');
				}
			} else {
				window.alert('Geocoder failed due to: ' + status);
			}

		});
	};

	if (!isNaN($routeParams.zoneId)) {
		Zone.details(parseInt($routeParams.zoneId)).then(function (details) {
			self.zoneName = details.zone_name;
			self.buildingName = details.central_building_name;
			self.address = details.central_parking_address;
			self.radius = details.zone_radius_meter;
			self.houses = details.houses_covered;
			self.mapZoom = details.zoom;
			self.initMap(details.latitude, details.longitude, details.zoom);
		});
	} else {
		var coords = Location.getRegionCoordinates(User.getRegionId());
		self.initMap(coords.lat, coords.long, self.mapZoom);
	}

	// Adds a marker to the map.
	this.addMarker = function (location, map) {
		self.clearMarkers();
		self.addressMarker.push(Map.addMarker(location, map, ICON_PATHS.parking));
	};

	this.clearMarkers = function () {
		self.addressMarker.forEach(function (marker) {
			Map.clearMarker(marker);
		});
		self.addressMarker.length = 0;
	};

	this.submit = function () {
		var zoneId = isNaN($routeParams.zoneId) ? null : $routeParams.zoneId;
		Zone.saveDetails({
			zone_id: zoneId,
			zone_name: self.zoneName,
			central_building_name: self.buildingName,
			central_parking_address: self.address,
			zone_radius_meter: self.radius,
			houses_covered: self.houses,
			zoom: self.mapZoom,
			latitude: self.addressMarker[0].getPosition().lat(),
			longitude: self.addressMarker[0].getPosition().lng()
		}).then(function(response) {
			switch(typeof response) {
				case 'string':
					$mdDialog.show(
						$mdDialog.alert()
							.clickOutsideToClose(true)
							.title('Zone Editing')
							.textContent(response)
							.ariaLabel('Alert: Zone Editing Failed')
							.ok('Okay')
					);
					break;
				case 'object':
				default:
					$mdDialog.show(
						$mdDialog.alert()
							.clickOutsideToClose(true)
							.title('Zone Editing')
							.textContent('Saving Successful. Going back to the list of zones.')
							.ariaLabel('Alert: Zone Editing Success')
							.ok('Okay')
					).finally(function () {
						$location.path(LOCATION_PATHS.zoneList);
					});
			}

		});
	};

	this.cancel = function () {
		$location.path(LOCATION_PATHS.zoneList);
	};

}

angular.module('zoneDetail').component('zoneDetail', {
	templateUrl: 'zone-detail/zone-detail.template.html',
	controller: ['$scope', '$routeParams', '$location', '$mdDialog', 'ICON_PATHS', 'LOCATION_PATHS', 'Location', 'Map', 'User', 'Zone', ZoneDetailController]
});