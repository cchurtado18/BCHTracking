<?php

namespace Tests\Feature;

use App\Models\AccountingExpense;
use App\Models\AccountingExpenseCategory;
use App\Models\AccountingInvoice;
use App\Models\AccountingOperatingCost;
use App\Models\AccountingRateCard;
use App\Models\Agency;
use App\Models\Delivery;
use App\Models\DeliveryNote;
use App\Models\Preregistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingExpensesAndReportsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['agency_id' => null, 'is_admin' => true]);
    }

    private function agency(string $code = 'G901'): Agency
    {
        return Agency::create([
            'name' => 'Agencia Gastos '.$code,
            'code' => $code,
            'phone' => '2222-0000',
            'department' => 'Managua',
            'is_active' => true,
            'is_main' => false,
        ]);
    }

    public function test_admin_can_register_and_delete_expense(): void
    {
        $admin = $this->admin();
        $category = AccountingExpenseCategory::query()->first()
            ?? AccountingExpenseCategory::create(['name' => 'Combustible', 'is_active' => true]);

        $this->actingAs($admin)->post(route('accounting.expenses.store'), [
            'category_id' => $category->id,
            'amount_usd' => 125.50,
            'spent_at' => now()->toDateString(),
            'note' => 'Diesel camión reparto',
        ])->assertRedirect(route('accounting.expenses.index'));

        $expense = AccountingExpense::first();
        $this->assertNotNull($expense);
        $this->assertEquals(125.50, (float) $expense->amount_usd);

        $this->actingAs($admin)->post(route('accounting.expenses.store'), [
            'category_id' => $category->id,
            'service_type' => 'AIR',
            'amount_usd' => 80,
            'spent_at' => now()->toDateString(),
            'note' => 'Flete aéreo consignatario',
        ])->assertRedirect(route('accounting.expenses.index'));

        $this->assertDatabaseHas('accounting_expenses', [
            'note' => 'Flete aéreo consignatario',
            'service_type' => 'AIR',
            'amount_usd' => 80,
        ]);

        $this->actingAs($admin)
            ->get(route('accounting.expenses.index'))
            ->assertOk()
            ->assertSee('Diesel camión reparto')
            ->assertSee('125.50');

        $this->actingAs($admin)
            ->delete(route('accounting.expenses.destroy', $expense))
            ->assertRedirect(route('accounting.expenses.index'));

        $this->assertDatabaseMissing('accounting_expenses', ['id' => $expense->id]);
    }

    public function test_admin_can_create_expense_category(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('accounting.expenses.store-category'), [
            'name' => 'Papelería Test',
        ])->assertRedirect(route('accounting.expenses.index'));

        $this->assertDatabaseHas('accounting_expense_categories', ['name' => 'Papelería Test']);
    }

    public function test_executive_report_shows_period_profit_and_loss(): void
    {
        $admin = $this->admin();
        $agency = $this->agency();

        // Precio de venta al cliente; costo de operación en Parámetros
        AccountingRateCard::create([
            'agency_id' => $agency->id,
            'service_type' => 'AIR',
            'price_per_lb' => 5.00,
            'cost_per_lb' => 0,
            'currency' => 'USD',
            'effective_from' => now()->subMonth()->toDateString(),
            'effective_to' => null,
        ]);
        AccountingOperatingCost::create([
            'service_type' => 'AIR',
            'cost_per_unit' => 3.00,
            'effective_from' => now()->subMonth()->toDateString(),
        ]);

        // Salida entregada: 10 lbs -> costo estimado $30
        $note = DeliveryNote::create(['code' => 'SLO-9701', 'agency_id' => $agency->id]);
        $package = Preregistration::create([
            'intake_type' => 'COURIER',
            'tracking_external' => 'TRK-PL-1',
            'warehouse_code' => '970101',
            'label_name' => 'Cliente PL',
            'service_type' => 'AIR',
            'intake_weight_lbs' => 10,
            'verified_weight_lbs' => 10,
            'status' => 'DELIVERED',
            'agency_id' => $agency->id,
            'ready_at' => now()->subHours(2),
            'delivered_at' => now(),
        ]);
        Delivery::create([
            'delivery_note_id' => $note->id,
            'preregistration_id' => $package->id,
            'delivered_at' => now(),
            'delivered_to' => 'Retira PL',
        ]);

        // Factura emitida en el período por $100
        AccountingInvoice::create([
            'folio' => AccountingInvoice::generateFolio(),
            'agency_id' => $agency->id,
            'delivery_note_id' => $note->id,
            'status' => 'issued',
            'issued_at' => now(),
            'total_lbs' => 10,
            'total_usd' => 100.00,
            'exchange_rate' => 36.5,
            'total_nio' => 3650.00,
            'amount_paid' => 0,
        ]);

        // Gasto extra del período: $20 (sin vía). El flete marcado no se resta otra vez.
        $category = AccountingExpenseCategory::query()->first();
        AccountingExpense::create([
            'category_id' => $category->id,
            'amount_usd' => 20.00,
            'spent_at' => now()->toDateString(),
        ]);
        AccountingExpense::create([
            'category_id' => $category->id,
            'service_type' => 'AIR',
            'amount_usd' => 35.00,
            'spent_at' => now()->toDateString(),
            'note' => 'Flete aéreo (ya va en costo/lb)',
        ]);

        // Resultado: 100 (facturado) - 30 (costo est.) - 20 (gastos) = 50
        $this->actingAs($admin)
            ->get(route('accounting.reports.index'))
            ->assertOk()
            ->assertSee('Estado de resultados')
            ->assertSee($agency->name)
            ->assertSee('100.00')
            ->assertSee('30.00')
            ->assertSee('20.00')
            ->assertSee('50.00');
    }

    public function test_agency_credit_and_fiscal_fields_can_be_saved(): void
    {
        $admin = $this->admin();
        $agency = $this->agency('G902');

        $this->actingAs($admin)->put(route('agencies.update', $agency->id), [
            'name' => $agency->name,
            'phone' => $agency->phone,
            'department' => 'Managua',
            'is_active' => 1,
            'credit_limit_usd' => 1500.00,
            'credit_days' => 30,
            'tax_id' => 'J0311111111111',
            'billing_contact_name' => 'María Cobranza',
            'billing_contact_phone' => '8888-7777',
            'billing_email' => 'maria@agencia.test',
        ])->assertRedirect(route('agencies.show', $agency->id));

        $agency->refresh();
        $this->assertEquals(1500.00, (float) $agency->credit_limit_usd);
        $this->assertSame(30, $agency->credit_days);
        $this->assertSame('J0311111111111', $agency->tax_id);
        $this->assertSame('maria@agencia.test', $agency->billing_email);

        $this->actingAs($admin)
            ->get(route('agencies.show', $agency->id))
            ->assertOk()
            ->assertSee('Contabilidad')
            ->assertSee('1,500.00')
            ->assertSee('María Cobranza');
    }

    public function test_non_admin_cannot_access_expenses_or_reports(): void
    {
        $user = User::factory()->create(['agency_id' => null, 'is_admin' => false]);

        $this->actingAs($user)->get(route('accounting.expenses.index'))->assertRedirect(route('packages.index'));
        $this->actingAs($user)->get(route('accounting.reports.index'))->assertRedirect(route('packages.index'));
    }

    public function test_invalid_date_filters_do_not_error(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('accounting.expenses.index', ['from' => 'no-es-fecha', 'to' => 'tampoco']))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('accounting.reports.index', ['from' => 'abc', 'to' => 'xyz']))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('accounting.profitability.index', ['preset' => 'custom', 'from' => 'bad', 'to' => 'bad']))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('accounting.invoices.index', ['issued_at' => '31-13-2026']))
            ->assertOk();
    }
}
