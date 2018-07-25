# PHP Rich Text Format Parser

Run frontend script `extract-text-from-rtf.php` to extract only the text in rtf file.

```
php extract-text-from-rtf.php -f sample.rtf [-i <input encoding>] [-o <output encoding>]
```

The input encoding is the encoding of rtf file.
It is normally current code page of Windows which a user created the file.
For example, Windows in Japanese version, `cp932`.

The output encoding is the encoding of standard output.
For example, Windows in Japanese version, `cp932` (cmd.exe encoding).
Of course you can encode to UTF-8 like `-o UTF-8`.

These arguments are passed to `mb_convert_encoding()` function if both encodings are not same.

Here are the default input/output encodings:

| OS | input | output |
----|----|---- 
| Windows | CP932 | CP932 |
| Others  | CP932 | UTF-8 |

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
