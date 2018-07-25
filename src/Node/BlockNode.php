<?php
namespace RtfParser\Node;

// An array that stores the control words, which hides inner TextNode
// For example, there may be a description of font or color palette etc.
define('DISABLE_PLAIN_TEXT', ["\\*", "\\fonttbl", "\\colortbl", "\\datastore", "\\themedata", "\\hl", "\\stylesheet", "\\nonshppict", "\\author", "\\operator"]);

// '{' ~ '}'
class BlockNode implements Node {
  private $childNodes;

  public function __construct(array $childNodes) {
    $this->childNodes = $childNodes;
    $this->show_text = true;
    foreach ($childNodes as $child) {
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
    foreach ($this->childNodes as $child) {
      $text .= $child->text();
    }
    return $text;
  }

  public function childNodes() {
    return $this->childNodes;
  }
}
