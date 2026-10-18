<?php

error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
require dirname(__DIR__).'/system/classes/class_post.php';
$C = (object) array('POST_ICONS' => array());
$post = (new ReflectionClass('post'))->newInstanceWithoutConstructor();
$post->post_id = 17;
$post->post_mentioned = $post->post_posttags = array();
$cases = array(
    'Prefix http://example.test/path. suffix' => 'Prefix <a href="http://example.test/path" title="http://example.test/path" target="_blank" rel="nofollow">http://example.test/path</a>. suffix',
    'ftp://example.test/file' => '<a href="ftp://example.test/file" title="ftp://example.test/file" target="_blank" rel="nofollow">ftp://example.test/file</a>',
    'http://example.test/?a=1&b=2' => '<a href="http://example.test/?a=1&amp;b=2" title="http://example.test/?a=1&amp;b=2" target="_blank" rel="nofollow">http://example.test/?a=1&amp;b=2</a>',
    '<script>fixture</script>' => '&lt;script&gt;fixture&lt;/script&gt;'
);
foreach ($cases as $message => $expected) {
    $post->post_message = $message;
    if ($post->parse_text() !== $expected) { throw new RuntimeException('Retain links, surrounding punctuation, query escaping, and plain-text escaping.'); }
}
restore_error_handler();
echo "PASS: post link callbacks preserve rendered HTML and escaping without removed functions.\n";
