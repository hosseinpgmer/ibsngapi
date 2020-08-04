<?php

class DB
{

    static function connect()
    {
        $db = new DB\SQL(
            'pgsql:host={yourhost};port={yourport};dbname={database name}',
            '{username}',
            '{password}'
        );
        return $db;
    }


}