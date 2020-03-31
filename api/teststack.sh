#!/usr/bin/env bash

STACK_NAME=output-test
BODY=file:///home/cl/PhpstormProjects/github.com/mjourard/trick-or-eat-demo/api/cfntest.yaml
aws cloudformation create-stack --stack-name $STACK_NAME --template-body $BODY --parameters ParameterKey=DBName,ParameterValue=outputtest ParameterKey=DBUser,ParameterValue=testuser ParameterKey=DBPassword,ParameterValue=asdf1234