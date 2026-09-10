<?php

namespace Database\Seeders;

use App\Models\Competition;
use App\Models\Sport;
use App\Models\User;
use Illuminate\Database\Seeder;

class CompetitionSeeder extends Seeder
{
    public function run(): void
    {
        $organizer = User::firstOrCreate(
            ['email' => 'organizer@example.com'],
            [
                'name' => 'Competition Organizer',
                'password' => 'password',
            ],
        );

        Competition::where('organizer_id', $organizer->id)
            ->whereIn('title', [
                'Riverside 5K',
                'City Sprint 10K',
                'Hill Country Ride',
                'Lakeside Time Trial',
                'Open Water Classic',
                'Harbor Swim Challenge',
                'Greenway Triathlon',
                'Sunrise Sprint Triathlon',
                'Autumn Trail Run',
                'Community Fun Run',
            ])
            ->delete();

        $sports = collect([
            ['name' => 'Football', 'slug' => 'football'],
            ['name' => 'Basketball', 'slug' => 'basketball'],
            ['name' => 'Volleyball', 'slug' => 'volleyball'],
            ['name' => 'Rugby', 'slug' => 'rugby'],
        ])->mapWithKeys(function (array $sport): array {
            $model = Sport::firstOrCreate(['slug' => $sport['slug']], ['name' => $sport['name']]);

            return [$sport['slug'] => $model];
        });

        $competitions = [
            ['title' => 'City Football Cup', 'sport' => 'football', 'location' => 'Municipal Stadium', 'max_participants' => 160],
            ['title' => 'Riverside Football League', 'sport' => 'football', 'location' => 'Riverside Sports Ground', 'max_participants' => 120],
            ['title' => 'Downtown Basketball Classic', 'sport' => 'basketball', 'location' => 'Central Sports Hall', 'max_participants' => 96],
            ['title' => 'Community Basketball Cup', 'sport' => 'basketball', 'location' => 'Westside Arena', 'max_participants' => 80],
            ['title' => 'Summer Volleyball Open', 'sport' => 'volleyball', 'location' => 'Beachside Courts', 'max_participants' => 72],
            ['title' => 'Indoor Volleyball League', 'sport' => 'volleyball', 'location' => 'North Recreation Centre', 'max_participants' => 96],
            ['title' => 'County Rugby Sevens', 'sport' => 'rugby', 'location' => 'County Rugby Club', 'max_participants' => 140],
            ['title' => 'Autumn Rugby Cup', 'sport' => 'rugby', 'location' => 'Pine Ridge Grounds', 'max_participants' => 120],
            ['title' => 'University Football Challenge', 'sport' => 'football', 'location' => 'University Stadium', 'max_participants' => 180],
            ['title' => 'Regional Basketball Finals', 'sport' => 'basketball', 'location' => 'Regional Arena', 'max_participants' => 128],
        ];

        foreach ($competitions as $index => $competition) {
            $startTime = now()->addWeeks($index + 1)->setTime(9, 0);

            Competition::updateOrCreate(
                ['title' => $competition['title']],
                [
                    'organizer_id' => $organizer->id,
                    'sport_id' => $sports[$competition['sport']]->id,
                    'description' => "Join us for the {$competition['title']}.",
                    'location' => $competition['location'],
                    'start_time' => $startTime,
                    'end_time' => $startTime->copy()->addHours(2),
                    'registration_deadline' => $startTime->copy()->subDays(3),
                    'max_participants' => $competition['max_participants'],
                    'status' => 'published',
                ],
            );
        }
    }
}
