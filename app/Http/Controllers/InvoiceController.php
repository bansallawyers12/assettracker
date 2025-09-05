<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\BusinessEntity;
use App\Services\InvoicePostingService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InvoiceController extends Controller
{
	public function index(BusinessEntity $businessEntity = null)
	{
		if ($businessEntity) {
			// Specific business entity view
			$invoices = Invoice::where('business_entity_id', $businessEntity->id)
				->orderByDesc('issue_date')
				->paginate(20);
			return view('invoices.index', compact('businessEntity', 'invoices'));
		} else {
			// Global view for all business entities
			$user = auth()->user();
			$businessEntities = BusinessEntity::where('user_id', $user->id)->get();
			$invoices = collect();
			
			foreach ($businessEntities as $entity) {
				$entityInvoices = Invoice::where('business_entity_id', $entity->id)
					->orderByDesc('issue_date')
					->get();
				$invoices = $invoices->merge($entityInvoices);
			}
			
			return view('invoices.index', compact('invoices', 'businessEntities'));
		}
	}

	public function create(BusinessEntity $businessEntity)
	{
		return view('invoices.create', compact('businessEntity'));
	}

	public function store(Request $request, BusinessEntity $businessEntity)
	{
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
		$this->authorizeInvoice($businessEntity, $invoice);
		$invoice->load('lines');
		return view('invoices.show', compact('businessEntity', 'invoice'));
	}

	public function edit(BusinessEntity $businessEntity, Invoice $invoice)
	{
		$this->authorizeInvoice($businessEntity, $invoice);
		$invoice->load('lines');
		return view('invoices.edit', compact('businessEntity', 'invoice'));
	}

	public function update(Request $request, BusinessEntity $businessEntity, Invoice $invoice)
	{
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
		$this->authorizeInvoice($businessEntity, $invoice);
		if ($invoice->is_posted) {
			return back()->with('error', 'Posted invoices cannot be deleted.');
		}
		$invoice->delete();
		return redirect()->route('business-entities.invoices.index', $businessEntity)
			->with('success', 'Invoice deleted');
	}

	public function post(BusinessEntity $businessEntity, Invoice $invoice, InvoicePostingService $postingService)
	{
		$this->authorizeInvoice($businessEntity, $invoice);
		if ($invoice->is_posted) {
			return back()->with('info', 'Invoice already posted.');
		}
		$invoice->load('lines');
		$postingService->post($invoice);
		return redirect()->route('business-entities.invoices.show', [$businessEntity, $invoice])
			->with('success', 'Invoice posted to ledger');
	}

	private function authorizeInvoice(BusinessEntity $businessEntity, Invoice $invoice): void
	{
		abort_unless($invoice->business_entity_id === $businessEntity->id, 404);
	}
}
