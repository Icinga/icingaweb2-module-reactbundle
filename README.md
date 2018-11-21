Icinga Web 2 - ReactPHP-based 3rd party libraries
=================================================

This repository is an attempt to ship 3rd party libraries that might be useful
for asynchronous PHP-based Icinga Web 2 modules. Please download the latest
release and install it like any other module.

> **HINT**: Do NOT install the GIT master, it will not work!

Sample Tarball installation
---------------------------

```sh
RELEASES="https://github.com/Icinga/icingaweb2-module-reactbundle/archive" \
&& MODULES_PATH="/usr/share/icingaweb2/modules" \
&& MODULE_VERSION=0.4.1 \
&& mkdir "$MODULES_PATH" \
&& wget -q $RELEASES/v${MODULE_VERSION}.tar.gz -O - \
   | tar xfz - -C "$MODULES_PATH" --strip-components 1
icingacli module enable reactbundle
```

Sample GIT installation
-----------------------

```sh
REPO="https://github.com/Icinga/icingaweb2-module-reactbundle" \
&& MODULES_PATH="/usr/share/icingaweb2/modules" \
&& MODULE_VERSION=0.4.1 \
&& mkdir -p "$MODULES_PATH" \
&& git clone ${REPO} "${MODULES_PATH}/reactbundle" --branch v${MODULE_VERSION}
icingacli module enable reactbundle
```


Developer Documentation
-----------------------

### Add a new dependency

    composer require author/library:version

### Create a new release

    ./bin/make-release.sh <version>

e.g.

    ./bin/make-release.sh 0.1.0
