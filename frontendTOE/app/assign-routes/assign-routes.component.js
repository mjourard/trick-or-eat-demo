/**
 * Created by Matt on 10/15/2016.
 */
function RouteAssignerController($mdDialog, $scope, Event, Route, User) {

	$scope.routesQuery = {
		order: 'route_name'
	};

    var assigner = this;
    var regionId = User.getRegionId();
    assigner.eventObj = null;
    assigner.selectedEvent = null;
    assigner.routes = null;
    assigner.stats = null;
    assigner.unassignedTeams = null;

	Event.getEvents(regionId).then(function(events) {
		assigner.eventObj = events;
	});

    assigner.getRouteAssignments = function () {
        Route.getRouteAssignments(assigner.selectedEvent.event_id, $scope.routesQuery)
            .then(function success(response) {
                if (response.data.success == true) {
                    assigner.routes = response.data.routes;
                    assigner.unassignedTeams = response.data.unassignedTeams;
                    assigner.stats = response.data.stats;
                } else {
                    console.log("getRouteAssignments failed.");
                    console.log(response);
                }
            }, function failure(response) {
                console.log("getRouteAssignments called the failure function.");
                console.log(response);
            });
    };

    /**
     * gets the listing of events for a specific region/location and sets them to the eventObj
     * for local usage
     */
    assigner.getEvents = function () {
        Event.getEvents(regionId).then(function(events) {
			assigner.eventObj = events;
		});
    };


    assigner.assignRoutes = function () {
        //get the id of the event that routes need to be assigned for
        if (assigner.selectedEvent == null) {
            return;
        }
        Route.assignRoutes(assigner.selectedEvent.event_id).then(function(response) {
			if (response.success) {
				$mdDialog.show(
					$mdDialog.alert()
						.clickOutsideToClose(false)
						.title('Route Assignment')
						.textContent('Teams Successfully Assigned.')
						.ariaLabel('Alert: assignment successful')
						.ok('Okay')
				);
				assigner.getRouteAssignments();
			} else {
				$mdDialog.show(
					$mdDialog.alert()
						.clickOutsideToClose(false)
						.title('Route Assignment')
						.textContent('An error occurred while attempting to assign routes to teams. Error: ' + response.message)
						.ariaLabel('Alert: assignment failed')
						.ok('Okay')
				);
			}

        });
    };

    assigner.removeRouteAssignments = function () {
        if (assigner.selectedEvent == null) {
            return;
        }
        Route.removeRouteAssignments(assigner.selectedEvent.event_id).then(function(response) {
			if (response.success) {
				assigner.getRouteAssignments();
			}
		});
    };
}

angular.module('assignRoutes').component('assignRoutes', {
    templateUrl: 'assign-routes/assign-routes.template.html',
    controller: ['$mdDialog', '$scope', 'Event', 'Route', 'User', RouteAssignerController]
});
