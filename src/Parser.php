<?php
namespace RtfParser;

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

class Parser {
  private $scanner;

  public function __construct(Scanner $scanner) {
    $this->scanner = $scanner;
  }

  public function parse() {
    $nodes = $this->parseOptimize();
    $this->scanner->reset();
    return new Document($nodes);
  }

  // Concat one or more CharNode to TextNode
  private function parseOptimize() {
    $nodes = [];
    $hex = '';
    foreach ($this->doParse() as $node) {
      if ($node->name() === 'char') {
        $hex .= dechex($node->charCode());
        continue;
      }
      if (!empty($hex)) {
        $nodes[] = new Node\TextNode(hex2bin($hex));
        $hex = '';
      }
      $nodes[] = $node;
    }
    if (!empty($hex)) {
      $nodes[] = new Node\TextNode(hex2bin($hex));
    }
    return $nodes;
  }

  private function doParse() {
    $nodes = [];

    while ($this->scanner->hasNext()) {
      $c = $this->scanner->next();

      switch ($c) {
      case "\\":    // control word
        $node = $this->parseBackslash();
        if (!is_null($node)) {
          $nodes[] = $node;
        }
        break;

      case "{":    // begin block
        $childNodes = $this->parseOptimize();
        $nodes[] = new Node\BlockNode($childNodes);
        break;

      case "}":    // end block
        return $nodes;

      case "\t": case "\r": case "\f": case "\n": case "\0":    // ignore
        break;

      case ' ':
        // Ignore consecutive whitespaces
        while ($this->scanner->next() === ' ');
        $this->scanner->back();
        $nodes[] = Node\CharNode::fromChar(' ');
        break;

      default:
        $nodes[] = Node\CharNode::fromChar($c);
        break;
      }
    }

    return $nodes;
  }

  private function parseBackslash() {
    $c = $this->scanner->next();
    if (is_null($c)) {
      throw new Exception('parse error: unexpected end after backslash at ' . $this->scanner->pos());
    }

    switch ($c) {
    case "\\":
      return Node\CharNode::fromChar("\\");

    case '*':
      return Node\CtrlWordNode::make("\\*", 0);

    case '~':    // Non-breaking space
      return Node\CharNode::fromChar(' ');

    case '_':    // Non-breaking hyphen
      return Node\CharNode::fromChar('-');

    case '-':    // Optional hyphen
      return Node\CharNode::fromChar('-');

    case "'":
      // Read next two characters that are the hexadecimal notation of a character
      $h1 = $this->scanner->next();
      if (is_null($h1)) {
        throw new Exception("parse error: unexpected end after \\' at " . $this->scanner->pos());
      }
      $h2 = $this->scanner->next();
      if (is_null($h2)) {
        throw new Exception("parse error: unexpected end after \\'x at " . $this->scanner->pos());
      }
      return Node\CharNode::fromCharCode(hexdec($h1 . $h2));

    default:
      if (!isAlpha($c)) {
        return null;    // ignore unknown control word
      }

      $name = "\\" . $c;
      while (!is_null($c = $this->scanner->next()) && isAlpha($c)) {
        $name .= $c;
      }
      if (!$this->scanner->hasNext() || isDelimitChar($c)) {
        return Node\CtrlWordNode::make($name, 0);
      }

      // parameter

      // minus
      $minus = false;
      if ($c === '-') {
        $minus = true;
        $c = $this->scanner->next();
        if (is_null($c)) {
          throw new Exception("parse error: unexpected end after minus in parameter at " . $this->scanner->pos());
        }
      } else if (!isDigit($c)) {
        $this->scanner->back();
        return Node\CtrlWordNode::make($name, 0);
      }
      // number
      $paramStr = $c;
      while (!is_null($c = $this->scanner->next()) && isDigit($c)) {
        $paramStr .= $c;
      }
      if (!isDelimitChar($c)) {
        $this->scanner->back();
      }
      $param = ($minus ? -1 : 1) * intval($paramStr);
      return Node\CtrlWordNode::make($name, $param);
    }
  }
}
