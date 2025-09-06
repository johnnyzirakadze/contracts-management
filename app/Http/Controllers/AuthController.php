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
     *   summary="Login and get JWT token",
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

        $secure = app()->environment('production') || (bool) env('APP_FORCE_HTTPS');
        $cookie = cookie('device_id', $deviceId, 60 * 24 * 365, '/', null, $secure, true, false, 'Lax');

        return $this->respondWithToken($token)->withCookie($cookie);
    }

    /**
     * @OA\Get(
     *   path="/api/auth/me",
     *   tags={"Auth"},
     *   security={{"bearerAuth":{}}},
     *   summary="Get current user",
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
     *   summary="Logout and invalidate token",
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function logout()
    {
        JWTAuth::invalidate(JWTAuth::getToken());
        $secure = app()->environment('production') || (bool) env('APP_FORCE_HTTPS');
        $forgetCookie = cookie('device_id', '', -1, '/', null, $secure, true, false, 'Lax');
        return response()->json(['message' => 'Successfully logged out'])->withCookie($forgetCookie);
    }

    /**
     * @OA\Post(
     *   path="/api/auth/refresh",
     *   tags={"Auth"},
     *   security={{"bearerAuth":{}}},
     *   summary="Refresh JWT token",
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function refresh()
    {
        $newToken = JWTAuth::refresh();
        return $this->respondWithToken($newToken);
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
     *   summary="Create a user (admin only)",
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
     *   summary="List users (admin only)",
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


