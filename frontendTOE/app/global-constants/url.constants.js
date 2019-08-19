// noinspection JSUnresolvedVariable
/**
 * Created by LENOVO-T430 on 1/9/2017.
 */

//The values of backend and routeHosting are replaced at compile time by webpack.
angular.module('app').constant('URLS', {
	backend: BACKEND,
	routeHosting: ROUTE_HOSTING
});