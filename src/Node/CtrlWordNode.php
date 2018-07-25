<?php
namespace RtfParser\Node;

define('CTRL_WORD_TABLE', [
  "\\par" => 'RtfParser\Node\ParNode'
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

  public function param() {
    return $this->param;
  }
}
