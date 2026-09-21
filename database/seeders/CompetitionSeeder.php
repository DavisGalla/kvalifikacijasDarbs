<?php

namespace Database\Seeders;

use App\Models\Competition;
use App\Models\Result;
use App\Models\Sport;
use App\Models\Team;
use App\Models\TeamMember;
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

        $official = User::firstOrCreate(
            ['email' => 'official@example.com'],
            [
                'name' => 'Match Official',
                'username' => 'matchofficial',
                'password' => 'password',
            ],
        );

        $players = collect([
            ['name' => 'Alex Morgan', 'email' => 'player1@example.com', 'username' => 'player1'],
            ['name' => 'Jordan Lee', 'email' => 'player2@example.com', 'username' => 'player2'],
            ['name' => 'Sam Rivera', 'email' => 'player3@example.com', 'username' => 'player3'],
            ['name' => 'Taylor Brooks', 'email' => 'player4@example.com', 'username' => 'player4'],
            ['name' => 'Casey Nguyen', 'email' => 'player5@example.com', 'username' => 'player5'],
            ['name' => 'Morgan Diaz', 'email' => 'player6@example.com', 'username' => 'player6'],
            ['name' => 'Riley Chen', 'email' => 'player7@example.com', 'username' => 'player7'],
            ['name' => 'Jamie Patel', 'email' => 'player8@example.com', 'username' => 'player8'],
        ])->map(fn (array $player): User => User::firstOrCreate(
            ['email' => $player['email']],
            [
                'name' => $player['name'],
                'username' => $player['username'],
                'password' => 'password',
            ],
        ));

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
            $model = Sport::firstOrCreate(
                ['slug' => $sport['slug']],
                ['name' => $sport['name'], 'result_type' => 'score'],
            );

            return [$sport['slug'] => $model];
        });

        // Two teams per sport, each with a captain and two members drawn from the player pool.
        $teams = $sports->mapWithKeys(function (Sport $sport, string $slug) use ($players): array {
            $teamNames = [
                ucfirst($slug).' Falcons',
                ucfirst($slug).' Wolves',
            ];

            $sportTeams = collect($teamNames)->values()->map(function (string $name, int $index) use ($sport, $players): Team {
                $captain = $players[$index * 2];
                $member = $players[$index * 2 + 1];

                $team = Team::firstOrCreate(
                    ['name' => $name, 'sport_id' => $sport->id],
                    ['captain_id' => $captain->id, 'is_public' => true],
                );

                TeamMember::firstOrCreate(
                    ['team_id' => $team->id, 'user_id' => $captain->id],
                    ['role' => 'captain', 'joined_at' => now()],
                );

                TeamMember::firstOrCreate(
                    ['team_id' => $team->id, 'user_id' => $member->id],
                    ['role' => 'member', 'joined_at' => now()],
                );

                return $team;
            });

            return [$slug => $sportTeams];
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

            $record = Competition::updateOrCreate(
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
                    'registration_mode' => 'team',
                    'status' => 'published',
                ],
            );

            // Every official officiates the first three competitions.
            if ($index < 3) {
                $record->officials()->syncWithoutDetaching([$official->id => ['assigned_at' => now()]]);
            }

            // Register the two teams for this competition's sport.
            $sportTeams = $teams[$competition['sport']];

            foreach ($sportTeams as $team) {
                $record->registrations()->updateOrCreate(
                    ['registrant_type' => 'team', 'registrant_id' => $team->id],
                    ['status' => 'confirmed', 'registered_at' => now()],
                );
            }

            // Record a result for the first two competitions to demonstrate the leaderboard.
            if ($index < 2) {
                foreach ($sportTeams as $teamIndex => $team) {
                    Result::updateOrCreate(
                        [
                            'competition_id' => $record->id,
                            'registrant_type' => 'team',
                            'registrant_id' => $team->id,
                        ],
                        ['value' => $teamIndex === 0 ? 3 : 1],
                    );
                }
            }
        }
    }
}
