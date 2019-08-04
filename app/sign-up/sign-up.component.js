function SignUpController($location, $mdDialog, $scope, Location, Account, LOCATION_PATHS) {
    var signUp = this;

    console.log('Inside the SignUpCtrl');

    $scope.fName = "";
    $scope.lName = "";

    signUp.countryNames = Location.getCountryNames();
    signUp.regions = Location.getRegionNames();
    signUp.regionNames = {};

    $scope.noCountrySelected = true;

    $scope.setRegions = function() {
        signUp.regionNames = signUp.regions[signUp.country];
        $scope.noCountrySelected = false;
    };

    /**
     * Opens the modal informing the user of the successful signup
     */
    signUp.success = function () {
        $mdDialog.show(
            $mdDialog.alert()
                .clickOutsideToClose(true)
                .title('SignUp Successful')
                .textContent('Your account has been created. Please login to proceed.')
                .ok('OK')
        );
    };

    /**
     * Opens the modal informing the user of the failed signup
     * @param  {String} failureMessage - A message to be displayed to the user containing the reason that signup failed.
     */
    signUp.failure = function (failureMessage) {
        $mdDialog.show(
            $mdDialog.alert()
                .clickOutsideToClose(true)
                .title('SignUp Failed')
                .textContent(failureMessage)
                .ok('OK')
        );
    };

    /**
     * Takes all the information from the form and sends it to the
     * create User function in the User Service
     */
    signUp.submitForm = function () {
        Account.registerUser($scope.userEmail, $scope.pass, $scope.fName, $scope.lName, parseInt($scope.region)).then(function(response) {
            console.log(response);
            if (response.data.success === true) {
                $location.path(LOCATION_PATHS.home);
                signUp.success();
            } else {
                signUp.failure(response.data.message);
            }
        });

    };
}

angular.module('signUp').component('signUp', {
    templateUrl: 'sign-up/sign-up.template.html',
    controller: ['$location', '$mdDialog', '$scope', 'Location', 'Account', 'LOCATION_PATHS', SignUpController]
});
