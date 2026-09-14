<?php


namespace App\Repositories\Tech;

use Carbon\Carbon;
use App\Models\User;
use App\Models\UserStock;
use App\Models\Warehouse;
use App\Models\Appointment;
use App\Models\Announcement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\Tech\StockItemResource;
use App\Models\AppointmentChangeStatusRequest;
use Illuminate\Validation\ValidationException;
use App\Repositories\Interfaces\TechRepositoryInterface;

class TechRepository implements TechRepositoryInterface
{
    public function getTodayAppointmentsSummary(User $user)
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        // $yesterdayCollectSum = $user->technicianAppointments()
        //     ->whereDate('appointment_date', $yesterday)
        //     ->with(['lines' => function ($query) {
        //         $query->where('payment_method', 'CASH')
        //             ->where('is_paid', 1);
        //     }])
        //     ->get()
        //     ->flatMap->lines
        //     ->sum('total_amount');

        // $todayCollectSum = $user->technicianAppointments()
        //     ->whereDate('appointment_date', $today)
        //     ->with(['lines' => function ($query) {
        //         $query->where('payment_method', 'CASH')
        //             ->where('is_paid', 1);
        //     }])
        //     ->get()
        //     ->flatMap->lines
        //     ->sum('total_amount');
        $yesterdayCollectSum = $user->technicianAppointments()
            ->whereDate('appointment_date', $yesterday)
            ->with(['payments' => function ($query) {
                $query->where('payment_type', 'cash');
            }])
            ->get()
            ->flatMap->payments
            ->sum('total_price');

        $todayCollectSum = $user->technicianAppointments()
            ->whereDate('appointment_date', $today)
            ->with(['payments' => function ($query) {
                $query->where('payment_type', 'cash');
            }])
            ->get()
            ->flatMap->payments
            ->sum('total_price');


        $appointments = $user->technicianAppointments()
            ->whereDate('appointment_date', now())
            ->with(['lines', 'latestChangeStatusRequest'])
            ->get();

        $appointmentItems = $appointments->flatMap(fn($appointment) => $appointment->items);
        $stock = $user->stock?->load(['items', 'parts']);
        $stockItems = $stock?->items ?? collect();
        // get missing items and parts
        $inventory = $this->newMissingInventory($user, $appointments);
        // Group appointments by type
        $installationAppointments = $appointments->where('type', 'installation');
        $installationItems = $installationAppointments->flatMap(fn($a) => $a->items);

        $periodicAppointments = $appointments->where('type', 'periodic');
        $periodicItems = $periodicAppointments->flatMap(fn($a) => $a->items);

