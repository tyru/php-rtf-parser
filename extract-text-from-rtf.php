<?php

function isAlpha($c) {
  return $c >= 'a' && $c <= 'z' || $c >= 'A' && $c <= 'Z';
}

function isSpace($c) {
  switch ($c) {
  case ' ': case "\t": case "\r": case "\f": case "\n": case "\0":
    return true;
  default:
    return false;
  }
}

function isDigit($c) {
  return $c >= '0' && $c <= '9';
}

function isDelimitChar($c) {
  return isSpace($c) || $c === ';';
}

interface Node {
  // CtrlWordNode: the name of control word (e.g. "\\par")
  // BlockNode: "block"
  // CharNode: "char"
  // TextNode: "text"
  public function name();

  // Stringify the node recursively
  public function text();
}

// An array that stores the control words, which hides inner TextNode
// For example, there may be a description of font or color palette etc.
define('DISABLE_PLAIN_TEXT', ["\\*", "\\fonttbl", "\\colortbl", "\\datastore", "\\themedata", "\\hl", "\\stylesheet", "\\nonshppict", "\\author", "\\operator"]);

// '{' ~ '}'
class BlockNode implements Node {
  private $children;

  public function __construct(array $children) {
    $this->children = $children;
    $this->show_text = true;
    foreach ($children as $child) {
      if (in_array($child->name(), DISABLE_PLAIN_TEXT)) {
        $this->show_text = false;
      }
    }
  }

  public function name() {
    return 'block';
  }

  public function text() {
    if (!$this->show_text) {
      return '';
    }
    $text = '';
    foreach ($this->children as $child) {
      $text .= $child->text();
    }
    return $text;
  }
}

define('CTRL_WORD_TABLE', [
  "\\par" => 'ParNode'
]);

// Control word node
class CtrlWordNode implements Node {
  private $name;
  private $param;

  protected function __construct(string $name, int $param) {
    $this->name = $name;
    $this->param = $param;
  }

  public static function make(string $name, int $param) {
    if (array_key_exists($name, CTRL_WORD_TABLE)) {
      $className = CTRL_WORD_TABLE[$name];
      return new $className($param);
    }
    return new CtrlWordNode($name, $param);
  }

  public function name() {
    return $this->name;
  }

  public function text() {
    return '';
  }
}

// Control word node: \par
class ParNode extends CtrlWordNode {

  public function __construct(int $param) {
    parent::__construct("\\par", $param);
  }

  public function text() {
    return "\n";
  }
}

// Other nodes except BlockNode, CtrlWordNode
class CharNode implements Node {
  private $charCode;

  protected function __construct(int $charCode) {
    $this->charCode = $charCode;
  }

  public static function fromChar(string $char) {
    return new CharNode(ord($char));
  }

  public static function fromCharCode(int $charCode) {
    return new CharNode($charCode);
  }

  public function name() {
    return 'char';
  }

  public function text() {
    return chr($this->charCode);
  }

  public function charCode() {
    return $this->charCode;
  }
}

// Consecutive nodes of CharNode is converted to one TextNode
class TextNode implements Node {
  private $value;

  public function __construct(string $value) {
    $this->value = $value;
  }

  public function name() {
    return 'text';
  }

  public function text() {
    return $this->value;
  }
}

class Scanner {
  private $source;
  private $length;
  private $pos;

  public function __construct(string $source) {
    if (empty($source)) {
      throw new InvalidArgumentException('$source');
    }
    $this->source = $source;
    $this->length = strlen($source);
    $this->pos = 0;
  }

  public function hasNext() {
    return $this->pos < $this->length;
  }

  public function next() {
    if (!$this->hasNext()) {
      $this->pos++;    // this method call -> back() -> next() also returns null
      return null;
    }
    return $this->source[$this->pos++];
  }

  public function back() {
    if ($this->pos === 0) {
      throw new Exception('pos is already 0');
    }
    $this->pos--;
  }

  public function pos() {
    return $this->pos;
  }
}

// Concat one or more CharNode to TextNode
function parse(Scanner $scanner) {
  $nodes = [];
  $hex = '';
  foreach (doParse($scanner) as $node) {
    if ($node->name() === 'char') {
      $hex .= dechex($node->charCode());
      continue;
    }
    if (!empty($hex)) {
      $nodes[] = new TextNode(hex2bin($hex));
      $hex = '';
    }
    $nodes[] = $node;
  }
  if (!empty($hex)) {
    $nodes[] = new TextNode(hex2bin($hex));
  }
  return $nodes;
}

