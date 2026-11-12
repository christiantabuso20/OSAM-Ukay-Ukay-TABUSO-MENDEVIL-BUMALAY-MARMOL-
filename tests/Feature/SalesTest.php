<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $staff;
    private Product $shirt;
    private Product $jeans;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create(['name' => 'Admin', 'username' => 'admin', 'email' => 'a@t.test', 'password' => 'secret123', 'role' => 'admin', 'is_active' => true]);
        $this->staff = User::create(['name' => 'Staff', 'username' => 'staff', 'email' => 's@t.test', 'password' => 'secret123', 'role' => 'staff', 'is_active' => true]);

        $category = Category::create(['name' => 'Tops']);
        $this->shirt = $this->product($category, 'Blue Shirt', 'OSAM-00001', 150.50, 10);
        $this->jeans = $this->product($category, 'Denim Jeans', 'OSAM-00002', 300, 3);
    }

    private function product(Category $category, string $name, string $sku, float $price, int $stock): Product
    {
        return Product::create([
            'sku' => $sku, 'name' => $name, 'category_id' => $category->id, 'size' => 'M', 'condition' => 'Good',
            'unit_price' => $price, 'initial_stock' => $stock, 'available_stock' => $stock, 'minimum_stock' => 2,
        ]);
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get(route('sales.index'))->assertRedirect(route('login'));
        $this->post(route('sales.store'), [])->assertRedirect(route('login'));
    }

    public function test_staff_can_record_a_sale_and_stock_is_deducted(): void
    {
        $response = $this->actingAs($this->staff)->post(route('sales.store'), [
            'items' => [
                ['product_id' => $this->shirt->id, 'quantity' => 2],
                ['product_id' => $this->jeans->id, 'quantity' => 1],
            ],
            'payment_amount' => '1000',
        ]);

        $sale = Sale::with('items')->firstOrFail();
        $response->assertRedirect(route('sales.receipt', $sale));

        $this->assertSame('601.00', $sale->total_amount);   // 2 x 150.50 + 300
        $this->assertSame('1000.00', $sale->payment_amount);
        $this->assertSame('399.00', $sale->change_amount);
        $this->assertSame($this->staff->id, $sale->user_id);
        $this->assertSame('Staff', $sale->staff_name);
        $this->assertCount(2, $sale->items);
        $this->assertSame(8, $this->shirt->fresh()->available_stock);
        $this->assertSame(2, $this->jeans->fresh()->available_stock);
        $this->assertMatchesRegularExpression('/^OSAM-\d{8}-0001$/', $sale->sale_number);
        $this->assertTrue(ActivityLog::where('module', 'Sales')->where('action', 'sale')->exists());
    }

    public function test_duplicate_lines_are_merged_and_checked_together(): void
    {
        $this->actingAs($this->staff)->post(route('sales.store'), [
            'items' => [
                ['product_id' => $this->jeans->id, 'quantity' => 2],
                ['product_id' => $this->jeans->id, 'quantity' => 2],
            ],
            'payment_amount' => '5000',
        ])->assertSessionHasErrors('items');

        $this->assertSame(3, $this->jeans->fresh()->available_stock);
        $this->assertSame(0, Sale::count());
    }

    public function test_insufficient_stock_is_rejected_and_nothing_changes(): void
    {
        $this->actingAs($this->staff)->post(route('sales.store'), [
            'items' => [
                ['product_id' => $this->shirt->id, 'quantity' => 1],
                ['product_id' => $this->jeans->id, 'quantity' => 4],
            ],
            'payment_amount' => '5000',
        ])->assertSessionHasErrors('items');

        $this->assertSame(10, $this->shirt->fresh()->available_stock);
        $this->assertSame(3, $this->jeans->fresh()->available_stock);
        $this->assertSame(0, Sale::count());
    }

    public function test_payment_less_than_total_is_rejected(): void
    {
        $this->actingAs($this->staff)->post(route('sales.store'), [
            'items' => [['product_id' => $this->shirt->id, 'quantity' => 2]],
            'payment_amount' => '300',
        ])->assertSessionHasErrors('payment_amount');

        $this->assertSame(10, $this->shirt->fresh()->available_stock);
        $this->assertSame(0, Sale::count());
    }

    public function test_exact_payment_gives_zero_change(): void
    {
        $this->actingAs($this->staff)->post(route('sales.store'), [
            'items' => [['product_id' => $this->shirt->id, 'quantity' => 2]],
            'payment_amount' => '301.00',
        ]);

        $this->assertSame('0.00', Sale::firstOrFail()->change_amount);
    }

    public function test_invalid_quantities_and_payments_are_rejected(): void
    {
        foreach ([0, -1, 1.5, 'abc'] as $qty) {
            $this->actingAs($this->staff)->post(route('sales.store'), [
                'items' => [['product_id' => $this->shirt->id, 'quantity' => $qty]],
                'payment_amount' => '1000',
            ])->assertSessionHasErrors('items.0.quantity');
        }

        foreach ([-5, 'abc', '10.999'] as $pay) {
            $this->actingAs($this->staff)->post(route('sales.store'), [
                'items' => [['product_id' => $this->shirt->id, 'quantity' => 1]],
                'payment_amount' => $pay,
            ])->assertSessionHasErrors('payment_amount');
        }

        $this->actingAs($this->staff)->post(route('sales.store'), ['items' => [], 'payment_amount' => '100'])->assertSessionHasErrors('items');

        $this->assertSame(0, Sale::count());
        $this->assertSame(10, $this->shirt->fresh()->available_stock);
    }

    public function test_price_comes_from_the_database_not_the_browser(): void
    {
        $this->actingAs($this->staff)->post(route('sales.store'), [
            'items' => [['product_id' => $this->shirt->id, 'quantity' => 1, 'unit_price' => 1]],
            'payment_amount' => '200',
        ]);

        $this->assertSame('150.50', Sale::firstOrFail()->total_amount);
    }

    public function test_deleted_products_cannot_be_sold_but_old_sales_keep_their_history(): void
    {
        $this->actingAs($this->staff)->post(route('sales.store'), [
            'items' => [['product_id' => $this->shirt->id, 'quantity' => 1]],
            'payment_amount' => '200',
        ]);

        $this->shirt->update(['unit_price' => 999]);
        $this->shirt->delete();

        $sale = Sale::with('items')->firstOrFail();
        $this->assertSame('Blue Shirt', $sale->items->first()->product_name);
        $this->assertSame('150.50', $sale->items->first()->unit_price);

        $this->actingAs($this->staff)->get(route('sales.show', $sale))->assertOk()->assertSee('Blue Shirt');

        $this->actingAs($this->staff)->post(route('sales.store'), [
            'items' => [['product_id' => $this->shirt->id, 'quantity' => 1]],
            'payment_amount' => '2000',
        ])->assertSessionHasErrors('items.0.product_id');
    }

    public function test_sale_numbers_increase_through_the_day(): void
    {
        foreach ([1, 2] as $i) {
            $this->actingAs($this->staff)->post(route('sales.store'), [
                'items' => [['product_id' => $this->shirt->id, 'quantity' => 1]],
                'payment_amount' => '200',
            ]);
        }

        $numbers = Sale::orderBy('id')->pluck('sale_number');
        $this->assertStringEndsWith('-0001', $numbers[0]);
        $this->assertStringEndsWith('-0002', $numbers[1]);
    }

    public function test_staff_only_see_their_own_sales_but_admin_sees_all(): void
    {
        $mine = $this->makeSale($this->staff);
        $theirs = $this->makeSale($this->admin);

        $this->actingAs($this->staff)->get(route('sales.show', $mine))->assertOk();
        $this->actingAs($this->staff)->get(route('sales.receipt', $mine))->assertOk()->assertSee('OSAM Ukay-Ukay');
        $this->actingAs($this->staff)->get(route('sales.show', $theirs))->assertForbidden();
        $this->actingAs($this->staff)->get(route('sales.receipt', $theirs))->assertForbidden();

        $this->actingAs($this->staff)->get(route('sales.index'))->assertOk()->assertSee($mine->sale_number)->assertDontSee($theirs->sale_number);
        $this->actingAs($this->admin)->get(route('sales.index'))->assertOk()->assertSee($mine->sale_number)->assertSee($theirs->sale_number);
    }

    public function test_history_search_and_pos_pages_and_product_search_work(): void
    {
        $sale = $this->makeSale($this->staff);

        $this->actingAs($this->staff)->get(route('sales.index', ['search' => 'Blue Shirt']))->assertOk()->assertSee($sale->sale_number);
        $this->actingAs($this->staff)->get(route('sales.index', ['search' => 'nothing-here']))->assertOk()->assertDontSee($sale->sale_number)->assertSee('No sales found');
        $this->actingAs($this->staff)->get(route('sales.create'))->assertOk()->assertSee('Complete Sale');

        $this->actingAs($this->staff)->getJson(route('sales.products', ['q' => 'denim']))
            ->assertOk()->assertJsonCount(1)->assertJsonPath('0.name', 'Denim Jeans')->assertJsonPath('0.stock', 3);
    }

    public function test_dashboard_reflects_the_sale(): void
    {
        $this->makeSale($this->staff, 2);

        $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk()->assertSee('OSAM-');
        $this->assertSame(8, $this->shirt->fresh()->available_stock);
    }

    private function makeSale(User $by, int $qty = 1): Sale
    {
        $this->actingAs($by)->post(route('sales.store'), [
            'items' => [['product_id' => $this->shirt->id, 'quantity' => $qty]],
            'payment_amount' => '1000',
        ])->assertSessionDoesntHaveErrors();

        return Sale::latest('id')->firstOrFail();
    }
}
