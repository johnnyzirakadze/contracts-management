<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\AttachedFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Response as ResponseFactory;
use Illuminate\Support\Facades\Storage;
use OpenApi\Annotations as OA;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Mpdf\Mpdf;
use App\Services\AuditLogger;

class ContractsController extends Controller
{
	/**
	 * @OA\Get(
	 *   path="/api/contracts",
	 *   summary="კონტრაქტების სიის ნახვა",
	 *   security={{"bearerAuth":{}}},
	 *   tags={"Contracts"},
	 *   @OA\Parameter(name="party_name", in="query", description="ძიება მხოლოდ სახელით (party_name)", @OA\Schema(type="string")),
	 *   @OA\Parameter(name="status", in="query", description="სტატუსი (მაგ: აქტივი, დასამტკიცებელი და სხვ.)", @OA\Schema(type="string")),
	 *   @OA\Parameter(name="sign_date_from", in="query", description="ხელმოწერის თარიღი - საწყისი", @OA\Schema(type="string", format="date")),
	 *   @OA\Parameter(name="sign_date_to", in="query", description="ხელმოწერის თარიღი - საბოლოო", @OA\Schema(type="string", format="date")),
	 *   @OA\Parameter(name="expiry_date_from", in="query", description="ვადის თარიღი - საწყისი", @OA\Schema(type="string", format="date")),
	 *   @OA\Parameter(name="expiry_date_to", in="query", description="ვადის თარიღი - საბოლოო", @OA\Schema(type="string", format="date")),
	 *   @OA\Parameter(name="sort_by", in="query", description="სორტირების ველი", @OA\Schema(type="string", enum={"id","contract_number","sign_date","expiry_date","amount","created_at","updated_at"})),
	 *   @OA\Parameter(name="sort_dir", in="query", description="სორტირების მიმართულება", @OA\Schema(type="string", enum={"asc","desc"})),
	 *   @OA\Response(response=200, description="OK")
	 * )
	 */
	public function index(Request $request): JsonResponse
	{
		$query = $this->baseQuery();
		$query = $this->applyFilters($request, $query);
		$query = $this->applySorting($request, $query);

		$items = $query->get();
		return response()->json($items);
	}

	/**
	 * @OA\Get(
	 *   path="/api/contracts/{id}/attachments",
	 *   summary="კონტრაქტზე მიმაგრებული ფაილების სია",
	 *   security={{"bearerAuth":{}}},
	 *   tags={"Contracts"},
	 *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
	 *   @OA\Response(response=200, description="OK")
	 * )
	 */
	public function listAttachments(int $id): JsonResponse
	{
		$contract = Contract::findOrFail($id);
		$files = AttachedFile::where('table_name', 'contracts')
			->where('row_id', $contract->id)
			->orderBy('uploaded_at', 'desc')
			->get();
		return response()->json($files);
	}

	/**
	 * @OA\Get(
	 *   path="/api/contracts/export",
	 *   summary="კონტრაქტების ექსპორტი CSV/XLSX/PDF",
	 *   security={{"bearerAuth":{}}},
	 *   tags={"Contracts"},
	 *   @OA\Parameter(name="format", in="query", required=true, description="ექსპორტის ფორმატი (ფილტრებიც მოქმედებს)", @OA\Schema(type="string", enum={"csv","xlsx","pdf"})),
	 *   @OA\Response(response=200, description="File stream")
	 * )
	 */
	public function export(Request $request)
	{
		$format = strtolower((string) $request->query('format', 'csv'));
		if (! in_array($format, ['csv', 'xlsx', 'pdf'], true)) {
			return response()->json(['message' => 'Invalid format'], 422);
		}

		$query = $this->baseQuery();
		$query = $this->applyFilters($request, $query);

		$rows = $query->limit(50000)->get();
		$exportRows = $rows->map(function (Contract $c): array {
			return [
				'ID' => $c->id,
				'Contract Number' => $c->contract_number,
				'Party Name' => $c->party_name,
				'Party Identifier' => $c->party_identifier,
				'Contractor' => optional($c->contractor)->name,
				'Type' => optional($c->type)->name,
				'Branch' => optional($c->branch)->name,
				'Department' => optional($c->department)->name,
				'Currency' => $c->currency,
				'Amount' => $c->amount,
				'Status' => $c->status,
				'Payment Type' => $c->payment_type,
				'Sign Date' => optional($c->sign_date)->format('Y-m-d'),
				'Expiry Date' => optional($c->expiry_date)->format('Y-m-d'),
				'Created At' => optional($c->created_at)->format('Y-m-d H:i:s'),
			];
		})->values()->all();

		switch ($format) {
			case 'csv':
				return $this->exportCsv($exportRows);
			case 'xlsx':
				return $this->exportXlsx($exportRows);
			case 'pdf':
				return $this->exportPdf($exportRows, $request->query());
		}

		return response()->json(['message' => 'Unsupported format'], 422);
	}

