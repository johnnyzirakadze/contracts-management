<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response as ResponseFactory;
use OpenApi\Annotations as OA;

class AuditLogsController extends Controller
{
	/**
	 * @OA\Get(
	 *   path="/api/audit-logs",
	 *   summary="აუდიტის ჟურნალი - სიის ნახვა",
	 *   security={{"bearerAuth":{}}},
	 *   tags={"Audit"},
	 *   @OA\Parameter(name="from", in="query", description="თარიღი - საწყისი (YYYY-MM-DD)", @OA\Schema(type="string", format="date")),
	 *   @OA\Parameter(name="to", in="query", description="თარიღი - საბოლოო", @OA\Schema(type="string", format="date")),
	 *   @OA\Parameter(name="table", in="query", description="ცხრილის სახელი", @OA\Schema(type="string")),
	 *   @OA\Parameter(name="action", in="query", description="მოქმედება (create/update/delete/upload/unlink)", @OA\Schema(type="string")),
	 *   @OA\Response(response=200, description="OK")
	 * )
	 */
	public function index(Request $request): JsonResponse
	{
		$q = AuditLog::query();
		if ($request->filled('from')) {
			$q->whereDate('created_at', '>=', (string) $request->query('from'));
		}
		if ($request->filled('to')) {
			$q->whereDate('created_at', '<=', (string) $request->query('to'));
		}
		if ($request->filled('table')) {
			$q->where('table_name', (string) $request->query('table'));
		}
		if ($request->filled('action')) {
			$q->where('action', (string) $request->query('action'));
		}
		$logs = $q->latest('created_at')->limit(1000)->get();
		return response()->json($logs);
	}

	/**
	 * @OA\Get(
	 *   path="/api/audit-logs/export",
	 *   summary="აუდიტ-რეპორტი ექსპორტი CSV/JSON",
	 *   security={{"bearerAuth":{}}},
	 *   tags={"Audit"},
	 *   @OA\Parameter(name="format", in="query", required=true, @OA\Schema(type="string", enum={"csv","json"})),
	 *   @OA\Parameter(name="from", in="query", @OA\Schema(type="string", format="date")),
	 *   @OA\Parameter(name="to", in="query", @OA\Schema(type="string", format="date")),
	 *   @OA\Parameter(name="table", in="query", @OA\Schema(type="string")),
	 *   @OA\Parameter(name="action", in="query", @OA\Schema(type="string")),
	 *   @OA\Response(response=200, description="OK")
	 * )
	 */
	public function export(Request $request)
	{
		$q = AuditLog::query();
		if ($request->filled('from')) {
			$q->whereDate('created_at', '>=', (string) $request->query('from'));
		}
		if ($request->filled('to')) {
			$q->whereDate('created_at', '<=', (string) $request->query('to'));
		}
		if ($request->filled('table')) {
			$q->where('table_name', (string) $request->query('table'));
		}
		if ($request->filled('action')) {
			$q->where('action', (string) $request->query('action'));
		}
		$logs = $q->latest('created_at')->limit(50000)->get();

		$format = strtolower((string) $request->query('format', 'csv'));
		if ($format === 'json') {
			$filename = 'audit-' . now()->format('Ymd_His') . '.json';
			return ResponseFactory::streamDownload(function () use ($logs): void {
				echo $logs->toJson(JSON_UNESCAPED_UNICODE);
			}, $filename, ['Content-Type' => 'application/json']);
		}

		$filename = 'audit-' . now()->format('Ymd_His') . '.csv';
		$headers = ['id','user_id','table_name','row_id','action','old_values','new_values','ip','user_agent','created_at'];
		return ResponseFactory::streamDownload(function () use ($logs, $headers): void {
			$stream = fopen('php://output', 'w');
			fwrite($stream, "\xEF\xBB\xBF");
			fputcsv($stream, $headers);
			foreach ($logs as $log) {
				fputcsv($stream, [
					$log->id,
					$log->user_id,
					$log->table_name,
					$log->row_id,
					$log->action,
					json_encode($log->old_values, JSON_UNESCAPED_UNICODE),
					json_encode($log->new_values, JSON_UNESCAPED_UNICODE),
					$log->ip,
					$log->user_agent,
					(optional($log->created_at)->format('Y-m-d H:i:s')),
				]);
			}
			fclose($stream);
		}, $filename, ['Content-Type' => 'text/csv']);
	}
}


