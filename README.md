# PHP Rich Text Format Parser

Run frontend script `extract-text-from-rtf.php` to extract only the text in rtf file.

```
php extract-text-from-rtf.php -f sample.rtf [-i <input encoding>] [-o <output encoding>]
```

# RtfParser

```php
$scanner = new RtfParser\Scanner($text);
$parser = new RtfParser\Parser($scanner);
$text = '';
foreach ($parser->parse() as $node) {
  $text .= $node->text();
}
echo $text;
```

`$parser->parse()` returns array of `RtfParser\Node\Node`.
Currently [`RtfParser\Node\Node` interface](https://github.com/tyru/php-rtf-parser/blob/master/src/Node/Node.php) only supports `text()` and `name()` method.
