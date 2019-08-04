function AccountController($mdDialog, $mdSidenav, $scope, Location, User, Account) {
    console.log("Inside the AccountCtrl");

    var acct = this;

    var curUser;
    $scope.User = User;
    acct.fName = null;
    acct.lName = null;
    acct.country = null;
    acct.activeSubmit = false;

    acct.countryNames = Location.getCountryNames();
    acct.regions = Location.getRegionNames();
    acct.regionNames = {};

    $scope.setRegions = function() {
        acct.regionNames = acct.regions[acct.country];
        //TODO: fix the account location stuff so that they properly display
    };

    $scope.$watch('User.userId', function () {
        resetUser();
    });


    /**
     * Toggles the edit account sidenav
     */
    acct.toggleEditNav = function () {
        $mdSidenav('edit').toggle();
    };

    acct.cancelEdit = function() {
        resetUser();
        acct.toggleEditNav();
    };

    /**
     * Calls the User Service to change the currently logged in user's account information
     */
    acct.updateUser = function () {
        /* stats to update: first name, last name, location */
        Account.updateUser(acct.fName, acct.lName, acct.region);
    };

    $scope.submitFunction = function() {
        alert("hello world!");
    };

    /**
     * Displays a modal for the user to change their password
     */
    acct.passModal = function (event) {
        $mdDialog.show({
            controllerUrl: 'js/controllers/account.js',
            templateUrl: 'templates/passChange.html',
            targetEvent: event,
            clickOutsideToClose: false,
            fullscreen: true
        })
    };

    /**
     * Closes the change password modal
     */
    acct.cancelPass = function () {
        $mdDialog.cancel();
    };

    /**
     * Sends the required information to the password change functionality of the User Service
     */
    acct.passChange = function () {
        User.changePass(acct.pass, acct.newPass);
    };

    var resetUser = function() {
        acct.fName = User.getfName();
        acct.lName = User.getlName();
        acct.country = User.getCountryId();
        $scope.setRegions();
        acct.region = User.getRegionId();
        curUser = User.getUser();
    };

    var showInfoDialog = function(title, textContent) {
        $mdDialog.show(
            $mdDialog.alert()
                .clickOutsideToClose(true)
                .title(title)
                .textContent(textContent)
                .ok('Okay')
        );
    };
}

angular.module('account')
    .controller('AccountCtrl',
        ['$mdDialog', '$mdSidenav', '$scope', 'Location', 'User', 'Account', AccountController]
    );