	/**
	 * @OA\Post(
	 *   path="/api/contracts",
	 *   summary="კონტრაქტის შექმნა",
	 *   security={{"bearerAuth":{}}},
	 *   tags={"Contracts"},
	 *   @OA\RequestBody(
	 *     required=true,
	 *     @OA\JsonContent(
	 *       required={"party_name","party_identifier","contract_type_id","subject","sign_date","branch_id","currency","status","payment_type"},
	 *       @OA\Property(property="party_name", type="string", description="მეორე მხარის სახელი (legacy)", example="შპს ალფა"),
	 *       @OA\Property(property="party_identifier", type="string", description="იდენტიფიკატორი 9-11 ციფრი", example="123456789"),
	 *       @OA\Property(property="contractor_id", type="integer", nullable=true, description="კონტრაქტორის ID (ნებაყოფლობითი)", example=1),
	 *       @OA\Property(property="contract_number", type="string", nullable=true, description="ხელშეკრულების ნომერი (უნიკალური)", example="CNT-2025-0003"),
	 *       @OA\Property(property="contract_type_id", type="integer", description="ხელშეკრულების ტიპი", example=1),
	 *       @OA\Property(property="subject", type="string", description="საგანი/შინაარსი", example="სერვისების მიწოდება და მხარდაჭერა"),
	 *       @OA\Property(property="sign_date", type="string", format="date", description="ხელმოწერის თარიღი", example="2025-09-10"),
	 *       @OA\Property(property="expiry_date", type="string", format="date", nullable=true, description="ვადის თარიღი (>= sign_date)", example="2026-09-10"),
	 *       @OA\Property(property="branch_id", type="integer", description="ფილიალი", example=1),
	 *       @OA\Property(property="department_id", type="integer", nullable=true, description="დეპარტამენტი", example=1),
	 *       @OA\Property(property="currency", type="string", description="ვალუტა ISO 4217", example="GEL"),
	 *       @OA\Property(property="amount", type="number", format="float", nullable=true, description="თანხა", example=12000.00),
	 *       @OA\Property(property="status", type="string", description="სტატუსი (default: დასამტკიცებელი)", example="დასამტკიცებელი"),
	 *       @OA\Property(property="responsible_manager_id", type="integer", nullable=true, description="პასუხისმგებელი მენეჯერი (users.id)", example=null),
	 *       @OA\Property(property="initiator_id", type="integer", nullable=true, description="ინიციატორი (initiators.id)", example=1),
	 *       @OA\Property(property="payment_type", type="string", description="გადახდის ტიპი", example="ერთჯერადი")
	 *     )
	 *   ),
	 *   @OA\Response(response=201, description="Created")
	 * )
	 */
	public function store(Request $request): JsonResponse
	{
		$data = $this->validateContract($request);
		if (!isset($data['status']) || $data['status'] === null || $data['status'] === '') {
			$data['status'] = 'დასამტკიცებელი';
		}
		$contract = Contract::create($data);
		AuditLogger::log(auth('api')->user(), 'contracts', $contract->id, 'create', null, $contract->toArray());
		return response()->json($contract, 201);
	}

