# Deployment

These docs are for deploying the app to AWS. I'll list out the technologies used and how to configure everything quickly

## Prerequisites

The following tools are required to deploy the web app to AWS:

1. php 7.2
2. composer - https://getcomposer.org/download
3. npm - https://npmjs.com/get-npm
4. aws-cli - https://docs.aws.amazon.com/cli/latest

You will also need the following services configured and the appropriate secrets

#### Email
This can be handled by either Google Cloud Platform or AWS. I've found AWS to be easier. They are described in the Development Setup document.

#### Google Maps
You will need an api key so that the routes for users is rendered properly.

#### AWS S3
Create a bucket that will store your frontend code. It should be setup such that nothing is publicly accessible.

#### AWS Systems Manager - Parameter Store
These parameter store values are used in the creation and deployment of the api:

| Name  | Type | Description |
| ------------- | ------------- | ------------- |
| /toe/prod/DBApiUsername | String | An alphanumeric string that is 12-24 characters long to be used as the database admin's username |
| /toe/prod/DBApiPass | String | An alphanumeric string that is 12-24 characters long to be used as the database admin's password |
| /toe/prod/TOE_ENCODED_JWT_KEY | String | An alphanumeric string is used to encrypt a JWT key for the frontend |
| /toe/prod/ClientDomain | String | The main domain that users will go to e.g. guelphtrickoreat.ca |
| /toe/prod/PASSWORD_RESET_EMAIL | String | The email address that will be sending out password reset emails e.g. noreply@guelphtrickoreat.ca |
| /toe/prod/GCP_CLIENT_ID | String | The client id for sending out emails. Not necessary if using AWS SES for emails |
| /toe/prod/GCP_CLIENT_SECRET | String | The client secret for sending out emails. Not necessary if using AWS SES for emails |
| /toe/prod/GCP_CLIENT_REFRESH | String | The refresh token for sending out emails. Not necessary if using AWS SES for emails |
| /toe/prod/CFBucketName | String | The name of the S3 bucket that holds the client code to be served to the user. Created earlier in the **AWS S3** section |
| /toe/prod/BaseDomain | String | The base domain of the web app e.g. guelphtrickoreat.ca |

To add new values to parameter store, use the following command:

`aws ssm put-parameter --name $PARAMETER_NAME --type String --value $PARAMETER_VALUE`

Add the `--overwrite` flag if you need to update the values

## Application Setup

Follow the steps found in [Development Setup](docs/application-setup.md) to ensure all dependency files are installed locally.



## Frontend

The frontend is your basic Cloudfront Distribution that points at an S3 bucket. That configuration should be handled by the serverless config in the backend.

To upload code to S3 which will then update the website, run `npm run deploy`, setting the appropriate environment variable for the bucketname. You'll need to have a configured [aws cli](https://docs.aws.amazon.com/cli/latest/userguide/cli-chap-install.html) first.

## Backend

The backend sits behind Cloudfront, API Gateway and Lambda. Deployment of that code is handled with the [serverless framework](https://serverless.com/). To get started quickly, install the following:

`npm i -g serverless`
`npm i -g serverless-pseudo-parameters`

Once you've done that, you can cd into the `backendTOE` directory and run `serverless deploy` and the new cloudformation stack based on the serverless.yml file defined will be in AWS. 

## Testing

Now just do a smoke test of checking a few of the screens, as they should be fine if all integration tests passed locally
