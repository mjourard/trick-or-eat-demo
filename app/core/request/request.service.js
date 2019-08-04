angular.module('core.request')
	.service('Request', ['$cookies', '$http', 'URLS', 'Upload', function ($cookies, $http, URLS, Upload) {
		let destination = URLS.backend;

		this.get = function (url, pushObj) {
			return $http.get(destination + url, {data: (pushObj), headers: getHeaders()});
		};

		this.post = function (url, pushObj) {
			return $http.post(destination + url, pushObj, {headers: getHeaders()});
		};

		this.put = function (url, pushObj) {
			return $http.put(destination + url, pushObj, {headers: getHeaders()});
		};

		this.delete = function (url, pushObj) {
			return $http.delete(destination + url, {data: (pushObj), headers: getHeaders()});
		};

		this.upload = function (url, pushObj) {
			return Upload.upload({
				url: destination + url,
				data: pushObj,
				headers: getHeaders()
			});
		};


		//gets the current token and sets it accordingly and sets the content-type.
		let getHeaders = function () {
			return {'Content-Type': 'application/json', 'X-Bearer-Token': ($cookies.get('authToken'))};
		}

	}]);