	/**
	 * @OA\Put(
	 *   path="/api/contracts/{id}",
	 *   summary="კონტრაქტის განახლება",
	 *   security={{"bearerAuth":{}}},
	 *   tags={"Contracts"},
	 *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
	 *   @OA\RequestBody(
	 *     required=true,
	 *     @OA\JsonContent(
	 *       @OA\Property(property="party_name", type="string", description="მეორე მხარის სახელი (legacy)", example="შპს ალფა"),
	 *       @OA\Property(property="party_identifier", type="string", description="იდენტიფიკატორი 9-11 ციფრი", example="123456789"),
	 *       @OA\Property(property="contractor_id", type="integer", nullable=true, description="კონტრაქტორის ID", example=2),
	 *       @OA\Property(property="contract_number", type="string", nullable=true, description="ხელშეკრულების ნომერი (უნიკალური)", example="CNT-2025-0003"),
	 *       @OA\Property(property="contract_type_id", type="integer", description="ხელშეკრულების ტიპი", example=1),
	 *       @OA\Property(property="subject", type="string", description="საგანი/შინაარსი", example="განახლებული აღწერა"),
	 *       @OA\Property(property="sign_date", type="string", format="date", description="ხელმოწერის თარიღი", example="2025-09-10"),
	 *       @OA\Property(property="expiry_date", type="string", format="date", nullable=true, description="ვადის თარიღი (>= sign_date)", example="2026-09-10"),
	 *       @OA\Property(property="branch_id", type="integer", description="ფილიალი", example=1),
	 *       @OA\Property(property="department_id", type="integer", nullable=true, description="დეპარტამენტი", example=3),
	 *       @OA\Property(property="currency", type="string", description="ვალუტა ISO 4217", example="GEL"),
	 *       @OA\Property(property="amount", type="number", format="float", nullable=true, description="თანხა", example=15000.00),
	 *       @OA\Property(property="status", type="string", description="სტატუსი", example="აქტიური"),
	 *       @OA\Property(property="responsible_manager_id", type="integer", nullable=true, description="პასუხისმგებელი მენეჯერი", example=1),
	 *       @OA\Property(property="initiator_id", type="integer", nullable=true, description="ინიციატორი (initiators.id)", example=1),
	 *       @OA\Property(property="payment_type", type="string", description="გადახდის ტიპი", example="ყოველთვიური")
	 *     )
	 *   ),
	 *   @OA\Response(response=200, description="OK")
	 * )
	 */
	public function update(Request $request, int $id): JsonResponse
	{
		$data = $this->validateContract($request, isUpdate: true);
		$contract = Contract::findOrFail($id);
		// ACL: Only owner (responsible_manager_id == me) or admin can edit record
		$user = auth('api')->user();
		$roleKey = optional($user?->role)->key;
		$owns = $user && (int) $contract->responsible_manager_id === (int) $user->id;
		if (! $owns && $roleKey !== 'admin') {
			return response()->json(['message' => 'Forbidden'], 403);
		}
		$old = $contract->getOriginal();
		$contract->update($data);
		AuditLogger::log(auth('api')->user(), 'contracts', $contract->id, 'update', $old, $contract->toArray());
		return response()->json($contract);
	}

