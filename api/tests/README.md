# Tests README

##How to Run Tests:

1. install composer https://getcomposer.org/download/
2. cd into the `backendTOE` directory
3. install the dependencies with `composer install` 
4. run the tests through composer: `composer run test`

##Test Groupings:

Tests can be grouped so that they can be ran in isolation. This is done by annotating 
a test function with a block comment directly above it in the style of: 

/**

 \* @group Auth
 
 */
 
This comment would mark a test as being part of the 'Auth' group. To execute the 'Auth' group of tests, add it to the 'include' group of the phpunit.xml file. Read about it in the phpunit manual.

## Test Data

In the 'tests' folder there is a subdirectory called 'POST-PUT-data'. In it will be subdirectories corresponding to the entities being mocked.

The subdirectories will contain .json files that will act as the data sent in a POST or PUT request.

To use them during tests, use the 'LoadJSONObject' and the 'GetModifiedJSONObject' methods.
