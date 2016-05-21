<?php

require_once __DIR__ . '/vendor/autoload.php';

$path = '/example/images/';
$dog = new \ImageCache\ImageCache(__DIR__ . $path . 'dog.gif');
$cat = new \ImageCache\ImageCache(__DIR__ . $path . 'cat.gif');

file_put_contents(__DIR__ . $path . 'new_dog.gif', $dog->resize(200));
file_put_contents(__DIR__ . $path . 'new_cat.gif', $cat->resize(null, 200));