	/**
	 * @OA\Post(
	 *   path="/api/contracts/{id}/attachments",
	 *   summary="ფაილების ატვირთვა კონტრაქტზე (მხარდაჭერა მრავალში)",
	 *   security={{"bearerAuth":{}}},
	 *   tags={"Contracts"},
	 *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
	 *   @OA\RequestBody(
	 *     required=true,
	 *     @OA\MediaType(mediaType="multipart/form-data",
	 *       @OA\Schema(
	 *         @OA\Property(property="files[]", type="array", @OA\Items(type="string", format="binary"), description="ერთზე მეტი ფაილი (pdf/doc/docx/xlsx/csv, ≤50MB თითოეული)"),
	 *         @OA\Property(property="file", type="string", format="binary", description="ერთეული ფაილი (ალტერნატიული ველი)" )
	 *       )
	 *     )
	 *   ),
	 *   @OA\Response(response=201, description="Uploaded")
	 * )
	 */
	public function uploadAttachment(Request $request, int $id): JsonResponse
	{
		$contract = Contract::findOrFail($id);
		// ACL: Only owner (responsible_manager_id == me) or admin can upload files
		$user = auth('api')->user();
		$roleKey = optional($user?->role)->key;
		$owns = $user && (int) $contract->responsible_manager_id === (int) $user->id;
		if (! $owns && $roleKey !== 'admin') {
			return response()->json(['message' => 'Forbidden'], 403);
		}

		// Accept either multiple files (files[]) or single file (file)
		$files = [];
		if ($request->hasFile('files')) {
			$files = (array) $request->file('files');
		} elseif ($request->hasFile('file')) {
			$files = [ $request->file('file') ];
		}
		if (empty($files)) {
			return response()->json(['message' => 'Validation failed', 'errors' => ['files' => ['No file provided. Use files[] or file.']]], 422);
		}

		$created = [];
		foreach ($files as $file) {
			if (! $file->isValid()) {
				return response()->json(['message' => 'Validation failed', 'errors' => ['files' => ['Invalid upload.']]], 422);
			}
			if ($file->getSize() > 50 * 1024 * 1024) {
				return response()->json(['message' => 'Validation failed', 'errors' => ['files' => ['File too large.']]], 422);
			}
			$ext = strtolower($file->getClientOriginalExtension() ?: '');
			$type = in_array($ext, ['pdf']) ? 'pdf' : (in_array($ext, ['doc','docx']) ? 'docx' : 'other');
			$storedPath = $file->store('contracts','public');
			$record = AttachedFile::create([
				'row_id' => $contract->id,
				'table_name' => 'contracts',
				'file_name' => $file->getClientOriginalName(),
				'file_type' => $type,
				'file_size' => $file->getSize(),
				'file_path' => '/storage/'.$storedPath,
				'uploaded_at' => now(),
			]);
			$created[] = $record;
			AuditLogger::log(auth('api')->user(), 'attached_files', $record->id, 'upload', null, $record->toArray());
		}
		return response()->json($created, 201);
	}

	/**
	 * @OA\Delete(
	 *   path="/api/contracts/{id}",
	 *   summary="კონტრაქტის წაშლა",
	 *   security={{"bearerAuth":{}}},
	 *   tags={"Contracts"},
	 *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
	 *   @OA\Response(response=204, description="Deleted")
	 * )
	 */
	public function destroy(int $id)
	{
		$contract = Contract::findOrFail($id);
		// Remove related attached_files and physical files if stored under /storage
		$attachments = AttachedFile::where('table_name', 'contracts')->where('row_id', $contract->id)->get();
		foreach ($attachments as $att) {
			if (is_string($att->file_path) && str_starts_with($att->file_path, '/storage/')) {
				$relative = ltrim(str_replace('/storage/', '', $att->file_path), '/');
				Storage::disk('public')->delete($relative);
			}
			$attOld = $att->toArray();
			$att->delete();
			AuditLogger::log(auth('api')->user(), 'attached_files', $att->id, 'unlink', $attOld, null);
		}
		$old = $contract->toArray();
		$contract->delete();
		AuditLogger::log(auth('api')->user(), 'contracts', $id, 'delete', $old, null);
		return response()->noContent();
	}

	private function validateContract(Request $request, bool $isUpdate = false): array
	{
		$ruleRequired = $isUpdate ? 'sometimes' : 'required';
		return $request->validate([
			'party_name' => [$ruleRequired,'string','max:200'],
			'party_identifier' => [$ruleRequired,'regex:/^[0-9]{9,11}$/'],
			'contractor_id' => ['nullable','integer','exists:contractors,id'],
			'contract_number' => ['nullable','string','max:50','unique:contracts,contract_number'.($isUpdate ? ',' . (int) $request->route('id') : '')],
			'contract_type_id' => [$ruleRequired,'integer','exists:contract_types,id'],
			'subject' => [$ruleRequired,'string'],
			'sign_date' => [$ruleRequired,'date'],
			'expiry_date' => ['nullable','date','after_or_equal:sign_date'],
			'branch_id' => [$ruleRequired,'integer','exists:branches,id'],
			'department_id' => ['nullable','integer','exists:departments,id'],
			'currency' => [$ruleRequired,'string','size:3'],
			'amount' => ['nullable','numeric','min:0'],
			'status' => [$ruleRequired,'string','max:255'],
			'responsible_manager_id' => ['nullable','integer','exists:users,id'],
			'initiator_id' => ['nullable','integer','exists:initiators,id'],
			'payment_type' => [$ruleRequired,'string','max:255'],
		]);
	}

