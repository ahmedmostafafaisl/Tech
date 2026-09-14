<?php

namespace Database\Seeders;



use Carbon\Carbon;
use App\Models\Item;
use App\Models\Part;
use App\Models\User;
use App\Models\Address;
use App\Models\Invoice;
use App\Models\Appointment;
use App\Models\InvoiceItem;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Models\AppointmentItem;
use App\Models\AppointmentPart;
use Illuminate\Database\Seeder;
use App\Models\PeriodicMainItem;
use App\Models\EmergencyMainItem;
use App\Models\PeriodicMainItemPart;
use App\Models\EmergencyMainItemPart;


class AppointmentSeeder extends Seeder
{
    public function run(): void
    {
        $technicians = User::where('type', 'tech')->get();
        $customer = User::where('type', 'customer')->inRandomOrder()->first();
        $items = Item::inRandomOrder()->take(10)->get();
        $parts = Part::inRandomOrder()->take(10)->get();

        if ($technicians->isEmpty() || !$customer || $items->isEmpty() || $parts->isEmpty()) {
            echo "Make sure you have techs, customers, items, and parts.\n";
            return;
        }

        foreach ($technicians as $technician) {
            $address = $customer->addresses()->inRandomOrder()->first();
            if (!$address) {
                echo "Customer has no address.\n";
                continue;
            }

            $this->createAppointment($technician, $customer, Carbon::today(), $items, $parts, $address->id);

            for ($i = 0; $i < 4; $i++) {
                $randomDate = Carbon::today()->subDays(rand(1, 3))->addDays(rand(0, 4));
                $this->createAppointment($technician, $customer, $randomDate, $items, $parts, $address->id);
            }
        }
    }

