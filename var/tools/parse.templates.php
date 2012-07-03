#!/usr/bin/env php
<?php

if (!isset($_SERVER['argc'])) {
	die("Run script via CLI mode!\n");
} else if ($_SERVER['argc'] != 3) {
	echo "Usage: {$_SERVER['argv'][0]} <root-path> <output.po>\n";
	echo "   Or: php -f {$_SERVER['argv'][0]} <root-path> <output.po>\n";
	die("\n");
}

define('ROOT_DIR', $_SERVER['argv'][1]);
define('DOMAIN', $_SERVER['argv'][2]);
define('SEARCH', '#\{tr\s*(context=[\'"]+([^\'"]*)[\'"]+)*\s*\}([^\{]+)\{/tr\}#');

// template subdirectories
$dirs = array(
	ROOT_DIR . "template/client/",
	ROOT_DIR . "config/"
);

$date = date("Y-m-d H:iP");

$header = <<<HEREDOC
#, fuzzy
msgid   ""
msgstr  "Content-Type: text/plain; charset=UTF-8\\n"
        "Content-Transfer-Encoding: 8bit\\n"


HEREDOC;

echo "\t* Searching Smarty HTML templates for translation...\n";

$files = array();
foreach ($dirs as $dir) {
	$files = array_merge($files, glob($dir . "*.html"));
}

if (!count($files)) die("\t* File(s) not found.\n");

// Found strings array list
// format:
//   $found = array(
//       'context_string' => array(
//           'phrase_string' => array(
//               'files' => array(
//                   0 => 'filename1'
//                   1 => 'filename2'
//                   2 => 'filename3'
//               ),
//               0 => 'filename1:line',
//               1 => 'filename2:line1 \n#: filename2:line2',
//               2 => 'filename3:line'
//           )
//       )
//   );
$found = array();

// Parse source files, file by file
foreach ($files as $file) {
	$source = file_get_contents($file);

	if (preg_match_all(SEARCH, $source, $matches)) {

		// parse file, phrase by phrase
		foreach ($matches[3] as $id => $phrase) {
			$context = $matches[2][$id];

			// pass to next search if the file is added already
			if ( isset($found[$context][$phrase]['files'])
			     && in_array($file, $found[$context][$phrase]['files'])
			) {
				continue;
			}

			// search for the line number
			$lines = array();
			$prevLine = 0;
			$pos = 0;
			while (($pos = mb_strpos($source, $matches[0][$id], $pos)) !== false) {
				$line = substr_count($source, "\n", 0, $pos) + 1;
				$pos++; // prevent forever loop

				// pass another reference at same line
				if ($prevLine == $line) continue;

				$lines[] = $file . ':' . $line;
				$prevLine = $line;
			}

			$found[$context][$phrase][] = implode("\n#: ", $lines);
			$found[$context][$phrase]['files'][] = $file;
		}
	}
}
unset($source);

if (!count($found)) die("\t* There's no strings to translate!");

// Convert $found array to plain text, format .po
$po = '';
foreach ($found as $context => $content) {
	foreach($content as $phrase => $files) {
		unset($files['files']);
		$po .= "#: " . implode(' ', $files) . "\n";

		if ($context) {
			$po .= 'msgctxt "' . $context . "\"\n";
		}

		$po .= 'msgid   "' . $phrase . "\"\n";
		$po .= 'msgstr  ""' . "\n\n";
	}
}

if (file_put_contents(DOMAIN, $header . $po, LOCK_EX) === false) {
	die("\t* Error writing po file!");
}
