<?php
require_once 'src/Scanner.php';
require_once 'src/Parser.php';
require_once 'src/Document.php';
require_once 'src/Node/Node.php';
require_once 'src/Node/BlockNode.php';
require_once 'src/Node/CharNode.php';
require_once 'src/Node/CtrlWordNode.php';
require_once 'src/Node/ParNode.php';
require_once 'src/Node/TextNode.php';

function extractText(string $filename, array $config) {
  // Read the data from the input file.
  $text = file_get_contents($filename);
  if (empty($text))
    return '';

  $scanner = new RtfParser\Scanner($text);
  $parser = new RtfParser\Parser($scanner);
  $text = '';
  $doc = $parser->parse();
  foreach ($doc->childNodes() as $node) {
    $text .= $node->text();
  }

  if ($config['input_encoding'] === 'guess') {
    $config['input_encoding'] = $doc->getEncoding();
    if (is_null($config['input_encoding'])) {
      $config['input_encoding'] = 'utf-8';
    }
  }
  if ($config['input_encoding'] !== $config['output_encoding']) {
    $text = mb_convert_encoding($text, $config['output_encoding'], $config['input_encoding']);
  }
  return $text;
}

function getConfig() {
  if (preg_match('/^Windows/', php_uname('s'))) {
    // TODO: Get current code page of output_encoding.
    // 'cp932' is default code page of Japanese version.
    return [
      'input_encoding' => 'guess',
      'output_encoding' => 'cp932',
    ];
  }
  // Detect encoding from LANG environment variable
  $lang = getenv('LANG');
  $matches = null;
  if (is_string($lang) && preg_match('/\.(.+)$/', $lang, $matches)) {
    $out = $matches[1];
  } else {
    $out = 'utf-8';
  }
  return [
    'input_encoding' => 'guess',
    'output_encoding' => $out,
  ];
}

function parseArgs(array $argv) {
  $opts = getopt('i:o:f:', []);
  if (!isset($opts['f']) || !is_string($opts['f'])) {
    return [$argv[0], null, []];
  }
  $config = getConfig();
  if (isset($opts['i']) && is_string($opts['i'])) {
    $config['input_encoding'] = $opts['i'];
  }
  if (isset($opts['o']) && is_string($opts['o'])) {
    $config['output_encoding'] = $opts['o'];
  }
  return [$argv[0], $opts['f'], $config];
}

function main(array $argv) {
  list($script, $filename, $config) = parseArgs($argv);
  if (is_null($filename)) {
    echo "Usage: $script [-i <input encoding>] [-o <output encoding>] -f <file.rtf>\n";
    return;
  }
  echo extractText($filename, $config);
}

main($argv);
