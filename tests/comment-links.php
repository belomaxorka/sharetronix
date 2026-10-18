<?php

error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
require dirname(__DIR__).'/system/classes/class_post.php';
require dirname(__DIR__).'/system/classes/class_postcomment.php';
require dirname(__DIR__).'/system/helpers/func_parse_forum_tags.php';
$C = (object) array('POST_ICONS' => array(), 'SITE_URL' => 'http://localhost/', 'NAME_INDENTIFICATOR' => 1);
// Expected HTML was captured from the original create_function() callbacks on PHP 7.2.
$comment = (new ReflectionClass('postcomment'))->newInstanceWithoutConstructor();
$comment->comment_mentioned = $comment->comment_posttags = array();
$comments = array(
    'Prefix http://example.test/path. suffix' => 'Prefix <a href="http://example.test/path" title="http://example.test/path" target="_blank" rel="nofollow">http://example.test/path</a>. suffix',
    'ftp://example.test/file' => '<a href="ftp://example.test/file" title="ftp://example.test/file" target="_blank" rel="nofollow">ftp://example.test/file</a>',
    'http://example.test/?a=1&b=2' => '<a href="http://example.test/?a=1&amp;b=2" title="http://example.test/?a=1&amp;b=2" target="_blank" rel="nofollow">http://example.test/?a=1&amp;b=2</a>',
    '<script>fixture</script>' => '&lt;script&gt;fixture&lt;/script&gt;'
);
foreach ($comments as $message => $expected) {
    $comment->comment_message = $message;
    if ($comment->parse_text() !== $expected) { throw new RuntimeException('Retain comment links, punctuation, query escaping, and plain-text escaping.'); }
}
$tags = array(
    'Read http://example.test/faq. now' => 'Read<a href="http://example.test/faq" target="_blank">http://example.test/faq</a>. now',
    '[url]http://example.test/u[/url]' => '<a href="http://example.test/u" target="_blank">http://example.test/u</a>',
    '[url=&quot;http://example.test/q&quot;]Quoted[/url]' => '<a href="http://example.test/q" target="_blank">Quoted</a>',
    '[url=http://example.test/p]Plain[/url]' => '<a href="http://example.test/p" target="_blank">Plain</a>',
    '[b]Bold[/b] news://example.test/n' => '<b>Bold</b><a href="news://example.test/n" target="_blank">news://example.test/n</a>'
);
foreach ($tags as $text => $expected) {
    if (bb_apply_tags($text) !== $expected) { throw new RuntimeException('Retain FAQ link tags and their existing markup.'); }
}
restore_error_handler();
echo "PASS: comment and FAQ link callbacks preserve rendered HTML without removed functions.\n";
