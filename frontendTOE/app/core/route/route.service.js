angular.module('core.route')
	.service('Route', ['$rootScope', 'URLS', 'Request', 'User', function ($rootScope, URLS, Request, User) {

		/**
		 * Gets the routes for the team of the passed in team_id for the event of the passed in event_id
		 *
		 * @param event_id The id of the event the team is participating in.
		 * @param team_id The id of the team getting routes for.
		 * @returns {*} List of route objects containing route information (and bus information if applicable)
		 */
		this.getTeamRoutes = function (event_id, team_id) {
			return Request.get("/routes/" + event_id + "/getRouteAssignments/" + team_id, {}).then(function success(response) {
				var routes = response.data.routes;
				routes.forEach(function(route) {
					route.route_file_url = route.route_file_url;
				});
				return routes;
			}, function failure(response) {
				return [];
			});
		};

		/**
		 * Gets the route assignments for all teams. Must be the super admin to call this function, otherwise backend returns an error.
		 *
		 * @param event_id The id you're getting the route assignments for.
		 * @param query An object containing the query data
		 * @returns {*}
		 */
		this.getRouteAssignments = function (event_id, query) {
			///routes/{event_id}/getRouteAssignments
			return Request.get("/routes/" + event_id + "/getRouteAssignments/orderBy/" + query.order , {});
		};

		/**
		 * Gets all routes currently assigned to an event
		 *
		 * @param event_id
		 * @returns {*}
		 */
		this.getRoutesForEvent = function(event_id) {
			return Request.get('/routes/' + event_id, {}).then(function success(response) {
				return response.data.routes;
			}, function failure(response) {
				console.log(response);
				return [];
			});
		};

		this.getUnallocatedRoutes = function(event_id) {
			return Request.get('/routes/unallocated/' + event_id, {}).then(function success(response) {
				return response.data.routes;
			}, function failure(response) {
				console.log(response);
				return [];
			});
		};

		//
		//SETTERS
		//

		/**
		 * When called, all teams without routes assigned to them will be given a route according to the Trick Or Eat algorithm.
		 */
		this.assignRoutes = function (event_id) {
			var userId = User.getUserId();
			return Request.put("/routes/" + event_id + "/assignAllRoutes", {userId: userId})
				.then(function success(response) {
					return response.data;
				}, function failure(response) {
					console.log(response);
					return response.data;
				});
		};

		/**
		 * When called, all teams will have their assigned routes removed for that event.
		 */
		this.removeRouteAssignments = function (event_id) {
			var userId = User.getUserId();
			return Request.put("/routes/" + event_id + "/removeAllRouteAssignments", {userId: userId})
				.then(function success(response) {
					return response.data;
				}, function failure(response) {
					console.log(response);
					return response;
				});
		};

		/**
		 * Gets a list of the details of a route based on the zone. Each route is returned in the following format:
		 *
		 * {
			route_name: String,
			zone_name: String,
			wheelchair_accessible: boolean,
			blind_accessible: boolean,
			hearing_accessible: boolean
		}
		 * @param zoneId
		 * @returns {*}
		 */
		this.getRouteDetailsInZone = function (zoneId) {
			if (zoneId === undefined) {
				zoneId = -1;
			}
			return Request.get('/zones/routes/' + zoneId).then(function success(response) {
				return response.data;
			}, function failure(response) {
				console.log("There was an error in retrieving the data");
				console.log(response);
				return response;
			})
		};

		this.deleteRoute = function (zoneId, routeId) {
			if (routeId === undefined) {
				routeId = -1;
			}

			if (zoneId === undefined) {
				zoneId = -1;
			}
			return Request.delete('/zones/routes/' + zoneId + '/' + routeId).then(function success() {
				return true;
			}, function failure() {
				return false;
			});
		};

		this.uploadRoute = function (zoneId, blind, mobility, deaf, file) {
			var obj = {
				zone_id: zoneId,
				visual: blind,
				mobility: mobility,
				hearing: deaf,
				type: 'Bus', //Only supporting bus routes at the moment
				file: file
			};

			return Request.upload('/zones/routes', obj);

		};

		/**
		 * Assigns a route to an event. Route should already exist in the route-archive and not be assigned the event already.
		 *
		 * @param zoneId
		 * @param routeId
		 * @param eventId
		 * @returns {boolean}
		 */
		this.addRoute = function(zoneId, routeId, eventId) {
			var postObj = {
				zoneId: zoneId,
				routeId: routeId,
				eventId: eventId
			};
			return Request.post('/routes/allocate', postObj).then(function success(response) {
				return true;
			}, function failure(response) {
				return response.data;
			});

		};

		this.removeRoute = function(routeId, eventId) {
			var postObj = {
				routeId: routeId,
				eventId: eventId
			};
			return Request.delete('/routes/deallocate', postObj).then(function success(response) {
				if (response.data.success) {
					return true;
				}
				console.log(response);
				return response.data;
			}, function failure(response) {
				console.log(response);
				return false;
			});
		};
	}]);
