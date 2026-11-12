<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $staff;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create(['name' => 'Admin', 'username' => 'admin', 'email' => 'a@t.test', 'password' => 'secret123', 'role' => 'admin', 'is_active' => true]);
        $this->staff = User::create(['name' => 'Staff', 'username' => 'staff', 'email' => 's@t.test', 'password' => 'secret123', 'role' => 'staff', 'is_active' => true]);
        $this->category = Category::create(['name' => 'Tops']);
    }

    /** A real 1x1 PNG (built by hand so the GD extension is not needed), optionally padded to $bytes. */
    private function png(string $name, int $bytes = 0): UploadedFile
    {
        $content = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
        if ($bytes > strlen($content)) {
            $content .= str_repeat("\0", $bytes - strlen($content));
        }

        return UploadedFile::fake()->createWithContent($name, $content);
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'name' => 'Blue Shirt', 'category_id' => $this->category->id, 'clothing_type' => 'Casual Wear',
            'size' => 'M', 'condition' => 'Good', 'description' => 'Soft cotton',
            'unit_price' => '150.50', 'initial_stock' => 10, 'minimum_stock' => 2,
        ], $override);
    }

    private function product(string $name = 'Blue Shirt', int $stock = 10, int $min = 2): Product
    {
        return Product::create([
            'sku' => 'OSAM-'.str_pad((string) (Product::withTrashed()->count() + 1), 5, '0', STR_PAD_LEFT),
            'name' => $name, 'category_id' => $this->category->id, 'clothing_type' => 'Casual Wear',
            'size' => 'M', 'condition' => 'Good', 'unit_price' => 100,
            'initial_stock' => $stock, 'available_stock' => $stock, 'minimum_stock' => $min,
        ]);
    }

    // ---- Products ----

    public function test_staff_can_view_but_not_manage_products(): void
    {
        $product = $this->product();

        $this->actingAs($this->staff)->get(route('products.index'))->assertOk();
        $this->actingAs($this->staff)->get(route('products.show', $product))->assertOk();
        $this->actingAs($this->staff)->get(route('products.create'))->assertForbidden();
        $this->actingAs($this->staff)->post(route('products.store'), $this->payload())->assertForbidden();
        $this->actingAs($this->staff)->put(route('products.update', $product), $this->payload())->assertForbidden();
        $this->actingAs($this->staff)->delete(route('products.destroy', $product))->assertForbidden();
        $this->assertSame(1, Product::count());
    }

    public function test_admin_can_create_a_product_with_sku_image_and_opening_stock(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin)->post(route('products.store'), $this->payload([
            'image' => $this->png('shirt.png'),
        ]));

        $product = Product::firstOrFail();
        $this->assertSame('OSAM-00001', $product->sku);
        $this->assertSame(10, $product->available_stock);
        Storage::disk('public')->assertExists($product->image_path);

        $movement = StockMovement::firstOrFail();
        $this->assertSame(StockMovement::TYPE_IN, $movement->type);
        $this->assertSame(0, $movement->previous_stock);
        $this->assertSame(10, $movement->updated_stock);
        $this->assertTrue(ActivityLog::where('module', 'Inventory')->where('action', 'create')->exists());
    }

    public function test_product_validation_rejects_bad_input(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin)->post(route('products.store'), $this->payload([
            'unit_price' => '-5', 'initial_stock' => -1, 'minimum_stock' => -1, 'size' => 'Huge',
            'image' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'),
        ]))->assertSessionHasErrors(['unit_price', 'initial_stock', 'minimum_stock', 'size', 'image']);

        $this->actingAs($this->admin)->post(route('products.store'), $this->payload([
            'image' => $this->png('big.png', 3 * 1024 * 1024),
        ]))->assertSessionHasErrors('image');

        $this->assertSame(0, Product::count());
    }

    public function test_admin_can_edit_a_product_without_changing_stock(): void
    {
        $product = $this->product('Old Name', 7);

        $this->actingAs($this->admin)->put(route('products.update', $product), $this->payload([
            'name' => 'New Name', 'unit_price' => '200', 'initial_stock' => 999,
        ]))->assertRedirect(route('products.show', $product));

        $product->refresh();
        $this->assertSame('New Name', $product->name);
        $this->assertSame(7, $product->available_stock);
    }

    public function test_deleting_a_product_is_a_soft_delete(): void
    {
        $product = $this->product();

        $this->actingAs($this->admin)->delete(route('products.destroy', $product))->assertRedirect(route('products.index'));

        $this->assertSoftDeleted('products', ['id' => $product->id]);
        $this->assertTrue(ActivityLog::where('module', 'Inventory')->where('action', 'delete')->exists());
    }

    public function test_inventory_search_filter_and_sort(): void
    {
        $this->product('Red Dress', 10, 2);
        $low = $this->product('Green Jacket', 1, 2);
        $this->product('Black Pants', 0, 2);

        // The navbar low-stock dropdown also lists product names, so check the table data itself.
        $names = fn (array $query) => $this->actingAs($this->admin)->get(route('products.index', $query))
            ->assertOk()->viewData('products')->pluck('name')->all();

        $this->assertSame(['Red Dress'], $names(['search' => 'dress']));
        $this->assertSame(['Green Jacket'], $names(['search' => $low->sku]));
        $this->assertSame(['Green Jacket'], $names(['status' => 'low_stock']));
        $this->assertSame(['Black Pants'], $names(['status' => 'out_of_stock']));
        $this->assertSame(['Red Dress'], $names(['status' => 'in_stock']));
        $this->assertSame(['Black Pants', 'Green Jacket', 'Red Dress'], $names(['sort' => 'name_asc']));
        $this->assertSame(['Red Dress', 'Green Jacket', 'Black Pants'], $names(['sort' => 'name_desc']));
    }

    // ---- Categories ----

    public function test_staff_cannot_manage_categories(): void
    {
        $this->actingAs($this->staff)->get(route('categories.index'))->assertForbidden();
        $this->actingAs($this->staff)->post(route('categories.store'), ['name' => 'Hats'])->assertForbidden();
        $this->actingAs($this->staff)->delete(route('categories.destroy', $this->category))->assertForbidden();
    }

    public function test_admin_can_create_edit_search_and_delete_a_category(): void
    {
        $this->actingAs($this->admin)->post(route('categories.store'), ['name' => 'Hats', 'description' => 'Headwear'])
            ->assertRedirect(route('categories.index'));
        $hats = Category::where('name', 'Hats')->firstOrFail();

        $this->actingAs($this->admin)->put(route('categories.update', $hats), ['name' => 'Caps'])
            ->assertRedirect(route('categories.index'));
        $this->assertSame('Caps', $hats->fresh()->name);

        $found = $this->actingAs($this->admin)->get(route('categories.index', ['search' => 'Caps']))
            ->assertOk()->viewData('categories')->pluck('name')->all();
        $this->assertSame(['Caps'], $found);

        $this->actingAs($this->admin)->delete(route('categories.destroy', $hats))->assertRedirect(route('categories.index'));
        $this->assertDatabaseMissing('categories', ['id' => $hats->id]);
    }

    public function test_category_name_must_be_unique(): void
    {
        $this->actingAs($this->admin)->post(route('categories.store'), ['name' => 'Tops'])
            ->assertSessionHasErrors('name');
        $this->assertSame(1, Category::count());
    }

    public function test_category_in_use_cannot_be_deleted_even_by_archived_products(): void
    {
        $product = $this->product();

        $this->actingAs($this->admin)->delete(route('categories.destroy', $this->category))->assertSessionHas('error');
        $this->assertDatabaseHas('categories', ['id' => $this->category->id]);

        $product->delete(); // archived products still reference the category
        $this->actingAs($this->admin)->delete(route('categories.destroy', $this->category))->assertSessionHas('error');
        $this->assertDatabaseHas('categories', ['id' => $this->category->id]);
    }

    // ---- Stock monitoring ----

    public function test_stock_in_and_damaged_update_stock_and_history(): void
    {
        $product = $this->product('Shirt', 10);

        $this->actingAs($this->staff)->post(route('stock.store'), ['type' => 'stock_in', 'product_id' => $product->id, 'quantity' => 5])
            ->assertRedirect(route('stock.index'));
        $this->assertSame(15, $product->fresh()->available_stock);

        $this->actingAs($this->staff)->post(route('stock.store'), ['type' => 'damaged', 'product_id' => $product->id, 'quantity' => 3, 'remarks' => 'Torn'])
            ->assertRedirect(route('stock.index'));
        $this->assertSame(12, $product->fresh()->available_stock);

        $damaged = StockMovement::where('type', 'damaged')->firstOrFail();
        $this->assertSame(15, $damaged->previous_stock);
        $this->assertSame(12, $damaged->updated_stock);
        $this->assertSame($this->staff->id, $damaged->user_id);
        $this->assertTrue(ActivityLog::where('module', 'Stock')->where('action', 'damaged')->exists());
    }

    public function test_only_admin_can_record_stock_out(): void
    {
        $product = $this->product('Shirt', 10);
        $data = ['type' => 'stock_out', 'product_id' => $product->id, 'quantity' => 2, 'remarks' => 'Donation'];

        $this->actingAs($this->staff)->post(route('stock.store'), $data)->assertSessionHasErrors('type');
        $this->assertSame(10, $product->fresh()->available_stock);

        $this->actingAs($this->admin)->post(route('stock.store'), $data)->assertRedirect(route('stock.index'));
        $this->assertSame(8, $product->fresh()->available_stock);
    }

    public function test_stock_cannot_go_negative_and_remarks_are_required_for_removals(): void
    {
        $product = $this->product('Shirt', 3);

        $this->actingAs($this->admin)->post(route('stock.store'), ['type' => 'stock_out', 'product_id' => $product->id, 'quantity' => 4, 'remarks' => 'Too many'])
            ->assertSessionHasErrors('quantity');

        $this->actingAs($this->admin)->post(route('stock.store'), ['type' => 'stock_out', 'product_id' => $product->id, 'quantity' => 1])
            ->assertSessionHasErrors('remarks');

        $this->actingAs($this->admin)->post(route('stock.store'), ['type' => 'stock_in', 'product_id' => $product->id, 'quantity' => 0])
            ->assertSessionHasErrors('quantity');

        $this->assertSame(3, $product->fresh()->available_stock);
        $this->assertSame(0, StockMovement::count());
    }

    public function test_stock_pages_load_and_low_stock_view_lists_only_low_products(): void
    {
        $this->product('Plenty Shirt', 50, 2);
        $this->product('Scarce Hat', 1, 2);

        $this->actingAs($this->staff)->get(route('stock.index'))->assertOk();
        $this->actingAs($this->staff)->get(route('stock.index', ['view' => 'stock']))->assertOk()->assertSee('Plenty Shirt');
        $low = $this->actingAs($this->staff)->get(route('stock.index', ['view' => 'low']))
            ->assertOk()->viewData('products')->pluck('name')->all();
        $this->assertSame(['Scarce Hat'], $low);
    }
}
