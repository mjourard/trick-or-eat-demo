/**
 * Created by LENOVO-T430 on 1/9/2017.
 */
angular.module('core.auth').factory('Auth', ['$cookies', 'Request', 'User', 'USER_ROLES', function ($cookies, Request, User, USER_ROLES) {
	var auth = {};

	/**
	 * Send the login information to the backend for verification. On success,
	 * calls the private function that loads the users info from the db
	 * @param  {string} email - the email of the attempted login
	 * @param  {string} password - the password of the attempted login
	 */
	auth.login = function (email, password) {
		return Request
			.post('/login', {"email": email, "password": password})
			.then(function success(response) {
				var body = response.data;
				if (body.success) {
					$cookies.put('authToken', body.token.jwt);
					return auth.setUser();
				}
			});
	};

	auth.setUser = function () {
		return Request.get('/user/userInfo').then(function success(response) {
			var body = response.data;
			if (body.success) {
				User.create(body.email, body.id, body.first_name, body.last_name, body.country_id, body.country_name, body.region_id, body.region_name, body.team_id, body.team_name, body.event_id, body.event_name, body.user_roles);
			} else {
				User.logout();
				//TODO: instead of destroying the cookie, use it to ask for a new token
			}
			return User.getUser();
		}, function failure(response) {
			User.logout();
			return User.getUser();
		})
	};

	auth.isAuthenticated = function () {
		return !!User.userId;
	};

	auth.isAuthorized = function (authorizedRoles) {
		if (!angular.isArray(authorizedRoles)) {
			authorizedRoles = [authorizedRoles];
		}
		if (authorizedRoles.indexOf(USER_ROLES.all) !== -1) {
			return true;
		}
		if (!auth.isAuthenticated()) {
			return false;
		}

		var roles = User.getUserRoles();
		if (roles.indexOf(USER_ROLES.all) !== -1 || roles.indexOf(USER_ROLES.admin) !== -1) {
			return true;
		}
		for (var i = 0; i < roles.length; i++) {
			if (authorizedRoles.indexOf(roles[i]) !== -1) {
				return true;
			}
		}
		return false;
	};

	auth._loadUser = function () {
		if (User.getUserId() === null && $cookies.get('authToken')) {
			return auth.setUser();
		}
		return null;
	};

	if ($cookies.get('authToken')) {
		auth.setUser();
	}

	return auth;
}]);