/**
 * Created by LENOVO-T430 on 7/11/2017.
 */
function ResetPasswordController($scope, $routeParams, Account) {
	$scope.newPassword = "";
	$scope.confirmPassword = "";
	$scope.validToken = true;
	$scope.resetSuccess = false;
	$scope.submitText = "Submit";

	$scope.resetPassword = function (newPassword) {
		$scope.responseMessage = "";
		$scope.submitText = "Processing...";
		Account.resetPassword(newPassword, $routeParams.token).then(function (response) {
			if (response === true) {
				$scope.resetSuccess = true;
			} else {
				$scope.responseMessage = response.data.message;
			}
			$scope.submitText = "Submit";
		});
	};

	Account.checkTokenStatus($routeParams.token).then(function(isValid) {
		$scope.validToken = isValid;
	});

}

angular.module('resetPassword').component('resetPassword', {
	templateUrl: 'reset-password/reset-password.template.html',
	controller: ['$scope', '$routeParams', 'Account', ResetPasswordController]
});
