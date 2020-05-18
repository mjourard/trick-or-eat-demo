function RouteViewController($scope, $element, Map) {
    this.mapElement = null;
    let ctrl = this;
    this.initRouteMap = function () {
        if (ctrl.mapElement === null) {
            return;
        }
        $scope.map = Map.initMap(Map.newLatLngObj(ctrl.lat, ctrl.long), ctrl.zoom, ctrl.mapElement);
        let layer = Map.initKmlLayer(ctrl.kmlUrl);
        layer.setMap($scope.map);
    };
    this.$onChanges = function(changesObj) {
        ctrl.initRouteMap()
    }
    this.$postLink = function() {
        ctrl.mapElement = $element.children()[0];
    }
}

angular.module('routeView').component('routeView', {
    template: '<div class="angular-google-map-container"></div>',
    controller: ['$scope', '$element', 'Map', RouteViewController],
    bindings: {
        lat: '<',
        long: '<',
        zoom: '<',
        kmlUrl: '<'
    }
});

//62x31.5x29.5