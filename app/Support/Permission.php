<?php

namespace App\Support;

class Permission
{
    public const MODULE_DASHBOARD = 'module.dashboard';

    public const MODULE_PACKAGES = 'module.packages';

    public const MODULE_PREREGISTRATIONS = 'module.preregistrations';

    public const MODULE_PREALERTS = 'module.prealerts';

    public const MODULE_ALERTS = 'module.alerts';

    public const MODULE_TIME_ENTRIES = 'module.time_entries';

    public const MODULE_CONSOLIDATIONS = 'module.consolidations';

    public const MODULE_NIC = 'module.nic';

    public const MODULE_DELIVERIES = 'module.deliveries';

    public const MODULE_RECEIPT_NOTES = 'module.receipt_notes';

    public const MODULE_ACCOUNTING = 'module.accounting';

    public const MODULE_AGENCIES = 'module.agencies';

    public const MODULE_AUDIT = 'module.audit';

    public const MODULE_TIME_ENTRIES_ADMIN = 'module.time_entries_admin';

    public const ACTION_DELETE_PREREGISTRATION = 'action.delete_preregistration';

    public const ACTION_CHANGE_INTAKE_TYPE = 'action.change_intake_type';

    public const ACTION_RESET_TO_MIAMI = 'action.reset_to_miami';

    public const ACTION_EDIT_DELIVERY_NOTE = 'action.edit_delivery_note';

    public const ACTION_SEND_INVOICE = 'action.send_invoice';

    public const ACTION_RECORD_PAYMENT = 'action.record_payment';

    public const ACTION_VOID_INVOICE = 'action.void_invoice';

    public const ACTION_MANAGE_CLIENT_ACCESS = 'action.manage_client_access';

    /**
     * @return list<array{key: string, label: string, group: string}>
     */
    public static function modules(): array
    {
        return [
            ['key' => self::MODULE_DASHBOARD, 'label' => 'Panel', 'group' => 'General'],
            ['key' => self::MODULE_PACKAGES, 'label' => 'Paquetes', 'group' => 'General'],
            ['key' => self::MODULE_PREREGISTRATIONS, 'label' => 'Preregistros', 'group' => 'General'],
            ['key' => self::MODULE_PREALERTS, 'label' => 'Prealerta', 'group' => 'General'],
            ['key' => self::MODULE_ALERTS, 'label' => 'Alertas', 'group' => 'General'],
            ['key' => self::MODULE_TIME_ENTRIES, 'label' => 'Fichaje', 'group' => 'General'],
            ['key' => self::MODULE_CONSOLIDATIONS, 'label' => 'Consolidaciones', 'group' => 'Operaciones'],
            ['key' => self::MODULE_NIC, 'label' => 'Escaneo NIC', 'group' => 'Operaciones'],
            ['key' => self::MODULE_DELIVERIES, 'label' => 'Salidas', 'group' => 'Operaciones'],
            ['key' => self::MODULE_RECEIPT_NOTES, 'label' => 'Comprobantes recepción', 'group' => 'Operaciones'],
            ['key' => self::MODULE_ACCOUNTING, 'label' => 'Contabilidad', 'group' => 'Administración'],
            ['key' => self::MODULE_AGENCIES, 'label' => 'Clientes', 'group' => 'Administración'],
            ['key' => self::MODULE_AUDIT, 'label' => 'Auditoría', 'group' => 'Administración'],
            ['key' => self::MODULE_TIME_ENTRIES_ADMIN, 'label' => 'Fichaje equipo', 'group' => 'Administración'],
        ];
    }

    /**
     * @return list<array{key: string, label: string, hint: string}>
     */
    public static function actions(): array
    {
        return [
            [
                'key' => self::ACTION_DELETE_PREREGISTRATION,
                'label' => 'Eliminar preregistro',
                'hint' => 'Quitar de la lista un paquete pendiente o recibido en Miami. Un administrador puede recuperarlo desde Auditoría unos días.',
            ],
            [
                'key' => self::ACTION_CHANGE_INTAKE_TYPE,
                'label' => 'Cambiar Courier / Drop Off',
                'hint' => 'Cambiar el tipo de ingreso de un paquete ya creado.',
            ],
            [
                'key' => self::ACTION_RESET_TO_MIAMI,
                'label' => 'Devolver a Miami',
                'hint' => 'Quitar el paquete del saco y regresarlo a Recibido en Miami.',
            ],
            [
                'key' => self::ACTION_EDIT_DELIVERY_NOTE,
                'label' => 'Editar hoja de salida',
                'hint' => 'Corregir quien retira, quitar paquetes o separar una hoja que mezcle clientes.',
            ],
            [
                'key' => self::ACTION_SEND_INVOICE,
                'label' => 'Enviar factura',
                'hint' => 'Mandar o reenviar el comprobante al correo de facturación del cliente.',
            ],
            [
                'key' => self::ACTION_RECORD_PAYMENT,
                'label' => 'Registrar cobro',
                'hint' => 'Registrar un pago o cancelar un cobro ya aplicado.',
            ],
            [
                'key' => self::ACTION_VOID_INVOICE,
                'label' => 'Anular factura',
                'hint' => 'Anular una factura emitida, ver anuladas y eliminar el registro anulado.',
            ],
            [
                'key' => self::ACTION_MANAGE_CLIENT_ACCESS,
                'label' => 'Acceso del cliente',
                'hint' => 'Crear, editar o restablecer la contraseña del portal del cliente.',
            ],
        ];
    }

    /**
     * Módulos que hoy tiene un usuario de operaciones (sin acciones sensibles).
     *
     * @return list<string>
     */
    public static function operationalDefaults(): array
    {
        return [
            self::MODULE_PACKAGES,
            self::MODULE_PREREGISTRATIONS,
            self::MODULE_PREALERTS,
            self::MODULE_TIME_ENTRIES,
            self::MODULE_CONSOLIDATIONS,
            self::MODULE_NIC,
            self::MODULE_DELIVERIES,
            self::MODULE_RECEIPT_NOTES,
        ];
    }

    /**
     * @return list<string>
     */
    public static function allKeys(): array
    {
        return array_values(array_unique(array_merge(
            array_column(self::modules(), 'key'),
            array_column(self::actions(), 'key'),
        )));
    }

    /**
     * @param  mixed  $values
     * @return list<string>
     */
    public static function sanitize(mixed $values): array
    {
        if (! is_array($values)) {
            return [];
        }

        $allowed = self::allKeys();

        return array_values(array_unique(array_values(array_filter(
            $values,
            fn ($key) => is_string($key) && in_array($key, $allowed, true)
        ))));
    }
}
