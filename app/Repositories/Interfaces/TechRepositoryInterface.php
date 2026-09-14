<?php


namespace App\Repositories\Interfaces;

use App\Models\User;

interface TechRepositoryInterface
{
    public function getTodayAppointmentsSummary(User $user);
    public function getTechStock(User $user,  $search = null, $filter = null);

    public function getSummaryBetweenDates(User $user, string $from, string $to);

    public function getTodayAndTomorrowSummary(User $user,   $search = null, $dateType = null);
    public function missingInventory($user, $appointments);
    public function newMissingInventory(User $user, $appointments);
    public function missingAppointmentLinesInventory($user, $appointments);

    // tech notifications
    public function getAllForUser($user);
    public function markAllAsRead($user);
    public function markAsRead($user, $notificationId);

    // create or update Technicians data from the dy365
    public function syncTechnicians(array $technicians): void;

    // create or update Customers data from the dy365
    public function syncCustomers(array $customers): void;
}
