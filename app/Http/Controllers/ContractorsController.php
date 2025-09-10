<?php

namespace App\Http\Controllers;

use App\Models\Contractor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class ContractorsController extends Controller
{
	/**
	 * @OA\Get(
	 *   path="/api/contractors",
	 *   summary="კონტრაგენტების სიის ნახვა/ძიება (სწრაფი შევსებისთვის)",
	 *   security={{"bearerAuth":{}}},
	 *   tags={"Contractors"},
	 *   @OA\Parameter(name="q", in="query", description="ძიება სახელით/ტელეფონით/ელფოსტით", @OA\Schema(type="string")),
	 *   @OA\Parameter(name="limit", in="query", description="რამდენი ჩანაწერი დავაბრუნოთ (default: 20, max: 100)", @OA\Schema(type="integer")),
	 *   @OA\Response(response=200, description="OK")
	 * )
	 */
	public function index(Request $request): JsonResponse
	{
		$query = Contractor::query();
		if ($search = trim((string) $request->query('q', ''))) {
			$query->where(function ($q) use ($search): void {
				$q->where('name', 'like', "%{$search}%")
					->orWhere('phone', 'like', "%{$search}%")
					->orWhere('email', 'like', "%{$search}%");
			});
		}
		$limit = min(max((int) $request->integer('limit', 20), 1), 100);
		$items = $query->orderBy('name')->limit($limit)->get(['id','name','phone','email']);
		return response()->json($items);
	}

	/**
	 * @OA\Post(
	 *   path="/api/contractors",
	 *   summary="კონტრაგენტის შექმნა",
	 *   security={{"bearerAuth":{}}},
	 *   tags={"Contractors"},
	 *   @OA\RequestBody(required=true,
	 *     @OA\JsonContent(
	 *       required={"name"},
	 *       @OA\Property(property="name", type="string", description="დასახელება", example="შპს ახალი პარტნიორი"),
	 *       @OA\Property(property="phone", type="string", nullable=true, description="ტელეფონი (E.164)", example="+995595000003"),
	 *       @OA\Property(property="email", type="string", nullable=true, description="ელფოსტა", example="partner@example.com")
	 *     )
	 *   ),
	 *   @OA\Response(response=201, description="Created")
	 * )
	 */
	public function store(Request $request): JsonResponse
	{
		$data = $request->validate([
			'name' => ['required','string','max:200'],
			'phone' => ['nullable','string','max:15'],
			'email' => ['nullable','email','max:255'],
		]);
		$contractor = Contractor::create($data);
		return response()->json($contractor, 201);
	}

	/**
	 * @OA\Put(
	 *   path="/api/contractors/{id}",
	 *   summary="კონტრაგენტის რედაქტირება",
	 *   security={{"bearerAuth":{}}},
	 *   tags={"Contractors"},
	 *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
	 *   @OA\RequestBody(required=true,
	 *     @OA\JsonContent(
	 *       @OA\Property(property="name", type="string", description="დასახელება", example="შპს პარტნიორი განახლებული"),
	 *       @OA\Property(property="phone", type="string", nullable=true, description="ტელეფონი (E.164)", example="+995595000004"),
	 *       @OA\Property(property="email", type="string", nullable=true, description="ელფოსტა", example="updated@example.com")
	 *     )
	 *   ),
	 *   @OA\Response(response=200, description="OK")
	 * )
	 */
	public function update(Request $request, int $id): JsonResponse
	{
		$data = $request->validate([
			'name' => ['sometimes','string','max:200'],
			'phone' => ['nullable','string','max:15'],
			'email' => ['nullable','email','max:255'],
		]);
		$contractor = Contractor::findOrFail($id);
		$contractor->update($data);
		return response()->json($contractor);
	}

	/**
	 * @OA\Delete(
	 *   path="/api/contractors/{id}",
	 *   summary="კონტრაგენტის წაშლა",
	 *   security={{"bearerAuth":{}}},
	 *   tags={"Contractors"},
	 *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
	 *   @OA\Response(response=204, description="Deleted")
	 * )
	 */
	public function destroy(int $id)
	{
		$contractor = Contractor::findOrFail($id);
		$contractor->delete();
		return response()->noContent();
	}
}


