/**
 * Created by LENOVO-T430 on 1/30/2017.
 */
angular.module('core.sitehealth').service('SiteHealth', ['Request', function (Request) {
	const badErrorResponse = {
		'message': "We've detected issues with the backend. Hold tight",
		'lvl': 'error'
	};
	this.getSiteIssues = function () {
		return Request.get('/health/siteissues', {}).then(function success(response) {
			if (response.status && response.status === 204) {
				return null;
			}
			console.log('issues found');
			console.log(response);
			if (response.data.success === true) {
				return response.data;
			} else {
				return badErrorResponse;
			}
		}, function failure(response) {
			console.log(response.data);
			return badErrorResponse;
		})
	};
}]);
