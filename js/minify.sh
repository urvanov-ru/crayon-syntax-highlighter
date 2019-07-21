#!/bin/bash
BASEDIR=$(dirname $0)
cd $BASEDIR

source ../util/minify.sh

common=$"$INPUT_PATH/util.js $INPUT_PATH/jquery.popup.js $INPUT_PATH/urvanov_syntax_highlighter.js"
minify $common $OUTPUT_PATH/urvanov_syntax_highlighter.min.js
minify $common $TE_PATH/urvanov_syntax_highlighter_qt.js $COLORBOX_PATH/jquery.colorbox-min.js $TE_PATH/urvanov_syntax_highlighter_tag_editor.js $OUTPUT_PATH/urvanov_syntax_highlighter.te.min.js
