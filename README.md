# Trick or Eat Demo

[guelphtrickoreat.ca](https://guelphtrickoreat.ca)

A demo site of the trick or eat web app I made in University.

Hosted on AWS.

To deploy your own version, follow the instructions in [Deployment](docs/deployment.md)  

## App Description

An app that would run the 'Trick or Eat' Halloween food collection event at the University of Guelph. 

Every year, the non-profit Meal Exchange would run a door-to-door non-perishable food collection campaign, 
in which volunteers (university undergrad students and other members of the community) would go trick-or-treating on Halloween and collect non-perishable food instead of candy. 
The food would then be donated to the local food bank.

The app was designed to help with the logistics of organizing and running the event as well as improve the organizer's ability to gather feedback from participants after the event.

The specific tasks that the app helped with were:

 * participant signup and 'team' organization
 * bus assignments
 * distributing maps to participants via the Google Maps API
 * waver signing and collection
 * accessibility concerns (visual/hearing/mobility impairments)
    * visually impaired participants would want streets that are well lit
    * hearing impaired participants would want streets further from motor ways
    * mobility impaired participants would prefer flatter streets, side walks on both sides, not a lot of stairs to lead up to doors, etc.
 * gathering feedback from participants

## Demo Accounts

The following accounts are available if you'd like to view the app's functionality from different types of user's perspectives

| Account Type  | Email | Password |
| ------------- | ------------- | ------------- |
| Admin  | admin_on_team_with_route@toetests.com  | password |
| Organizer  | organizer@toetests.com | password |
| Team Captain  | user_on_team_as_captain@toetests.com  | password |
| User On Team  | user_on_team@toetests.com  | password |
| User Without Team  | user_registered_for_event@toetests.com  | password |

## Docs

The docs folder contains documentation on development setup and deployment

  1. [Development Setup](docs/application-setup.md)
  2. [Deployment](docs/deployment.md)
  3. [Changelog](docs/version-notes.md)
