<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * @apiDefine Authenticated Bejelentkezés szükséges
 * A kérésnek tartalmaznia kell a bejelentkezéskor kapott tokent:
 * <code>Authorization: Bearer {token}</code>
 */
class AuthController extends Controller
{
    /**
     * @api {post} /login Bejelentkezés
     * @apiName Login
     * @apiGroup Auth
     * @apiVersion 1.0.0
     * @apiDescription Bejelentkezés e-mail címmel és jelszóval. A korábban kiadott tokenek érvénytelenné válnak, az új tokent az írási végpontoknál kell használni.
     *
     * @apiBody {String} email A felhasználó e-mail címe.
     * @apiBody {String} password A felhasználó jelszava.
     *
     * @apiSuccess {Object} user A bejelentkezett felhasználó.
     * @apiSuccess {Number} user.id Azonosító.
     * @apiSuccess {String} user.name Név.
     * @apiSuccess {String} user.email E-mail cím.
     * @apiSuccess {String} token Bearer token (Sanctum).
     *
     * @apiSuccessExample {json} Sikeres válasz:
     *     HTTP/1.1 200 OK
     *     {
     *       "user": { "id": 1, "name": "Test User", "email": "test@example.com" },
     *       "token": "1|aBcDeFgHiJkLmNoPqRsTuVwXyZ0123456789"
     *     }
     *
     * @apiError (401) {String} message Hibás e-mail cím vagy jelszó.
     * @apiError (422) {String} message Hiányzó vagy hibás mező.
     * @apiError (422) {Object} errors Mezőnkénti hibaüzenetek.
     *
     * @apiErrorExample {json} Hibás belépési adatok:
     *     HTTP/1.1 401 Unauthorized
     *     { "message": "Hibás e-mail cím vagy jelszó." }
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json(['message' => 'Hibás e-mail cím vagy jelszó.'], 401);
        }

        // Egyszerre csak egy érvényes token van felhasználónként.
        $user->tokens()->delete();

        return response()->json([
            'user' => $user,
            'token' => $user->createToken('access')->plainTextToken,
        ]);
    }

    /**
     * @api {post} /logout Kijelentkezés
     * @apiName Logout
     * @apiGroup Auth
     * @apiVersion 1.0.0
     * @apiPermission Authenticated
     * @apiUse Authenticated
     * @apiDescription Az aktuális token törlése.
     *
     * @apiSuccess {String} message Visszaigazolás.
     *
     * @apiSuccessExample {json} Sikeres válasz:
     *     HTTP/1.1 200 OK
     *     { "message": "Sikeres kijelentkezés." }
     *
     * @apiError (401) {String} message Hiányzó vagy érvénytelen token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sikeres kijelentkezés.']);
    }
}
