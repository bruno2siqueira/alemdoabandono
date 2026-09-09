<?php
class Database
{
    private string $host = 'SEU_HOST_MYSQL';
    private string $database = 'SEU_BANCO';
    private string $username = 'SEU_USUARIO';
    private string $password = 'SUA_SENHA';

    public function connect(): PDO
    {
        return new PDO(
            "mysql:host={$this->host};dbname={$this->database};charset=utf8mb4",
            $this->username,
            $this->password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    }
}

