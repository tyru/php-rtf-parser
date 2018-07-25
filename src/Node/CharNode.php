<?php
namespace RtfParser\Node;

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
