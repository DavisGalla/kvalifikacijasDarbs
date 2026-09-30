<?php

namespace App\Http\Controllers;

use App\Models\TeamMember;
use App\Services\GoogleCalendarService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();

        $stats = [
            'personalBests' => $user->personalBests()->count(),
            'upcomingCompetitions' => $user->registrations()
                ->whereIn('status', ['pending', 'confirmed'])
                ->whereHas('competition', fn ($query) => $query->where('start_time', '>', now()))
                ->count(),
            'teams' => TeamMember::where('user_id', $user->id)->count(),
        ];

        $recentBests = $user->personalBests()
            ->with('entries')
            ->latest('updated_at')
            ->limit(4)
            ->get();

        return view('dashboard', [
            'stats' => $stats,
            'recentBests' => $recentBests,
            ...$this->upcomingCalendarEvents($user),
        ]);
    }

    /**
     * A thin, failure-tolerant peek at the next few Google Calendar events.
     * The dashboard must still render if Google is unreachable or the token is dead.
     */
    private function upcomingCalendarEvents($user): array
    {
        if (! $user->google_access_token) {
            return ['calendarConnected' => false, 'calendarEvents' => [], 'calendarError' => false];
        }

        try {
            $events = Cache::remember("dashboard.calendar.{$user->id}", 300, function () use ($user) {
                return collect((new GoogleCalendarService($user))->upcomingEvents(5))
                    ->map(fn ($e) => [
                        'id' => $e->getId(),
                        'title' => $e->getSummary() ?? '(No title)',
                        'start' => $e->getStart()->dateTime ?? $e->getStart()->date,
                        'all_day' => $e->getStart()->dateTime === null,
                    ])->all();
            });

            return ['calendarConnected' => true, 'calendarEvents' => $events, 'calendarError' => false];
        } catch (\Exception $e) {
            Log::warning('Dashboard calendar fetch failed: '.$e->getMessage(), ['user_id' => $user->id]);

            return ['calendarConnected' => true, 'calendarEvents' => [], 'calendarError' => true];
        }
    }
}
