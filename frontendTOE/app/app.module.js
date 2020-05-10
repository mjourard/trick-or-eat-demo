const angular = require('angular');
require('angular-animate');
require('angular-aria');
require('angular-cookies');
require('angular-messages');
require('angular-route');
require('ng-file-upload');
require('angular-material');
require('angular-material-data-table');
require('angular-sanitize');

angular.module('app', [
    'core',
    'combinationLock',
    'account',
    'assignRoutes',
    'createTeam',
    'eventRoutes',
    'feedback',
    'home',
    'joinTeam',
    'login',
    'logout',
    'pageFooter',
    'pageHeader',
    'register',
    'requestPasswordReset',
    'resetPassword',
    'routes',
    'routeArchive',
    'signUp',
    'zoneDetail',
    'zoneList',
    'md.data.table',
    'ngCookies',
    'ngFileUpload',
    'ngMaterial',
    'ngRoute',
    'ngSanitize',
    'viewTeam'
])
    .run(['$rootScope', '$route', '$location', 'AUTH_EVENTS', 'LOCATION_PATHS', 'Auth', function ($rootScope, $route, $location, AUTH_EVENTS, LOCATION_PATHS, Auth) {
        var loaded = Auth._loadUser();
        if (loaded !== null) {
            loaded.then(function () {
                // console.log("calling route.reload");
                $route.reload();
            });
        }
        //TODO: redirect to home and pop up the login modal on not authorized error

        $rootScope.$on('$routeChangeStart', function (event, next) {
            var authorizedRoles = next.$$route.authorized;
            $rootScope.loadingView = true;
            if (!Auth.isAuthorized(authorizedRoles)) {
                console.log("not authorized");
                event.preventDefault();
                if (Auth.isAuthenticated()) {
                    // user is not allowed
                    $rootScope.$broadcast(AUTH_EVENTS.notAuthorized);
                } else {
                    // user is not logged in
                    $rootScope.$broadcast(AUTH_EVENTS.notAuthenticated);
                }
                $location.path(LOCATION_PATHS.home);
            }
        });

        $rootScope.$on('$routeChangeSuccess', function (e, next, prev) {
            $rootScope.loadingView = false;
        });
    }]);

require('./app.styles');
require('./app.config');
require('./global-constants/api-keys.constants.js');
require('./global-constants/auth-events.constants.js');
require('./global-constants/icon-paths.constants.js');
require('./global-constants/location-paths.constants.js');
require('./global-constants/url.constants.js');
require('./global-constants/user-roles.constants.js');
require('./core/event/event.module.js');
require('./core/core.module.js');
require('./core/auth/auth.module.js');
require('./core/request/request.module.js');
require('./core/account/account.module.js');
require('./core/map/map.module.js');
require('./core/user/user.module.js');
require('./core/location/location.module.js');
require('./core/zone/zone.module.js');
require('./core/team/team.module.js');
require('./core/route/route.module.js');
require('./core/feedback/feedback.module.js');
require('./create-team/create-team.module.js');
require('./login/login.module.js');
require('./page-footer/page-footer.module.js');
require('./view-team/view-team.module.js');
require('./reset-password/reset-password.module.js');
require('./join-team/join-team.module.js');
require('./route-archive/route-archive.module.js');
require('./account/account.module.js');
require('./faq/faq.module.js');
require('./page-header/page-header.module.js');
require('./register/register.module.js');
require('./event-routes/event-routes.module.js');
require('./request-password-reset/request-password-reset.module.js');
require('./assign-routes/assign-routes.module.js');
require('./zone-detail/zone-detail.module.js');
require('./home/home.module.js');
require('./logout/logout.module.js');
require('./combination-lock/combination-lock.module.js');
require('./routes/routes.module.js');
require('./feedback/feedback.module.js');
require('./zone-list/zone-list.module.js');
require('./sign-up/sign-up.module.js');
