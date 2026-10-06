<?php

namespace App\Services\Payment;

/**
 * Which `direct_appointment_payments.payment_type` labels belong to which online gateway.
 *
 * sendPaymentLinks() normalises what it stores (NewDirectIntegrationController): Tabby is saved as `TABI`, Tamara as
 * `TAMARA`, and `E-Commerce` is the branch that creates a ClickPay invoice, so "clickpay" means `E-Commerce`.
 * Other methods (CASH, POS, TRNS, ...) are not gateways and are never part of the three types.
 *
 * Older or hand-made rows may use other spellings (`tabby`, `clickpay`...), so a few aliases and case variants are
 * accepted. The variants are listed explicitly (instead of LOWER(payment_type) in SQL) so the query can still use an
 * index on the column, and so it behaves the same on a case-sensitive database.
 */
final class DirectPaymentGateways
{
    public const TABBY = 'tabby';

    public const TAMARA = 'tamara';

    public const CLICKPAY = 'clickpay';

    /** @var array<string, array<int, string>> canonical stored label first */
    private const STORED_AS = [
        self::TABBY    => ['TABI', 'TABBY'],
        self::TAMARA   => ['TAMARA'],
        self::CLICKPAY => ['E-Commerce', 'E-COMMERCE', 'ECOMMERCE', 'CLICKPAY'],
    ];

    /** @return array<int, string> */
    public static function types(): array
    {
        return array_keys(self::STORED_AS);
    }

    /**
     * Every stored spelling for one gateway, or for all three when no type is given.
     *
     * @return array<int, string>
     */
    public static function storedLabels(?string $type = null): array
    {
        $type = $type !== null ? strtolower(trim($type)) : null;

        if ($type !== null && $type !== '' && ! isset(self::STORED_AS[$type])) {
            throw new \InvalidArgumentException("Unknown direct payment type [{$type}].");
        }

        $aliases = ($type === null || $type === '')
            ? array_merge(...array_values(self::STORED_AS))
            : self::STORED_AS[$type];

        $labels = [];

        foreach ($aliases as $alias) {
            foreach ([$alias, strtoupper($alias), strtolower($alias), ucfirst(strtolower($alias))] as $variant) {
                $labels[$variant] = true;
            }
        }

        return array_keys($labels);
    }

    /** The gateway a stored payment_type belongs to, or null (CASH, POS, empty, unknown...). */
    public static function typeOf(?string $paymentType): ?string
    {
        $needle = strtoupper(trim((string) $paymentType));

        if ($needle === '') {
            return null;
        }

        foreach (self::STORED_AS as $type => $aliases) {
            if (in_array($needle, array_map('strtoupper', $aliases), true)) {
                return $type;
            }
        }

        return null;
    }
}
