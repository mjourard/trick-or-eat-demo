# README

## Backend Setup

### Dependencies
* mysql 5.7
* nginx
* php-fpm7.2
* redis 3+

* Note: a development environment is provided in the docker-compose file contained within the `dev-container` folder. To start the development environment, `cd` into the `dev-container` folder and run `docker-compose up`. The composer commands will still need to be run for the dev environment.

1. Open a terminal/commandline, cd into the **backendTOE** directory

2. Next you must run the following commands:

    `composer install`
    `composer dumpautoload -o`
    
    They will install all the dependencies that the backend needs to work and then creates an autoloader for our own code. If you do not have composer, see [here](https://getcomposer.org/)

3. Once all of the dependencies are installed, we'll be setting up the environment variables that the application requires. If running on a server, they will be set in the php-fpm configuration file.
* Check ./backendTOE/src/GlobalCode/clsEnv.php for the environment variable names that are required for the application to run

You are all setup and are now able to use the backend.

# Frontend Setup

## Get Local Angular

1. install npm
2. `cd` into frontendTOE
3. run `npm install` to install the frontend dependencies
4. run `npm run build-prod` to build the distributable static assets for the frontend
5. The `dist/` folder is now setup to serve the angular application on prod. Here are some example methods to serve the files:
* copy the `dist/` folder into the `root` folder defined in the nginx `trickoreat.conf` folder on the server that will act as the webserver 
* deploy the `dist/` folder into an S3 bucket on AWS in which cloudfront is serving content from
* run the docker-compose file in the `dev-container` folder. Note, with the default setup you can also use the webpack dev server with hot-module reloading

## Tests
There are functional tests written for the backend. After setting up the backend, you can `cd` into the backendTOE folder and run `composer run test`. Ensure your php version is set to **php7.2**     