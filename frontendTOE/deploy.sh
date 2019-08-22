#!/usr/bin/env bash

if [ -z "$TOE_FRONTEND_BUCKET" ]
then
	echo "The environment variable TOE_FRONTEND_BUCKET was empty. Set that before running this script"
	exit 1
fi

DEPLOYMENT_FOLDER="dist"

echo "Syncing the $DEPLOYMENT_FOLDER/ folder..."
aws s3 sync dist/ s3://$TOE_FRONTEND_BUCKET --delete
