<?php

namespace App\Http\Controllers;

use App\Http\Requests\CityRequest;
use App\Http\Resources\CityResource;
use App\Models\City;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CitiesController extends Controller
{
    /**
     * @api {get} /cities Városok listája
     * @apiName GetCities
     * @apiGroup Cities
     * @apiVersion 1.0.0
     * @apiDescription Lapozott városlista név szerint rendezve. Kereshető a város nevében, és szűrhető megyére; a két feltétel együtt is használható.
     *
     * @apiQuery {String} [search] Keresés a város nevében (részszó is jó).
     * @apiQuery {Number} [county] Szűrés a megye azonosítójára.
     * @apiQuery {Number} [page=1] Oldalszám.
     * @apiQuery {Number{1..100}} [per_page=25] Elemek száma oldalanként.
     *
     * @apiSuccess {Object[]} data A városok.
     * @apiSuccess {Number} data.id Azonosító.
     * @apiSuccess {String} data.name Város neve.
     * @apiSuccess {Number} data.zip_code Irányítószám.
     * @apiSuccess {Number} data.population Lakosságszám.
     * @apiSuccess {Number} data.id_county A megye azonosítója.
     * @apiSuccess {Object} data.county A megye (id, name, badge, badge_url).
     * @apiSuccess {Object} links Lapozó linkek (first, last, prev, next).
     * @apiSuccess {Object} meta Lapozási adatok (current_page, last_page, per_page, total, ...).
     *
     * @apiSuccessExample {json} Sikeres válasz (/cities?search=Szeged&county=7):
     *     HTTP/1.1 200 OK
     *     {
     *       "data": [
     *         {
     *           "id": 1234,
     *           "name": "Szeged",
     *           "zip_code": 6720,
     *           "population": 4210,
     *           "id_county": 7,
     *           "county": { "id": 7, "name": "Csongrád-Csanád", "badge": "images/badges/csongrad-csanad.jpg", "badge_url": "http://localhost:8000/images/badges/csongrad-csanad.jpg" }
     *         }
     *       ],
     *       "links": { "first": "http://localhost:8000/api/cities?page=1", "last": "http://localhost:8000/api/cities?page=1", "prev": null, "next": null },
     *       "meta": { "current_page": 1, "last_page": 1, "per_page": 25, "total": 1 }
     *     }
     */
    public function index(Request $request)
    {
        $perPage = min(max((int) $request->query('per_page', 25), 1), 100);

        $cities = City::with('county')
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('name', 'like', '%'.$request->search.'%');
            })
            ->when($request->filled('county'), function ($query) use ($request) {
                $query->where('id_county', $request->county);
            })
            ->orderBy('name')
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();

        return CityResource::collection($cities);
    }

    /**
     * @api {get} /cities/:id Egy város lekérése
     * @apiName GetCity
     * @apiGroup Cities
     * @apiVersion 1.0.0
     *
     * @apiParam {Number} id A város azonosítója.
     *
     * @apiSuccess {Object} data A város (mezői a listával megegyeznek, a megyével együtt).
     *
     * @apiSuccessExample {json} Sikeres válasz:
     *     HTTP/1.1 200 OK
     *     {
     *       "data": {
     *         "id": 1234,
     *         "name": "Szeged",
     *         "zip_code": 6720,
     *         "population": 4210,
     *         "id_county": 7,
     *         "county": { "id": 7, "name": "Csongrád-Csanád", "badge": "images/badges/csongrad-csanad.jpg", "badge_url": "http://localhost:8000/images/badges/csongrad-csanad.jpg" }
     *       }
     *     }
     *
     * @apiError (404) {String} message A város nem található.
     */
    public function show(string $id)
    {
        return new CityResource(City::with('county')->findOrFail($id));
    }

    /**
     * @api {post} /cities Új város létrehozása
     * @apiName CreateCity
     * @apiGroup Cities
     * @apiVersion 1.0.0
     * @apiPermission Authenticated
     * @apiUse Authenticated
     *
     * @apiBody {String{3..50}} name A város neve.
     * @apiBody {Number} id_county A megye azonosítója (létező megye).
     * @apiBody {Number{1000..9999}} zip_code Irányítószám.
     * @apiBody {Number{0..}} population Lakosságszám.
     *
     * @apiParamExample {json} Kérés:
     *     {
     *       "name": "Újváros",
     *       "id_county": 7,
     *       "zip_code": 6720,
     *       "population": 1500
     *     }
     *
     * @apiSuccess (201) {Object} data A létrehozott város.
     *
     * @apiError (401) {String} message Hiányzó vagy érvénytelen token.
     * @apiError (422) {Object} errors Mezőnkénti hibaüzenetek.
     *
     * @apiErrorExample {json} Validációs hiba:
     *     HTTP/1.1 422 Unprocessable Entity
     *     {
     *       "message": "A megadott megye nem létezik.",
     *       "errors": { "id_county": ["A megadott megye nem létezik."] }
     *     }
     */
    public function store(CityRequest $request): JsonResponse
    {
        $city = City::create($request->validated());

        return (new CityResource($city->load('county')))->response()->setStatusCode(201);
    }

    /**
     * @api {put} /cities/:id Város módosítása
     * @apiName UpdateCity
     * @apiGroup Cities
     * @apiVersion 1.0.0
     * @apiPermission Authenticated
     * @apiUse Authenticated
     * @apiDescription PUT esetén minden mező kötelező, PATCH esetén (<code>PATCH /cities/:id</code>) elég a módosítandó mezőket küldeni.
     *
     * @apiParam {Number} id A város azonosítója.
     * @apiBody {String{3..50}} name A város neve.
     * @apiBody {Number} id_county A megye azonosítója.
     * @apiBody {Number{1000..9999}} zip_code Irányítószám.
     * @apiBody {Number{0..}} population Lakosságszám.
     *
     * @apiSuccess {Object} data A módosított város.
     *
     * @apiError (401) {String} message Hiányzó vagy érvénytelen token.
     * @apiError (404) {String} message A város nem található.
     * @apiError (422) {Object} errors Mezőnkénti hibaüzenetek.
     */
    public function update(CityRequest $request, string $id)
    {
        $city = City::findOrFail($id);
        $city->update($request->validated());

        return new CityResource($city->load('county'));
    }

    /**
     * @api {delete} /cities/:id Város törlése
     * @apiName DeleteCity
     * @apiGroup Cities
     * @apiVersion 1.0.0
     * @apiPermission Authenticated
     * @apiUse Authenticated
     *
     * @apiParam {Number} id A város azonosítója.
     *
     * @apiSuccess {String} message Visszaigazolás.
     * @apiSuccess {Number} id A törölt város azonosítója.
     *
     * @apiSuccessExample {json} Sikeres válasz:
     *     HTTP/1.1 200 OK
     *     { "message": "Város sikeresen törölve.", "id": 1234 }
     *
     * @apiError (401) {String} message Hiányzó vagy érvénytelen token.
     * @apiError (404) {String} message A város nem található.
     */
    public function destroy(string $id): JsonResponse
    {
        City::findOrFail($id)->delete();

        return response()->json(['message' => 'Város sikeresen törölve.', 'id' => (int) $id]);
    }
}