        $emergencyAppointments = $appointments->where('type', 'emergency');
        $emergencyItems = $emergencyAppointments->flatMap(fn($a) => $a->emergencyItems);
        // summary
        $summaryAppointment = [
            'pending' => $appointments
                ->whereIn('status', ['pending', 'Delayed', 'Scheduled'])
                ->count(),
            'on_way' => $appointments->where('status', 'on_way')->count(),
            'on_site'     => $appointments->where('status', 'on_site')->count(),
            'reschedule' => $appointments->where('status', 'reschedule')->count(),
            'cancel'  => $appointments->where('status', 'cancel')->count(),
            'completed' => $appointments
                ->whereIn('status', ['complete', 'Completed'])
                ->count(),
        ];
        $lastThreeChangeStatusRequests = AppointmentChangeStatusRequest::whereIn('appointment_id', $appointments->pluck('id'))
            ->latest()
            ->take(3)
            ->get();
        //announcement
        $announcement = Announcement::all();
        return [
            'current_appointment' =>  $appointments->whereIn('status', ['pending', 'Delayed', 'Scheduled', 'on_way', 'on_site'])->first()?->load('customer'),
            'appointments_count' => $appointments->count(),
            'all_appointments_items' => $appointmentItems,
            'user' => $user->makeHidden('stock'),
            'stock' => $stock,
            'stock_items' => StockItemResource::collection($stockItems),
            'missing_items' =>   $inventory['missing_items'],
            'missing_items_count' => count($inventory['missing_items']),
            'missing_parts' => $inventory['missing_parts'],
            'missing_parts_count' => count($inventory['missing_parts']),
            'installation_appointments' => $installationAppointments->values(),
            'installation_items' => $installationItems->values(),
            'periodic_appointments' => $periodicAppointments->values(),
            'periodic_items' => $periodicItems->values(),
            'emergency_appointments' => $emergencyAppointments->values(),
            'emergency_items' => $emergencyItems->values(),
            'cash_collect_yesterday' => $yesterdayCollectSum,
            'cash_collect_today' => $todayCollectSum,
            'summary_appointment' => $summaryAppointment,
            'tasks' => $user->tasks()->with('images')->latest()->take(10)->get(),
            'announcement' => $announcement,
            'last_three_status_requests' => $lastThreeChangeStatusRequests,

        ];
    }
    // today and Tomorrow summary
    public function getTodayAndTomorrowSummary(User $user, $search = null, $dateType = null)
    {
        $today = Carbon::today();
        $tomorrow = Carbon::tomorrow();
        $yesterday = Carbon::yesterday();

        if ($dateType === 'tomorrow') {
            $date = $tomorrow;
        } elseif ($dateType === 'previous') {
            $date = $yesterday;
        } elseif ($dateType === 'today' || $dateType === null) {
            $date = $today;
        } else {
            try {
                $date = Carbon::parse($dateType)->startOfDay();
            } catch (\Exception $e) {
                $date = $today;
            }
        }

        // ✅ Build the base query
        $appointmentsQuery = $user->technicianAppointments()
            ->with(['customer', 'lines']);

        // ✅ Apply date filter
        if ($dateType === 'previous') {
            $appointmentsQuery->whereDate('appointment_date', '<=', $yesterday);
        } else {
            $appointmentsQuery->whereDate('appointment_date', $date);
        }

        // ✅ Apply search filter if provided
        if ($search) {
            $appointmentsQuery->where(function ($query) use ($search) {
                $query->where('appointment_num', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($q) use ($search) {
                        $q->where('username', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        $appointments = $appointmentsQuery->get();

        // ✅ Open appointments
        $openStatuses = ['pending', 'Delayed', 'Scheduled', 'on_way', 'on_site', 'processing'];

        $openAppointments = $appointments
            ->whereIn('status', $openStatuses)
            ->map(function ($appointment) {
                $totalPrice = $appointment->lines
                    ? $appointment->lines->where('warranty_status', '!=', 'Yes')->sum('total_amount')
                    : 0;

                $discountType = $appointment->discount_type ?? 'fixed';
                $discountValue = floatval($appointment->discount_value ?? 0);

                $discount = $discountType === 'percentage'
                    ? ($totalPrice * ($discountValue / 100))
                    : $discountValue;

                $totalPriceAfterDiscount = max(0, $totalPrice - $discount);

                $paid = floatval($appointment->paid ?? 0);

                $collect = ($appointment->paid === null)
                    ? $totalPriceAfterDiscount
                    : max(0, $totalPriceAfterDiscount - $paid);

                $appointment->total_price = round($totalPriceAfterDiscount, 2);
                $appointment->discount = round($discount, 2);
                $appointment->collect = round($collect, 2);

                return $appointment;
            })
            ->values();

        // ✅ Missing inventory logic
        $inventory = $this->newMissingInventory($user, $openAppointments);
        $stock = $user->stock()->with(['items', 'parts'])->first();
        if (!$stock) {
            $stock = (object)['items' => collect(), 'parts' => collect()];
        }

        $missingItems = collect($inventory['missing_items']);
        $missingParts = collect($inventory['missing_parts']);

        $filteredMissingItems = $missingItems->reject(function ($missing) use ($stock) {
            return collect($stock->items)->contains(fn($s) => $s->item_number === $missing['item_number'] && $s->quantity > 0);
        });

        $filteredMissingParts = $missingParts->reject(function ($missing) use ($stock) {
            return collect($stock->parts)->contains(fn($s) => $s->item_number === $missing['item_number'] && $s->quantity > 0);
        });

        $allMissing = $filteredMissingItems->merge($filteredMissingParts)->values();

        // ✅ Closed appointments
        $closedAppointments = $appointments->filter(function ($appointment) {
            if (in_array($appointment->status, ['complete', 'cancel', 'reschedule', 'Completed'])) {
                return $appointment->dy_completed == 1;
            }
            return in_array($appointment->status, ['cancel', 'reschedule']);
        })->values();

        // ✅ Final summary
        return [
            'open_appointments_count' => $openAppointments->count(),
            'closed_appointments_count' => $closedAppointments->count(),
            'missing_inventory' => $allMissing,
            'missing_inventory_count' => $allMissing->count(),
            'open_appointments' => $openAppointments,
            'closed_appointments' => $closedAppointments,
        ];
    }


    // new missing inventory
    public function newMissingInventory(User $user, $appointments)
    {


        $stock = $user->stock()
            ->with(['items', 'parts'])
            ->first() ?? (object)['items' => collect(), 'parts' => collect()];


        $missingItems = [];
        $missingParts = [];
        $requiredTotals = []; // track total required quantity per item_number

        // normalize $appointments collection
        if ($appointments instanceof \App\Models\Appointment) {
            $appointments = collect([$appointments]);
        }

        // Step 1: Sum total required per item_number across all appointments
        foreach ($appointments as $appointment) {
            foreach ($appointment->lines as $line) {
                $key = trim(strtolower($line->item_number));
                $requiredTotals[$key] = ($requiredTotals[$key] ?? 0) + $line->quantity;
            }
        }

        // Step 2: Loop again to calculate missing based on total requirements
        foreach ($appointments as $appointment) {
            foreach ($appointment->lines as $line) {
                $key = trim(strtolower($line->item_number));

                // handle items (line_type item or null)
                if ($line->line_type === 'item' || is_null($line->line_type)) {
                    $matchingStockItems = collect($stock?->items ?? [])
                        ->filter(function ($item) use ($line) {
                            return strcasecmp(trim($item->item_number), trim($line->item_number)) === 0
                                || strcasecmp(trim($item->item_number), trim($line->item_id)) === 0;
                        })
                        ->where('quantity', '>', 0);

                    $availableQty = $matchingStockItems->isNotEmpty()
                        ? $matchingStockItems->max('quantity')
                        : 0;

                    $itemName = $line?->item?->name ?? $line->item_number;
                    $maxQty   = $availableQty; // optional, depending on how you use it

                    // $availableQty = $stockItem?->quantity ?? 0;
                    // $itemName     = $line?->item?->name ?? $line->item_number;
                    // $maxQty       = $stockItem?->quantity ?? null;

                    $requiredQty = $requiredTotals[$key] ?? 0;
                    $shortage    = max(0, $requiredQty - $availableQty);

                    if ($shortage > 0) {
                        if (!isset($missingItems[$key])) {
                            $missingItems[$key] = [
                                'appointment_id'     => $appointment->id,
                                'item_id'            => $line->line_id,
                                'item_number'        => $line->item_number,
                                'item_name'          => $itemName,
                                'required'           => $requiredQty,
                                'available'          => $availableQty,
                                'shortage'           => $shortage,
                                'item_quantity_max'  => $maxQty,
                            ];
                        }
                    }
                }
                // handle parts
                elseif ($line->line_type === 'part') {
                    $stockPart = $stock?->parts
                        ->filter(fn($part) => strcasecmp(trim($part->item_number), $line->item_number) === 0)
                        ->where('quantity', '>', 0)
                        ->first();

                    $availableQty = $stockPart?->quantity ?? 0;
                    $partName     = $line?->part?->name ?? $line->item_number;
                    $maxQty       = $stockPart?->max_quantity ?? null;

                    $requiredQty = $requiredTotals[$key] ?? 0;
                    $shortage    = max(0, $requiredQty - $availableQty);

                    if ($shortage > 0) {
                        if (!isset($missingParts[$key])) {
                            $missingParts[$key] = [
                                'appointment_id'     => $appointment->id,
                                'part_id'            => $line->line_id,
                                'item_number'        => $line->item_number,
                                'item_name'          => $partName,
                                'required'           => $requiredQty,
                                'available'          => $availableQty,
                                'shortage'           => $shortage,
                                'item_quantity_max'  => $maxQty,
                            ];
                        }
                    }
                }
            }
        }

        // reset keys to numeric arrays
        // return [
        //     'missing_items' => array_values($missingItems),
        //     'missing_parts' => array_values($missingParts),
        // ];
        return [
            "missing_items" => [],
            "missing_parts" => []
        ];
    }




    public function getTechStock(User $user, $search = null, $filter = null)
    {
        $query = UserStock::where('user_id', $user->id);

        // if ($filter === 'item') {
        //     $query->with([
        //         'items' => function ($q) use ($search) {
        //             $q->when($search, function ($q) use ($search) {
        //                 $q->whereHas('item', function ($q) use ($search) {
        //                     $q->where(function ($sub) use ($search) {
        //                         $sub->where('name', 'like', '%' . $search . '%')
        //                             ->orWhere('item_number', 'like', '%' . $search . '%');
        //                     });
        //                 });
        //             })->with('item.category');
        //         }
        //     ]);
        // } elseif ($filter === 'part') {
        //     $query->with([
        //         'parts' => function ($q) use ($search) {
        //             $q->when($search, function ($q) use ($search) {
        //                 $q->whereHas('part', function ($q) use ($search) {
        //                     $q->where(function ($sub) use ($search) {
        //                         $sub->where('name', 'like', '%' . $search . '%')
        //                             ->orWhere('item_number', 'like', '%' . $search . '%');
        //                     });
        //                 });
        //             })->with('part.category');
        //         }
        //     ]);
        // } else {
        //     // Load both if no filter is specified
        //     $query->with([
        //         'items' => function ($q) use ($search) {
        //             $q->when($search, function ($q) use ($search) {
        //                 $q->whereHas('item', function ($q) use ($search) {
        //                     $q->where(function ($sub) use ($search) {
        //                         $sub->where('name', 'like', '%' . $search . '%')
        //                             ->orWhere('item_number', 'like', '%' . $search . '%');
        //                     });
        //                 });
        //             })->with('item.category');
        //         },
        //         'parts' => function ($q) use ($search) {
        //             $q->when($search, function ($q) use ($search) {
        //                 $q->whereHas('part', function ($q) use ($search) {
        //                     $q->where(function ($sub) use ($search) {
        //                         $sub->where('name', 'like', '%' . $search . '%')
        //                             ->orWhere('item_number', 'like', '%' . $search . '%');
        //                     });
        //                 });
        //             })->with('part.category');
        //         }
        //     ]);
        // }

        return $query->first();
    }



    public function getSummaryBetweenDates(User $user, ?string $from = null, ?string $to = null): array
    {
        $from = $from ? Carbon::parse($from)->startOfDay() : null;
        $to = $to ? Carbon::parse($to)->endOfDay() : null;

        // If only one is provided, use it for both
        if (!$from && $to) {
            $from = Carbon::parse($to)->startOfDay();
        } elseif ($from && !$to) {
            $to = Carbon::parse($from)->endOfDay();
        } elseif (!$from && !$to) {
            throw ValidationException::withMessages([
                'date' => ['You must provide at least one date (from or to).']
            ]);
        }


        $appointments = $user->technicianAppointments()
            ->whereBetween('appointment_date', [$from, $to])
            ->with('items')
            ->get();




        $stock = $user->stock?->load('items');
        $stockItems = $stock?->items ?? collect();
        $stockMap = $stockItems->keyBy('item_id');

        $appointmentItems =  $appointments->flatMap(fn($a) => $a->lines);
        $requiredItems = $appointmentItems
            ->filter(fn($line) => $line->line_type === 'item' || is_null($line->line_type)) // include items or null type
            ->groupBy('line_id')
            ->map(function ($lines) {
                return [
                    'item_id'            => $lines->first()->line_id,
                    'name'               => $lines->first()?->item?->name,
                    'code'               => $lines->first()?->item?->item_number,
                    'serial'             => $lines->first()?->serial ?? null,
                    'required_quantity'  => $lines->sum('quantity'),
                ];
            })->values();
        $missingItems = $requiredItems->filter(function ($item) use ($stockMap) {
            $stockItem = $stockMap->get($item['item_id']);
            return !$stockItem || $stockItem->quantity < $item['required_quantity'];
        })->map(function ($item) use ($stockMap) {
            $stockQty = $stockMap->get($item['item_id'])->quantity ?? 0;
            return [
                'item_id' => $item['item_id'],
                'name' => $item['name'],
                'code' => $item['code'],
                'required_quantity' => $item['required_quantity'],
                'stock_quantity' => $stockQty,
                'missing_quantity' => $item['required_quantity'] - $stockQty,
            ];
        })->values();

        //new

        $summaryStatus = [
            'pending' => $appointments
                ->whereIn('status', ['pending', 'Delayed', 'Scheduled'])
                ->count(),
            'completed' => $appointments
                ->whereIn('status', ['complete', 'Completed'])
                ->count(),
            'reschedule' => $appointments->where('status', 'reschedule')->count(),
            'cancelled' => $appointments->where('status', 'cancelled')->count(),
            'on_the_way' => $appointments->where('status', 'on_the_way')->count(),
            'onsite' => $appointments->where('status', 'onsite')->count(),
        ];

        $summaryStatus['total_open_appointment'] =
            $summaryStatus['pending'] +
            $summaryStatus['on_the_way'] +
            $summaryStatus['onsite'];

        $summaryStatus['total_closed_appointment'] =
            $summaryStatus['completed'] +
            $summaryStatus['cancelled'] +
            $summaryStatus['reschedule'];

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'summary_appointment' => $summaryStatus,
            'total_pieces' =>    $requiredItems->count(),
            'items' =>    $requiredItems,
            // 'appointments_count' => $appointments->count(),
            // 'missing_items' => $missingItems,
            // 'installation_appointments' => $appointments->where('type', 'installation')->values(),
            // 'periodic_appointments' => $appointments->where('type', 'periodic')->values(),
            // 'emergency_appointments' => $appointments->where('type', 'emergency')->values(),
        ];
    }


    public function missingInventory($user, $appointments)
    {
        $appointments = $appointments instanceof Collection ? $appointments : collect([$appointments]);

        // Load user stock
        $stock = $user->stock?->load(['items', 'parts']);
        $stockItems = $stock?->items ?? collect();
        $stockParts = $stock?->parts ?? collect();
        $stockItemsMap = $stockItems->keyBy('item_id');
        $stockPartsMap = $stockParts->keyBy('part_id');

        // Split appointments by type
        $installationAppointments = $appointments->filter(fn($a) => $a->type === 'installation');
        $emergencyAppointments = $appointments->filter(fn($a) => $a->type === 'emergency');
        $periodicAppointments = $appointments->filter(fn($a) => $a->type === 'periodic');

        // --- Installation Items ---
        $appointmentItems = $installationAppointments->flatMap(fn($a) => $a->items);

        $requiredItems = $appointmentItems->groupBy('item_id')->map(function ($items) {
            return [
                'item_id' => $items->first()->item_id,
                'name' => $items->first()->name,
                'code' => $items->first()->code,
                'image' => $items->first()->image,
                'serial' => $items->first()->serial,
                'required_quantity' => $items->sum('quantity'),
            ];
        })->values();

        $missingItems = $requiredItems->filter(function ($item) use ($stockItemsMap) {
            $stockItem = $stockItemsMap->get($item['item_id']);
            return !$stockItem || $stockItem->quantity < $item['required_quantity'];
        })->map(function ($item) use ($stockItemsMap) {
            $stockQty = $stockItemsMap->get($item['item_id'])->quantity ?? 0;
            return [
                'id' => $item['item_id'],
                'name' => $item['name'],
                'code' => $item['code'],
                'serial' => $item['serial'],
                'image' => $item['image'],
                'required_quantity' => $item['required_quantity'],
                'stock_quantity' => $stockQty,
                'missing_quantity' => $item['required_quantity'] - $stockQty,
            ];
        })->values();

        // --- All Parts: Installation + Emergency + Periodic ---
        $installationParts = $installationAppointments->flatMap(fn($a) => $a->parts);
        $emergencyParts = $emergencyAppointments->flatMap(fn($a) => $a->emergencyItems->flatMap->parts);
        $periodicParts = $periodicAppointments
            ->flatMap(fn($a) => $a->periodicItems)
            ->flatMap(fn($item) => $item->parts);

        $allParts = $installationParts->merge($emergencyParts)->merge($periodicParts);

        $requiredParts = $allParts->groupBy('part_id')->map(function ($parts) {
            return [
                'part_id' => $parts->first()->part_id,
                'name' => $parts->first()->name,
                'code' => $parts->first()->code,
                'serial' => $parts->first()->serial,
                'image' => $parts->first()->image,
                'required_quantity' => $parts->sum('quantity'),
            ];
        });

        $missingParts = $requiredParts->filter(function ($part) use ($stockPartsMap) {
            $stockPart = $stockPartsMap->get($part['part_id']);
            return !$stockPart || $stockPart->quantity < $part['required_quantity'];
        })->map(function ($part) use ($stockPartsMap) {
            $stockQty = $stockPartsMap->get($part['part_id'])->quantity ?? 0;
            return [
                'id' => $part['part_id'],
                'name' => $part['name'],
                'serial' => $part['serial'],
                'code' => $part['code'],
                'image' => $part['image'],
                'required_quantity' => $part['required_quantity'],
                'stock_quantity' => $stockQty,
                'missing_quantity' => $part['required_quantity'] - $stockQty,
            ];
        })->values();

        return [
            'missing_items' => $missingItems,  // only from installation
            'missing_parts' => $missingParts,  // from installation + emergency + periodic
        ];
    }

    // missingAppointmentLinesInventory
    public function missingAppointmentLinesInventory($user, $appointments)
    {
        $appointments = $appointments instanceof Collection ? $appointments : collect([$appointments]);

        // Load user stock
        $stock = $user->stock?->load(['items', 'parts']);
        $stockItems = $stock?->items ?? collect();
        $stockParts = $stock?->parts ?? collect();
        $stockItemsMap = $stockItems->keyBy('item_id');
        $stockPartsMap = $stockParts->keyBy('part_id');

        // Flatten all appointment lines
        $lines = $appointments->flatMap->lines;

        // --- Required Items ---
        $requiredItems = $lines->where('line_type', 'item')
            ->groupBy('line_id')
            ->map(function ($group) {
                $first = $group->first();
                $item = \App\Models\Item::find($first->line_id);

                return [
                    'item_id' => $first->line_id,
                    'name' => $item?->name,
                    'code' => $item?->code,
                    'image' => $item?->image,
                    'serial' => $item?->serial,
                    'required_quantity' => $group->sum('quantity'),
                ];
            })->values();

        $missingItems = $requiredItems->filter(function ($item) use ($stockItemsMap) {
            $stock = $stockItemsMap->get($item['item_id']);
            return !$stock || $stock->quantity < $item['required_quantity'];
        })->map(function ($item) use ($stockItemsMap) {
            $stockQty = $stockItemsMap->get($item['item_id'])->quantity ?? 0;
            return [
                'id' => $item['item_id'],
                'name' => $item['name'],
                'code' => $item['code'],
                'serial' => $item['serial'],
                'image' => $item['image'],
                'required_quantity' => $item['required_quantity'],
                'stock_quantity' => $stockQty,
                'missing_quantity' => $item['required_quantity'] - $stockQty,
            ];
        })->values();

        // --- Required Parts ---
        $requiredParts = $lines->where('line_type', 'part')
            ->groupBy('line_id')
            ->map(function ($group) {
                $first = $group->first();
                $part = \App\Models\Part::find($first->line_id);

                return [
                    'part_id' => $first->line_id,
                    'name' => $part?->name,
                    'code' => $part?->code,
                    'image' => $part?->image,
                    'serial' => $part?->serial,
                    'required_quantity' => $group->sum('quantity'),
                ];
            })->values();

        $missingParts = $requiredParts->filter(function ($part) use ($stockPartsMap) {
            $stock = $stockPartsMap->get($part['part_id']);
            return !$stock || $stock->quantity < $part['required_quantity'];
        })->map(function ($part) use ($stockPartsMap) {
            $stockQty = $stockPartsMap->get($part['part_id'])->quantity ?? 0;
            return [
                'id' => $part['part_id'],
                'name' => $part['name'],
                'code' => $part['code'],
                'serial' => $part['serial'],
                'image' => $part['image'],
                'required_quantity' => $part['required_quantity'],
                'stock_quantity' => $stockQty,
                'missing_quantity' => $part['required_quantity'] - $stockQty,
            ];
        })->values();

        return [
            'missing_items' => $missingItems,
            'missing_parts' => $missingParts,
        ];
    }

    // tech notifications

    public function getAllForUser($user)
    {
        return $user->notifications()->latest()->get();
    }

    public function markAllAsRead($user)
    {
        return $user->unreadNotifications->markAsRead();
    }

    public function markAsRead($user, $notificationId)
    {
        $notification = $user->notifications()->findOrFail($notificationId);
        return $notification->markAsRead();
    }

    // create or update Technicians data from the dy365
    public function syncTechnicians(array $technicians): void
    {
        DB::beginTransaction();

        try {
            foreach ($technicians as $tech) {
                // إنشاء أو تحديث بيانات الفني
                $user = User::updateOrCreate(
                    [
                        'technician_rec_id' => $tech['TechnicianRecId'],
                        'type'    => 'tech',
                    ],
                    [
                        'warehouse_id'     => $tech['WarehouseId'],
                        'personnel_number' => $tech['PersonnelNumber'],
                        'tech_id'          => $tech['TechnicianRecId'],
                        'username'         => $tech['Username'],
                        'email'            => $tech['Email'],
                        'phone'            => $tech['Phone'],
                        'pin_code'         => bcrypt($tech['PINCode']),
                        'type'             => 'tech',
                        'image'            => $tech['Image'],
                        'password'         => bcrypt($tech['Password']),
                        'status'           => $tech['Status'],
                    ]
                );
                // $user->update(['username' => $tech['Username']] ?? $user->username ?? null);

                // التعامل مع MainWarehouseId لو موجود
                $warehouseIds = [];

                if (!empty($tech['MainWarehouses']) && is_array($tech['MainWarehouses'])) {
                    foreach ($tech['MainWarehouses'] as $mainWarehouse) {
                        if (isset($mainWarehouse['MainWarehouseId']) && !empty($mainWarehouse['MainWarehouseId'])) {
                            $warehouse = Warehouse::firstOrCreate(
                                ['invent_location_id' => $mainWarehouse['MainWarehouseId']],
                                [
                                    'name' => $mainWarehouse['MainWarehouseId'],
                                    'type' => 'MainWarehouse',
                                ]
                            );

                            $warehouseIds[] = $warehouse->id;
                        }
                    }
                }

                // لو لقيت warehouses اربطهم، وهيلغي أي ربط قديم
                if (!empty($warehouseIds)) {
                    $user->warehouses()->sync($warehouseIds);
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception("Technician sync failed at line {$e->getLine()} in {$e->getFile()}: " . $e->getMessage());
        }
    }

    // create or update Customers data from the dy365
    public function syncCustomers(array $customers): void
    {
        DB::beginTransaction();

        try {
            foreach ($customers as $customer) {
                User::updateOrCreate(
                    [
                        'technician_rec_id' => $customer['CustomerRecId'],
                        'type'    => 'customer',
                    ],
                    [
                        'username' => $customer['Name'],
                        'phone'    => $customer['Phone'] ?? null,
                        'email'    => $customer['Email'] ?? null,
                        'type'     => 'customer',
                        'image'    => $customer['Image'] ?? null,
                        'address'  => $customer['Address'] ?? null,
                        'status'   => 'active',
                    ]
                );
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception("Customer sync failed: " . $e->getMessage());
        }
    }
}
