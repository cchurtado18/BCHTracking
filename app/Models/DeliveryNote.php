<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DeliveryNote extends Model
{
    protected $fillable = ['code', 'agency_id'];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }

    /**
     * Primera entrega registrada (la más antigua por delivered_at).
     * Útil para listados que muestran "Retirado por" / "Fecha" de la nota
     * sin caer en el bug de eager-load con limit(1) global.
     */
    public function firstDelivery(): HasOne
    {
        return $this->hasOne(Delivery::class)->ofMany('delivered_at', 'min');
    }

    /**
     * Factura PrimeTrack activa (no anulada) vinculada a esta hoja de salida.
     */
    public function accountingInvoice(): HasOne
    {
        return $this->hasOne(AccountingInvoice::class)->ofMany(
            ['id' => 'max'],
            fn ($query) => $query->where('status', '!=', 'void')
        );
    }

    public function accountingInvoices(): HasMany
    {
        return $this->hasMany(AccountingInvoice::class);
    }

    public function linkedInvoices(): BelongsToMany
    {
        return $this->belongsToMany(AccountingInvoice::class, 'accounting_invoice_delivery_notes')
            ->withTimestamps();
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeWithoutActiveInvoice(Builder $query): Builder
    {
        $ids = static::activeInvoiceNoteIds();
        if ($ids === []) {
            return $query;
        }

        return $query->whereNotIn($query->getModel()->getQualifiedKeyName(), $ids);
    }

    /**
     * Hojas con factura activa (columna o pivot), para excluirlas de una sola vez.
     *
     * @return list<int>
     */
    public static function activeInvoiceNoteIds(): array
    {
        if (app()->bound('deliveryNote.activeInvoiceNoteIds')) {
            return app('deliveryNote.activeInvoiceNoteIds');
        }

        $direct = DB::table('accounting_invoices')
            ->where('status', '!=', 'void')
            ->whereNotNull('delivery_note_id')
            ->pluck('delivery_note_id');

        $linked = DB::table('accounting_invoice_delivery_notes as pivot')
            ->join('accounting_invoices as invoices', 'invoices.id', '=', 'pivot.accounting_invoice_id')
            ->where('invoices.status', '!=', 'void')
            ->pluck('pivot.delivery_note_id');

        $ids = $direct->merge($linked)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        app()->instance('deliveryNote.activeInvoiceNoteIds', $ids);

        return $ids;
    }

    /**
     * Lista liviana para Nueva factura: sin cargar cada paquete ni contar hoja por hoja.
     *
     * @return Collection<int, self>
     */
    public static function queryForInvoicePicker(int $limit = 200): Collection
    {
        $notes = static::query()
            ->select(['id', 'code', 'agency_id'])
            ->with([
                'agency:id,name,code,account_type,parent_agency_id,is_main',
                'agency.parent:id,name,code,account_type,is_main',
            ])
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('deliveries')
                    ->whereColumn('deliveries.delivery_note_id', 'delivery_notes.id');
            })
            ->withoutActiveInvoice()
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        static::attachDeliveryCounts($notes);
        static::decorateForInvoicePicker($notes);

        return $notes;
    }

    /**
     * @param  Collection<int, self>  $notes
     */
    public static function attachDeliveryCounts(Collection $notes): void
    {
        if ($notes->isEmpty()) {
            return;
        }

        $counts = DB::table('deliveries')
            ->whereIn('delivery_note_id', $notes->pluck('id')->all())
            ->selectRaw('delivery_note_id, COUNT(*) as aggregate')
            ->groupBy('delivery_note_id')
            ->pluck('aggregate', 'delivery_note_id');

        foreach ($notes as $note) {
            $note->setAttribute('deliveries_count', (int) ($counts[$note->id] ?? 0));
        }
    }

    /**
     * @var Collection<int, Agency>|null
     */
    protected ?Collection $packageBillTosMemo = null;

    /**
     * Cuentas comerciales (bill-to) de los paquetes de esta hoja.
     *
     * @return Collection<int, Agency>
     */
    public function packageBillToAgencies()
    {
        if ($this->packageBillTosMemo !== null) {
            return $this->packageBillTosMemo;
        }

        $this->loadMissing(['deliveries.preregistration.agency.parent.parent.parent']);
        $sloClientsByName = Agency::sloDirectClientsKeyedByName();

        $resolved = $this->deliveries
            ->map(function ($delivery) use ($sloClientsByName) {
                $package = $delivery->preregistration;
                if (! $package) {
                    return null;
                }

                $billTo = $package->billToAgency($sloClientsByName);
                if (! $billTo) {
                    return null;
                }

                return [
                    'billTo' => $billTo,
                    'label' => Agency::normalizePersonNameForMatch($package->label_name),
                ];
            })
            ->filter();

        return $this->packageBillTosMemo = $this->collapseResolvedBillTos($resolved, $sloClientsByName);
    }

    /**
     * Lista de Nueva factura: resuelve bill-to por combinaciones únicas
     * (agencia + destinatario), no cargando cada paquete de cada hoja.
     *
     * @param  Collection<int, self>|iterable<self>  $notes
     */
    public static function decorateForInvoicePicker(iterable $notes): void
    {
        $notes = collect($notes)->values();
        if ($notes->isEmpty()) {
            return;
        }

        $noteIds = $notes->pluck('id')->map(fn ($id) => (int) $id)->all();
        $rows = DB::table('deliveries')
            ->join('preregistrations', 'preregistrations.id', '=', 'deliveries.preregistration_id')
            ->whereIn('deliveries.delivery_note_id', $noteIds)
            ->select([
                'deliveries.delivery_note_id',
                'preregistrations.agency_id',
                'preregistrations.label_name',
            ])
            ->distinct()
            ->get();

        $agencyIds = $rows->pluck('agency_id')
            ->merge($notes->pluck('agency_id'))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $agencies = $agencyIds === []
            ? collect()
            : Agency::query()
                ->with('parent.parent.parent')
                ->whereIn('id', $agencyIds)
                ->get()
                ->keyBy(fn (Agency $agency) => (int) $agency->id);

        $sloClientsByName = Agency::sloDirectClientsKeyedByName();
        $resolvedByNote = [];

        foreach ($rows as $row) {
            $agency = $agencies->get((int) $row->agency_id);
            if (! $agency) {
                continue;
            }

            $package = new Preregistration([
                'agency_id' => $agency->id,
                'label_name' => $row->label_name,
            ]);
            $package->setRelation('agency', $agency);
            $billTo = $package->billToAgency($sloClientsByName);
            if (! $billTo) {
                continue;
            }

            $resolvedByNote[(int) $row->delivery_note_id][] = [
                'billTo' => $billTo,
                'label' => Agency::normalizePersonNameForMatch($row->label_name),
            ];
        }

        foreach ($notes as $note) {
            $resolved = collect($resolvedByNote[(int) $note->id] ?? []);
            $note->packageBillTosMemo = $note->collapseResolvedBillTos($resolved, $sloClientsByName);
        }
    }

    /**
     * Botones «Facturar a padre» para redes de subagencia con hijas.
     *
     * @param  Collection<int, self>|iterable<self>  $notes
     * @return list<array{key: string, parent: Agency, count: int}>
     */
    public static function parentInvoiceActions(iterable $notes): array
    {
        $notes = collect($notes);
        $groups = [];

        foreach ($notes as $note) {
            $key = $note->invoiceGroupKey();
            if (! str_starts_with($key, 'family:') || $note->hasMixedBillTos()) {
                continue;
            }

            $billTo = $note->billingAgency();
            if (! $billTo) {
                continue;
            }

            $parent = $billTo->canonicalInvoiceBillTo()->invoiceFamilyRoot();
            if ($parent->isDirectClient() || $parent->isRootAccount()) {
                continue;
            }

            $groups[$key] ??= [
                'key' => $key,
                'parent' => $parent,
                'count' => 0,
            ];
            $groups[$key]['count']++;
        }

        if ($groups === []) {
            return [];
        }

        $parentsWithNestedChildren = Agency::query()
            ->where('account_type', Agency::TYPE_SUBAGENCY)
            ->whereNotNull('parent_agency_id')
            ->whereHas('parent', function ($query) {
                $query->where('is_main', false)
                    ->where(function ($inner) {
                        $inner->whereNull('account_type')
                            ->orWhere('account_type', '!=', Agency::TYPE_ROOT);
                    });
            })
            ->pluck('parent_agency_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->all();

        return array_values(array_filter(
            $groups,
            fn (array $group) => in_array((int) $group['parent']->id, $parentsWithNestedChildren, true)
        ));
    }

    /**
     * @param  Collection<int, array{billTo: Agency, label: string}>  $resolved
     * @param  array<string, Agency>  $sloClientsByName
     * @return Collection<int, Agency>
     */
    private function collapseResolvedBillTos(Collection $resolved, array $sloClientsByName): Collection
    {
        $billTos = $resolved
            ->map(fn (array $row) => $row['billTo'])
            ->unique(fn (Agency $agency) => (int) $agency->id)
            ->values();

        if ($billTos->count() <= 1) {
            return $billTos;
        }

        $nonRoot = $billTos->reject(fn (Agency $agency) => $agency->isRootAccount())->values();
        $labels = $resolved->pluck('label')->filter()->unique()->values();

        if ($labels->count() === 1) {
            $match = $sloClientsByName[$labels->first()] ?? null;
            if ($match instanceof Agency) {
                return collect([$match])->values();
            }
        }

        if ($nonRoot->count() === 1 && ($labels->count() <= 1 || $this->sloLabelsBelongToClient($resolved, $nonRoot->first()))) {
            return collect([$nonRoot->first()])->values();
        }

        if ($labels->count() === 1 && $nonRoot->count() <= 1) {
            $match = $nonRoot->first();
            if ($match instanceof Agency) {
                return collect([$match])->values();
            }
        }

        return $billTos;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array{billTo: Agency, label: string}>  $resolved
     */
    private function sloLabelsBelongToClient($resolved, Agency $client): bool
    {
        $clientKey = Agency::normalizePersonNameForMatch($client->name);
        $clientLabels = $resolved
            ->filter(fn (array $row) => (int) $row['billTo']->id === (int) $client->id)
            ->pluck('label')
            ->filter();

        return $resolved
            ->filter(fn (array $row) => $row['billTo']->isRootAccount())
            ->pluck('label')
            ->filter()
            ->every(fn (string $label) => $label === $clientKey || $clientLabels->contains($label));
    }

    public function hasMixedBillTos(): bool
    {
        return $this->packageBillToAgencies()->count() > 1;
    }

    /**
     * Hojas con paquetes de más de una cuenta (Magali + Brenda en la misma SLO, etc.).
     */
    public function scopeWithMultiplePackageAgencies(Builder $query): Builder
    {
        return $query->whereRaw('(
            SELECT COUNT(DISTINCT preregistrations.agency_id)
            FROM deliveries
            INNER JOIN preregistrations ON preregistrations.id = deliveries.preregistration_id
            WHERE deliveries.delivery_note_id = delivery_notes.id
        ) > 1');
    }

    /**
     * Cliente a facturar: si todos los paquetes caen en una cuenta comercial, esa;
     * si hay varias, la agencia de la hoja (salida de red, p. ej. SkyLink One).
     */
    public function billingAgency(): ?Agency
    {
        $billTos = $this->packageBillToAgencies();
        if ($billTos->count() === 1) {
            return $billTos->first();
        }

        $this->loadMissing(['agency.parent.parent.parent']);
        if (! $this->agency) {
            return null;
        }

        return $this->agency->commercialBillTo();
    }

    /**
     * @return list<int>
     */
    public function invoiceFamilyIds(): array
    {
        $billTos = $this->packageBillToAgencies();
        if ($billTos->count() > 1) {
            return [-(int) $this->id];
        }

        $agency = $billTos->first() ?? $this->billingAgency();
        if (! $agency) {
            return [];
        }

        $ids = $agency->invoiceFamilyIds();
        sort($ids);

        return $ids;
    }

    public function invoiceFamilyKey(): string
    {
        return $this->invoiceGroupKey();
    }

    /**
     * Clave para juntar hojas en una misma factura: el cliente canónico, no el id crudo de agencia.
     */
    public function invoiceGroupKey(): string
    {
        if ($this->hasMixedBillTos()) {
            return 'mixed:'.$this->id;
        }

        $billTo = $this->billingAgency();
        if (! $billTo) {
            return 'none:'.$this->id;
        }

        $canonical = $billTo->canonicalInvoiceBillTo();
        if ($canonical->isDirectClient() || $canonical->isRootAccount()) {
            return 'billto:'.(int) $canonical->id;
        }

        return 'family:'.(int) $canonical->invoiceFamilyRoot()->id;
    }

    public function currentInvoice(): ?AccountingInvoice
    {
        if ($this->accountingInvoice) {
            return $this->accountingInvoice;
        }

        $linked = $this->relationLoaded('linkedInvoices')
            ? $this->linkedInvoices
            : $this->linkedInvoices()->where('status', '!=', 'void')->orderByDesc('accounting_invoices.id')->get();

        return $linked->first(fn (AccountingInvoice $invoice) => $invoice->status !== 'void');
    }

    /**
     * Genera el siguiente código único para una nota de entrega: SLO-0001, SLO-0002, ...
     * También considera códigos históricos BCH- para no reiniciar la secuencia.
     */
    public static function generateCode(): string
    {
        $prefix = 'SLO-';
        $codes = static::query()
            ->where(function ($query) {
                $query->where('code', 'like', 'SLO-%')
                    ->orWhere('code', 'like', 'BCH-%');
            })
            ->pluck('code');

        $max = 0;
        foreach ($codes as $code) {
            if (preg_match('/^(?:SLO|BCH)-(\d+)$/', (string) $code, $m)) {
                $max = max($max, (int) $m[1]);
            }
        }

        return $prefix.str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
    }
}
