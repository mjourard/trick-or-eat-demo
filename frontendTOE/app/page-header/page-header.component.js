function PageHeaderController($location, $mdDialog, $mdSidenav, $scope, User, Auth, USER_ROLES, LOCATION_PATHS) {
    var head = this;
    $scope.userfName = null;
    $scope.userRoles = USER_ROLES;
    $scope.locationPaths = LOCATION_PATHS;

    $scope.User = User;

    $scope.$watch('User.fName', function(newValue) {
        $scope.userfName = newValue;
    });

    head.loggedIn = User.loggedIn;
    head.isAuthorized = Auth.isAuthorized;
    head.isAuthenticated = Auth.isAuthenticated;

    head.links = [
        {}
    ];

    /**
     * Toggles the header menu sidenav
     */
    head.toggleMenuNav = function () {
        console.log("In togglemenunav");
        $mdSidenav('nav').toggle();
    };

    /**
     * Toggles the edit account sidenav
     */
    head.toggleEditNav = function () {
        console.log("in the toggleEditNav function");
        $mdSidenav('edit').toggle();
    };

    /**
     * The modal for logging in
     * @param  {[type]} event [description]
     */
    head.loginModal = function (event) {
        $mdDialog.show({
            controller: 'LoginCtrl',
            templateUrl: 'login/login.template.html',
            targetEvent: event,
            clickOutsideToClose: true,
            fullscreen: true
        }).finally(function() {
            $scope.userfName = User.getfName();
            $location.path(LOCATION_PATHS.home)
        })
    };
}

angular.module('pageHeader').component('pageHeader', {
    templateUrl: 'page-header/page-header.template.html',
    controller: ['$location', '$mdDialog', '$mdSidenav', '$scope', 'User', 'Auth', 'USER_ROLES', 'LOCATION_PATHS', PageHeaderController]
});
