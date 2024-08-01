<?php

namespace Jakiboy;

include './exc/IoException.php';
include './exc/TranslateException.php';
include './inc/Translator.php';
include './inc/Sorter.php';
include './Poto.php';

$poto = new Poto([
    'translate' => true,
]);

$poto->read('file.po')->process();

if ( $poto->error()) {
    echo $poto->getError();
}