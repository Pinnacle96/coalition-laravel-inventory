<?php

namespace Tests\Feature;

use App\InventoryStore;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    private string $directory;

    private string $inventoryPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'inventory-'.Str::uuid();
        File::makeDirectory($this->directory);
        $this->inventoryPath = $this->directory.DIRECTORY_SEPARATOR.'inventory.json';
        $this->app->instance(InventoryStore::class, new InventoryStore($this->inventoryPath));
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->directory);

        parent::tearDown();
    }

    public function test_adds_entries_with_exact_totals_and_orders_them_by_submission_time(): void
    {
        $this->get('/')->assertOk()->assertSee('No products added yet.');

        $this->travelTo(now()->utc()->setDate(2026, 2, 9)->setTime(12, 0));
        $this->postJson('/inventory', ['name' => 'Later', 'quantity' => 3, 'price' => '0.10'])
            ->assertCreated()
            ->assertJsonPath('grand_total', '0.30');

        $this->travelTo(now()->subMinute());
        $second = $this->postJson('/inventory', ['name' => 'Earlier', 'quantity' => 4, 'price' => '2.25'])
            ->assertCreated()
            ->assertJsonPath('grand_total', '9.30')
            ->assertJsonPath('count', 2);
        $this->assertLessThan(strpos($second->json('rows'), '>Later<'), strpos($second->json('rows'), '>Earlier<'));

        $entries = json_decode(File::get($this->inventoryPath), true, flags: JSON_THROW_ON_ERROR);
        $this->assertCount(2, $entries);
        $this->assertSame(10, $entries[0]['price_minor']);
        $this->assertStringContainsString('T12:00:00.', $entries[0]['submitted_at']);
        $this->get('/')->assertOk()->assertSeeInOrder(['Earlier', 'Later'])->assertSee('9.30');
    }

    public function test_edit_preserves_the_submission_time_and_renders_escaped_text(): void
    {
        $this->postJson('/inventory', ['name' => 'Original', 'quantity' => 2, 'price' => '4.25'])->assertCreated();
        [$original] = json_decode(File::get($this->inventoryPath), true, flags: JSON_THROW_ON_ERROR);

        $this->travel(1)->hour();
        $this->patchJson('/inventory/'.$original['id'], ['name' => '<sample>', 'quantity' => 7, 'price' => '1.10'])
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('grand_total', '7.70');
        [$edited] = json_decode(File::get($this->inventoryPath), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame($original['id'], $edited['id']);
        $this->assertSame($original['submitted_at'], $edited['submitted_at']);
        $this->assertSame('<sample>', $edited['name']);
        $this->get('/')->assertOk()->assertSee('&lt;sample&gt;', false)->assertDontSee('<sample>', false);
    }

    public function test_invalid_requests_and_unknown_entries_leave_data_untouched(): void
    {
        $this->postJson('/inventory', ['name' => 'Spare', 'quantity' => 0, 'price' => '0'])->assertCreated();
        $before = File::get($this->inventoryPath);

        $this->postJson('/inventory', ['name' => '', 'quantity' => 1.5, 'price' => '0.001'])
            ->assertUnprocessable()->assertJsonValidationErrors(['name', 'quantity', 'price']);
        $this->postJson('/inventory', ['name' => 'Invalid', 'quantity' => 1, 'price' => '-4'])
            ->assertUnprocessable()->assertJsonValidationErrors('price');
        $this->patchJson('/inventory/'.Str::uuid(), ['name' => 'Missing', 'quantity' => 2, 'price' => '1.00'])
            ->assertNotFound();

        $this->assertSame($before, File::get($this->inventoryPath));
    }

    public function test_a_corrupt_inventory_is_preserved_instead_of_overwritten(): void
    {
        File::put($this->inventoryPath, '{broken');
        $this->postJson('/inventory', ['name' => 'Test', 'quantity' => 1, 'price' => '1.00'])
            ->assertServerError();
        $this->assertSame('{broken', File::get($this->inventoryPath));
    }
}
