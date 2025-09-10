<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

/**
 * @OA\Info(title="Contracts API", version="1.0.0")
 * @OA\Server(url="/", description="Base server")
 * @OA\SecurityScheme(
 *   securityScheme="bearerAuth",
 *   type="http",
 *   scheme="bearer",
 *   bearerFormat="JWT"
 * )
 */
class AuthController extends Controller
{
	/**
	 * @OA\Post(
	 *   path="/api/auth/login",
	 *   tags={"Auth"},
	 *   summary="შესვლა და JWT ტოკენის მიღება",
	 *   description="აბრუნებს access_token-ს JSON-ში და აყენებს refresh_token-ს HttpOnly ქუქიში",
	 *   @OA\RequestBody(
	 *     required=true,
	 *     @OA\JsonContent(required={"email","password"},
	 *       @OA\Property(property="email", type="string", format="email", example="admin@example.com"),
	 *       @OA\Property(property="password", type="string", example="admin123")
	 *     )
	 *   ),
	 *   @OA\Response(response=200, description="OK",
	 *     @OA\JsonContent(
	 *       @OA\Property(property="access_token", type="string"),
	 *       @OA\Property(property="token_type", type="string"),
	 *       @OA\Property(property="expires_in", type="integer")
	 *     )
	 *   ),
	 *   @OA\Response(response=422, description="Invalid credentials")
	 * )
	 */
	public function login(Request $request)
	{
		$credentials = $request->validate([
			'email' => ['required', 'email'],
			'password' => ['required', 'string'],
		]);

		// Ensure device binding: get or create device_id and include as JWT claim
		$deviceId = (string) ($request->cookies->get('device_id', '') ?? '');
		if ($deviceId === '') {
			$deviceId = bin2hex(random_bytes(16));
		}

		if (! $token = JWTAuth::claims(['dev' => $deviceId])->attempt($credentials)) {
			throw ValidationException::withMessages([
				'email' => ['Invalid credentials.'],
			]);
		}

		// Issue refresh token in HttpOnly cookie (rotation will happen on /auth/refresh)
		$user = JWTAuth::user();
		$refreshTtl = (int) config('jwt.refresh_ttl');
		$factory = JWTAuth::factory();
		$origTtl = $factory->getTTL();
		$factory->setTTL($refreshTtl);
		$refreshToken = JWTAuth::claims(['dev' => $deviceId, 'rt' => true])->fromUser($user);
		$factory->setTTL($origTtl);

		$secure = app()->environment('production') || (bool) env('APP_FORCE_HTTPS');
		$deviceCookie = cookie('device_id', $deviceId, 60 * 24 * 365, '/', null, $secure, true, false, 'Lax');
		$refreshCookie = cookie('refresh_token', $refreshToken, $refreshTtl, '/', null, $secure, true, true, 'Lax');

		return $this->respondWithToken($token)->withCookie($deviceCookie)->withCookie($refreshCookie);
	}

	/**
	 * @OA\Get(
	 *   path="/api/auth/me",
	 *   tags={"Auth"},
	 *   security={{"bearerAuth":{}}},
	 *   summary="მიმდინარე მომხმარებლის მიღება",
	 *   @OA\Response(response=200, description="OK")
	 * )
	 */
	public function me()
	{
		$user = JWTAuth::user();
		if ($user) {
			$user->load('role');
		}
		return response()->json($user);
	}

	/**
	 * @OA\Post(
	 *   path="/api/auth/logout",
	 *   tags={"Auth"},
	 *   security={{"bearerAuth":{}}},
	 *   summary="გასვლა და ტოკენების გაუქმება",
	 *   description="აუქმებს access_token-ს (Bearer) და შლის refresh_token ქუქის",
	 *   @OA\Response(response=200, description="OK")
	 * )
	 */
	public function logout()
	{
		// Invalidate current access token if present
		try {
			JWTAuth::invalidate(JWTAuth::getToken());
		} catch (\Throwable $e) {
			// ignore if missing/expired
		}

		// Invalidate refresh token if present
		$refreshToken = request()->cookie('refresh_token');
		if (is_string($refreshToken) && $refreshToken !== '') {
			try {
				JWTAuth::setToken($refreshToken)->invalidate();
			} catch (\Throwable $e) {
				// ignore if already expired/invalid
			}
		}

		$secure = app()->environment('production') || (bool) env('APP_FORCE_HTTPS');
		$forgetDevice = cookie('device_id', '', -1, '/', null, $secure, true, false, 'Lax');
		$forgetRefresh = cookie('refresh_token', '', -1, '/', null, $secure, true, true, 'Lax');
		return response()->json(['message' => 'Successfully logged out'])->withCookie($forgetDevice)->withCookie($forgetRefresh);
	}

