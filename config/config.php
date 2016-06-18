<?php

$this['base_path'] = dirname(__DIR__).'/';

/**
* Import project configuration
*/
$this->addFile(__DIR__.'/config.json');
$application_env = preg_replace('/!^[A-Za-z0-9_]+$/', '', getenv('APPLICATION_ENV'));
if(file_exists(__DIR__.'/config.'.$application_env.'.json')) {
    $this->addFile(__DIR__.'/config.'.$application_env.'.json');
}

$this->addFile(__DIR__.'/routes.json');

$this->addFile(__DIR__.'/admin.json');
