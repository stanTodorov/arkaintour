#!/bin/bash

DOMAIN=messages

echo -e "Compile .po file to .mo...\n"
msgfmt ${DOMAIN}.po -o ${DOMAIN}.mo
