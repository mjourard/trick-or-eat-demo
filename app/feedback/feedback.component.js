/**
 * Created by LENOVO-T430 on 10/29/2017.
 */

function FeedbackController($location, $mdDialog, Feedback, LOCATION_PATHS) {
	//ensure to write this in such a way that it can be used for both creation and editing
	var self = this;
	self.questions = [];
	self.comments = [];
	Feedback.getComments().then(function(questions) {
		self.questions = questions;
	});

	self.saveComment = function (comment, questionId) {
		questionId = Number(questionId);
		if (comment !== undefined && comment !== '') {
			Feedback.saveComment(comment, questionId).then(function (response) {
				if (response === true) {
					showDialog('Feedback Response', 'Comment saved. Thank you for your feedback <3', 'success');
					return;
				}
				showDialog('Feedback Response', 'Something went wrong in trying to save your comment.' + response, 'failure');
			})
		}
	};

	self.saveAllComments = function() {
		console.log(self.comments);
		self.comments.forEach(function(comment, index) {
			if (comment !== '') {
				Feedback.saveComment(comment, index);
			}
		});
		showDialog('Feedback Response', 'Comment saved. Thank you for your feedback <3', 'success');
		$location.path(LOCATION_PATHS.home);
		return;
	};

	self.cancel = function() {
		$location.path(LOCATION_PATHS.home);
	};

	var showDialog = function(title, text, label, finalFunction) {
		finalFunction = finalFunction || function(){};

		$mdDialog.show(
			$mdDialog.alert()
				.clickOutsideToClose(false)
				.title(title)
				.textContent(text)
				.ariaLabel(label)
				.ok('Okay')
		).finally(finalFunction);
	};
}

angular.module('feedback').component('feedback', {
	templateUrl: 'feedback/feedback.template.html',
	controller: ['$location', '$mdDialog', 'Feedback', 'LOCATION_PATHS', FeedbackController]
});