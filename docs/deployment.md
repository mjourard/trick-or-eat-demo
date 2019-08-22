# Deployment

These docs are for deploying the app to AWS. I'll list out the technologies used and how to configure everything quickly

## Frontend

The frontend is your basic Cloudfront Distribution that points at an S3 bucket. That configuration should be handled by the serverless config in the backend.

To upload code to S3 which will then update the website, run frontendTOE/deploy.sh, setting the appropriate environment variable for the bucketname. You'll need to have a configured [aws cli](https://docs.aws.amazon.com/cli/latest/userguide/cli-chap-install.html) first.

## Backend

The backend sits behind Cloudfront, API Gateway and Lambda. Deployment of that code is handled with the [serverless framework](https://serverless.com/). To get started quickly, install the following:

`npm i -g serverless`
`npm i -g serverless-pseudo-parameters`

Once you've done that, you can cd into the `backendTOE` directory and run `serverless deploy` and the new cloudformation stack based on the serverless.yml file defined will be in AWS. 

## Testing

Now just do a smoke test of checking a few of the screens, as they should be fine if all integration tests passed locally