	private function baseQuery(): Builder
	{
		return Contract::query()
			->with(['type', 'contractor', 'branch', 'department', 'responsibleManager', 'initiator']);
	}

	private function applyFilters(Request $request, Builder $query): Builder
	{
		$term = $request->query('party_name', $request->query('name', $request->query('q', '')));
		if ($search = trim((string) $term)) {
			$query->where(function (Builder $q) use ($search): void {
				$q->where('party_name', 'like', "%{$search}%");
			});
		}

		foreach ([ 'status' ] as $strField) {
			if ($request->filled($strField)) {
				$query->where($strField, (string) $request->query($strField));
			}
		}

		if ($request->filled('sign_date_from')) {
			$query->whereDate('sign_date', '>=', (string) $request->query('sign_date_from'));
		}
		if ($request->filled('sign_date_to')) {
			$query->whereDate('sign_date', '<=', (string) $request->query('sign_date_to'));
		}
		if ($request->filled('expiry_date_from')) {
			$query->whereDate('expiry_date', '>=', (string) $request->query('expiry_date_from'));
		}
		if ($request->filled('expiry_date_to')) {
			$query->whereDate('expiry_date', '<=', (string) $request->query('expiry_date_to'));
		}

		return $query;
	}

	private function applySorting(Request $request, Builder $query): Builder
	{
		$allowed = ['id', 'contract_number', 'sign_date', 'expiry_date', 'amount', 'created_at', 'updated_at'];
		$sortBy = (string) $request->query('sort_by', 'created_at');
		$sortDir = strtolower((string) $request->query('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';
		if (! in_array($sortBy, $allowed, true)) {
			$sortBy = 'created_at';
		}
		return $query->orderBy($sortBy, $sortDir);
	}

	private function exportCsv(array $rows)
	{
		$filename = 'contracts-' . now()->format('Ymd_His') . '.csv';
		$headers = array_keys($rows[0] ?? ['No Data' => '']);
		return ResponseFactory::streamDownload(function () use ($rows, $headers): void {
			$stream = fopen('php://output', 'w');
			// UTF-8 BOM for Excel compatibility
			fwrite($stream, "\xEF\xBB\xBF");
			fputcsv($stream, $headers);
			foreach ($rows as $row) {
				fputcsv($stream, array_values($row));
			}
			fclose($stream);
		}, $filename, ['Content-Type' => 'text/csv']);
	}

	private function exportXlsx(array $rows)
	{
		$filename = 'contracts-' . now()->format('Ymd_His') . '.xlsx';
		$headers = array_keys($rows[0] ?? ['No Data' => '']);
		return ResponseFactory::streamDownload(function () use ($rows, $headers): void {
			$spreadsheet = new Spreadsheet();
			$sheet = $spreadsheet->getActiveSheet();
			$sheet->fromArray($headers, null, 'A1', true);
			if (! empty($rows)) {
				$sheet->fromArray(array_map('array_values', $rows), null, 'A2', true);
			}
			$writer = new Xlsx($spreadsheet);
			$writer->save('php://output');
		}, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
	}

	private function exportPdf(array $rows, array $filters)
	{
		$html = view('contracts.export_pdf', [
			'rows' => $rows,
			'filters' => $filters,
		])->render();

		$mpdf = new Mpdf(['mode' => 'utf-8', 'format' => 'A4']);
		$mpdf->WriteHTML($html);
		$filename = 'contracts-' . now()->format('Ymd_His') . '.pdf';
		return new Response($mpdf->Output($filename, \Mpdf\Output\Destination::STRING_RETURN), 200, [
			'Content-Type' => 'application/pdf',
			'Content-Disposition' => 'attachment; filename="' . $filename . '"',
		]);
	}
}


