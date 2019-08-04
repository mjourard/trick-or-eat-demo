## Trick Or Eat Version Notes

### 0.3.9
#### 2017-10-04
 * Added a feedback module, allowing users to leave us direct feedback in 2000 characters or less
 * Added a tool to import user and team information from the CSV files exported from the main MX database.

### 0.3.8
#### 2017-10-04
 * Added the ability for team captains, admins and organizers to kick their teammates from the team
 * Added the ability for users to leave their team
 * Made the backend classes psr-4 compliant and updated the package.json file to include the tests folder in the autoloader under a different namespace


### 0.3.7
#### 2017-10-04
 * Set the footer to always be at the bottom of the page.

### 0.3.6
#### 2017-10-01
 * Added a footer to the page
 * Updated the mobile nav menu to have the same values as the desktop version
 * Added a page for participants to view a list of their teammates
 * Replaced some of the headers in the assign-routes page with icons.

### 0.3.5
#### 2017-08-22
 * Improved the look of the 'Assign Routes' page by using the md-data-table component
 * Added the TOE icon to the header
 * Added the server's crontab to the project

### 0.3.4
#### 2017-08-16
##### General
 * moved the database host and port into clsConstants for later removal into a config constants file
 * Added a thin DAL class to the cron-jobs folder to be used in cron-job scripts
 * modified the UserInfo service to be able to get any data from the user table based on an email
 
##### Reset Password Story
 * Added a clean-reset-token script for cron-jobs
 * Added the password_request table to the dumps that were missing it
 * the reset password page now checks on load if the token being used is valid
 * reset password page now displays information stating that the password has been reset
 * checkTokenStatus is now an anonymous function since it needs to be
 * requestResetToken now invalidates all previous tokens by that user.

### 0.3.3
#### 2017-08-14
 * Added Role Based Access Control to the backend
 * [DEV-FEATURE] Added a 'catch-all' error handler to the silex routes so it no longer outputs HTML pages on exceptions. Includes error message and stack trace. Stack trace omits internal silex calls and phpunit calls.
 * Added backend error logging that now pushes to a remote Redis server
 * Added the password reset functionality

### 0.3.2
#### 2017-04-13
 * Added routes to the application. Organizers can now upload .kmz and .kml files to be assigned to an event
 * Users now able to view the routes assigned to them
 * - still need to preprocess the .kmz file
 * - - add the zone's starting point
 * - - split the kml file into multiple routes if multiple routes are detected. 1 route per file

### 0.3.1
#### 2017-02-22 
 * Made the disability requirements page on the team creation page match the ones on the registration page
 * Added a 'none' option to the disability requirements on both the team creation page and the registration page.

### 0.3.0
#### 2017-02-20 
 * Added zones to the application. Still need to make it record the zoom of the map.

### 0.2.0
#### 2017-02-01 
 * Completed refactor of front end