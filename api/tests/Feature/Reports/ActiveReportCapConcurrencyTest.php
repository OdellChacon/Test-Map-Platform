<?php

namespace Tests\Feature\Reports;

use App\Models\OutageReport;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PDO;
use PDOException;
use Tests\TestCase;

/**
 * Covers the active-report cap (config('outage.max_active_reports_per_user')) under
 * concurrency, via the real POST /api/v1/reports endpoint / ReportController::store(),
 * not just the raw database primitive it relies on.
 *
 * A previous version of this file only opened two raw PDO connections and asserted that
 * Postgres' row locking works (which it does, and is still exercised below as a baseline
 * primitive check) — it never called the endpoint, so removing the
 * `User::query()->whereKey(...)->lockForUpdate()->first()` call from the controller would
 * have left this file green. The tests below exercise the controller for real.
 */
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
        // Safety net: never let a short lock_timeout we set for a test leak into the
        // next test on this connection.
        try {
            DB::statement('SET lock_timeout = DEFAULT');
        } catch (\Throwable) {
            // Connection may already be in a failed/aborted transaction state; ignore.
        }

        if ($this->userId !== null) {
            User::query()->whereKey($this->userId)->delete();
        }

        parent::tearDown();
    }

    private function pdoConnection(): PDO
    {
        $dbConfig = config('database.connections.pgsql');
        $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', $dbConfig['host'], $dbConfig['port'], $dbConfig['database']);

        return new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    }

    private function validReportPayload(array $overrides = []): array
    {
        return array_merge([
            'latitude' => 40.4168,
            'longitude' => -3.7038,
            'radius_km' => 3,
            'duration_type' => 'fixed',
            'duration_hours' => 6,
            'description' => 'No power since this morning.',
        ], $overrides);
    }

    /**
     * Baseline primitive check (kept from the original test, renamed so it no longer
     * implies it covers the endpoint): Postgres itself blocks a second `FOR UPDATE`
     * transaction on a row that is already locked by another transaction. This is the
     * database guarantee ReportController::store() relies on.
     */
    public function test_it_demonstrates_the_postgres_row_locking_primitive_the_controller_relies_on(): void
    {
        $user = User::factory()->create();
        $this->userId = $user->id;

        $connectionA = $this->pdoConnection();
        $connectionB = $this->pdoConnection();

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

    /**
     * End-to-end, real-endpoint version of the cap check: a user already sitting at
     * max-1 active reports fires two back-to-back POST /api/v1/reports requests, and at
     * most one of them may succeed.
     *
     * Caveat (documented rather than hidden): PHPUnit/Pest run single-process and
     * single-threaded, so these two requests cannot truly overlap — the first request's
     * DB::transaction() fully commits (or rolls back) before the second one's HTTP call
     * even starts. That means this particular test would still pass even if
     * lockForUpdate() were removed, because the active-reports COUNT check alone already
     * reflects real, committed state by the time the second request runs. It is kept
     * because it is still real, valuable functional coverage of the cap via the actual
     * controller (something the old test never did at all) — but the test below this one
     * is the one that actually requires the row lock to pass.
     */
    public function test_a_user_cannot_create_a_second_active_report_via_two_rapid_consecutive_requests(): void
    {
        $max = (int) config('outage.max_active_reports_per_user');

        $user = User::factory()->create();
        $this->userId = $user->id;

        OutageReport::factory()->count($max - 1)->for($user)->create();

        $responses = [
            $this->actingAs($user)->postJson('/api/v1/reports', $this->validReportPayload()),
            $this->actingAs($user)->postJson('/api/v1/reports', $this->validReportPayload()),
        ];

        $succeeded = array_filter($responses, fn ($response) => $response->status() === 201);
        $rejected = array_filter($responses, fn ($response) => $response->status() === 422);

        $this->assertCount(1, $succeeded, 'Exactly one of the two requests should be allowed to create a report.');
        $this->assertCount(1, $rejected, 'The request that would exceed the cap should be rejected.');

        collect($rejected)->each(
            fn ($response) => $response->assertJsonValidationErrors('reports')
        );

        $this->assertSame(
            $max,
            OutageReport::query()->where('user_id', $user->id)->active()->count(),
            'The user must end up with exactly the configured cap of active reports, never more.'
        );
    }

    /**
     * The real concurrency guarantee: while another transaction is actively holding the
     * row lock on the user (as ReportController::store() does for the duration of its own
     * DB::transaction()), a concurrent request attempting to create a report for that same
     * user must be made to wait for that lock rather than reading a stale, pre-commit
     * count and slipping past the cap check.
     *
     * To make a single-process/single-thread test observe that waiting, a raw PDO
     * connection (simulating "the first in-flight request") opens a transaction, locks the
     * user row and inserts the report it is about to commit, but does not commit yet. The
     * app's own connection (simulating "the second, concurrent request") gets a short
     * lock_timeout, then we hit the real endpoint. If ReportController::store() still calls
     * lockForUpdate(), its SELECT ... FOR UPDATE on the same user row has to wait behind the
     * held lock and Postgres cancels it with a lock_timeout error — proving the two requests
     * were genuinely serialized on that row. If lockForUpdate() is removed, the endpoint's
     * query no longer touches the locked row at all, so it would return instead of timing
     * out, and the assertion below fails (this test goes red).
     */
    public function test_a_concurrent_request_is_blocked_by_the_row_lock_while_another_transaction_is_in_flight(): void
    {
        $max = (int) config('outage.max_active_reports_per_user');

        $user = User::factory()->create();
        $this->userId = $user->id;

        OutageReport::factory()->count($max - 1)->for($user)->create();

        $inFlight = $this->pdoConnection();
        $inFlight->beginTransaction();
        $inFlight->prepare('SELECT id FROM users WHERE id = :id FOR UPDATE')->execute(['id' => $user->id]);
        $inFlight->prepare(
            "INSERT INTO outage_reports (user_id, radius_km, duration_type, duration_hours, status, expires_at, created_at, updated_at)
             VALUES (:user_id, 3, 'fixed', 6, 'active', NOW() + INTERVAL '6 hours', NOW(), NOW())"
        )->execute(['user_id' => $user->id]);

        DB::statement("SET lock_timeout = '500ms'");

        // We need the real exception (not the HTTP response the exception handler would
        // turn it into) so we can assert it is specifically a lock_timeout, not some
        // unrelated failure.
        $this->withoutExceptionHandling();

        $blocked = false;

        try {
            $this->actingAs($user)->postJson('/api/v1/reports', $this->validReportPayload());
        } catch (QueryException $exception) {
            $blocked = true;
            $this->assertSame('55P03', $exception->errorInfo[0] ?? null, 'Expected the query to fail because it was waiting on the lock, not for some other reason.');
        } finally {
            DB::statement('SET lock_timeout = DEFAULT');
        }

        $this->assertTrue(
            $blocked,
            'The concurrent request should have blocked on the user row lock (and hit our test lock_timeout) instead of reading a stale report count.'
        );

        // Restore normal exception handling for the rest of the test: the request below
        // is expected to resolve into an ordinary 422 JSON response, not throw.
        $this->withExceptionHandling();

        // The first ("in flight") transaction now commits its report, reaching the cap for real.
        $inFlight->commit();

        $this->assertSame(
            $max,
            OutageReport::query()->where('user_id', $user->id)->active()->count()
        );

        // Now that the cap is genuinely reached, a fresh request must be rejected — the
        // lock is no longer held, so this one resolves normally.
        $this->actingAs($user)
            ->postJson('/api/v1/reports', $this->validReportPayload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('reports');

        $this->assertSame(
            $max,
            OutageReport::query()->where('user_id', $user->id)->active()->count(),
            'The cap must still hold after the extra attempt.'
        );
    }
}
