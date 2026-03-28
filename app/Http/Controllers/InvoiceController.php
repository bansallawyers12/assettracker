<?php

namespace App\Http\Controllers;

use App\Mail\InvoiceReminderMail;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\BusinessEntity;
use App\Services\InvoicePostingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class InvoiceController extends Controller
{
	public function index(?BusinessEntity $businessEntity = null)
	{
		if ($businessEntity) {
			$this->authorize('view', $businessEntity);

			$invoices = Invoice::where('business_entity_id', $businessEntity->id)
				->with(['asset'])
				->orderByDesc('issue_date')
				->paginate(20);

			return view('invoices.index', compact('businessEntity', 'invoices'));
		}

		$invoices = Invoice::query()
			->with(['asset', 'businessEntity'])
			->orderByDesc('issue_date')
			->paginate(30);

		return view('invoices.index', compact('invoices'));
	}

	public function create(BusinessEntity $businessEntity)
	{
		$this->authorize('view', $businessEntity);

		return view('invoices.create', compact('businessEntity'));
	}

	public function store(Request $request, BusinessEntity $businessEntity)
	{
		$this->authorize('update', $businessEntity);

		$data = $request->validate([
			'invoice_number' => [
				'required',
				'max:50',
				Rule::unique('invoices', 'invoice_number')->where('business_entity_id', $businessEntity->id),
			],
			'issue_date' => 'required|date',
			'due_date' => 'nullable|date',
			'customer_name' => 'required|string|max:255',
			'reference' => 'nullable|string|max:255',
			'currency' => 'nullable|string|size:3',
			'notes' => 'nullable|string',
			'lines' => 'required|array|min:1',
			'lines.*.description' => 'required|string',
			'lines.*.quantity' => 'required|numeric|min:0.0001',
			'lines.*.unit_price' => 'required|numeric|min:0',
			'lines.*.gst_rate' => 'nullable|numeric|min:0',
			'lines.*.account_code' => 'nullable|string|max:20',
		]);

		$invoice = new Invoice();
		$invoice->fill($data);
		$invoice->business_entity_id = $businessEntity->id;
		$invoice->status = 'draft';
		$invoice->subtotal = 0;
		$invoice->gst_amount = 0;
		$invoice->total_amount = 0;
		$invoice->currency = $data['currency'] ?? 'AUD';
		$invoice->save();

		$subtotal = 0; $gstTotal = 0; $grand = 0;
		foreach ($data['lines'] as $line) {
			$qty = (float) $line['quantity'];
			$price = (float) $line['unit_price'];
			$gstRate = isset($line['gst_rate']) ? (float) $line['gst_rate'] : 0.1;
			$lineTotal = round($qty * $price * (1 + $gstRate), 2);
			InvoiceLine::create([
				'invoice_id' => $invoice->id,
				'description' => $line['description'],
				'quantity' => $qty,
				'unit_price' => $price,
				'line_total' => $lineTotal,
				'gst_rate' => $gstRate,
				'account_code' => $line['account_code'] ?? null,
			]);
			$net = round($qty * $price, 2);
			$gst = round($net * $gstRate, 2);
			$subtotal += $net; $gstTotal += $gst; $grand += $net + $gst;
		}
		$invoice->subtotal = $subtotal;
		$invoice->gst_amount = $gstTotal;
		$invoice->total_amount = $grand;
		$invoice->save();

		return redirect()->route('business-entities.invoices.show', [$businessEntity, $invoice])
			->with('success', 'Invoice created');
	}

	public function show(BusinessEntity $businessEntity, Invoice $invoice)
	{
		$this->authorize('view', $businessEntity);
		$this->authorizeInvoice($businessEntity, $invoice);
		$invoice->load(['lines', 'lease.tenant', 'asset']);
		return view('invoices.show', compact('businessEntity', 'invoice'));
	}

	public function edit(BusinessEntity $businessEntity, Invoice $invoice)
	{
		$this->authorize('update', $businessEntity);
		$this->authorizeInvoice($businessEntity, $invoice);
		$invoice->load('lines');
		return view('invoices.edit', compact('businessEntity', 'invoice'));
	}

	public function update(Request $request, BusinessEntity $businessEntity, Invoice $invoice)
	{
		$this->authorize('update', $businessEntity);
		$this->authorizeInvoice($businessEntity, $invoice);
		if ($invoice->is_posted) {
			return back()->with('error', 'Posted invoices cannot be edited.');
		}

		$data = $request->validate([
			'invoice_number' => [
				'required',
				'max:50',
				Rule::unique('invoices', 'invoice_number')->where('business_entity_id', $businessEntity->id)->ignore($invoice->id),
			],
			'issue_date' => 'required|date',
			'due_date' => 'nullable|date',
			'customer_name' => 'required|string|max:255',
			'reference' => 'nullable|string|max:255',
			'currency' => 'nullable|string|size:3',
			'notes' => 'nullable|string',
			'lines' => 'required|array|min:1',
			'lines.*.description' => 'required|string',
			'lines.*.quantity' => 'required|numeric|min:0.0001',
			'lines.*.unit_price' => 'required|numeric|min:0',
			'lines.*.gst_rate' => 'nullable|numeric|min:0',
			'lines.*.account_code' => 'nullable|string|max:20',
		]);

		$invoice->fill($data);
		$invoice->currency = $data['currency'] ?? $invoice->currency;
		$invoice->save();

		$invoice->lines()->delete();
		$subtotal = 0; $gstTotal = 0; $grand = 0;
		foreach ($data['lines'] as $line) {
			$qty = (float) $line['quantity'];
			$price = (float) $line['unit_price'];
			$gstRate = isset($line['gst_rate']) ? (float) $line['gst_rate'] : 0.1;
			$lineTotal = round($qty * $price * (1 + $gstRate), 2);
			InvoiceLine::create([
				'invoice_id' => $invoice->id,
				'description' => $line['description'],
				'quantity' => $qty,
				'unit_price' => $price,
				'line_total' => $lineTotal,
				'gst_rate' => $gstRate,
				'account_code' => $line['account_code'] ?? null,
			]);
			$net = round($qty * $price, 2);
			$gst = round($net * $gstRate, 2);
			$subtotal += $net; $gstTotal += $gst; $grand += $net + $gst;
		}
		$invoice->subtotal = $subtotal;
		$invoice->gst_amount = $gstTotal;
		$invoice->total_amount = $grand;
		$invoice->save();

		return redirect()->route('business-entities.invoices.show', [$businessEntity, $invoice])
			->with('success', 'Invoice updated');
	}

	public function destroy(BusinessEntity $businessEntity, Invoice $invoice)
	{
		$this->authorize('update', $businessEntity);
		$this->authorizeInvoice($businessEntity, $invoice);
		if ($invoice->is_posted) {
			return back()->with('error', 'Posted invoices cannot be deleted.');
		}
		$invoice->delete();
		return redirect()->route('business-entities.invoices.index', $businessEntity)
			->with('success', 'Invoice deleted');
	}

	/**
	 * Browsers issue GET when this URL is opened directly; posting requires POST from the invoice page.
	 */
	public function postRedirect(BusinessEntity $businessEntity, Invoice $invoice)
	{
		$this->authorize('view', $businessEntity);
		$this->authorizeInvoice($businessEntity, $invoice);

		return redirect()->route('business-entities.invoices.show', [$businessEntity, $invoice])
			->with('info', 'Use the Post to ledger button on this page to post the invoice.');
	}

	public function post(BusinessEntity $businessEntity, Invoice $invoice, InvoicePostingService $postingService)
	{
		$this->authorize('update', $businessEntity);
		$this->authorizeInvoice($businessEntity, $invoice);
		if ($invoice->is_posted) {
			return back()->with('info', 'Invoice already posted.');
		}
		$invoice->load('lines');
		$postingService->post($invoice);
		return redirect()->route('business-entities.invoices.show', [$businessEntity, $invoice])
			->with('success', 'Invoice posted to ledger');
	}

	public function recordPayment(Request $request, BusinessEntity $businessEntity, Invoice $invoice)
	{
		$this->authorize('update', $businessEntity);
		$this->authorizeInvoice($businessEntity, $invoice);

		if ($invoice->status !== 'approved') {
			return back()->with('error', 'Only approved (posted) invoices can be marked paid.');
		}
		if ($invoice->paid_at) {
			return back()->with('error', 'This invoice is already recorded as paid.');
		}

		$data = $request->validate([
			'paid_at' => 'required|date',
			'payment_method' => 'nullable|string|max:100',
			'payment_reference' => 'nullable|string|max:255',
		]);

		$invoice->update([
			'paid_at' => $data['paid_at'],
			'payment_method' => $data['payment_method'] ?? null,
			'payment_reference' => $data['payment_reference'] ?? null,
			'status' => 'paid',
		]);

		return back()->with('success', 'Payment recorded.');
	}

	public function remind(BusinessEntity $businessEntity, Invoice $invoice)
	{
		$this->authorize('update', $businessEntity);
		$this->authorizeInvoice($businessEntity, $invoice);

		if ($invoice->status !== 'approved') {
			return back()->with('error', 'Reminders can only be sent for approved (posted) invoices.');
		}

		$invoice->loadMissing(['lease.tenant']);
		$tenant = $invoice->lease?->tenant;
		$email = $tenant?->email;
		if (!$email) {
			return back()->with('error', 'No tenant email on file for this invoice.');
		}

		$customerName = $tenant->name ?? $invoice->customer_name;

		try {
			Mail::to($email)->send(new InvoiceReminderMail($invoice, $customerName));
		} catch (\Throwable $e) {
			report($e);

			return back()->with('error', 'Could not send email. Check your mail configuration.');
		}

		$invoice->update([
			'last_reminder_sent_at' => now(),
			'reminder_count' => (int) $invoice->reminder_count + 1,
		]);

		return back()->with('success', 'Reminder sent to '.$email);
	}

	private function authorizeInvoice(BusinessEntity $businessEntity, Invoice $invoice): void
	{
		abort_unless((int) $invoice->business_entity_id === (int) $businessEntity->id, 404);
	}
}
