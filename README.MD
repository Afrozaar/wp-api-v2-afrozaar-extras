# wp-api-v2-afrozaar-extras
Adds additional WP REST Api calls and configuration changes that are needed by Mobile apps.
This plugin extends the **[WP REST Api](https://github.com/WP-API)** plugin for WordPress.

## Features
**WIP: All calls and features are in developement and might change drastically or removed completely**

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

2. Retrieve all media items for a post<br/>
  The standard WP REST API does not support getting all media items linked to a post. This call allows you to get media attached to a specific post.
  - HTTP Method: **GET**
  ~~~~
  /posts/<id>/media/<type>
  id: The post id
  type: MIME type of media, eg. image or audio
  ~~~~
  - Returns the media id and guid of all media items found as a JSON array:
  ~~~~
  [
  { "id":12, "guid":"http://path/to/file.jpg" },
  { "id":13, "guid":"http://another/file.jpg" }
  ]
  ~~~~
  - Currently returns an empty array if no media items where found.

### Configuration changes

1. Allow additional HTML tags to be included in Post body.<br/>
  By default, WP does not allow **script** tags to be included in a Post body (for good reason).
  We currently need that to be allowed to be able to use JWPlayer to show videos.

  This option is hardcoded in the plugin source but future plans is to make this customizable and/or just have the ability to turn this option off.
