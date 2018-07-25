<?php
namespace RtfParser\Node;

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
