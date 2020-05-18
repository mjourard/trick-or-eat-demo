function RoutesController($routeParams, $scope, Map, Route, User) {
	var route = this;
	route.event = $routeParams.location;
	route.routesObj = null;
	route.hasRoutes = false;
	route.curRoute = {
		latitude: null,
		longitude: null,
		zoom: null,
		routeFileUrl: null
	}


	/**
	 * checks to see if a list of routes exits and if it does returns true and if it does not returns false
	 * @return {Boolean} true if routes exit in routesObj. false if routes do not exit in routesObj.
	 */
	var hasRoutes = function () {
		route.hasRoutes = route.routesObj !== null && route.routesObj.length > 0;
		if (route.hasRoutes) {
			route.setCurRoute(route.routesObj[0].latitude, route.routesObj[0].longitude, route.routesObj[0].zoom, route.routesObj[0].route_file_url);
		}
	};

	/**
	 * Goes to the RouteService to get the listing of routes for the
	 * current team and sets it to be the route.routeObj variable
	 */
	route.getRoutes = function () {
		var event = User.getEvent();
		if (event === null) {
			return;
		}

		var teamId = User.getTeamId();
		if (teamId === null) {
			return;
		}

		Route.getTeamRoutes(event.event_id, teamId).then(function (routes) {
			route.routesObj = routes;
			hasRoutes();
		});
	};

	/**
	 * Sets the current route of the controller
	 *
	 * To be used later when there are multiple routes to select from for teams
	 *
	 * @param lat
	 * @param long
	 * @param zoom
	 * @param routeFileUrl
	 */
	route.setCurRoute = function(lat, long, zoom, routeFileUrl) {
		route.curRoute = {
			latitude: lat,
			longitude: long,
			zoom: zoom,
			routeFileUrl: routeFileUrl
		}
	}
}

angular.module('routes').component('routes', {
		templateUrl: 'routes/routes.template.html',
		controller: ['$routeParams', '$scope', 'Map', 'Route', 'User', RoutesController]
	}
);
