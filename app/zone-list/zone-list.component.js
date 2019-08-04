/**
 * Created by LENOVO-T430 on 1/30/2017.
 */
function ZoneListController($location, $mdDialog, LOCATION_PATHS, User, Zone) {
	var zoneList = this;
	this.zones = [];
	Zone.query(Zone.queryOptions.working).then(function (response) {
		if (Array.isArray(response)) {
			zoneList.zones = response;
		}
	});
	this.orderProp = 'zone_name';
	this.region_name = User.getRegionName();

	this.zoneSelected = function (zoneId) {
		$location.path(LOCATION_PATHS.zoneList + "/" + zoneId);
	};

	this.setZoneStatus = function (zoneId, status) {
		Zone.setStatus(zoneId, status).then(function (response) {
			if (response !== true) {
				$mdDialog.show(
					$mdDialog.alert()
						.clickOutsideToClose(false)
						.title('Status Set Failed')
						.textContent(response)
						.ariaLabel('Alert: status failed to set')
						.ok('Okay')
				);
			}
		})
	};

	this.newZone = function() {
		$location.path(LOCATION_PATHS.createZone);
	};
}

angular.module('zoneList')
	.component('zoneList', {
		templateUrl: 'zone-list/zone-list.template.html',
		controller: ['$location', '$mdDialog', 'LOCATION_PATHS', 'User', 'Zone', ZoneListController]
	});