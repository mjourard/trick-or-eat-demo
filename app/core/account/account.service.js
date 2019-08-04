/**
 * Created by LENOVO-T430 on 1/9/2017.
 */
angular.module('core.account').service('Account', ['Request', 'User', function(Request, User) {
    /**
     * Sends new account information to be posted into the db
     * @param  fName - the first name of the new user
     * @param  lName - the last name of the new user
     * @param  pass - the password of the new user
     * @param  email - the email of the new user
     * @param  regionId - the regionId of the new user
     */
    this.registerUser = function (email, pass, fName, lName, regionId) {
        return Request.post('/register', {
            "email": email,
            "password": pass,
            "first_name": fName,
            "last_name": lName,
            "region_id": regionId
        })
            .then(function success(response) {
                return response;
            }, function failure(response) {
                return response;
            });
    };

    /**
     * Sends the back end the users new account information
     * @param  {string} fName  - The new first name of the logged in account
     * @param  {string} lName  - The new last name of the logged in account
     * @param  {Object} region_id - the new region of the logged in account
     */
    this.updateUser = function (fName, lName, region_id) {
        var pushObj = {"first_name": fName, "last_name": lName, "region_id": region_id};
        Request.put('/user/update', pushObj)
            .then(function success(response) {
                var body = response.data;
                if (response.data.success) {
                    User.destroy();
                    User.create(
                        body.email,
                        body.id,
                        body.first_name,
                        body.last_name,
                        body.country_id,
                        body.country_name,
                        body.region_id,
                        body.region_name,
                        body.team_id,
                        body.team_name,
                        body.event_id,
                        body.event_name,
						body.user_roles
                    );
                }
            }, function failure(response) {
                console.log("failed to update the user: response = ");
                console.log(response);
            })
    };

    /**
     * Sends a password reset request to the backend. Password reset email is sent out to the user's email account.
     * @param  {string} email - The email of the account that is having their password reset
     */
    this.requestPasswordReset = function(email) {
        return Request.post('/requestReset', {
            "email": email
        })
            .then(function success(response) {
                return response;
            }, function failure(response) {
                return response;
            })
    };

    /**
     * Sends the new password and the request token to the backend. Should now set the password of the linked account to the new password
     * @param {string} newPassword - the requestd value of the password for the user
     * @param {string} token - the token sent after the user requested a password reset
     * @returns {*}
     */
    this.resetPassword = function(newPassword, token) {
        return Request.post('/resetPassword', {
            "password": newPassword,
            "jwt": token
        })
            .then(function success() {
                return true;
            }, function failure(response) {
                return response.data.message;
            })
    };

    this.checkTokenStatus = function(token) {
        return Request.get('/checkTokenStatus/' + token)
            .then(function success() {
                return true;
            }, function failure() {
                return false;
            })
    }

}]);