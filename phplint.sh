#/bin/bash

find src -type f -name '*.php' -exec php -l {} \;
