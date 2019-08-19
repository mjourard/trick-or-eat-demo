/**
 * Created by LENOVO-T430 on 1/30/2017.
 */
function RouteArchiveController($scope, $mdDialog, $timeout, ICON_PATHS, URLS, Request, User, Route, Zone) {
	//ensure to write this in such a way that it can be used for both creation and editing
	var self = this;

	self.zoneNames = {};

	$scope.logs = [];

	$scope.curZone = "none selected";
	$scope.$watch(
		function watchZone() {
			return self.zone;
		},
		function (newValue, oldValue) {
			if (self.zoneNames[newValue]) {
				$scope.curZone = self.zoneNames[newValue];
			}
		}
	);

	$scope.none = false;
	$scope.$watch('none', function () {
		self.blind = false;
		self.mobility = false;
		self.deaf = false;
	});


	Zone.query(Zone.queryOptions.working).then(function (data) {
		if (typeof(data) === 'string') {

		} else {
			data.forEach(function (zone) {
				self.zoneNames[zone.zone_id] = zone.zone_name;
			});
			self.zone = data[0].zone_id;
		}
	});


	$scope.$watch('file', function () {
		if ($scope.file != null) {
			$scope.files = [$scope.file];
		}
	});

	$scope.$watch('$ctrl.zone', function () {
		if (self.zone) {
			self.updateRoutes();
		}
	});

	self.updateRoutes = function () {
		Route.getRouteDetailsInZone(self.zone).then(function (data) {
			self.routes = data.routes;
		})
	};

	self.deleteRoute = function (zoneId, routeName, routeId) {
		$mdDialog.show(
			$mdDialog.confirm()
				.title('Delete Route')
				.textContent("Are you sure you wish to delete route " + routeName + "?")
				.ariaLabel('Delete Route')
				.cancel('Cancel')
				.ok('Delete')
		).then(function () {
			if (Route.deleteRoute(zoneId, routeId)) {
				self.updateRoutes();
			}
		});
	};

	$scope.upload = function (files) {
		if (files && files.length) {
			for (var i = 0; i < files.length; i++) {
				var file = files[i];
				if (!file.$error) {
					Route.uploadRoute(self.zone, self.blind, self.mobility, self.deaf, file).then(function (resp) {
						$timeout(function () {
							$scope.logs.push( ($scope.logs.length + 1) + ': file: ' + resp.config.data.file.name + ' - ' + resp.data.message);
							self.updateRoutes();
						});
					}, null, function (evt) {
						var progressPercentage = parseInt(100.0 * evt.loaded / evt.total);
						$scope.logs.push(($scope.logs.length + 1) + ': progress: ' + progressPercentage + '% ' + evt.config.data.file.name);
					});
				}
			}
		}
	};

	$scope.clearPending = function() {
		$scope.files = [];
	}

}

angular.module('routeArchive').component('routeArchive', {
	templateUrl: 'route-archive/route-archive.template.html',
	controller: ['$scope', '$mdDialog', '$timeout', 'ICON_PATHS', 'URLS', 'Request', 'User', 'Route', 'Zone', RouteArchiveController]
});