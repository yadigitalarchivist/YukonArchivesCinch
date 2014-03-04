Yukon Archives' fork of the CINCH Project
=========================================

The original CINCH was developed by the State Library of North Carolina and can be found here: http://cinch.nclive.org/

A project to develop a bulk download service to a central repository that will maintain original file timestamps, extract file level metadata, create file checksums and periodically validate checksums for continued file integrity. 

Users merely need to upload a list of URLs to download and when the process completes they can download the requested files and file metadata to their local environment.

Currently supported file types:
 
* PDF
* Microsoft Word (.doc and .docx)
* Microsoft Excel (.xls and .xlsx)
* Microsoft PowerPoint (.ppt and .pptx)
* JPG
* PNG
* GIF
* Text (e.g. files with .txt, .csv extensions, etc.) Note that text files may be flagged as "problems".

<a href="http://digitalpreservation.ncdcr.gov/cinch/" target="_blank">Learn more about CINCH</a>.

<a href="http://cinch.nclive.org/Cinch/CINCHdocumentation.pdf">Full end user instructions</a>

Funding for the CINCH: Capture, Ingest, & Checksum tool was made possible through an IMLS Sparks! Ignition grant.

License:  CINCH is released under the Unlicense (http://unlicense.org/)

Requirements
------------

* Currently Cinch will only run on *nix systems. Yukon Archives recommends Ubuntu Server 12.04/14.04 LTS.
* PHP 5.3+
* MySQL 5.5+
* ClamAV
* Java 6 JRE

If installing via `apt`, the specific packages, and their minimum acceptable versions are as follows:

* apache2-mpm-prefork 2.2.22+
* php5 5.3.10+
* php5-curl 5.3.10+
* php5-mysql 5.3.10+
* mysql-server 5.5.35+
* clamav-daemon 0.97.8+
* openjdk-6-jre 6b27-1.12.6+
* postfix 2.9.6-1+ or an equivalent MTA

Installation
------------

To set up Cinch on your system:

1. Download or clone the Cinch files into a web accessible directory (e.g., `/var/www/`).
1. Create a new MySQL database and import the project.sql file into it. Create a user for the Cinch application with full access to that database.
1. Open `protected/config/main.php` in a text editor:
 1. Scroll down to the db settings (line 68 or so). Set the database name, the username and password for your new Cinch database.
 1. Scroll to the bottom of main.php and set 'adminEmail' email address to your email address.
1. Repeat the previous step for `protected/config/console.php`.
1. In `protected/config/console.php` if the setting in date_default_timezone_set() isn't correct you should change it to your timezone setting. For a complete list of timezone settings see: http://us2.php.net/manual/en/timezones.php.
1. Go to http://tika.apache.org/download.html and download the Apache Tika jar file. Copy it to the `protected/` directory. For now, Cinch expects version 1.4 of Tika.
1. Run the `bin/set-cinch-permissions` script to apply the required permissions to the uploaded files directories.
1. Configure Cinch cron tasks. See the sample cron.txt.original file the root of Cinch for suggestions on how you might want to configure it. It is advised to create a special OS-level user account to run these tasks. That account must have write permissions on the `protected/curl_downloads/` directory.
1. In the `/etc/cron.daily/` directory, add a symbolic link to `/usr/bin/freshclam` so that it will be run by root once each day.

You should now be able to login to the web interface as: admin admin.

You should then go the change password tab and update your password.

If you don't want to run Cinch via cron you can run it from the command line.
If you navigate to Cinch/protected and run the following: path/to/php yiic.php you should be presented with a list of available commands.
The general way to run a command is: path/to/php yiic.php command.
Several commands such as checksum and purgeystem have subcommands, which have to be run like so from the command line: path/to/php yiic.php command sub-command.

You should run the commands in the following order:

* readfile
* download
* viruscheck
* checksum create
* metadata
* metadatacsv
* checksum check (optional, recalculates checksum to see if anything has changed between download and current time.)
* errorcsv
* zipcreation
* purgesystem check (optional, Notifies users after 20 days that they have files marked for deletion in 10 days.)
* purgesystem delete (optional, deletes user files older than 30 days old. Note this deletes upload lists, and all csv file information from the database, but downloaded file, metadata, errors, and event information is retained in the database.)

You may also run all of the tasks in order in one step using the `bin/run-cinch-tasks` script.

Useful Notes
------------  

* You should only run the zipcreation command once a day otherwise it will cause conflicts in file processing.
* Uploaded URL lists are saved into protected/uploads/"user's username". With the user's directory being created on first upload and being deleted thereafter if it's empty.
* Downloaded user files are saved  into protected/curl_downloads/"user's username". With the user's directory being created on first file downloaded and being deleted thereafter if it's empty.
* CINCH API documentation can be viewed at: http://cinch.nclive.org/c_docs/packages/db_Default.html.

Adding New Users:

Currently users can't self-register (This fits our own particular needs.)

1. Login as a user with admin privileges such as the default "admin" user account.
1. Go to Admin > User Administration->Create User and add the user. The user will be sent an email with their username and password. Users may then login and change their password.
1. Go to Admin > User Rights. Click the user's username and assign the "Authenticated" role.

You might want to take a look at the documentation for the Yii Rights extension used in CINCH: http://yii-rights.googlecode.com/files/yii-rights-doc-1.2.0.pdf

Parts of Cinch include:

- Yii Framework <http://www.yiiframework.com>
- jQuery <http://jquery.com>
- jQuery UI <http://jqueryui.com>
- Apache Tika <http://tika.apache.org>
- ClamAV <http://www.clamav.net>
