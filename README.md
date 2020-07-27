# Recipe Drupal

Færing recipe for a Drupal project.

- [Setup](#setup)
    - [Requirements](#requirements)
    - [Installation](#installation)
    - [Configuration](#configuration)
- [How to use](#how-to-use)
    - [Docker compose commands](#docker-compose-commands)
    - [Drush commands](#drush-commands)
    - [Customizations](#customizations)
        - [Change PHP configuration](#change-php-configuration)
        - [Add PHP packages](#add-php-packages)
        - [Settings](#settings)
- [Resources](#resources)

## Setup

### Requirements

- Færing: [https://github.com/aerzas/faering](https://github.com/aerzas/faering)

### Installation

**Define the project name and PHP version**

The PHP versions `7.2`, `7.3` and `7.4` are available.

```sh
PROJECT_NAME=drupal
PHP_VERSION=7.3
```

**Create a new Drupal project**

This step is optional, you can use an existing project instead.

```sh
mkdir -p ${PROJECT_NAME:-drupal} && cd ${PROJECT_NAME:-drupal}
docker run \
    --rm \
    -u $(id -u) \
    -e COMPOSER_MEMORY_LIMIT=-1 \
    -v $(pwd):/drupal aerzas/php:${PHP_VERSION:-7.3}-1.0.2-drupal-dev \
    composer create-project drupal/recommended-project /drupal --no-interaction
```

**Define the Drupal recipe location**

```sh
DOCKER_FOLDER=./docker
mkdir -p ${DOCKER_FOLDER:-./docker}
```

**Install Drupal recipe**

Set the drupal recipe as a submodule or a copy.

- As a submodule (recommended if the project is a git repository):
```sh
git submodule add git@github.com:aerzas/faering-drupal.git ${DOCKER_FOLDER:-./docker}
```
- As a copy
```sh
git clone --depth=1 git@github.com:aerzas/faering-drupal.git ${DOCKER_FOLDER:-./docker}
rm -rf ${DOCKER_FOLDER:-./docker}/.git
```

**Set environment variables**

```sh
cp ${DOCKER_FOLDER:-./docker}/.env.dist ${DOCKER_FOLDER:-./docker}/.env
sed -i "s/PROJECT_NAME=drupal/PROJECT_NAME=${PROJECT_NAME}/g" ${DOCKER_FOLDER:-./docker}/.env
sed -i "s/PHP_VERSION=7.3/PHP_VERSION=${PHP_VERSION}/g" ${DOCKER_FOLDER:-./docker}/.env
sed -i "s/COMPOSE_PROJECT_NAME=drupal/COMPOSE_PROJECT_NAME=${PROJECT_NAME}/g" ${DOCKER_FOLDER:-./docker}/.env
sed -i "s/COMPOSE_MOUNT_MODE=/COMPOSE_MOUNT_MODE=$([ "${OSTYPE}" != "${OSTYPE#darwin}" ] && echo ':cached')/g" ${DOCKER_FOLDER:-./docker}/.env
```

### Configuration

A default configuration applies, but it should probably be fine-tuned according to the needs.

| Variable | Description | Default Value
| --- | --- | ---
| Project
| `PROJECT_NAME` | Project name | `drupal`
| `PHP_VERSION` | PHP version (`7.2`, `7.3` and `7.4`) | `7.4`
| `CODEBASE_PATH` | Codebase path relative to the main `docker-compose.yml` file | `../.`
| Docker compose
| `COMPOSE_PROJECT_NAME` | Compose stack name (should be the same as `PROJECT_NAME`) | `drupal`
| `COMPOSE_FILE` | List of docker-compose file separated by `;` | `docker-compose.yml:docker-compose.settings.yml`
| `COMPOSE_MOUNT_MODE` | Mount mode, recommended empty on Linux and `:cached` on MacOS
| Project
| `DRUPAL_BATCH_SIZE` | Items to process per batch | `50`
| `DRUPAL_HASH_SALT` | Salt for security hardening | `sample-hash-which-needs-to-be-replaced`
| `DRUPAL_DEV` | Development mode, set it to `1` to activate | `0`

## How to use

### Docker compose commands

Docker compose commands must be executed inside the `DOCKER_FOLDER`.

**Start the containers**
```sh
docker-compose up -d
```

**List running containers**
```sh
docker-compose ps
```

**Execute a command in a service**
```sh
docker-compose exec [service] [command]
```
For examples
```sh
# Clear the drupal caches
docker-compose exec php drush cr
# Connect to the PHP container (starting a shell)
docker-compose exec php ash
```

**Stop the containers**
```sh
docker-compose down
```

**Remove the database volume**
```sh
docker-compose down -v
```

### Drush commands

Drush commands must be executed:
- inside the `DOCKER_FOLDER` using a docker-compose command, example:
```sh
docker-compose exec php drush cr
``` 
- inside the project folder (`var/www/html`) as an interactive shell:
```sh
docker-compose exec php ash
drush cr
```

**Installing drush**

On fresh install, the drush package is not installed by default.

```sh
docker-compose exec php composer require drush/drush
```

**Install a new website**
```sh
docker-compose exec php drush site-install minimal -y
```

**Clear caches**
```sh
docker-compose exec php drush cr
```

**Get admin one-time URL login**
```sh
docker-compose exec php drush uli
```

### Customizations

Container customizations must be added to a new `docker-compose.custom.yml` or `docker-compose.override.yml` file which
can be loaded by appending the name of the file to the `COMPOSE_FILE` environment variable joined with the `:`
separator.

To avoid editing a submodule, a `docker-custom` folder can be created at the root of the project with all the
customizations. Alternatively, the `DOCKER_FOLDER` can be set to `./docker/base` while the customizations could be
set in `./docker/custom`, in that case the `CODEBASE_PATH` must be updated to `../../.`.

The `.env` file ust be updated with the file reference.
```yaml
COMPOSE_FILE=docker-compose.yml:docker-compose.settings.yml:../docker-custom/docker-compose.custom.yml
```

Containers must be reloaded to apply the changes.
```sh
docker-compose down
docker-compose up -d
```

#### Change PHP configuration

Create a new or update an existing `docker-custom/docker-compose.custom.yml` from the root of the project.
```yaml
version: "3.5"
services:
  php:
    environment:
      PHP_MEMORY_LIMIT: 256M
```

A full list of configuration variables is available in the PHP image
[documentation](https://github.com/aerzas/docker-php#environment-variables)

#### Add PHP packages

Faering images are minimalists, so some PHP packages may require additional libraries.

Create a new or update an existing `docker-custom/php/Dockerfile` from the root of the project.
```dockerfile
ARG BASE_IMAGE_TAG

FROM aerzas/php:${BASE_IMAGE_TAG}

USER root

RUN set -ex; \
    # Install SOAP depencies
    apk add --no-cache libxml2-dev; \
    # Install and enable SOAP
    docker-php-ext-install soap; \
    docker-php-ext-enable soap

USER 1001
```

Create a new or update an existing `docker-custom/docker-compose.custom.yml` from the root of the project.
```yaml
version: "3.5"

services:
  php:
    image: ${PROJECT_NAME:-drupal}/php:${PHP_VERSION}
    build:
      context: ../docker-custom/php
      dockerfile: ./Dockerfile
      args:
        BASE_IMAGE_TAG: aerzas/php:${PHP_VERSION:-7.3}-1.0.2-drupal-dev
```

#### Settings

Optimized Drupal settings are loaded by default via the `docker-compose.settings.yml` file. Additional settings or
settings overrides must be added to a new `settings.custom.php` or `settings.local.php` file in the
`web/sites/default/` folder of the project.

## Resources

- [HTTPD docker image (`aerzas/httpd`)](https://hub.docker.com/r/aerzas/httpd)
- [MariaDB docker image (`aerzas/mariadb`)](https://hub.docker.com/r/aerzas/mariadb)
- [PHP docker image (`aerzas/php`)](https://hub.docker.com/r/aerzas/php)
- [Mailhog docker image (`mailhog/mailhog`)](https://hub.docker.com/r/mailhog/mailhog)
- [Drupal documentation](https://www.drupal.org/documentation)
- [Drush documentation](https://docs.drush.org/en/master/)
