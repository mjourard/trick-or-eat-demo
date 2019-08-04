function RequestPasswordResetController($mdToast, $scope, Account) {
	console.log("Inside the RequestPasswordResetController");

	$scope.email = "";
	$scope.responseMessage = "";
	$scope.errorMessage = "";
	$scope.submitText = "Submit";

	$scope.requestPasswordReset = function (email) {
		$scope.responseMessage = "";
		$scope.errorMessage = "";
		$scope.submitText = "Processing...";
		Account.requestPasswordReset(email).then(function (response) {
			if (true === response.data.success) {
				$scope.responseMessage = "Check your email for a link to reset your password!";
			} else {
				$scope.errorMessage = response.data.message;
			}
			$scope.submitText = "Submit";
		});
	};

}

angular.module('requestPasswordReset').component('requestPasswordReset', {
	templateUrl: 'request-password-reset/request-password-reset.template.html',
	controller: ['$mdToast', '$scope', 'Account', RequestPasswordResetController]
});
