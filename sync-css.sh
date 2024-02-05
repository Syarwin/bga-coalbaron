#!/bin/bash
SRC=~/bga/bga-coalbaron/ # with trailing slash
NAME=coalbaron

# Sass
sass "$NAME.scss" "$NAME.css"

# Copy
rsync $SRC/$NAME.css ~/bga/studio/$NAME/
rsync $SRC/$NAME.css.map ~/bga/studio/$NAME/
