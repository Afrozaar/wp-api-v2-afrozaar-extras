# Changelog

## 0.34
- Added live stream Meta object check to stream

## 0.33
- Added kses tags for iframe

## 0.32
- Updated plugin to work with new WP versions that has rest-api included

## 0.31
- Removed adding users to blog automatically

## 0.30
- Updated msg for iOS comment notification

## 0.29
- Added hook to remove post map from AWS when status changes to Private

## 0.28
- Moved meta delete function into it's own hook

## 0.27
- Added check for empty key on meta delete

## 0.26
- Added hook to removed Baobab ingested meta object on post updated

## 0.25
- Added hooks to create and delete DynamoDB entries for Post markers

## 0.24
- Added logic to add user to site if user already exists on another multi site

## 0.23
- Updated Stream call to include Comments for post

## 0.22
- Updated AWS classes to latest version
- New comment hook and SNS config

## 0.21
- Added role field to custom user calls

## 0.20
- Fixed bug in post pages causing divide by zero error in output

## 0.19
- Updated stream call to add related post info

## 0.18
- Updated SNS publish call to support Apple specific data

## 0.17
- Updated media info call

## 0.16
- Added custom post call for use with Stream
- Removed unused post counts call

## 0.15
- Added authorId to push message json
- Added check to not overwrite secret_access_key

## 0.14
- Added Amazon AWS/SNS support to send push notifications to apps

## 0.11
- Added **class** and **download** to <a> tags

## 0.10
- Added post counts call

## 0.9
- Added caption to media call

## 0.8
- Fixed typo in username variable

## 0.7
- Updated retrieve user call to return if user is an admin.

## 0.6
- Added REST call to retrieve user using email address.

## 0.5
- Add custom meta keys used in filtering.

## 0.4
- added JWPlayer script tags to headers

## 0.3
- added a Setings page to enable or disable the custom tags

## 0.2
- added HTML **script** to allowed tags in Post body

## 0.1
- Initial REST calls for user and media retrieval
