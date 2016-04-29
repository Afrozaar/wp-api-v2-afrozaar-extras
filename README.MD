# wp-api-v2-afrozaar-extras
Adds additional WP REST Api calls and configuration changes that are needed by Mobile apps.
This plugin extends the **[WP REST Api](https://github.com/WP-API/WP-API)** plugin for WordPress.

## Installation

Copy this folder into your WordPress plugins directory and activate it throught the Plugins manager on WP-ADMIN.
Requires the WP REST API to be installed and active to work properly.

## Features
**WIP: All calls and features are in developement and might change drastically or removed completely**

### Settings
From version 0.3, there is a Settings (WP-ADMIN-->Settings-->Afrozaar Options) page for the module that can be used for any required configuration. At the moment it has a checkbox to allow you to enable or disable the &lt;script&gt; tag in a Post body.

This page was added as a test more than anything else and is very basic at the moment.

### New API calls:
1. Get a user by username<br />
  Used to retrieve basic user information for an already created WP user.
  - HTTP Method: **GET**
  ~~~~
  /users/login/<username>
  username: WP user login name
  ~~~~
  - Returns the **id**, **username**, **name** and **email** fields if user exists, eg:
  ~~~~
  {
    "id":1,
  "username":"admin",
  "name":"Administrator",
  "email":"admin@example.com"
}
  ~~~~
  - If the user does not exist, returns the standard WP REST API invalid user error, with HTTP Status code **404**:
  ~~~~
  {"code":"rest_user_invalid_username",
  "message":"Invalid user name.",
  "data":{"status":404}}
  ~~~~

2. Get a user using an email address<br />
  Works exactly like the call above, just uses an email address to fetch the user details
  - HTTP Method: **GET**
  ~~~~
  /users/email/<email_addr>
  email_addr: WP user email address
  ~~~~
  The response is the same as **no. 1** above.


3. Retrieve all media items for a post<br/>
  The standard WP REST API does not support getting all media items linked to a post. This call allows you to get media attached to a specific post.
  - HTTP Method: **GET**
  ~~~~
  /posts/<id>/media/<type>
  id: The post id
  type: MIME type of media, eg. image or audio
  ~~~~
  - Returns the media id, source_url and caption of all media items found as a JSON array:
  ~~~~
  [
  { "id":12, "source_url":"http://path/to/file.jpg", "Nice image caption" },
  { "id":13, "source_url":"http://another/file.jpg", "More caption" }
  ]
  ~~~~
  - Currently returns an empty array if no media items where found.

4. Get number of pages for posts
  By default there isn't an easy way of getting the number of posts on the site. This call gets the page counts for the 3 main post state type (publish, draft & private).
  A page consits of 10 posts by default.
  - HTTP Method: **GET**
  ~~~~
  /posts/pages
  ~~~~
  - Returns a JSON object with page counts:
  ~~~~
  {
    "publish":"1","draft":0,"privatePub":0
  }
  ~~~~  

### Configuration changes

1. Allow additional HTML tags to be included in Post body.<br/>
  By default, WP does not allow **script** tags to be included in a Post body (for good reason).
  We currently need that to be allowed to be able to use JWPlayer to show videos.

  ~~This option is hardcoded in the plugin source but future plans is to make this customizable and/or just have the ability to turn this option off.~~<br />
  There is now a Settings page for the plugin that allows you to turn this setting on or off.

## License  

[Apache License v2](http://www.apache.org/licenses/LICENSE-2.0)
