#/bin/bash
# Find all files with a php file extension and run php lint on them.
find src -type f -name '*.php' -exec php -l {} \;
