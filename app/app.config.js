/**
 * Created by LENOVO-T430 on 12/27/2016.
 */
'use strict';

angular.module('app').config(['$locationProvider', '$routeProvider', '$compileProvider', 'USER_ROLES', 'LOCATION_PATHS',
	function config($locationProvider, $routeProvider, $compileProvider, USER_ROLES, LOCATION_PATHS) {

		$routeProvider
			.when('/', {
				template: "<home></home>",
				authorized: USER_ROLES.all
			})
			.when('/event/:location/create-team', {
				template: "<create-team></create-team>",
				authorized: USER_ROLES.participant
			})
			.when('/event/:location/join-team', {
				template: "<join-team></join-team>",
				authorized: USER_ROLES.participant
			})
			.when('/event/:location/team', {
				template: "<team></team>",
				authorized: USER_ROLES.participant
			})
			.when('/event/:location/team/routes', {
				template: "<routes></routes>",
				authorized: USER_ROLES.participant
			})
			.when('/faq', {
				template: "<faq></faq>",
				authorized: USER_ROLES.all
			})
			.when(LOCATION_PATHS.assignRoutes, {
				template: "<assign-routes></assign-routes>",
				authorized: [USER_ROLES.admin, USER_ROLES.organizer]
			})
			.when(LOCATION_PATHS.createZone, {
				template: "<zone-detail></zone-detail>",
				authorized: [USER_ROLES.admin, USER_ROLES.organizer]
			})
			.when(LOCATION_PATHS.eventRoutes, {
				template: "<event-routes></event-routes>",
				authorized: [USER_ROLES.admin, USER_ROLES.organizer]
			})
			.when(LOCATION_PATHS.feedback, {
				template: "<feedback></feedback>",
				authorized: USER_ROLES.all
			})
			.when(LOCATION_PATHS.home, {
				template: "<home></home>",
				authorized: USER_ROLES.all
			})
			.when(LOCATION_PATHS.logout, {
				template: "<logout></logout>",
				authorized: USER_ROLES.all
			})
			.when(LOCATION_PATHS.register, {
				template: "<register></register>",
				authorized: USER_ROLES.participant
			})
			.when(LOCATION_PATHS.requestPasswordReset, {
				template: "<request-password-reset></request-password-reset>",
				authorized: USER_ROLES.all
			})
			.when(LOCATION_PATHS.resetPassword + '/:token', {
				template: "<reset-password></reset-password>",
				authorized: USER_ROLES.all
			})
			.when(LOCATION_PATHS.routeArchive, {
				template: "<route-archive></route-archive>",
				authorized: [USER_ROLES.admin, USER_ROLES.organizer]
			})
			.when(LOCATION_PATHS.signUp, {
				template: "<sign-up></sign-up>",
				authorized: USER_ROLES.all
			})
			.when(LOCATION_PATHS.teamRoutes, {
				template: "<routes></routes>",
				authorized: [USER_ROLES.driver, USER_ROLES.moderator, USER_ROLES.organizer, USER_ROLES.participant]
			})
			.when(LOCATION_PATHS.viewTeam, {
				template: "<view-team></view-team>",
				authorized: USER_ROLES.all
			})
			.when(LOCATION_PATHS.zoneList, {
				template: "<zone-list></zone-list>",
				authorized: USER_ROLES.organizer
			})
			.when(LOCATION_PATHS.zoneList + "/:zoneId", {
				template: "<zone-detail></zone-detail>",
				authorized: USER_ROLES.organizer
			})
			.when('/403', {
				templateUrl: "error-pages/403.html",
				authorized: USER_ROLES.all
			})
			.when('/404', {
				templateUrl: "error-pages/404.html",
				authorized: USER_ROLES.all
			})
			.otherwise({
				templateUrl: "error-pages/404.html",
				authorized: USER_ROLES.all
			});

		//Change this value to false on production servers
		//TODO: create a constant for dev or production and go through the project and replace any true/false values for dev vs production with that constant
		$compileProvider.debugInfoEnabled(true);
	}
]);
