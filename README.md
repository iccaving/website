The ICCC website.

# Local

## Dockerised

Ensure you have docker and docker-compose installed. Then run:

```
docker-compose up -d
```

And the website should be accessible at localhost:8080.

Or you can do it without docker, see below.

## Non-docker

### Prerequisites

Ensure you have the following installed:

- php
- php-mysql
- mysql

Ensure this repo is downloaded.

### Install Bolt

The simplest way to install is to unzip the bolt distribution into this folder. We are using the `flat` distribution.

```
curl -O https://bolt.cm/distribution/archive/3.7/bolt-3.7.2-flat-structure.tar.gz
tar -xzf bolt-3.7.2-flat-structure.tar.gz --strip-components=1
```

If it was succesful you should be able to run the Bolt init command.

```
php app/nut init
```

### Set up database

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

### Run a local server

With Bolt installed and the mysql database running and populated you can run a local server.

```
php app/nut server:run
```

You should be able to access the site on `0.0.0.0:8000`.

# Server

## Prerequisites

Install [git-ftp](https://github.com/git-ftp/git-ftp).

Configure `git-ftp`:

```
git config git-ftp.user u666684881
git config git-ftp.password password
git config git-ftp.url "sftp://141.136.33.34:65002/~/public_html/"
```

## Update / Install Bolt

To update or install Bolt on the web server first update your local and the copy on the server:

```
curl -O https://bolt.cm/distribution/archive/3.7/bolt-3.7.2-flat-structure.tar.gz
tar -xzf bolt-3.7.2-flat-structure.tar.gz --strip-components=1
```

Check in git what has been replaced. This update will overwrite a few files we don't want to change so ensure you revert changes to the following files:

```
git checkout README.md .htaccess .gitignore
rm -rf theme/base-2016/ theme/base-2018/ theme/skeleton/
```

Then push the files. If this is the first time then use:

```
git ftp init
```

Otherwise:

```
git ftp push

```

## Server Configuration

Make a file in `app/config` called `config_local.yml`. This will contain the server database settings and any other settings overrides:

For example:

```
database:
    driver: mysql
    databasename: u666684881_rcc_caving
    username: u666684881_rcc_caving
    password: blah

siteurl: https://imperialcaving.com
siteroot: /home/u666684881/public_html
debug: false
```

## Update Live Site

Make any changes to themes or plugins in this repository, test them locally, then commit them. To push your changes to the live site use `git-ftp`:

```
git ftp push --insecure
```

which will overwrite the relevant files on the server.

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