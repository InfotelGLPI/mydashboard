#!/bin/bash


# --- Étape 1 : Extraction des chaînes PHP ---
find . -name '*.php' > php_files.list

xgettext --files-from=php_files.list \
  --copyright-holder='MyDashboard Development Team' \
  --package-name='MyDashboard plugin' \
  -o locales/glpi.pot \
  -L PHP \
  --add-comments=TRANS \
  --from-code=UTF-8 \
  --force-po \
  --sort-output \
  --keyword=_n:1,2,4t \
  --keyword=__s:1,2t \
  --keyword=__:1,2t \
  --keyword=_e:1,2t \
  --keyword=_x:1c,2,3t \
  --keyword=_ex:1c,2,3t \
  --keyword=_nx:1c,2,3,5t \
  --keyword=_sx:1c,2,3t

rm php_files.list

# --- Étape 2 : Extraction des chaînes Twig ---

# Append locales from Twig templates
SCRIPT_DIR=$(dirname $0)
WORKING_DIR=$(readlink -f "$SCRIPT_DIR/..") # Script will be executed from "vendor/bin" directory
# Define translate function args
F_ARGS_N="1,2"
F_ARGS__S="1"
F_ARGS__="1"
F_ARGS_X="1c,2"
F_ARGS_SX="1c,2"
F_ARGS_NX="1c,2,3"
F_ARGS_SN="1,2"

for file in $(cd $WORKING_DIR && find -regextype posix-egrep -not -regex $EXCLUDE_REGEX "$SCRIPT_DIR/.." -name "*.twig")
do
    # 1. Convert file content to replace "{{ function(.*) }}" by "<?php function(.*); ?>" and extract strings via std input
    # 2. Replace "standard input:line_no" by file location in po file comments
    contents=`cat $file | sed -r "s|\{\{\s*([a-z0-9_]+\(.*\))\s*\}\}|<?php \1; ?>|gi"`
    cat $file | perl -0pe "s/\{\{(.*?)\}\}/<?php \1; ?>/gism" | xgettext - \
        -o locales/glpi.pot \
        -L PHP \
        --add-comments=TRANS \
        --from-code=UTF-8 \
        --force-po \
        --join-existing \
        --sort-output \
        --keyword=_n:$F_ARGS_N \
        --keyword=__:$F_ARGS__ \
        --keyword=_x:$F_ARGS_X \
        --keyword=_nx:$F_ARGS_NX
    sed -i -r "s|standard input:([0-9]+)|`echo $file | sed "s|./||"`:\1|g" locales/glpi.pot
done
