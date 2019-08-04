function LoginController($location, $mdDialog, $mdToast, $scope, $rootScope, AUTH_EVENTS, LOCATION_PATHS, Auth) {
	$scope.success = true;
	$scope.locationPaths = LOCATION_PATHS;
	$scope.creds = {
		email: "",
		password: ""
	};

	$scope.login = function (creds) {
		Auth.login(creds.email, creds.password).then(function (user) {
			$scope.success = true;
			$rootScope.$broadcast(AUTH_EVENTS.loginSuccess);
			$rootScope.currentUser = user;
			$mdToast.show(
				$mdToast.simple()
					.textContent('Login Successful. Hello ' + user.fName)
					.position('top')
					.hideDelay(3000)
			);
			$mdDialog.hide();
		}, function () {
			$rootScope.$broadcast(AUTH_EVENTS.loginFailed);
			$scope.success = false;
		});
	};

	/**
	 * if called closes the popup window that login is inside of.
	 */
	$scope.cancel = function () {
		console.log("in cancel");
		$mdDialog.cancel();
	};

	//necessary because login is a modal. Can be changed in the template to ng-href if login is changed to its own page
	$scope.redirect = function (location) {
		$mdDialog.cancel().then(
			function () {
				$location.path(location);
			}
		);
	}
}

//by registering the controller as a separate entity. It allows mdDialog to use it. Not sure if this is the proper approach. Feels hacky.
angular.module('login').controller('LoginCtrl', ['$location', '$mdDialog', '$mdToast', '$scope', '$rootScope', 'AUTH_EVENTS', 'LOCATION_PATHS', 'Auth', LoginController]);

angular.module('login').component('login', {
	templateUrl: 'login/login.template.html',
	controller: 'LoginCtrl'
});
