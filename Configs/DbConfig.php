<?php
namespace Configs;

class DbConfig extends Structure
{

    public $host = 'mysql';

    public $port = '3306';

    public $libr = 'dx_325';

    public $user = 'root';

    public $pass = 'root';

    public $prefix = 'ims_';

    public $persistent = false;

    public $charset = 'utf8';
}