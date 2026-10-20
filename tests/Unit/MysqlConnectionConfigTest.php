<?php

namespace Tests\Unit;

use App\Support\MysqlConnectionConfig;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MysqlConnectionConfigTest extends TestCase
{
    #[DataProvider('hostProvider')]
    public function test_resolves_host_from_env(array $env, string $expected): void
    {
        $this->assertSame($expected, MysqlConnectionConfig::hostFrom($env));
    }

    public static function hostProvider(): array
    {
        return [
            'explicit remote host' => [
                ['DB_HOST' => 'db.example.com'],
                'db.example.com',
            ],
            'mysql host overrides local placeholder' => [
                ['DB_HOST' => '127.0.0.1', 'MYSQL_HOST' => 'mysql.default.svc.cluster.local'],
                'mysql.default.svc.cluster.local',
            ],
            'crm db host overrides local placeholder' => [
                ['DB_HOST' => 'localhost', 'CRM_DB_HOST' => 'mariadb'],
                'mariadb',
            ],
            'empty db host uses mysql host' => [
                ['DB_HOST' => '', 'MYSQL_HOST' => '10.0.0.5'],
                '10.0.0.5',
            ],
            'default' => [
                [],
                '127.0.0.1',
            ],
        ];
    }
}
