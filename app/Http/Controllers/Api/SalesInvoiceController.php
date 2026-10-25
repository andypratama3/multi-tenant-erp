<?php

namespace App\Http\Controllers\Api;

use App\Domain\Sales\Actions\IssueSalesInvoiceAction;
use App\Domain\Sales\Actions\IssueSalesInvoiceData;
use App\Domain\Sales\Actions\RecordPaymentAction;
use App\Domain\Sales\Actions\RecordPaymentData;
use App\Domain\Sales\Models\SalesInvoice;
use App\Domain\Sales\Pdfs\SalesInvoicePdf;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\IssueInvoiceRequest;
use App\Http\Requests\Api\RecordPaymentRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @group Sales Invoices
 *
 * APIs for managing sales invoices.
 */
class SalesInvoiceController extends Controller
{
    public function index(): ResourceCollection
    {
        return JsonResource::collection(
            SalesInvoice::with(['customer', 'items.product'])->orderByDesc('created_at')->paginate(20)
        );
    }

    public function store(IssueInvoiceRequest $request, IssueSalesInvoiceAction $action): JsonResponse
    {
        $invoice = $action->execute(new IssueSalesInvoiceData(
            salesOrderId: $request->validated('sales_order_id'),
            customerId: $request->validated('customer_id'),
            issuedAt: $request->validated('issued_at'),
            dueAt: $request->validated('due_at'),
            createdBy: $request->user()?->id,
            items: $request->validated('items'),
        ));

        return response()->json([
            'message' => 'Invoice issued.',
            'data' => $invoice->load(['customer', 'items.product']),
        ], 201);
    }

    public function show(SalesInvoice $salesInvoice): JsonResource
    {
        return JsonResource::make($salesInvoice->load(['customer', 'items.product', 'payments']));
    }

    /**
     * Record a payment for an invoice.
     */
    public function recordPayment(RecordPaymentRequest $request, SalesInvoice $salesInvoice, RecordPaymentAction $action): JsonResponse
    {
        $payment = $action->execute(new RecordPaymentData(
            invoiceId: $salesInvoice->id,
            amount: $request->validated('amount'),
            paidAt: $request->validated('paid_at'),
            paymentMethod: $request->validated('payment_method'),
            reference: $request->validated('reference'),
            createdBy: $request->user()?->id,
        ));

        return response()->json([
            'message' => 'Payment recorded.',
            'data' => $payment,
            'invoice_status' => $salesInvoice->fresh()?->status,
        ], 201);
    }

    /**
     * Download invoice as PDF.
     */
    public function downloadPdf(SalesInvoice $salesInvoice): StreamedResponse
    {
        return (new SalesInvoicePdf($salesInvoice))->download();
    }
}