function doParse(Scanner $scanner) {
  $nodes = [];

  while ($scanner->hasNext()) {
    $c = $scanner->next();

    switch ($c) {
    case "\\":    // control word
      $node = parseBackslash($scanner);
      if (!is_null($node)) {
        $nodes[] = $node;
      }
      break;

    case "{":    // begin block
      $children = parse($scanner);
      $nodes[] = new BlockNode($children);
      break;

    case "}":    // end block
      return $nodes;

    case "\t": case "\r": case "\f": case "\n": case "\0":    // ignore
      break;

    case ' ':
      // Ignore consecutive whitespaces
      while ($scanner->next() === ' ');
      $scanner->back();
      $nodes[] = CharNode::fromChar(' ');
      break;

    default:
      $nodes[] = CharNode::fromChar($c);
      break;
    }
  }

  return $nodes;
}

function parseBackslash(Scanner $scanner) {
  $c = $scanner->next();
  if (is_null($c)) {
    throw new Exception('parse error: unexpected end after backslash at ' . $scanner->pos());
  }

  switch ($c) {
  case "\\":
    return CharNode::fromChar("\\");

  case '*':
    return CtrlWordNode::make("\\*", 0);

  case '~':    // Non-breaking space
    return CharNode::fromChar(' ');

  case '_':    // Non-breaking hyphen
    return CharNode::fromChar('-');

  case '-':    // Optional hyphen
    return CharNode::fromChar('-');

  case "'":
    // Read next two characters that are the hexadecimal notation of a character
    $h1 = $scanner->next();
    if (is_null($h1)) {
      throw new Exception("parse error: unexpected end after \\' at " . $scanner->pos());
    }
    $h2 = $scanner->next();
    if (is_null($h2)) {
      throw new Exception("parse error: unexpected end after \\'x at " . $scanner->pos());
    }
    return CharNode::fromCharCode(hexdec($h1 . $h2));

  default:
    if (!isAlpha($c)) {
      return null;    // ignore unknown control word
    }

    $name = "\\" . $c;
    while (!is_null($c = $scanner->next()) && isAlpha($c)) {
      $name .= $c;
    }
    if (!$scanner->hasNext() || isDelimitChar($c)) {
      return CtrlWordNode::make($name, 0);
    }

    // parameter

    // minus
    $minus = false;
    if ($c === '-') {
      $minus = true;
      $c = $scanner->next();
      if (is_null($c)) {
        throw new Exception("parse error: unexpected end after minus in parameter at " . $scanner->pos());
      }
    } else if (!isDigit($c)) {
      $scanner->back();
      return CtrlWordNode::make($name, 0);
    }
    // number
    $paramStr = $c;
    while (!is_null($c = $scanner->next()) && isDigit($c)) {
      $paramStr .= $c;
    }
    if (!isDelimitChar($c)) {
      $scanner->back();
    }
    $param = ($minus ? -1 : 1) * intval($paramStr);
    return CtrlWordNode::make($name, $param);
  }
}

function extractText($filename, $config) {
  // Read the data from the input file.
  $text = file_get_contents($filename);
  if (empty($text))
    return '';

  $scanner = new Scanner($text);
  $text = '';
  foreach (parse($scanner) as $node) {
    $text .= $node->text();
  }

  if ($config['input_encoding'] !== $config['output_encoding']) {
    $text = mb_convert_encoding($text, $config['output_encoding'], $config['input_encoding']);
  }
  return $text;
}

function getConfig() {
  $windows = preg_match('/^Windows/', php_uname('s'));
  if ($windows) {
    // TODO: Get current code page. This is default code page of Japanese version.
    return [
      'input_encoding' => 'cp932',
      'output_encoding' => 'cp932',
    ];
  }
  // FIXME: what input/output encoding is better? :(
  return [
    'input_encoding' => 'cp932',
    'output_encoding' => 'utf-8',
  ];
}

function parseArgs($argv) {
  $opts = getopt('i:o:f:', []);
  if (!isset($opts['f']) || !is_string($opts['f'])) {
    return [$argv[0], null, []];
  }
  $config = getConfig();
  if (isset($opts['i']) && is_string($opts['i'])) {
    $config['input_encoding'] = $opts['i'];
  }
  if (isset($opts['o']) && is_string($opts['o'])) {
    $config['output_encoding'] = $opts['i'];
  }
  return [$argv[0], $opts['f'], $config];
}

function main($argv) {
  list($script, $filename, $config) = parseArgs($argv);
  if (is_null($filename)) {
    echo "Usage: $script [-i <input encoding>] [-o <output encoding>] -f <file.rtf>\n";
    return;
  }
  echo extractText($filename, $config);
}

main($argv);
