<?php

error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) { throw new ErrorException($message, 0, $severity, $file, $line); });
require dirname(__DIR__).'/system/libraries/IXR_Library.inc.php';
$value = new IXR_Value(42);
if ($value->type !== 'int' || $value->getXml() !== '<int>42</int>') { throw new RuntimeException('Initialize XML-RPC scalar values.'); }
$value->IXR_Value('fixture');
if ($value->getXml() !== '<string>fixture</string>') { throw new RuntimeException('Retain explicit legacy value initialization.'); }
$date = new IXR_Date('20240102T03:04:05');
$base64 = new IXR_Base64('abc');
$request = new IXR_Request('fixture.echo', array(array('answer' => 42), $date, $base64));
$xml = $request->getXml();
if (strpos($xml, '<methodName>fixture.echo</methodName>') === FALSE || strpos($xml, '<int>42</int>') === FALSE || strpos($xml, '20240102T03:04:05') === FALSE || strpos($xml, '<base64>YWJj</base64>') === FALSE || $request->getLength() !== strlen($xml)) {
    throw new RuntimeException('Retain request serialization of nested values, ISO dates, and base64 data.');
}
$message = new IXR_Message($xml);
if ($message->message !== $xml) { throw new RuntimeException('Initialize message input without parsing or network I/O.'); }
foreach (array('IXR_Client','IXR_ClientMulticall') as $class) {
    $client = new $class('http://localhost.test:8093/rpc');
    if ($client->server !== 'localhost.test' || $client->port !== 8093 || $client->path !== '/rpc') { throw new RuntimeException('Initialize XML-RPC client URL fields without sending a request.'); }
}
$client->addCall('fixture.echo', 'hello');
if (count($client->calls) !== 1 || $client->calls[0]['methodName'] !== 'fixture.echo') { throw new RuntimeException('Retain multicall accumulation.'); }
$error = new IXR_Error(17, 'fixture-error');
$newError = new IXR_ErrorNew(TRUE, 'fixture-new-error');
if ($error->code !== 17 || $error->message !== 'fixture-error' || $newError->code !== TRUE || $newError->message !== 'fixture-new-error') { throw new RuntimeException('Initialize error payloads.'); }
class RegressionXmlRpcServer extends IXR_Server
{
    public function serve($data = FALSE) { $this->data = $data; }
}
class RegressionXmlRpcIntrospection extends IXR_IntrospectionServer
{
    public function serve($data = FALSE) {}
}
$server = new RegressionXmlRpcServer(array('fixture.echo' => 'fixture_callback'), 'offline-input');
$introspection = new RegressionXmlRpcIntrospection();
if ($server->data !== 'offline-input' || !isset($server->callbacks['fixture.echo'], $introspection->callbacks['system.methodSignature'])) { throw new RuntimeException('Initialize server callbacks without serving HTTP.'); }
restore_error_handler();
echo "PASS: XML-RPC construction and request serialization retain offline values, clients, errors, and callback state.\n";
