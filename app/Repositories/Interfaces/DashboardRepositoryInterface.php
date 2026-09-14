<?php

namespace App\Repositories\Interfaces;

use Illuminate\Http\Request;
use App\Models\DirectAppointment;
use App\Models\PreAppointmentMessage;

interface DashboardRepositoryInterface
{
    public function getAllTechnicians(Request $request): array;
    public function getSingleTechnician(int $id);
    public function getDirectAppointments(Request $request): array;
    public function getSingleDirectAppointment(int $id);
    public function getTechnicianLogs(Request $request, int $techId): array;
    public function getTechnicianDirectAppointments(Request $request, int $techId): array;

    //  send appointment reminder
    public function getLatestBySalesOrderId(string $salesOrderId): ?DirectAppointment;
    public function getPaidPayments(string $salesOrderId);

    // pre appointment messages
    public function getAllPreMessages(Request $request): array;

    public function getPreMessageById(int $id): ?PreAppointmentMessage;
    // send pre  appointment reminder
    public function getLatestBySalesOrder(string $salesOrder): ?PreAppointmentMessage;

    public function exportPreMessages(Request $request);

    public function getYesterdayPreMessages(): \Illuminate\Support\Collection;
}
