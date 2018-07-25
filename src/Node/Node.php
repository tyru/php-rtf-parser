<?php
namespace RtfParser\Node;

interface Node {
  // CtrlWordNode: the name of control word (e.g. "\\par")
  // BlockNode: "block"
  // CharNode: "char"
  // TextNode: "text"
  public function name();

  // Stringify the node recursively
  public function text();
}
