# README

## Prerequisites

You will need the following development tools installed on your machine to download the app dependencies:
1. php 7.2
2. composer - https://getcomposer.org/download
3. npm - https://npmjs.com/get-npm
4. docker - https://docs.docker.com
5. docker-compose - https://docs.docker.com

You will also need to setup an email service with either Amazon Web Services or Google Cloud Platform.

This service is only for password reset requests, so if that cannot be setup then you can add this group to the `<exclude>` block of 
the phpunit config file: `<group>Request-Password</group`



## Backend Setup

Here we will setup the backend of the web app for development

1. Install Dependences - open a terminal in the `api` directory and run `composer install`. This will download all the php dependencies required
2. Generate the autoloader - `composer dumpautoload -o`
3. Create env vars file - copy the **.env.dist** file to a **.env** file. Depending on which cloud provider you went with, fill in the appropriate environment variables: 
4. Once you've installed the frontend, `cd` into the `.docker` folder and run `docker-compose up`. You will now be able to run everything in the backend
5. Verify everything is setup correctly by going into the `api` folder and running `composer run test`. If any tests fail, something is misconfigured.

### Environment variables
These values are based on the cloud provider you went with for sending out email values.

**GCP** 

These values will be obtained by following the OAUTH2 instructions found at https://github.com/thephpleague/oauth2-google

* TOE_RESET_CLIENT_ID
* TOE_RESET_CLIENT_SECRET 
* TOE_RESET_REFRESH_TOKEN
* TOE_EMAIL_CLIENT=gmail

**AWS**
* TOE_AWS_REGION - The region you have SES access in
* TOE_AWS_ACCESS_KEY - your AWS access key
* TOE_AWS_SECRET_KEY - your AWS secret key
* TOE_AWS_ASSUME_ROLE_ARN - An optional ARN of a role that you will assume because you've followed best practices and have limited your user access to be able to assume roles of different accounts
* TOE_EMAIL_CLIENT=ses

For both cases, fill out TOE_RESET_ACCOUNT_EMAIL as the email that will be in the `from` section of an email, e.g. `noreply@guelphtrickoreat.ca`

### Dependencies
* mysql 5.7
* nginx
* php-fpm7.2
* redis 3+

## Frontend Setup

2. `cd` into frontendTOE
3. run `npm install` to install the frontend dependencies
4. run `npm run build:dev` to build the distributable static assets for the frontend
5. The `dist/` folder is now setup to serve the frontend locally. 