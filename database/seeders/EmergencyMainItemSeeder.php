<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\Part;
use App\Models\User;
use App\Models\Appointment;
use Illuminate\Support\Arr;
use Illuminate\Database\Seeder;
use App\Models\EmergencyMainItem;
use App\Models\EmergencyMainItemPart;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class EmergencyMainItemSeeder extends Seeder
{
    public function run(): void
    {
        // Get sample data
        $items = Item::all();
        $parts = Part::all();
        $customers = User::where('type', 'customer')->get(); // adjust if needed
        $technicians = User::where('type', 'tech')->get(); // adjust if needed

        // Create 5 emergency appointments
        for ($i = 0; $i < 5; $i++) {
            $customer = $customers->random();
            $technician = $technicians->random();

            $appointment = Appointment::create([
                'customer_id' => $customer->id,
                'technician_id' => $technician->id,
                'appointment_num' => 'APPT-' . rand(10000, 99999),
                'type' => 'emergency',
                'phone' => '050' . rand(1000000, 9999999),
                'whats_app' => '050' . rand(1000000, 9999999),
                'appointment_date' => now()->addDays(rand(0, 1)),
                'status' => 'pending',
                'billing_status' => 'unpaid',
                'maintenance_type' => 'urgent',
            ]);

            // Select a real item from the DB
            $item = $items->random();

            // Create EmergencyMainItem
            $mainItem = EmergencyMainItem::create([
                'appointment_id' => $appointment->id,
                'item_id' => $item->id,
                'serial' => 'SERIAL-' . rand(1000, 9999),
                'floor' => rand(1, 5),
                'apart' => rand(101, 999),
                'room' => 'Room ' . rand(1, 10),
                'code' => 'CODE-' . rand(1000, 9999),
                'name' => $item->name,
                'quantity' => rand(1, 3),
                'description' => $item->description,
                'image' => null,
                'price' => $item->price,
                'sub_total_price' => $item->price,
                'discount' => 0,
                'discount_type' => 'fixed',
                'discount_value' => 0,
                'total_price' => $item->price,
                'status' => 'pending',
                'payment_type' => 'cash',
                'payment_status' => 'unpaid',
                'check_list' => json_encode(['checked' => true]),
                'valid_warranty' => true,
                'paid_service' => false,
                'missing' => false,
                'issues_reported_from_client' => json_encode(['issue_1']),
                'item_form_type' => Arr::random([
                    'return_for_refund',
                    'collect_for_maintenance',
                    'replace_with_new',
                ]),
                'item_form_status' => 'pending',
            ]);

            // Attach 1–3 parts
            foreach ($parts->random(rand(1, 3)) as $part) {
                EmergencyMainItemPart::create([
                    'emergency_main_item_id' => $mainItem->id,
                    'appointment_id' => $appointment->id,
                    'part_id' => $part->id,
                    'serial' => 'PART-' . rand(1000, 9999),
                    'floor' => rand(1, 5),
                    'apart' => rand(101, 999),
                    'room' => 'Part Room ' . rand(1, 5),
                    'code' => 'PART-CODE-' . rand(1000, 9999),
                    'name' => $part->name,
                    'quantity' => rand(1, 3),
                    'description' => $part->description,
                    'image' => null,
                    'price' => $part->price,
                    'sub_total_price' => $part->price,
                    'discount' => 0,
                    'discount_value' => 0,
                    'discount_type' => 'fixed',
                    'total_price' => $part->price,
                    'status' => 'pending',
                    'payment_type' => 'cash',
                    'payment_status' => 'unpaid',
                ]);
            }
        }
    }
}
