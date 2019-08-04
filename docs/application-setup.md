# README

# Backend Setup


1. Open a terminal/commandline, cd into the **backendTOE** directory

2. Next you must run the following command:

    `composer install`
    
    This will install all the dependancies that the backend needs to work. If you do not have composer, see [here](https://getcomposer.org/)

3. Once all of the dependancies are installed move into the config directory inside of backendTOE.

4. Open up config.php and edit the field labeled password with its value being 'root'. Update 'root' with your mysql password if it differs and save the file.

5. Lastly you must update or create your database in mysql. If you do not have a database already setup for mysql please see below for more help.

6. To update your database run the following command:

    `mysql -u root -p [database name (default is scotchbox)] < dump.sql`
    
    This will update or initallize your database. If you do not have your database set to scotchbox you must be sure to update that info also inside of the config.php file along with the password.

7. If you are setting up a development environment, you will need to pass a $_ENV environment variable. The name is 'dev_mode' and the value is 'on'. This is used for separating controlling where test and production data is sent, e.g. redis error logs go to the central server in production and will go to the local host in a development environment.

You are all setup and are now able to use the backend.

## Don't have a mysql database?
Before starting this section make sure you have installed mysql on your machine.

First open your mysql database this differs for many different machines. On mac it can be opened one of two ways.

1. mysql -u root -p (Then you will be prompted for a password. This will be your mysql password)

2. Applications/something/else/here...

For linux users it should be the same as the first one for mac

1. mysql -u root -p (Then you will be prompted for a password. This will be your mysql password).

#### mysql is open
When inside of mysql, run the command:

`create database scotchbox;`

# Frontend Setup

## Get Local Angular

1. install npm
2. in the 'app' directory, call 'npm install'