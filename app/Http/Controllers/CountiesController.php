<?php

namespace App\Http\Controllers;

use App\Http\Requests\CountyRequest;
use App\Http\Resources\CountyResource;
use App\Models\County;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CountiesController extends Controller
{
    /**
     * @api {get} /counties Megyék listája
     * @apiName GetCounties
     * @apiGroup Counties
     * @apiVersion 1.0.0
     * @apiDescription Az összes megye név szerint rendezve, a hozzájuk tartozó városok összlakosságával. Nincs lapozás.
     *
     * @apiQuery {String} [search] Keresés a megye nevében (részszó is jó).
     *
     * @apiSuccess {Object[]} data A megyék.
     * @apiSuccess {Number} data.id Azonosító.
     * @apiSuccess {String} data.name Megye neve.
     * @apiSuccess {String} [data.badge] A címer tárolt útvonala vagy URL-je.
     * @apiSuccess {String} [data.badge_url] A címer teljes URL-je (null, ha nincs címer).
     * @apiSuccess {Number} data.population A megye városainak összlakossága.
     *
     * @apiSuccessExample {json} Sikeres válasz:
     *     HTTP/1.1 200 OK
     *     {
     *       "data": [
     *         {
     *           "id": 2,
     *           "name": "Bács-Kiskun",
     *           "badge": "images/badges/bacs-kiskun.jpg",
     *           "badge_url": "http://localhost:8000/images/badges/bacs-kiskun.jpg",
     *           "population": 1234567
     *         }
     *       ]
     *     }
     */
    public function index(Request $request)
    {
        $counties = County::withSum('cities', 'population')
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('name', 'like', '%'.$request->search.'%');
            })
            ->orderBy('name')
            ->get();

        return CountyResource::collection($counties);
    }

    /**
     * @api {get} /counties/:id Egy megye lekérése
     * @apiName GetCounty
     * @apiGroup Counties
     * @apiVersion 1.0.0
     *
     * @apiParam {Number} id A megye azonosítója.
     *
     * @apiSuccess {Object} data A megye (mezői a listával megegyeznek).
     *
     * @apiSuccessExample {json} Sikeres válasz:
     *     HTTP/1.1 200 OK
     *     {
     *       "data": {
     *         "id": 2,
     *         "name": "Bács-Kiskun",
     *         "badge": "images/badges/bacs-kiskun.jpg",
     *         "badge_url": "http://localhost:8000/images/badges/bacs-kiskun.jpg",
     *         "population": 1234567
     *       }
     *     }
     *
     * @apiError (404) {String} message A megye nem található.
     */
    public function show(string $id)
    {
        return new CountyResource(County::withSum('cities', 'population')->findOrFail($id));
    }

    /**
     * @api {post} /counties Új megye létrehozása
     * @apiName CreateCounty
     * @apiGroup Counties
     * @apiVersion 1.0.0
     * @apiPermission Authenticated
     * @apiUse Authenticated
     *
     * @apiBody {String{3..50}} name A megye neve.
     * @apiBody {String{..255}} [badge] A címer útvonala vagy URL-je.
     *
     * @apiSuccess (201) {Object} data A létrehozott megye.
     *
     * @apiSuccessExample {json} Sikeres válasz:
     *     HTTP/1.1 201 Created
     *     {
     *       "data": { "id": 21, "name": "Teszt megye", "badge": null, "badge_url": null }
     *     }
     *
     * @apiError (401) {String} message Hiányzó vagy érvénytelen token.
     * @apiError (422) {Object} errors Mezőnkénti hibaüzenetek.
     *
     * @apiErrorExample {json} Validációs hiba:
     *     HTTP/1.1 422 Unprocessable Entity
     *     {
     *       "message": "A megye neve legalább 3 karakter hosszú legyen.",
     *       "errors": { "name": ["A megye neve legalább 3 karakter hosszú legyen."] }
     *     }
     */
    public function store(CountyRequest $request): JsonResponse
    {
        $county = County::create($request->validated());

        return (new CountyResource($county))->response()->setStatusCode(201);
    }

    /**
     * @api {put} /counties/:id Megye módosítása
     * @apiName UpdateCounty
     * @apiGroup Counties
     * @apiVersion 1.0.0
     * @apiPermission Authenticated
     * @apiUse Authenticated
     * @apiDescription PUT esetén a <code>name</code> kötelező, PATCH esetén (<code>PATCH /counties/:id</code>) elég a módosítandó mezőket küldeni.
     *
     * @apiParam {Number} id A megye azonosítója.
     * @apiBody {String{3..50}} name A megye neve.
     * @apiBody {String{..255}} [badge] A címer útvonala vagy URL-je.
     *
     * @apiSuccess {Object} data A módosított megye.
     *
     * @apiError (401) {String} message Hiányzó vagy érvénytelen token.
     * @apiError (404) {String} message A megye nem található.
     * @apiError (422) {Object} errors Mezőnkénti hibaüzenetek.
     */
    public function update(CountyRequest $request, string $id)
    {
        $county = County::findOrFail($id);
        $county->update($request->validated());

        return new CountyResource($county);
    }

    /**
     * @api {delete} /counties/:id Megye törlése
     * @apiName DeleteCounty
     * @apiGroup Counties
     * @apiVersion 1.0.0
     * @apiPermission Authenticated
     * @apiUse Authenticated
     * @apiDescription Csak olyan megye törölhető, amelyhez nem tartozik város.
     *
     * @apiParam {Number} id A megye azonosítója.
     *
     * @apiSuccess {String} message Visszaigazolás.
     * @apiSuccess {Number} id A törölt megye azonosítója.
     *
     * @apiSuccessExample {json} Sikeres válasz:
     *     HTTP/1.1 200 OK
     *     { "message": "Megye sikeresen törölve.", "id": 21 }
     *
     * @apiError (401) {String} message Hiányzó vagy érvénytelen token.
     * @apiError (404) {String} message A megye nem található.
     * @apiError (409) {String} message A megye nem törölhető, mert vannak hozzá rendelt városok.
     *
     * @apiErrorExample {json} Van hozzá rendelt város:
     *     HTTP/1.1 409 Conflict
     *     { "message": "A megye nem törölhető, mert vannak hozzá rendelt városok." }
     */
    public function destroy(string $id): JsonResponse
    {
        $county = County::findOrFail($id);

        if ($county->cities()->exists()) {
            return response()->json(['message' => 'A megye nem törölhető, mert vannak hozzá rendelt városok.'], 409);
        }

        $county->delete();

        return response()->json(['message' => 'Megye sikeresen törölve.', 'id' => (int) $id]);
    }
}
