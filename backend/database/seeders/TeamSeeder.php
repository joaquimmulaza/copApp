<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class TeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $teams = [
            [
                'api_football_id' => 27,
                'name' => 'Portugal',
                'code' => 'POR',
                'country' => 'Portugal',
                'logo_url' => 'https://media.api-sports.io/football/teams/27.png',
                'group_name' => 'A',
                'venue' => json_encode([
                    'name' => 'Estádio da Luz',
                    'city' => 'Lisboa',
                    'capacity' => 65647,
                ]),
                'coach' => json_encode([
                    'name' => 'Roberto Martínez',
                    'nationality' => 'Spanish',
                    'photo' => 'https://media.api-sports.io/football/coaches/roberto-martinez.png',
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'api_football_id' => 6,
                'name' => 'Brasil',
                'code' => 'BRA',
                'country' => 'Brasil',
                'logo_url' => 'https://media.api-sports.io/football/teams/6.png',
                'group_name' => 'A',
                'venue' => json_encode([
                    'name' => 'Maracanã',
                    'city' => 'Rio de Janeiro',
                    'capacity' => 78838,
                ]),
                'coach' => json_encode([
                    'name' => 'Dorival Júnior',
                    'nationality' => 'Brazilian',
                    'photo' => 'https://media.api-sports.io/football/coaches/dorival-junior.png',
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'api_football_id' => 16,
                'name' => 'Angola',
                'code' => 'ANG',
                'country' => 'Angola',
                'logo_url' => 'https://media.api-sports.io/football/teams/16.png',
                'group_name' => 'B',
                'venue' => json_encode([
                    'name' => 'Estádio 11 de Novembro',
                    'city' => 'Luanda',
                    'capacity' => 50000,
                ]),
                'coach' => json_encode([
                    'name' => 'Pedro Gonçalves',
                    'nationality' => 'Portuguese',
                    'photo' => 'https://media.api-sports.io/football/coaches/pedro-goncalves.png',
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'api_football_id' => 26,
                'name' => 'Argentina',
                'code' => 'ARG',
                'country' => 'Argentina',
                'logo_url' => 'https://media.api-sports.io/football/teams/26.png',
                'group_name' => 'B',
                'venue' => json_encode([
                    'name' => 'Estádio Monumental',
                    'city' => 'Buenos Aires',
                    'capacity' => 84567,
                ]),
                'coach' => json_encode([
                    'name' => 'Lionel Scaloni',
                    'nationality' => 'Argentinian',
                    'photo' => 'https://media.api-sports.io/football/coaches/lionel-scaloni.png',
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'api_football_id' => 2,
                'name' => 'França',
                'code' => 'FRA',
                'country' => 'França',
                'logo_url' => 'https://media.api-sports.io/football/teams/2.png',
                'group_name' => 'C',
                'venue' => json_encode([
                    'name' => 'Stade de France',
                    'city' => 'Saint-Denis',
                    'capacity' => 80698,
                ]),
                'coach' => json_encode([
                    'name' => 'Didier Deschamps',
                    'nationality' => 'French',
                    'photo' => 'https://media.api-sports.io/football/coaches/didier-deschamps.png',
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'api_football_id' => 9,
                'name' => 'Espanha',
                'code' => 'ESP',
                'country' => 'Espanha',
                'logo_url' => 'https://media.api-sports.io/football/teams/9.png',
                'group_name' => 'C',
                'venue' => json_encode([
                    'name' => 'Santiago Bernabéu',
                    'city' => 'Madrid',
                    'capacity' => 81044,
                ]),
                'coach' => json_encode([
                    'name' => 'Luis de la Fuente',
                    'nationality' => 'Spanish',
                    'photo' => 'https://media.api-sports.io/football/coaches/luis-de-la-fuente.png',
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'api_football_id' => 25,
                'name' => 'Alemanha',
                'code' => 'GER',
                'country' => 'Alemanha',
                'logo_url' => 'https://media.api-sports.io/football/teams/25.png',
                'group_name' => 'D',
                'venue' => json_encode([
                    'name' => 'Allianz Arena',
                    'city' => 'Munique',
                    'capacity' => 75000,
                ]),
                'coach' => json_encode([
                    'name' => 'Julian Nagelsmann',
                    'nationality' => 'German',
                    'photo' => 'https://media.api-sports.io/football/coaches/julian-nagelsmann.png',
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'api_football_id' => 2384,
                'name' => 'Estados Unidos',
                'code' => 'USA',
                'country' => 'Estados Unidos',
                'logo_url' => 'https://media.api-sports.io/football/teams/2384.png',
                'group_name' => 'D',
                'venue' => json_encode([
                    'name' => 'MetLife Stadium',
                    'city' => 'East Rutherford',
                    'capacity' => 82500,
                ]),
                'coach' => json_encode([
                    'name' => 'Mauricio Pochettino',
                    'nationality' => 'Argentinian',
                    'photo' => 'https://media.api-sports.io/football/coaches/mauricio-pochettino.png',
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($teams as $team) {
            DB::table('teams')->updateOrInsert(
                ['api_football_id' => $team['api_football_id']],
                $team
            );
        }
    }
}
