<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $staff;
    private User $otherStaff;
    private Product $shirt;
    private Product $jeans;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->user('Admin', 'admin');
        $this->staff = $this->user('Staff One', 'staff');
        $this->otherStaff = $this->user('Staff Two', 'staff');

        $category = Category::create(['name' => 'Tops']);
        $this->shirt = $this->product($category, 'Blue Shirt', 'OSAM-00001', 150, 10, 2);
        $this->jeans = $this->product($category, 'Denim Jeans', 'OSAM-00002', 300, 1, 2); // low stock

        $this->sale($this->staff, $this->shirt, 2);      // 300.00
        $this->sale($this->otherStaff, $this->jeans, 1); // 300.00

        StockMovement::create([
            'product_id' => $this->shirt->id, 'user_id' => $this->admin->id, 'type' => 'damaged',
            'quantity' => 1, 'previous_stock' => 11, 'updated_stock' => 10, 'remarks' => 'Torn sleeve',
        ]);
    }

    private function user(string $name, string $role): User
    {
        $key = strtolower(str_replace(' ', '', $name));

        return User::create(['name' => $name, 'username' => $key, 'email' => $key.'@t.test', 'password' => 'secret123', 'role' => $role, 'is_active' => true]);
    }

    private function product(Category $category, string $name, string $sku, float $price, int $stock, int $min): Product
    {
        return Product::create([
            'sku' => $sku, 'name' => $name, 'category_id' => $category->id, 'size' => 'M', 'condition' => 'Good',
            'unit_price' => $price, 'initial_stock' => $stock, 'available_stock' => $stock, 'minimum_stock' => $min,
        ]);
    }

    private function sale(User $by, Product $product, int $qty): Sale
    {
        $total = $product->unit_price * $qty;
        $sale = Sale::create([
            'sale_number' => Sale::generateNumber(), 'user_id' => $by->id, 'staff_name' => $by->name,
            'total_amount' => $total, 'payment_amount' => $total, 'change_amount' => 0,
        ]);
        SaleItem::create([
            'sale_id' => $sale->id, 'product_id' => $product->id, 'product_name' => $product->name, 'sku' => $product->sku,
            'unit_price' => $product->unit_price, 'quantity' => $qty, 'subtotal' => $total,
        ]);

        return $sale;
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get(route('reports.index'))->assertRedirect(route('login'));
        $this->get(route('reports.export', ['format' => 'excel', 'type' => 'inventory_complete']))->assertRedirect(route('login'));
    }

    public function test_complete_inventory_report_lists_products_and_totals(): void
    {
        $this->actingAs($this->admin)
            ->get(route('reports.index', ['type' => 'inventory_complete']))
            ->assertOk()
            ->assertSee('Complete Inventory Report')
            ->assertSee('Blue Shirt')
            ->assertSee('Denim Jeans')
            ->assertSee('Low Stock')
            ->assertSee('₱1,800.00'); // 10 x 150 + 1 x 300
    }

    public function test_low_stock_report_only_lists_products_at_or_below_minimum(): void
    {
        $this->actingAs($this->admin)
            ->get(route('reports.index', ['type' => 'inventory_low']))
            ->assertOk()
            ->assertSee('Denim Jeans')
            ->assertDontSee('Blue Shirt');
    }

    public function test_damaged_report_shows_records_for_admin(): void
    {
        $this->actingAs($this->admin)
            ->get(route('reports.index', ['type' => 'inventory_damaged']))
            ->assertOk()
            ->assertSee('Torn sleeve')
            ->assertSee('Blue Shirt');
    }

    public function test_staff_cannot_open_admin_only_reports(): void
    {
        $this->actingAs($this->staff)->get(route('reports.index', ['type' => 'inventory_damaged']))->assertForbidden();
        $this->actingAs($this->staff)->get(route('reports.index', ['type' => 'inventory_movements']))->assertForbidden();
        $this->actingAs($this->staff)->get(route('reports.export', ['format' => 'excel', 'type' => 'inventory_damaged']))->assertForbidden();
    }

    public function test_staff_can_open_inventory_reports(): void
    {
        $this->actingAs($this->staff)->get(route('reports.index', ['type' => 'inventory_available']))->assertOk()->assertSee('Blue Shirt');
    }

    public function test_admin_daily_sales_report_totals_every_sale(): void
    {
        $this->actingAs($this->admin)
            ->get(route('reports.index', ['type' => 'sales_daily', 'date' => now()->format('Y-m-d')]))
            ->assertOk()
            ->assertSee('Daily Sales Report')
            ->assertSee('₱600.00')
            ->assertSee('Staff One')
            ->assertSee('Staff Two');
    }

    public function test_staff_sales_reports_only_include_their_own_sales(): void
    {
        $this->actingAs($this->staff)
            ->get(route('reports.index', ['type' => 'sales_monthly']))
            ->assertOk()
            ->assertSee('Staff One')
            ->assertDontSee('Staff Two')
            ->assertSee('₱300.00');
    }

    public function test_sales_outside_the_date_range_are_left_out(): void
    {
        $this->actingAs($this->admin)
            ->get(route('reports.index', ['type' => 'sales_custom', 'date_from' => '2020-01-01', 'date_to' => '2020-01-31']))
            ->assertOk()
            ->assertSee('No records found');
    }

    public function test_best_selling_report_ranks_by_quantity(): void
    {
        $response = $this->actingAs($this->admin)->get(route('reports.index', ['type' => 'sales_best']));

        $response->assertOk()->assertSeeInOrder(['Blue Shirt', 'Denim Jeans']);
    }

    public function test_print_view_has_branding_and_logs_activity(): void
    {
        $this->actingAs($this->admin)
            ->get(route('reports.export', ['format' => 'print', 'type' => 'inventory_complete']))
            ->assertOk()
            ->assertSee('OSAM Ukay-Ukay')
            ->assertSee('Complete Inventory Report')
            ->assertSee('Generated');

        $this->assertDatabaseHas('activity_logs', ['module' => 'Reports', 'action' => 'print', 'user_id' => $this->admin->id]);
    }

    public function test_pdf_export_downloads_a_real_pdf(): void
    {
        $response = $this->actingAs($this->admin)->get(route('reports.export', ['format' => 'pdf', 'type' => 'sales_daily']));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF-1.4', $response->getContent());
        $this->assertStringContainsString('%%EOF', $response->getContent());

        $this->assertDatabaseHas('activity_logs', ['module' => 'Reports', 'action' => 'export_pdf']);
    }

    public function test_excel_export_is_a_valid_xlsx_with_the_report_data(): void
    {
        if (! class_exists(ZipArchive::class)) {
            $this->markTestSkipped('PHP zip extension is not enabled.');
        }

        $response = $this->actingAs($this->admin)->get(route('reports.export', ['format' => 'excel', 'type' => 'inventory_complete']));

        $response->assertOk();
        $this->assertStringContainsString('spreadsheetml', $response->headers->get('Content-Type'));

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($response->baseResponse->getFile()->getPathname()));
        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        $this->assertStringContainsString('Blue Shirt', $sheet);
        $this->assertStringContainsString('OSAM-00002', $sheet);
        $this->assertNotNull(simplexml_load_string($sheet), 'sheet1.xml must be well-formed XML');
        $this->assertDatabaseHas('activity_logs', ['module' => 'Reports', 'action' => 'export_excel']);
    }

    public function test_export_without_a_type_goes_back_to_the_report_page(): void
    {
        $this->actingAs($this->admin)->get(route('reports.export', ['format' => 'excel']))->assertRedirect(route('reports.index'));
    }

    public function test_unknown_export_format_is_not_found(): void
    {
        $this->actingAs($this->admin)->get('/reports/export/docx?type=sales_daily')->assertNotFound();
    }
}
