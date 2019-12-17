The ICCC website.

# Development

## Dockerised

Ensure you have docker and docker-compose installed. Then run:

```
docker-compose up -d
```

And the website should be accessible at localhost:8080.

Or you can do it without docker, see below.

## Prerequisites

Ensure you have the following installed:

- php 
- php-mysql
- mysql

Ensure this repo is downloaded.

## Install Bolt

The simplest way to install is to unzip the bolt distribution into this folder. We are using the `flat` distribution.

```
curl -O https://bolt.cm/distribution/bolt-latest-flat-structure.tar.gz
tar -xzf bolt-v3.6.6-flat-structure.tar.gz --strip-components=1
```

If it was succesful you should be able to run the Bolt init command.

```
php app/nut init
```

## Set up database

Ensure mysql is running and then create a user and database that use can access.

In `app/config/config_local.yml` you can add configure bolt to use this database and user. It is helpful to have the database name set to `rcc_caving`.

```
database:
    driver: mysql
    databasename: rcc_caving
    username: root
    password: root
    host: localhost
    port: 3306
```

If the database is running you can import the current site data into it. This is normally backed up as a `backup.sql` file. This is an sql file that you need to run against your mysql instance. This should find the `rcc_caving` database, clear it, and fill it with the most recently committed data.

## Run a local server

With Bolt installed and the mysql database running and populated you can run a local server.

```
php app/nut server:run
```

You should be able to access the site on `0.0.0.0:8000`.

# Data backup

The data is stored in a mysql database. There are two ways make a backup of the live instance.

- `ssh` into the web server and run the backup script
- Make a backup using the web ui
    - Log into the phpmyadmin instance
    - Go to the export tab.
    - Select `Custom - display all possible options` under `Export method`
    - Ensure `Add DROP TABLE / VIEW / PROCEDURE / FUNCTION / EVENT / TRIGGER statement` is enabled
    - Leave everything else as is.
    - Click go and save the file at the resulting prompt.

This will be a backup of all the article and page content. Images, videos, and files are not stored in the DB so need to be backed up seperately (rsync for example). 

You should replace the `backup.sql` file when you make backups to ensure it is up to date.


# Update Live Site

You can keep the live site up-to-date with the git repo using [git-ftp](https://github.com/git-ftp/git-ftp).

You don't need to edit your .git/config file, just clone a copy of this repo and call the command

```
git ftp init -u "yourusernamehere123" "sftp://dougal.union.ic.ac.uk:10022/website/rcc/caving/" -P -vv --insecure
```

which will overwrite the relevant files on the server.


# Updating Bolt

To update Bolt on the web server first update your local copy. Do not update directly on the web server.

```
curl -O https://bolt.cm/distribution/bolt-latest-flat-structure.tar.gz
tar -xzf bolt-v3.6.6-flat-structure.tar.gz --strip-components=1
```

Check in git what has been replaced. This update will overwrite a few files we don't want to change so ensure you reverty changes to the following files:

- `.gitignore`
- `.htaccess`
- `README.md`

Ensure this does not break the website by running it and clicking around.

```
php app/nut server:run
```
