Icinga Web 2 - ReactPHP-based 3rd party libraries
=================================================

This repository is an attempt to ship 3rd party libraries that might be useful
for asynchronous PHP-based Icinga Web 2 modules. Please download the latest
release and install it like any other module.

Please do not use the GIT master, as it will never contain any of the referenced
libraries. Instead, download a tagged release like in this script (as root):

```sh
RELEASES="https://github.com/Icinga/icingaweb2-module-reactbundle/archive" \
&& MODULES_PATH="/usr/share/icingaweb2/modules" \
&& MODULE_VERSION=0.2.0 \
&& mkdir "$MODULES_PATH" \
&& wget -q $RELEASES/v${MODULE_VERSION}.tar.gz -O - \
   | tar xfz - -C "$MODULES_PATH" --strip-components 1
icingacli module enable reactbundle
```

Alternatively, in case you prefer working with GIT, please clone the
repository and check out the desired tag or branch:

```sh
git clone https://github.com/Icinga/icingaweb2-module-reactbundle.git \
  /usr/share/icingaweb2/modules/reactbundle
cd /usr/share/icingaweb2/modules/reactbundle
git checkout -b release/v0.2.0 v0.2.0
icingacli module enable reactbundle
```

Add a new dependency
--------------------

    composer require author/library:version

Create a new release
--------------------

    ./bin/make-release.sh <version>

e.g.

    ./bin/make-release.sh 0.2.0
