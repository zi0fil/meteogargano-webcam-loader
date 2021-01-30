# Introduction

This is a simple script to display images continuously uploaded by FTP or other protocol.

it is used within the site meteogargano.org.

It has the following goals:
 - to avoid displaying images which are not completely uploaded - it checks for the end of JPEG image marker
   and in case of corrupted or uncompleted upload continues to use data from cache file
 - to enable further processing of the source image (currently not present)
 - to allow an uniform URL schema regardless of source images.
 
# How to use it

Configuration file is `webcam_config.json`. It contains the name of the image, the source filename etc.
All the source images should be loaded into the same folder of this script.

Usage:
```
 load_webcam.php?webcam=<NameOfWebcam>
 
```

or, in case of using directory-level configuration file `.htaccess`:
```
 /webcam/<NameOfWebcam>
```