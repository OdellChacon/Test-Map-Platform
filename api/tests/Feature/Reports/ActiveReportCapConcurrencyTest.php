<?php

namespace Tests\Feature\Reports;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use PDO;
use PDOException;
use Tests\TestCase;

class ActiveReportCapConcurrencyTest extends TestCase
{
    private ?int $userId = null;

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('migrate', ['--force' => true]);
    }

    protected function tearDown(): void
    {
        if ($this->userId !== null) {
            User::query()->whereKey($this->userId)->delete();
        }

        parent::tearDown();
    }

    public function test_the_lock_used_to_enforce_the_active_report_cap_blocks_a_concurrent_transaction_until_released(): void
    {
        $user = User::factory()->create();
        $this->userId = $user->id;

        $dbConfig = config('database.connections.pgsql');
        $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', $dbConfig['host'], $dbConfig['port'], $dbConfig['database']);
        $options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];

        $connectionA = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $options);
        $connectionB = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $options);

        $connectionA->beginTransaction();
        $lockUser = $connectionA->prepare('SELECT id FROM users WHERE id = :id FOR UPDATE');
        $lockUser->execute(['id' => $user->id]);

        $connectionB->beginTransaction();
        $competingLock = $connectionB->prepare('SELECT id FROM users WHERE id = :id FOR UPDATE NOWAIT');

        $blocked = false;

        try {
            $competingLock->execute(['id' => $user->id]);
        } catch (PDOException $exception) {
            $blocked = true;
            $this->assertSame('55P03', $exception->errorInfo[0] ?? null);
        }

        $connectionB->rollBack();
        $connectionA->commit();

        $this->assertTrue($blocked, 'A second transaction should not be able to lock the same user row while the first one holds it.');

        $connectionB->beginTransaction();
        $releasedLock = $connectionB->prepare('SELECT id FROM users WHERE id = :id FOR UPDATE NOWAIT');
        $releasedLock->execute(['id' => $user->id]);
        $connectionB->commit();
    }
}
