angular.module('app', [
	'core',
	'combinationLock',
	'account',
	'assignRoutes',
	'createTeam',
	'eventRoutes',
	'feedback',
	'home',
	'joinTeam',
	'login',
	'logout',
	'pageFooter',
	'pageHeader',
	'register',
	'requestPasswordReset',
	'resetPassword',
	'routes',
	'routeArchive',
	'signUp',
	'zoneDetail',
	'zoneList',
	'md.data.table',
	'ngCookies',
	'ngFileUpload',
	'ngMaterial',
	'ngRoute',
	'viewTeam'
])
	.run(['$rootScope', '$route', '$location', 'AUTH_EVENTS', 'LOCATION_PATHS', 'Auth', function ($rootScope, $route, $location, AUTH_EVENTS, LOCATION_PATHS, Auth) {
		var loaded = Auth._loadUser();
		if (loaded !== null) {
			loaded.then(function() {
				// console.log("calling route.reload");
				$route.reload();
			});
		}
		//TODO: redirect to home and pop up the login modal on not authorized error

		$rootScope.$on('$routeChangeStart', function (event, next) {
			var authorizedRoles = next.$$route.authorized;
			$rootScope.loadingView = true;
			if (!Auth.isAuthorized(authorizedRoles)) {
				console.log("not authorized");
				event.preventDefault();
				if (Auth.isAuthenticated()) {
					// user is not allowed
					$rootScope.$broadcast(AUTH_EVENTS.notAuthorized);
				} else {
					// user is not logged in
					$rootScope.$broadcast(AUTH_EVENTS.notAuthenticated);
				}
				$location.path(LOCATION_PATHS.home);
			}
		});

		$rootScope.$on('$routeChangeSuccess', function (e, next, prev) {
			$rootScope.loadingView = false;
		});
	}]);

