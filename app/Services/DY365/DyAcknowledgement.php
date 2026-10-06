<?php

namespace App\Services\DY365;

/**
 * Did DY365 actually accept a notification?
 *
 * DyService::sendRequest() swallows every failure and returns null, so "no exception" has never meant
 * "DY heard us". DY's own envelope is {"Status": true, "Error": null, "Code": 200, ...}. Only a transport
 * failure (null / not an array) or an explicit Status:false / Error counts as "not accepted"; a reply
 * without those keys is given the benefit of the doubt, so an unknown envelope can never block a payment.
 */
final class DyAcknowledgement
{
    public static function accepted(mixed $reply): bool
    {
        if (! is_array($reply)) {
            return false;
        }

        if (array_key_exists('Status', $reply) && ! filter_var($reply['Status'], FILTER_VALIDATE_BOOLEAN)) {
            return false;
        }

        return empty($reply['Error']);
    }
}
