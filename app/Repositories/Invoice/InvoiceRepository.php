<?php


namespace App\Repositories\Invoice;



use App\Models\Invoice;
use App\Helper\ApiResponseHelper;
use App\Http\Resources\Invoice\InvoiceResource;
use App\Repositories\Interfaces\InvoiceRepositoryInterface;

class InvoiceRepository implements InvoiceRepositoryInterface
{
    use ApiResponseHelper;
    public function all()
    {
        return Invoice::with('items')->get();
    }

    public function find($id)
    {
        return Invoice::with('items')->findOrFail($id);
    }

    public function create(array $data)
    {
        $invoice = Invoice::create($data);
        if (!empty($data['invoice_items'])) {
            $invoice->items()->createMany($data['invoice_items']);
        }
        return $invoice->load('items');
    }

    public function update($id, array $data)
    {
        $invoice = Invoice::findOrFail($id);
        $invoice->update($data);
        if (!empty($data['invoice_items'])) {
            $invoice->items()->delete();
            $invoice->items()->createMany($data['invoice_items']);
        }
        return $invoice->load('items');
    }

    public function delete($id)
    {
        $invoice = Invoice::findOrFail($id);
        $invoice->items()->delete();
        return $invoice->delete();
    }

    public function getByAppointment($appointmentId)
    {
        $invoices = Invoice::with('items')
            ->where('appointment_id', $appointmentId)
            ->where('invoice_status', 'paid')
            ->get();

        $totalSum = $invoices->sum('total');
        return $this->setCode(code: 200)->setData([
            'total_sum' => $totalSum,
            'invoices' => InvoiceResource::collection($invoices),
        ])->setMessage('Success.')->send();
    }
}
