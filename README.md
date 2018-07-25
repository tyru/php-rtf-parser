# PHP Rich Text Format Parser

Run frontend script `extract-text-from-rtf.php` to extract only the text in rtf file.

```
php extract-text-from-rtf.php -f sample.rtf [-i <input encoding>] [-o <output encoding>]
```

Here are the default input/output encodings:

| OS      | input | output               |
| ------- | ----- | -------------------- |
| Windows | guess | CP932                |
| Others  | guess | (detect from $LANG)  |

The input encoding is the encoding of rtf file.  It is normally current code
page of Windows on which a user created the file.  For example, Windows in
Japanese version, `CP932`.  If input encoding is `guess`, it tries to find
`\ansicpg` control word.  `\ansicpg` declares the default character set used in
the document unless it is \ansi (the default).  if `\ansicpgN` (N is parameter)
is found, it returns encoding string `"cp<N>"`.  For example, `\ansicpg932` is
found, it returns string `"cp932"`.  The library user can get the encoding by
`RtfParser\Document#getEncoding()` method.

The output encoding is the encoding of standard output.  For example, Windows in
Japanese version, `CP932` (cmd.exe encoding).  Of course you can encode to UTF-8
like `-o UTF-8`.  By default, on non-Windows platform, output encoding is
detected by `LANG` environment variable. if it fails, 'UTF-8' is the default
value.

These arguments are passed to `mb_convert_encoding()` function if both encodings are not same.

# RtfParser

```php
$scanner = new RtfParser\Scanner($text);
$parser = new RtfParser\Parser($scanner);
$text = '';
$doc = $parser->parse();
foreach ($doc->childNodes() as $node) {
  $text .= $node->text();
}
echo $text;
```

`$parser->parse()` returns `RtfParser\Document` instance.  `$doc->childNodes()`
returns array of `RtfParser\Node\Node`.  Currently [`RtfParser\Node\Node`
interface](https://github.com/tyru/php-rtf-parser/blob/master/src/Node/Node.php)
only supports `text()` and `name()` method.
