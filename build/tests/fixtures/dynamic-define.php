<?php

$name = 'DYNAMIC_CONST';

// Dynamic constant name - cannot be resolved statically, must be skipped
// without aborting generation of the rest of the file.
define($name, 'value');

define('STATIC_CONST', 'value');
