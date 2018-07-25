<?php
namespace RtfParser;

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

  public function reset() {
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
