/**
 * Created by LENOVO-T430 on 1/30/2017.
 */
angular.module('core.zone').service('Zone', ['Request', 'User', function (Request, User) {

	this.queryOptions = {
		working: 'working',
		all: 'all',
		active: 'active',
		inactive: 'inactive',
		retired: 'retired'
	};

	this.query = function (status) {
		if (User.getRegionId() === null) {
			return null;
		}

		return Request.get('/zones/' + User.getRegionId() + '/' + status ).then(function success(response) {
			if (response.data.success === true) {
				return response.data.zones;
			} else {
				return response.data.message;
			}
		}, function failure(response) {
			console.log(response.data);
			return 'There was an issue getting the zones from the server';
		})
	};

	this.details = function(zoneId) {
		return Request.get('/zones/details/' + zoneId).then(function success(response) {
			if (response.data.success === true) {
				return response.data.zone;
			} else {
				return response.data.message;
			}
		}, function failure(response) {
			console.log(response.data);
			return 'There was an issue getting zone with id ' + zoneId;
		})
	};

	this.setStatus = function(zoneId, status) {
		var postObj = {
			zone_id: parseInt(zoneId),
			status: status
		};

		return Request.put('/zones/status', postObj).then(function success(response) {
			if (response.data.success === true) {
				return true;
			} else {
				return response.data.message;
			}
		}, function failure(response) {
			console.log(response.data);
			return 'There was an issue setting the status of zone with id ' + zoneId;
		})
	};

	this.saveDetails = function(postObj) {
		if (postObj.zone_id === null) {
			return Request.post('/zones/create', postObj).then(function success(response) {
				if (response.data.success === true) {
					return response.data.zone;
				}

				return response.data.message;
			}, function failure(response) {
				console.log(response);
				return response.data.message;
			});
		}

		postObj.zone_id = parseInt(postObj.zone_id );

		return Request.put('/zones/edit' , postObj).then(function success(response) {
			if (response.data.success === true) {
				return true;
			}
			return response.data.message;
		}, function failure(response) {
			console.log(response);
			return response.data.message;
		})
	}

}]);
