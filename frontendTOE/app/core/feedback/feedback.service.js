/**
 * Created by LENOVO-T430 on 10/29/2017.
 */
angular.module('core.feedback').service('Feedback', ['Request', function (Request) {

	this.getMaxCharacterCount = function() {
		return Request.get('/feedback/comment/maxCharacterCount').then(function success(response) {
			if (response.data.success === true) {
				return response.data.limit;
			} else {
				return response.data.message;
			}
		}, function failure(response) {
			console.log(response.data);
			return 'There was an issue getting the max character count';
		})
	};

	this.getComments = function() {
		return Request.get('/feedback/getquestions').then(function success(response) {
			if (response.data.success === true) {
				return response.data.questions;
			} else {
				return response.data.message;
			}
		}, function failure(response) {
			console.log(response.data);
			//TODO: tell the user who to notify
			return 'Unable to retrieve questions at this time.';
		})
	};

	this.getComment = function(questionId) {
		return Request.get('/feedback/comment/' + questionId).then(function success(response) {
			if (response.data.success === true) {
				return response.data.comment;
			} else {
				return response.data.message;
			}
		}, function failure(response) {
			console.log(response.data);
			return 'There was an getting your previously saved comment';
		})
	};

	this.saveComment = function(comment, questionId) {
		return Request.post('/feedback/saveComment',
			{
				comment: comment,
				question_id: questionId
			}
			).then(function success(response) {
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
