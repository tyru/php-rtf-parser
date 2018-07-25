<?php
namespace RtfParser;

class Document {
  private $childNodes;

  public function __construct(array $childNodes) {
    $this->childNodes = $childNodes;
  }

  public function childNodes() {
    return $this->childNodes;
  }

  // Get document encoding by \ansicpg control word.
  // If \ansicpg is not found, return null.
  public function getEncoding() {
    foreach ($this->childNodes as $node) {
      if ($node->name() === 'block') {
        foreach ($node->childNodes() as $child) {
          if ($child->name() === "\\ansicpg") {
            return 'cp' . $child->param();
          }
        }
      }
    }
    return null;
  }
}
