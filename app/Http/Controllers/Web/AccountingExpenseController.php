<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AccountingExpense;
use App\Models\AccountingExpenseCategory;
use App\Models\Agency;
use App\Models\AuditLog;
use App\Support\QueryDate;
use Illuminate\Http\Request;

class AccountingExpenseController extends Controller
{
    public function index(Request $request)
    {
        $from = QueryDate::parse($request, 'from')?->startOfDay() ?? now()->startOfMonth();
        $to = QueryDate::parse($request, 'to')?->endOfDay() ?? now()->endOfDay();

        $query = AccountingExpense::query()
            ->with(['category:id,name', 'agency:id,code,name', 'createdBy:id,name'])
            ->whereDate('spent_at', '>=', $from->toDateString())
            ->whereDate('spent_at', '<=', $to->toDateString())
            ->orderByDesc('spent_at')
            ->orderByDesc('id');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $expenses = $query->paginate(25)->withQueryString();

        $periodTotal = (clone $query)->reorder()->sum('amount_usd');

        // Categoría con mayor gasto dentro del rango filtrado
        $topCategoryRow = (clone $query)
            ->reorder()
            ->selectRaw('category_id, COUNT(*) as movs, COALESCE(SUM(amount_usd),0) as total')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->first();
        $topCategory = $topCategoryRow
            ? (object) [
                'name' => AccountingExpenseCategory::find($topCategoryRow->category_id)?->name ?? '—',
                'total' => round((float) $topCategoryRow->total, 2),
                'movs' => (int) $topCategoryRow->movs,
            ]
            : null;

        $categories = AccountingExpenseCategory::query()->where('is_active', true)->orderBy('name')->get();
        $agencies = Agency::query()->orderBy('name')->get(['id', 'code', 'name']);

        return view('accounting.expenses.index', [
            'expenses' => $expenses,
            'categories' => $categories,
            'agencies' => $agencies,
            'from' => $from,
            'to' => $to,
            'periodTotal' => round((float) $periodTotal, 2),
            'topCategory' => $topCategory,
        ]);
    }

    public function store(Request $request)
    {
        if ($request->input('service_type') === '') {
            $request->merge(['service_type' => null]);
        }

        $data = $request->validate([
            'category_id' => 'required|exists:accounting_expense_categories,id',
            'agency_id' => 'nullable|exists:agencies,id',
            'service_type' => 'nullable|'.\App\Support\ServiceType::rule(),
            'amount_usd' => 'required|numeric|min:0.01|max:9999999',
            'spent_at' => 'required|date',
            'note' => 'nullable|string|max:500',
        ], [
            'category_id.required' => 'Seleccione la categoría del gasto.',
            'amount_usd.required' => 'Indique el monto del gasto.',
            'amount_usd.min' => 'El monto debe ser mayor que cero.',
            'spent_at.required' => 'Indique la fecha del gasto.',
        ]);

        $expense = AccountingExpense::create($data + ['created_by' => $request->user()->id]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'auditable_type' => 'accounting_expense',
            'auditable_id' => $expense->id,
            'action' => 'expense_registered',
            'summary' => 'Registró gasto de $'.number_format((float) $expense->amount_usd, 2).' ('.$expense->category?->name.')',
            'old_values' => null,
            'new_values' => $data,
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('accounting.expenses.index')->with('success', 'Gasto registrado.');
    }

    public function destroy(Request $request, AccountingExpense $expense)
    {
        AuditLog::create([
            'user_id' => $request->user()->id,
            'auditable_type' => 'accounting_expense',
            'auditable_id' => $expense->id,
            'action' => 'expense_deleted',
            'summary' => 'Eliminó gasto de $'.number_format((float) $expense->amount_usd, 2).' ('.$expense->category?->name.')',
            'old_values' => $expense->only(['category_id', 'agency_id', 'amount_usd', 'spent_at', 'note']),
            'new_values' => null,
            'ip_address' => $request->ip(),
        ]);

        $expense->delete();

        return redirect()->route('accounting.expenses.index')->with('success', 'Gasto eliminado.');
    }

    public function storeCategory(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120|unique:accounting_expense_categories,name',
        ], [
            'name.required' => 'Indique el nombre de la categoría.',
            'name.unique' => 'Ya existe una categoría con ese nombre.',
        ]);

        AccountingExpenseCategory::create(['name' => trim($data['name']), 'is_active' => true]);

        return redirect()->route('accounting.expenses.index')->with('success', 'Categoría creada.');
    }
}
