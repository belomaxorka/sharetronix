<?php

require_once dirname(__DIR__).'/system/helpers/func_main.php';
require_once dirname(__DIR__).'/system/libraries/securimage/WavFile.php';
require_once dirname(__DIR__).'/system/libraries/securimage/securimage.php';

$password = generate_password(32, 'AB09');
if (strlen($password) !== 32 || strspn($password, 'AB09') !== 32 || generate_password(0) !== '') {
    throw new RuntimeException('Password generation must retain its length and alphabet.');
}
$wav = new WavFile(1, 8000, 16);
$wav->setSampleValue(0.25, 0, 1);
$wav->setSampleValue(0.5, 1, 1);
$wav->setSampleValue(-0.375, 0, 1);
if (abs($wav->getSampleValue(0, 1)+0.375) > 0.0001 || abs($wav->getSampleValue(1, 1)-0.5) > 0.0001) {
    throw new RuntimeException('Replacing an audio sample must preserve neighboring samples.');
}
class RegressionAudibleCode extends Securimage
{
    public function getCode($array = FALSE, $returnExisting = FALSE)
    {
        return array('code' => 'abc', 'display' => 'ABC');
    }

    protected function generateWAV($letters)
    {
        return $letters;
    }

    public function letters()
    {
        return $this->getAudibleCode();
    }
}
$audible = (new ReflectionClass('RegressionAudibleCode'))->newInstanceWithoutConstructor();
if ($audible->letters() !== array('A', 'B', 'C')) {
    throw new RuntimeException('The audio-code path must retain its synthetic display characters.');
}
echo "PASS: password generation, audio sample replacement, and synthetic audible-code characters.\n";
