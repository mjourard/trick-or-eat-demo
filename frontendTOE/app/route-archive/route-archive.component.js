/**
 * Created by LENOVO-T430 on 1/30/2017.
 */
function RouteArchiveController($scope, $mdDialog, $timeout, ICON_PATHS, URLS, Request, User, Route, Zone) {
    //ensure to write this in such a way that it can be used for both creation and editing
    var self = this;

    self.zoneNames = {};
    self.maps = {};
    self.curMap = {
        route_id: null,
        route_name: null,
        route_file_url: null,
        latitude: null,
        longitude: null,
        zoom: null,
    };

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
        if (typeof (data) === 'string') {

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

    /**
     * Pulls in the route data and map data from the api based on the selected zone
     */
    self.updateRoutes = function () {
        Route.getRouteDetailsInZone(self.zone).then(function (data) {
            self.routes = data.routes;
        })
        Route.getRouteMapDetails(self.zone).then(function (data) {
            let maps = {};
            data.route_details.forEach(details => {
                maps[details.route_id] = details;
            });
            self.maps = maps;
        })
    };

    /**
     * Sends a request to delete the selected route after it is confirmed by the user
     *
     * @param zoneId
     * @param routeName
     * @param routeId
     */
    self.deleteRoute = function (zoneId, routeName, routeId) {
        $mdDialog.show(
            $mdDialog.confirm()
                .title('Delete Route')
                .textContent("Are you sure you wish to delete route " + routeName + "?")
                .ariaLabel('Delete Route')
                .cancel('Cancel')
                .ok('Delete')
        ).then(function () {
            let resp = Route.deleteRoute(zoneId, routeId).then(function (resp) {
                console.log(resp);
                if (resp) {
                    self.updateRoutes();
                }
            });
        });
    };

    /**
     * Updates the map dialog with the newly selected route map info
     *
     * @param ev The click event
     * @param routeId The id of the route being viewed
     */
    self.viewRoute = function (ev, routeId) {
        if (!self.maps.hasOwnProperty(routeId)) {
            $mdDialog.show(
                $mdDialog.alert()
                    .title('Error Displaying Route')
                    .textContent('Unable to display route. The route data did not load properly. Try refreshing the page. If that does not work, contact support.')
                    .ariaLabel('Error Displaying Route')
                    .ok('Ok')
            );
            return;
        }
        self.curMap = self.maps[routeId];
        $mdDialog.show({
            contentElement: '#showMapDialog',
            parent: angular.element(document.body),
            targetEvent: ev,
            clickOutsideToClose: true
        }).catch(function () {
            //browser complains if there is no catch, despite nothing needing to be done here when the user closes the box
        });
    }

    $scope.upload = function (files) {
        if (files && files.length) {
            for (let i = 0; i < files.length; i++) {
                let file = files[i];
                if (!file.$error) {
                    Route.uploadRoute(self.zone, self.blind, self.mobility, self.deaf, file).then(function (resp) {
                        $timeout(function () {
                            $scope.logs.push(self.getTime() + ' file: ' + resp.config.data.file.name + ' - ' + resp.data.message);
                            self.updateRoutes();
                        });
                    }, function (resp) {
                        let message = "Uploaded failed. Unable to determine the reason. Please contact support for assistance.";
                        if (resp.data && resp.data.message) {
                            message = resp.data.message;
                        }
                        $scope.logs.push(self.getTime() + ' ERROR: ' + message);
                    }, function (evt) {
                        let progressPercentage = parseInt(100.0 * evt.loaded / evt.total);
                        $scope.logs.push(self.getTime() + ': progress: ' + progressPercentage + '% ' + evt.config.data.file.name);
                    })
                        .catch(function () {
                            $scope.logs.push(self.getTime() + ' ERROR: Uploaded failed. Unable to determine the reason. Please contact support for assistance.');
                        });
                }
            }
        }
    };

    /**
     * Get an hour:minute timestamp which can be prepended to the $scope.logs array entries
     * @returns {string}
     */
    self.getTime = function () {
        let date = new Date();
        return date.getHours() + ":" + date.getMinutes() + ":" + date.getSeconds();
    }

    $scope.clearPending = function () {
        $scope.files = [];
    }

}

angular.module('routeArchive').component('routeArchive', {
    templateUrl: 'route-archive/route-archive.template.html',
    controller: ['$scope', '$mdDialog', '$timeout', 'ICON_PATHS', 'URLS', 'Request', 'User', 'Route', 'Zone', RouteArchiveController]
});