#!/bin/bash
BASEDIR=$(dirname $0)
cd $BASEDIR

MINIFIER='/home/fedya/programs/yuicompressor-2.4.8.jar'
INPUT_PATH='src'
OUTPUT_PATH='min'
TE_PATH='../util/tag-editor'
COLORBOX_PATH='../util/tag-editor/colorbox'
JS_PATH='../js'

function minify {
    inputs=${@:0:$#}
    output=${@:$#}
    cat $inputs > $output
    /home/fedya/programs/jdk1.6.0_45/bin/java -jar $MINIFIER $output -o $output
}