	/**
	 * @OA\Post(
	 *   path="/api/auth/refresh",
	 *   tags={"Auth"},
	 *   summary="JWT access_token-ის განახლება (HttpOnly refresh ქუქით)",
	 *   description="იღებს refresh_token-ს ქუქიდან და აბრუნებს ახალ access_token-ს; აბრუნებს ახალ refresh ქუქისაც (როტაცია)",
	 *   @OA\Response(response=200, description="OK",
	 *     @OA\JsonContent(
	 *       @OA\Property(property="access_token", type="string"),
	 *       @OA\Property(property="token_type", type="string"),
	 *       @OA\Property(property="expires_in", type="integer")
	 *     )
	 *   ),
	 *   @OA\Response(response=401, description="Unauthorized")
	 * )
	 */
	public function refreshWithCookie(Request $request)
	{
		$refreshToken = (string) ($request->cookies->get('refresh_token', '') ?? '');
		if ($refreshToken === '') {
			return response()->json(['message' => 'No refresh token'], 401);
		}

		try {
			$payload = JWTAuth::setToken($refreshToken)->getPayload();
			if (! (bool) ($payload->get('rt') ?? false)) {
				return response()->json(['message' => 'Invalid refresh token'], 401);
			}
			// Authenticate user from refresh token
			$user = JWTAuth::setToken($refreshToken)->authenticate();
			if (! $user) {
				return response()->json(['message' => 'User not found'], 401);
			}

			$deviceId = (string) ($request->cookies->get('device_id', '') ?? '');
			$accessToken = JWTAuth::claims(['dev' => $deviceId])->fromUser($user);

			// Rotate refresh token
			$refreshTtl = (int) config('jwt.refresh_ttl');
			$factory = JWTAuth::factory();
			$origTtl = $factory->getTTL();
			$factory->setTTL($refreshTtl);
			$newRefreshToken = JWTAuth::claims(['dev' => $deviceId, 'rt' => true])->fromUser($user);
			$factory->setTTL($origTtl);

			// Blacklist old refresh token
			try {
				JWTAuth::setToken($refreshToken)->invalidate();
			} catch (\Throwable $e) {
				// ignore if already invalid
			}

			$secure = app()->environment('production') || (bool) env('APP_FORCE_HTTPS');
			$refreshCookie = cookie('refresh_token', $newRefreshToken, $refreshTtl, '/', null, $secure, true, true, 'Lax');
			return $this->respondWithToken($accessToken)->withCookie($refreshCookie);
		} catch (\Throwable $e) {
			return response()->json(['message' => 'Unauthorized'], 401);
		}
	}

	protected function respondWithToken(string $token)
	{
		return response()->json([
			'access_token' => $token,
			'token_type' => 'bearer',
			'expires_in' => (int) config('jwt.ttl') * 60,
		]);
	}

	// Admin-only: create user (admin can register others)
	/**
	 * @OA\Post(
	 *   path="/api/admin/users",
	 *   tags={"Admin"},
	 *   security={{"bearerAuth":{}}},
	 *   summary="მომხმარებლის შექმნა (მხოლოდ ადმინი)",
	 *   @OA\RequestBody(
	 *     required=true,
	 *     @OA\JsonContent(required={"name","email","password","role"},
	 *       @OA\Property(property="name", type="string"),
	 *       @OA\Property(property="email", type="string", format="email"),
	 *       @OA\Property(property="password", type="string", minLength=8),
	 *       @OA\Property(property="role", type="string", enum={"viewer","editor","approver","admin"})
	 *     )
	 *   ),
	 *   @OA\Response(response=201, description="Created")
	 * )
	 */
	public function createUser(Request $request)
	{
		$this->authorizeAdmin();

		$data = $request->validate([
			'name' => ['required', 'string', 'max:255'],
			'email' => ['required', 'email', 'max:255', 'unique:users,email'],
			'password' => ['required', 'string', 'min:8'],
			'role' => ['required', 'in:viewer,editor,approver,admin'],
		]);

		$role = Role::where('key', $data['role'])->firstOrFail();

		$user = User::create([
			'name' => $data['name'],
			'email' => $data['email'],
			'password' => $data['password'],
			'role_id' => $role->id,
		]);

		return response()->json($user, 201);
	}

	/**
	 * @OA\Get(
	 *   path="/api/admin/users",
	 *   tags={"Admin"},
	 *   security={{"bearerAuth":{}}},
	 *   summary="მომხმარებლების სია (მხოლოდ ადმინი)",
	 *   @OA\Response(response=200, description="OK")
	 * )
	 */
	public function listUsers()
	{
		$this->authorizeAdmin();
		$users = User::with('role')->latest()->paginate(50);
		return response()->json(['data' => $users->items()]);
	}

	// Optional device binding helper: set device_id cookie if not set
	protected function ensureDeviceCookie(): void
	{
		// This is a placeholder. In a real app, set cookie on login response with HttpOnly & Secure.
	}

	protected function authorizeAdmin(): void
	{
		$user = auth('api')->user();
		if (! $user || ! $user->role || $user->role->key !== 'admin') {
			abort(403, 'Insufficient role');
		}
	}
}