    protected function createAppointment($technician, $customer, $date, $items, $parts, $addressId)
    {
        $appointmentItems = [];
        $invoiceItems = [];
        $itemMap = [];
        $subTotal = 0;

        // Decide discount type for appointment
        $appointmentDiscountType = Arr::random(['fixed', 'percentage']);
        $appointmentDiscountValue = rand(10, 50);

        // Group and accumulate 3 random items with their own discount type and value
        for ($i = 0; $i < 3; $i++) {
            $item = $items->random();
            $itemId = $item->id;
            $unitPrice = $item->price ?? rand(10, 100);
            $qty = rand(1, 5);
            $itemSubTotal = $unitPrice * $qty;

            // Item discount type and value
            $itemDiscountType = Arr::random(['fixed', 'percentage']);
            $itemDiscountValue = $itemDiscountType === 'fixed'
                ? rand(0, $itemSubTotal) // fixed discount cannot exceed itemSubTotal
                : rand(0, 100);          // percentage discount max 100%

            // Calculate item discount amount
            $itemDiscountAmount = $itemDiscountType === 'fixed'
                ? $itemDiscountValue
                : round(($itemDiscountValue / 100) * $itemSubTotal, 2);

            // Final item price after discount
            $itemFinalPrice = max($itemSubTotal - $itemDiscountAmount, 0);

            if (isset($itemMap[$itemId])) {
                $itemMap[$itemId]['quantity'] += $qty;
                $itemMap[$itemId]['sub_total_price'] += $itemSubTotal;
                $itemMap[$itemId]['discount'] += $itemDiscountAmount;
                $itemMap[$itemId]['total_price'] += $itemFinalPrice;
                // For simplicity, keep discount_type and discount_value from first occurrence or adjust logic if needed
            } else {
                $itemMap[$itemId] = [
                    'item_id' => $itemId,
                    'serial' => $item->serial ?? Str::random(10),
                    'floor' => '1st',
                    'apart' => 'A1',
                    'room' => 'Living Room',
                    'code' => 'ITEM' . rand(1000, 9999),
                    'name' => $item->name ?? 'Item ' . $i,
                    'quantity' => $qty,
                    'description' => 'Description for item ' . $i,
                    'image' => null,
                    'sub_total_price' => $itemSubTotal,
                    'discount' => $itemDiscountAmount,
                    'discount_type' => $itemDiscountType,
                    'discount_value' => $itemDiscountValue,
                    'price' => $unitPrice,
                    'total_price' => $itemFinalPrice,
                    'status' => 'pending',
                    'payment_type' => 'cash',
                    'payment_status' => 'pending',
                ];
            }
        }

        // Calculate subtotal and prepare arrays
        foreach ($itemMap as $itemData) {
            $appointmentItems[] = $itemData;

            $invoiceItems[] = [
                'item_id' => $itemData['item_id'],
                'item_name' => $itemData['name'],
                'item_qty' => $itemData['quantity'],
                'item_price' => $itemData['price'],
                'item_sub_total_price' => $itemData['sub_total_price'],
                'item_discount' => $itemData['discount'],
                'discount_type' => $itemData['discount_type'],
                'discount_value' =>  $itemData['discount_value'],
                'item_total_price' => $itemData['total_price'],
            ];

            $subTotal += $itemData['total_price'];
        }

        // Validate and calculate appointment discount amount
        if ($appointmentDiscountType === 'fixed') {
            // fixed discount cannot be greater than subtotal
            $appointmentDiscountValue = min($appointmentDiscountValue, $subTotal);
            $appointmentDiscountAmount = $appointmentDiscountValue;
        } else {
            // percentage discount max 100%
            $appointmentDiscountValue = min($appointmentDiscountValue, 100);
            $appointmentDiscountAmount = round(($appointmentDiscountValue / 100) * $subTotal, 2);
        }

        $total = max($subTotal - $appointmentDiscountAmount, 0);
        $vatAmount = round(($total * 15) / 115, 2); // extract VAT

        // Create appointment record with discount info
        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'technician_id' => $technician->id,
            'type' => 'installation',
            'phone' => '0500000000',
            'whats_app' => '0500000001',
            'alternative_number' => '0500000005',
            'address_id' => $addressId,
            'address' => 'Sample Address',
            'latitude' => '24.7136',
            'longitude' => '46.6753',
            'branch' => 'Main Branch',
            'sector' => 'Sector A',
            'appointment_date' => $date,
            'appointment_time' => '14:00',
            'sub_total_price' => $subTotal,
            'discount' => $appointmentDiscountAmount,
            'discount_type' => $appointmentDiscountType,
            'discount_value' => $appointmentDiscountValue,
            'total_price' => $total,
            'paid' => 0,
            'collect' => $total,
            'status' => 'pending',
            'service_type' => 'pending'
        ]);

        // Save appointment items with discount info
        foreach ($appointmentItems as $itemData) {
            AppointmentItem::create(array_merge($itemData, [
                'appointment_id' => $appointment->id
            ]));
        }

        // Create invoice
        $invoice = \App\Models\Invoice::create([
            'appointment_id' => $appointment->id,
            'invoice_status' => Arr::random(['pending', 'paid', 'unpaid']),
            'sub_total' => $subTotal,
            'discount' => $appointmentDiscountAmount,
            'discount_type' => $appointmentDiscountType,
            'discount_value' => $appointmentDiscountValue,
            'vat' => $vatAmount,
            'total' => $total,
            'invoice_url_pdf' => 'https://example.com/invoices/' . $appointment->id . '.pdf',
        ]);

        // Add invoice items
        foreach ($invoiceItems as $item) {
            \App\Models\InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'item_id' => $item['item_id'],
                'item_name' => $item['item_name'],
                'item_qty' => $item['item_qty'],
                'item_price' => $item['item_price'],
                'item_sub_total_price' => $item['item_sub_total_price'],
                'item_discount' => $item['item_discount'],
                'discount_type' =>  $item['discount_type'],
                'discount_value' => $item['discount_value'],
                'item_total_price' => $item['item_total_price'],
            ]);
        }

        // Generate emergency appointments
        foreach ($appointment->items as $installItem) {
            $emergencyAppointment = Appointment::create([
                'customer_id' => $appointment->customer_id,
                'technician_id' => $appointment->technician_id,
                'type' => 'emergency',
                'service_type' => 'emergency',
                'phone' => $appointment->phone,
                'whats_app' => $appointment->whats_app,
                'alternative_number' => $appointment->alternative_number,
                'address_id' => $appointment->address_id,
                'address' => $appointment->address,
                'latitude' => $appointment->latitude,
                'longitude' => $appointment->longitude,
                'branch' => $appointment->branch,
                'sector' => $appointment->sector,
                'appointment_date' => now()->addDays(1),
                'appointment_time' => '15:00',
                'status' => 'pending',
                'paid' => 0,
                'collect' => 0,
                'sub_total_price' => 0,
                'discount' => 0,
                'discount_type' => 'fixed',
                'discount_value' => 0,
                'total_price' => 0,
            ]);

            $emergencyMainItem = EmergencyMainItem::create([
                'appointment_id' => $emergencyAppointment->id,
                'item_id' => $installItem->item_id,
                'serial' => $installItem->serial,
                'floor' => $installItem->floor,
                'apart' => $installItem->apart,
                'room' => $installItem->room,
                'code' => $installItem->code,
                'name' => $installItem->name,
                'price' => $installItem->price,
                'quantity' => $installItem->quantity,
                'description' => $installItem->description,
                'image' => $installItem->image,
                'check_list' => json_encode(['checked' => true]),
                'valid_warranty' => true,
                'paid_service' => false,
                'missing' => false,
                'issues_reported_from_client' => json_encode(['issue_1']),
                'item_form_type' => null,
                'item_form_status' => 'pending',
            ]);

            $partsTotal = 0;
            for ($j = 0; $j < 2; $j++) {
                $part = Part::inRandomOrder()->first();
                $unitPrice = $part->price ?? rand(10, 40);
                $qty = rand(1, 3);
                $totalPrice = $unitPrice * $qty;
                $partsTotal += $totalPrice;

                EmergencyMainItemPart::create([
                    'emergency_main_item_id' => $emergencyMainItem->id,
                    'appointment_id' => $emergencyAppointment->id,
                    'part_id' => $part->id,
                    'serial' => $part->serial ?? Str::random(10),
                    'floor' => '2nd',
                    'apart' => 'B2',
                    'room' => 'Utility Room',
                    'code' => $part->code ?? 'PART' . rand(1000, 9999),
                    'name' => $part->name ?? 'Part ' . $j,
                    'quantity' => $qty,
                    'description' => 'Used in emergency',
                    'image' => null,
                    'price' => $unitPrice,
                    'sub_total_price' => $totalPrice,
                    'discount' => 0,
                    'discount_type' => 'fixed',
                    'discount_value' => 0,
                    'total_price' => $totalPrice,
                    'status' => 'pending',
                    'payment_type' => 'cash',
                    'payment_status' => 'pending',

                ]);
            }

            $emergencyAppointment->update([
                'sub_total_price' => $partsTotal,
                'total_price' => $partsTotal,
                'collect' => $partsTotal,
            ]);

            Invoice::create([
                'appointment_id' => $emergencyAppointment->id,
                'invoice_status' => 'pending',
                'sub_total' => $partsTotal,
                'discount' => 0,
                'discount_type' => 'fixed',
                'discount_value' => 0,
                'vat' => round(($partsTotal * 15) / 115, 2),
                'total' => $partsTotal,
                'invoice_url_pdf' => 'https://example.com/emergency-invoices/' . $emergencyAppointment->id . '.pdf',
            ]);
        }

        // Generate Periodic appointments
        foreach ($appointment->items as $installItem) {
            $periodicAppointment = Appointment::create([
                'customer_id' => $appointment->customer_id,
                'technician_id' => $appointment->technician_id,
                'type' => 'periodic',
                'service_type' => Arr::random(["1 year", "2 year", "6 months", "18 months", "3 months",]),
                'phone' => $appointment->phone,
                'whats_app' => $appointment->whats_app,
                'alternative_number' => $appointment->alternative_number,
                'address_id' => $appointment->address_id,
                'address' => $appointment->address,
                'latitude' => $appointment->latitude,
                'longitude' => $appointment->longitude,
                'branch' => $appointment->branch,
                'sector' => $appointment->sector,
                'appointment_date' => now()->addDays(1),
                'appointment_time' => '15:00',
                'status' => 'pending',
                'paid' => 0,
                'collect' => 0,
                'sub_total_price' => 0,
                'discount' => 0,
                'discount_type' => 'fixed',
                'discount_value' => 0,
                'total_price' => 0,
            ]);

            $periodicMainItem = PeriodicMainItem::create([
                'appointment_id' => $periodicAppointment->id,
                'item_id' => $installItem->item_id,
                'serial' => $installItem->serial,
                'floor' => $installItem->floor,
                'apart' => $installItem->apart,
                'room' => $installItem->room,
                'code' => $installItem->code,
                'name' => $installItem->name,
                'price' => $installItem->price,
                'quantity' => $installItem->quantity,
                'description' => $installItem->description,
                'image' => $installItem->image,
                'check_list' => json_encode(['checked' => true]),
                'valid_warranty' => true,
                'paid_service' => true,
                'missing' => false,
                'maintenance_type' => $periodicAppointment->service_type,

            ]);

            $partsTotal = 0;
            for ($j = 0; $j < 2; $j++) {
                $part = Part::inRandomOrder()->first();
                $unitPrice = $part->price ?? rand(10, 40);
                $qty = rand(1, 3);
                $totalPrice = $unitPrice * $qty;
                $partsTotal += $totalPrice;

                PeriodicMainItemPart::create([
                    'periodic_main_item_id' => $periodicMainItem->id,
                    'appointment_id' => $periodicAppointment->id,
                    'part_id' => $part->id,
                    'serial' => $part->serial ?? Str::random(10),
                    'floor' => '2nd',
                    'apart' => 'B2',
                    'room' => 'Utility Room',
                    'code' => $part->code ?? 'PART' . rand(1000, 9999),
                    'name' => $part->name ?? 'Part ' . $j,
                    'quantity' => $qty,
                    'description' => 'Desc in periodic',
                    'image' => null,
                    'price' => $unitPrice,
                    'sub_total_price' => $totalPrice,
                    'discount' => 0,
                    'discount_type' => 'fixed',
                    'discount_value' => 0,
                    'total_price' => $totalPrice,
                    'status' => 'pending',
                    'payment_type' => 'cash',
                    'payment_status' => 'pending',

                ]);
            }

            $periodicAppointment->update([
                'sub_total_price' => $partsTotal,
                'total_price' => $partsTotal,
                'collect' => $partsTotal,
            ]);

            Invoice::create([
                'appointment_id' => $periodicAppointment->id,
                'invoice_status' => 'pending',
                'sub_total' => $partsTotal,
                'discount' => 0,
                'discount_type' => 'fixed',
                'discount_value' => 0,
                'vat' => round(($partsTotal * 15) / 115, 2),
                'total' => $partsTotal,
                'invoice_url_pdf' => 'https://example.com/emergency-invoices/' . $emergencyAppointment->id . '.pdf',
            ]);
        }
        // Example for parts (optional, uncomment if needed)
        /*
        for ($i = 0; $i < 2; $i++) {
            $part = $parts->random();
            $unitPrice = $part->price ?? rand(5, 30);
            $qty = rand(1, 3);
            $partSubTotal = $unitPrice * $qty;

            $partDiscountType = Arr::random(['fixed', 'percentage']);
            $partDiscountValue = $partDiscountType === 'fixed'
                ? rand(0, $partSubTotal)
                : rand(0, 100);

            $partDiscountAmount = $partDiscountType === 'fixed'
                ? $partDiscountValue
                : round(($partDiscountValue / 100) * $partSubTotal, 2);

            $partFinalPrice = max($partSubTotal - $partDiscountAmount, 0);

            AppointmentPart::create([
                'appointment_id' => $appointment->id,
                'part_id' => $part->id,
                'serial' => $part->serial ?? Str::random(10),
                'floor' => '2nd',
                'apart' => 'B2',
                'room' => 'Kitchen',
                'code' => $part->code ?? 'PART' . rand(1000, 9999),
                'name' => $part->name ?? 'Part ' . $i,
                'quantity' => $qty,
                'description' => 'Description for part ' . $i,
                'image' => null,
                'sub_total_price' => $partSubTotal,
                'discount' => $partDiscountAmount,
                'discount_type' => $partDiscountType,
                'discount_value' => $partDiscountValue,
                'price' => $unitPrice,
                'total_price' => $partFinalPrice,
                'status' => 'pending',
                'payment_type' => 'cash',
                'payment_status' => 'pending',
            ]);
        }
        */
    }
}



//
