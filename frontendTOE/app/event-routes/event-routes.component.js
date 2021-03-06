/**
 * Created by LENOVO-T430 on 1/30/2017.
 */
function EventRoutesController($mdDialog, $scope, $timeout, Event, Route, User) {
	//ensure to write this in such a way that it can be used for both creation and editing
	var self = this;
	var regionId = User.getRegionId();

	Event.getEvents(regionId).then(function (events) {
		self.events = events;
	});

	self.getEventRoutes = function (eventId) {
		Route.getRoutesForEvent(eventId).then(function (routes) {
			self.activeRoutes = routes;
		})
	};

	self.getUnallocatedRoutes = function (eventId) {
		Route.getUnallocatedRoutes(eventId).then(function (routes) {
			self.unallocatedRoutes = routes;
		})
	};

	self.removeRoute = function (routeId, eventId) {
		if (eventId) {
			Route.removeRoute(routeId, eventId).then(function(response) {
				if (response === true) {
					self.updateRoutes(eventId);
				} else {
					let msg = response.message;
					if (response.teams) {
						msg += "<br><br>Teams:";
						response.teams.forEach(team => {
							msg += "<br> " + team.team_id + ": " + team.name
						})
					}
					$mdDialog.show(
						$mdDialog.alert()
							.clickOutsideToClose(true)
							.title('Route Deallocation Failed')
							.htmlContent(msg)
							.ok('OK')
					);
				}
			});
		}
	};

	self.addRoute = function (zoneId, routeId, eventId) {
		if (eventId) {
			Route.addRoute(zoneId, routeId, eventId).then(function(response) {
				if (response === true) {
					self.updateRoutes(eventId);
				} else {
					$mdDialog.show(
						$mdDialog.alert()
							.clickOutsideToClose(true)
							.title("Unable to Allocate Route To Event")
							.textContent(response.message)
							.ok('Okay')
					);
				}
			});
		}
	};

	self.updateRoutes = function (eventId) {
		self.getEventRoutes(eventId);
		self.getUnallocatedRoutes(eventId);
	}


}

angular.module('eventRoutes').component('eventRoutes', {
	templateUrl: 'event-routes/event-routes.template.html',
	controller: ['$mdDialog', '$scope', '$timeout', 'Event', 'Route', 'User', EventRoutesController]
});