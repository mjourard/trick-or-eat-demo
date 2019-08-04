# Docker Basics

Adding a temp README t othe repo for quick docker tips since there isn't a form home for those at the moment

## Getting Started
To launch the trick or eat docker environment, cd into the 'dev-contrainer' folder and call ```docker-compose up```

Adding a -d flag will put it in detached state, not sending log messages to your terminal's STDOU

## Making Changes
To have your container-level changes take effect (such as adding or removing a service, or installing something in the container) you'll need to restart the containers. Run reset.sh and it will bring your container down and remove any built containers. You can then restart the container and your changes will have taken effect.

## Modifying Images
Sometimes you'll want to know the location of configuratoin files so that you can modify them with sed commands in the build phase. Thiere locations are not always obvious, so to find them you can get shell access into your active container by running docker ```exec -it $CONTAINER_ID /bin/bash```

To get the $CONTAINER_ID, you can run ```docker ps```