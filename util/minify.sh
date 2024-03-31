#!/bin/bash
BASEDIR=$(dirname $0)
cd $BASEDIR

MINIFIER='/home/fedor/programs/yuicompressor-2.4.8.jar'
INPUT_PATH='src'
OUTPUT_PATH='min'
TE_PATH='../util/tag-editor'
COLORBOX_PATH='../util/tag-editor/colorbox'
JS_PATH='../js'

function minify {
    echo "parameters"
    echo ${@:1:$#-1}
    echo "last parameter"
    echo ${@:$#}
    inputs=${@:1:$#-1}
    output=${@:$#}
    cat $inputs > $output
    /home/fedor/programs/jdk1.6.0_45/bin/java -jar $MINIFIER $output -o $output
}
