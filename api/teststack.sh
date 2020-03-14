#!/usr/bin/env bash

STACK_NAME=redis-test
BODY=file:///home/cl/PhpstormProjects/github.com/mjourard/trick-or-eat-demo/api/cfntest.yml
aws cloudformation create-stack --stack-name $STACK_NAME --template-body $BODY