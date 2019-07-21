#!/bin/bash
BASEDIR=$(dirname $0)
cd $BASEDIR

source ../util/minify.sh

minify $COLORBOX_PATH/colorbox.css $INPUT_PATH/admin_style.css $INPUT_PATH/urvanov_syntax_highlighter_style.css $INPUT_PATH/global_style.css $OUTPUT_PATH/urvanov_syntax_highlighter.min.css
