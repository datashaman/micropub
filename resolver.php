<?php

class ConnectionResolver extends Illuminate\Database\ConnectionResolver
{
    public function connection($name = null)
    {
        return parent::connection('testing');
    }

    public function getDefaultConnection()
    {
        return 'testing';
    }
}
