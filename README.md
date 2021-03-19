# social_virtual_event_bbb_enrollment
This module overwrites the handling for Virtual Event BBB Meetings
## Installation
Install this module like any other Drupal module.
## Dependencies
Make sure you have installed and enabled the following modules:
- virtual_event_bbb
- social_event_an_enroll
## How that module works
This module acts on event content types only and uses the enrollment handling from open social to show or hide the "Join Meeting" link. It uses the open social enrollment service for "anonymous users" and grants access to virtual events once a valid token has been detected. Please note that this works only for "Public groups" as designed by OpenSocial.


 
