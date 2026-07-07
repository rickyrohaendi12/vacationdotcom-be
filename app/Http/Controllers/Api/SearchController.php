<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SearchScheduleRequest;
use App\Models\Schedule;
use Illuminate\Http\JsonResponse;

class SearchController extends Controller
{
    public function __invoke(SearchScheduleRequest $request): JsonResponse
    {
        $type        = $request->type;
        $origin      = strtoupper($request->origin);
        $destination = strtoupper($request->destination);
        $date        = $request->date;
        $passengers  = $request->passengers ?? 1;
        $classFilter = $request->class;
        $sort        = $request->sort ?? 'departure_asc';

        $query = Schedule::query()
            ->with([
                'route.operator',
                'route.origin',
                'route.destination',
                'scheduleClasses.transportClass',
                'scheduleClasses.seats',
            ])
            ->whereHas('route', function ($q) use ($type, $origin, $destination) {
                $q->where('type', $type)
                  ->whereHas('origin', fn($q) => $q->where('code', $origin))
                  ->whereHas('destination', fn($q) => $q->where('code', $destination));
            })
            ->whereDate('departure_time', $date);

        if ($classFilter) {
            $query->whereHas('scheduleClasses.transportClass', function ($q) use ($classFilter) {
                $q->where('name', $classFilter);
            });
        }

        $query->whereHas('scheduleClasses', function ($q) use ($passengers) {
            $q->whereHas('seats', function ($s) {
                $s->where('status', 'available');
            }, '>=', $passengers);
        });

        $schedules = $query->get();

        $results = $schedules->map(function (Schedule $schedule) use ($classFilter, $passengers) {
            $departure = $schedule->departure_time;
            $arrival   = $schedule->arrival_time;
            $duration  = $departure->diffInMinutes($arrival);

            $classes = $schedule->scheduleClasses
                ->when($classFilter, fn($c) => $c->filter(
                    fn($sc) => $sc->transportClass?->name === $classFilter
                ))
                ->map(function ($sc) use ($passengers) {
                    $availableSeats = $sc->seats->where('status', 'available')->count();
                    return [
                        'id'              => $sc->id,
                        'class_name'      => $sc->transportClass?->name,
                        'facilities'      => $sc->transportClass?->facilities ?? [],
                        'price'           => (float) $sc->price,
                        'total_price'     => (float) $sc->price * $passengers,
                        'available_seats' => $availableSeats,
                        'is_available'    => $availableSeats >= $passengers,
                    ];
                })
                ->values();

            $lowestPrice = $classes->where('is_available', true)->min('price') ?? 0;

            return [
                'schedule_id'      => $schedule->id,
                'flight_code'      => $schedule->route->code,
                'operator'         => [
                    'id'   => $schedule->route->operator->id,
                    'name' => $schedule->route->operator->name,
                    'logo' => $schedule->route->operator->logo,
                ],
                'origin'           => [
                    'code' => $schedule->route->origin->code,
                    'name' => $schedule->route->origin->name,
                    'city' => $schedule->route->origin->city,
                ],
                'destination'      => [
                    'code' => $schedule->route->destination->code,
                    'name' => $schedule->route->destination->name,
                    'city' => $schedule->route->destination->city,
                ],
                'departure_time'   => $departure->toIso8601String(),
                'arrival_time'     => $arrival->toIso8601String(),
                'duration_minutes' => $duration,
                'duration_label'   => $this->formatDuration($duration),
                'classes'          => $classes,
                'lowest_price'     => $lowestPrice,
            ];
        });

        $results = match ($sort) {
            'price_asc'      => $results->sortBy('lowest_price'),
            'price_desc'     => $results->sortByDesc('lowest_price'),
            'departure_desc' => $results->sortByDesc('departure_time'),
            'duration_asc'   => $results->sortBy('duration_minutes'),
            default          => $results->sortBy('departure_time'),
        };

        return response()->json([
            'meta' => [
                'type'        => $type,
                'origin'      => $origin,
                'destination' => $destination,
                'date'        => $date,
                'passengers'  => $passengers,
                'total'       => $results->count(),
            ],
            'data' => $results->values(),
        ]);
    }

    private function formatDuration(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $mins  = $minutes % 60;
        return $hours > 0
            ? "{$hours}j " . ($mins > 0 ? "{$mins}m" : '')
            : "{$mins}m";
    }
}
