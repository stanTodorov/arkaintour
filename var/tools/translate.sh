#!/bin/bash

# options
ROOT_DIR=../../
TMP_DIR=./tmp.$$/
DOMAIN=messages
DOMAIN_SCRIPTS=${TMP_DIR}scripts
DOMAIN_TEMPLATES=${TMP_DIR}templates
FILES_LIST=${TMP_DIR}files.list
SCRIPTS=(
	""
	"libraries/"
	"config/"
)

# pre-create tmp dir and files
mkdir -p ${TMP_DIR}
touch ${DOMAIN}.po
touch ${DOMAIN_SCRIPTS}.po
touch ${DOMAIN_TEMPLATES}.po

echo "1) Start Parsers."

# First search for .php source files and execute xgettext tool
echo -e "\t* Search script files to translate..."
echo -n "" > $FILES_LIST

for i in ${!SCRIPTS[*]}
do
	find "${ROOT_DIR}${SCRIPTS[$i]}" -maxdepth 1 \
		-type f -iname '*.php' -print >> $FILES_LIST
done

xgettext --language=PHP --indent \
	--keyword=_ \
	--keyword=__:1 \
	--keyword=__:1,2c \
	--keyword=_n:1,2 \
	--keyword=_n:1,2,4c \
	--keyword=_ngettext:1,2 \
	--sort-output --add-location \
	--from-code=UTF-8 -f $FILES_LIST \
	-o ${DOMAIN_SCRIPTS}.po

# Next search in HTML templates through php parser script
php -f ./parse.templates.php ${ROOT_DIR} ${DOMAIN_TEMPLATES}.po

# Concatenate files to main domain file
echo ""
echo "2) Merge po files into single po file."
msgcat --indent --use-first \
	${DOMAIN_SCRIPTS}.po ${DOMAIN_TEMPLATES}.po \
	--to-code=UTF-8 -o ${DOMAIN}.po

COUNT=`cat ${DOMAIN}.po | grep '^msgid' | grep -v '""' | wc -l`
echo -e "\t* Total strings to translate: ${COUNT}\n"

# remove temporary files and folder
echo "3) Clean up temporary files."
rm -rf ${TMP_DIR}
