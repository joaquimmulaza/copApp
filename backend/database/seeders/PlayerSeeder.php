<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class PlayerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $playersData = [
            // PORTUGAL (API ID 27)
            27 => [
                [
                    'api_football_id' => 2934,
                    'name' => 'Diogo Costa',
                    'firstname' => 'Diogo',
                    'lastname' => 'Meireles da Costa',
                    'birth_date' => '1999-09-19',
                    'nationality' => 'Portugal',
                    'age' => 26,
                    'height' => 186.00,
                    'weight' => 82.00,
                    'photo_url' => 'https://media.api-sports.io/football/players/2934.png',
                    'position' => 'Goalkeeper',
                    'number' => '1',
                    'stats' => [
                        'appearances' => 3, 'goals' => 0, 'assists' => 0, 'yellow_cards' => 0, 'red_cards' => 0,
                        'minutes_played' => 270, 'rating' => 7.20, 'shots_total' => 0, 'shots_on' => 0,
                        'passes_total' => 90, 'passes_accuracy' => 82.50, 'tackles' => 0, 'dribbles_success' => 0
                    ]
                ],
                [
                    'api_football_id' => 567,
                    'name' => 'Rúben Dias',
                    'firstname' => 'Rúben',
                    'lastname' => 'dos Santos Gato Alves Dias',
                    'birth_date' => '1997-05-14',
                    'nationality' => 'Portugal',
                    'age' => 29,
                    'height' => 187.00,
                    'weight' => 82.00,
                    'photo_url' => 'https://media.api-sports.io/football/players/567.png',
                    'position' => 'Defender',
                    'number' => '4',
                    'stats' => [
                        'appearances' => 3, 'goals' => 0, 'assists' => 0, 'yellow_cards' => 1, 'red_cards' => 0,
                        'minutes_played' => 270, 'rating' => 7.15, 'shots_total' => 2, 'shots_on' => 0,
                        'passes_total' => 210, 'passes_accuracy' => 91.20, 'tackles' => 6, 'dribbles_success' => 1
                    ]
                ],
                [
                    'api_football_id' => 1485,
                    'name' => 'Bruno Fernandes',
                    'firstname' => 'Bruno',
                    'lastname' => 'Miguel Borges Fernandes',
                    'birth_date' => '1994-09-08',
                    'nationality' => 'Portugal',
                    'age' => 31,
                    'height' => 179.00,
                    'weight' => 69.00,
                    'photo_url' => 'https://media.api-sports.io/football/players/1485.png',
                    'position' => 'Midfielder',
                    'number' => '8',
                    'stats' => [
                        'appearances' => 3, 'goals' => 1, 'assists' => 3, 'yellow_cards' => 0, 'red_cards' => 0,
                        'minutes_played' => 255, 'rating' => 8.10, 'shots_total' => 8, 'shots_on' => 4,
                        'passes_total' => 180, 'passes_accuracy' => 85.60, 'tackles' => 4, 'dribbles_success' => 3
                    ]
                ],
                [
                    'api_football_id' => 874,
                    'name' => 'Cristiano Ronaldo',
                    'firstname' => 'Cristiano Ronaldo',
                    'lastname' => 'dos Santos Aveiro',
                    'birth_date' => '1985-02-05',
                    'nationality' => 'Portugal',
                    'age' => 41,
                    'height' => 187.00,
                    'weight' => 83.00,
                    'photo_url' => 'https://media.api-sports.io/football/players/874.png',
                    'position' => 'Attacker',
                    'number' => '7',
                    'stats' => [
                        'appearances' => 3, 'goals' => 4, 'assists' => 1, 'yellow_cards' => 1, 'red_cards' => 0,
                        'minutes_played' => 260, 'rating' => 8.45, 'shots_total' => 15, 'shots_on' => 9,
                        'passes_total' => 65, 'passes_accuracy' => 81.30, 'tackles' => 1, 'dribbles_success' => 4
                    ]
                ],
                [
                    'api_football_id' => 633,
                    'name' => 'Bernardo Silva',
                    'firstname' => 'Bernardo',
                    'lastname' => 'Mota Veiga de Carvalho e Silva',
                    'birth_date' => '1994-08-10',
                    'nationality' => 'Portugal',
                    'age' => 31,
                    'height' => 173.00,
                    'weight' => 64.00,
                    'photo_url' => 'https://media.api-sports.io/football/players/633.png',
                    'position' => 'Attacker',
                    'number' => '10',
                    'stats' => [
                        'appearances' => 3, 'goals' => 1, 'assists' => 1, 'yellow_cards' => 0, 'red_cards' => 0,
                        'minutes_played' => 240, 'rating' => 7.80, 'shots_total' => 5, 'shots_on' => 2,
                        'passes_total' => 160, 'passes_accuracy' => 88.90, 'tackles' => 3, 'dribbles_success' => 5
                    ]
                ]
            ],
            // BRASIL (API ID 6)
            6 => [
                [
                    'api_football_id' => 276,
                    'name' => 'Alisson Becker',
                    'firstname' => 'Alisson',
                    'lastname' => 'Ramses Becker',
                    'birth_date' => '1992-10-02',
                    'nationality' => 'Brasil',
                    'age' => 33,
                    'height' => 191.00,
                    'weight' => 91.00,
                    'photo_url' => 'https://media.api-sports.io/football/players/276.png',
                    'position' => 'Goalkeeper',
                    'number' => '1',
                    'stats' => [
                        'appearances' => 3, 'goals' => 0, 'assists' => 0, 'yellow_cards' => 0, 'red_cards' => 0,
                        'minutes_played' => 270, 'rating' => 7.30, 'shots_total' => 0, 'shots_on' => 0,
                        'passes_total' => 80, 'passes_accuracy' => 85.00, 'tackles' => 0, 'dribbles_success' => 0
                    ]
                ],
                [
                    'api_football_id' => 259,
                    'name' => 'Marquinhos',
                    'firstname' => 'Marcos',
                    'lastname' => 'Aoás Corrêa',
                    'birth_date' => '1994-05-14',
                    'nationality' => 'Brasil',
                    'age' => 32,
                    'height' => 183.00,
                    'weight' => 75.00,
                    'photo_url' => 'https://media.api-sports.io/football/players/259.png',
                    'position' => 'Defender',
                    'number' => '4',
                    'stats' => [
                        'appearances' => 3, 'goals' => 0, 'assists' => 0, 'yellow_cards' => 0, 'red_cards' => 0,
                        'minutes_played' => 270, 'rating' => 7.10, 'shots_total' => 1, 'shots_on' => 0,
                        'passes_total' => 230, 'passes_accuracy' => 92.40, 'tackles' => 5, 'dribbles_success' => 0
                    ]
                ],
                [
                    'api_football_id' => 2289,
                    'name' => 'Bruno Guimarães',
                    'firstname' => 'Bruno',
                    'lastname' => 'Guimarães Rodriguez Moura',
                    'birth_date' => '1997-11-16',
                    'nationality' => 'Brasil',
                    'age' => 28,
                    'height' => 182.00,
                    'weight' => 74.00,
                    'photo_url' => 'https://media.api-sports.io/football/players/2289.png',
                    'position' => 'Midfielder',
                    'number' => '5',
                    'stats' => [
                        'appearances' => 3, 'goals' => 0, 'assists' => 1, 'yellow_cards' => 2, 'red_cards' => 0,
                        'minutes_played' => 260, 'rating' => 7.40, 'shots_total' => 4, 'shots_on' => 1,
                        'passes_total' => 195, 'passes_accuracy' => 89.10, 'tackles' => 10, 'dribbles_success' => 2
                    ]
                ],
                [
                    'api_football_id' => 647,
                    'name' => 'Vinícius Júnior',
                    'firstname' => 'Vinícius',
                    'lastname' => 'José Paixão de Oliveira Júnior',
                    'birth_date' => '2000-07-12',
                    'nationality' => 'Brasil',
                    'age' => 25,
                    'height' => 176.00,
                    'weight' => 73.00,
                    'photo_url' => 'https://media.api-sports.io/football/players/647.png',
                    'position' => 'Attacker',
                    'number' => '7',
                    'stats' => [
                        'appearances' => 2, 'goals' => 2, 'assists' => 2, 'yellow_cards' => 0, 'red_cards' => 0,
                        'minutes_played' => 170, 'rating' => 8.35, 'shots_total' => 9, 'shots_on' => 5,
                        'passes_total' => 75, 'passes_accuracy' => 83.20, 'tackles' => 2, 'dribbles_success' => 12
                    ],
                    'status' => [
                        'type' => 'injury',
                        'reason' => 'Thigh Muscle Strain',
                        'start_date' => '2026-06-12',
                        'expected_return' => '2026-07-02',
                        'is_active' => true,
                    ]
                ],
                [
                    'api_football_id' => 648,
                    'name' => 'Rodrygo',
                    'firstname' => 'Rodrygo',
                    'lastname' => 'Silva de Goes',
                    'birth_date' => '2001-01-09',
                    'nationality' => 'Brasil',
                    'age' => 25,
                    'height' => 174.00,
                    'weight' => 64.00,
                    'photo_url' => 'https://media.api-sports.io/football/players/648.png',
                    'position' => 'Attacker',
                    'number' => '10',
                    'stats' => [
                        'appearances' => 3, 'goals' => 2, 'assists' => 0, 'yellow_cards' => 0, 'red_cards' => 0,
                        'minutes_played' => 250, 'rating' => 7.90, 'shots_total' => 8, 'shots_on' => 4,
                        'passes_total' => 110, 'passes_accuracy' => 86.40, 'tackles' => 3, 'dribbles_success' => 7
                    ]
                ]
            ],
            // ANGOLA (API ID 16)
            16 => [
                [
                    'api_football_id' => 4321,
                    'name' => 'Neblú',
                    'firstname' => 'Adilson',
                    'lastname' => 'Cipriano da Cruz',
                    'birth_date' => '1993-12-16',
                    'nationality' => 'Angola',
                    'age' => 32,
                    'height' => 184.00,
                    'weight' => 78.00,
                    'photo_url' => 'https://media.api-sports.io/football/players/4321.png',
                    'position' => 'Goalkeeper',
                    'number' => '1',
                    'stats' => [
                        'appearances' => 3, 'goals' => 0, 'assists' => 0, 'yellow_cards' => 1, 'red_cards' => 0,
                        'minutes_played' => 270, 'rating' => 7.05, 'shots_total' => 0, 'shots_on' => 0,
                        'passes_total' => 75, 'passes_accuracy' => 71.40, 'tackles' => 0, 'dribbles_success' => 0
                    ]
                ],
                [
                    'api_football_id' => 4322,
                    'name' => 'Kialonda Gaspar',
                    'firstname' => 'Kialonda',
                    'lastname' => 'Gaspar',
                    'birth_date' => '1997-09-27',
                    'nationality' => 'Angola',
                    'age' => 28,
                    'height' => 193.00,
                    'weight' => 85.00,
                    'photo_url' => 'https://media.api-sports.io/football/players/4322.png',
                    'position' => 'Defender',
                    'number' => '3',
                    'stats' => [
                        'appearances' => 3, 'goals' => 0, 'assists' => 0, 'yellow_cards' => 0, 'red_cards' => 0,
                        'minutes_played' => 270, 'rating' => 7.20, 'shots_total' => 2, 'shots_on' => 1,
                        'passes_total' => 130, 'passes_accuracy' => 84.60, 'tackles' => 9, 'dribbles_success' => 1
                    ]
                ],
                [
                    'api_football_id' => 4323,
                    'name' => 'Fredy',
                    'firstname' => 'Alfredo',
                    'lastname' => 'Kulembe Ribeiro',
                    'birth_date' => '1990-03-27',
                    'nationality' => 'Angola',
                    'age' => 36,
                    'height' => 176.00,
                    'weight' => 70.00,
                    'photo_url' => 'https://media.api-sports.io/football/players/4323.png',
                    'position' => 'Midfielder',
                    'number' => '16',
                    'stats' => [
                        'appearances' => 3, 'goals' => 0, 'assists' => 2, 'yellow_cards' => 1, 'red_cards' => 0,
                        'minutes_played' => 260, 'rating' => 7.35, 'shots_total' => 3, 'shots_on' => 1,
                        'passes_total' => 145, 'passes_accuracy' => 82.00, 'tackles' => 5, 'dribbles_success' => 2
                    ]
                ],
                [
                    'api_football_id' => 4324,
                    'name' => 'Gelson Dala',
                    'firstname' => 'Jacinto',
                    'lastname' => 'Muondo Dala',
                    'birth_date' => '1996-07-13',
                    'nationality' => 'Angola',
                    'age' => 29,
                    'height' => 175.00,
                    'weight' => 71.00,
                    'photo_url' => 'https://media.api-sports.io/football/players/4324.png',
                    'position' => 'Attacker',
                    'number' => '10',
                    'stats' => [
                        'appearances' => 3, 'goals' => 3, 'assists' => 0, 'yellow_cards' => 0, 'red_cards' => 0,
                        'minutes_played' => 250, 'rating' => 8.00, 'shots_total' => 9, 'shots_on' => 6,
                        'passes_total' => 70, 'passes_accuracy' => 79.50, 'tackles' => 2, 'dribbles_success' => 6
                    ]
                ],
                [
                    'api_football_id' => 4325,
                    'name' => 'Mabululu',
                    'firstname' => 'Agostinho',
                    'lastname' => 'Mabululu',
                    'birth_date' => '1992-06-01',
                    'nationality' => 'Angola',
                    'age' => 34,
                    'height' => 181.00,
                    'weight' => 78.00,
                    'photo_url' => 'https://media.api-sports.io/football/players/4325.png',
                    'position' => 'Attacker',
                    'number' => '19',
                    'stats' => [
                        'appearances' => 3, 'goals' => 1, 'assists' => 1, 'yellow_cards' => 0, 'red_cards' => 0,
                        'minutes_played' => 200, 'rating' => 7.45, 'shots_total' => 6, 'shots_on' => 3,
                        'passes_total' => 40, 'passes_accuracy' => 75.00, 'tackles' => 1, 'dribbles_success' => 2
                    ]
                ]
            ],
            // ARGENTINA (API ID 26)
            26 => [
                [
                    'api_football_id' => 189,
                    'name' => 'Emiliano Martínez',
                    'firstname' => 'Emiliano',
                    'lastname' => 'Martínez',
                    'birth_date' => '1992-09-02',
                    'nationality' => 'Argentina',
                    'age' => 33,
                    'height' => 195.00,
                    'weight' => 88.00,
                    'photo_url' => 'https://media.api-sports.io/football/players/189.png',
                    'position' => 'Goalkeeper',
                    'number' => '23',
                    'stats' => [
                        'appearances' => 3, 'goals' => 0, 'assists' => 0, 'yellow_cards' => 1, 'red_cards' => 0,
                        'minutes_played' => 270, 'rating' => 7.40, 'shots_total' => 0, 'shots_on' => 0,
                        'passes_total' => 85, 'passes_accuracy' => 78.90, 'tackles' => 0, 'dribbles_success' => 0
                    ]
                ],
                [
                    'api_football_id' => 234,
                    'name' => 'Cristian Romero',
                    'firstname' => 'Cristian',
                    'lastname' => 'Romero',
                    'birth_date' => '1998-04-27',
                    'nationality' => 'Argentina',
                    'age' => 28,
                    'height' => 185.00,
                    'weight' => 79.00,
                    'photo_url' => 'https://media.api-sports.io/football/players/234.png',
                    'position' => 'Defender',
                    'number' => '13',
                    'stats' => [
                        'appearances' => 3, 'goals' => 0, 'assists' => 0, 'yellow_cards' => 0, 'red_cards' => 0,
                        'minutes_played' => 270, 'rating' => 7.30, 'shots_total' => 2, 'shots_on' => 1,
                        'passes_total' => 190, 'passes_accuracy' => 90.50, 'tackles' => 8, 'dribbles_success' => 2
                    ]
                ],
                [
                    'api_football_id' => 543,
                    'name' => 'Alexis Mac Allister',
                    'firstname' => 'Alexis',
                    'lastname' => 'Mac Allister',
                    'birth_date' => '1998-12-24',
                    'nationality' => 'Argentina',
                    'age' => 27,
                    'height' => 176.00,
                    'weight' => 72.00,
                    'photo_url' => 'https://media.api-sports.io/football/players/543.png',
                    'position' => 'Midfielder',
                    'number' => '20',
                    'stats' => [
                        'appearances' => 3, 'goals' => 1, 'assists' => 1, 'yellow_cards' => 0, 'red_cards' => 0,
                        'minutes_played' => 260, 'rating' => 7.65, 'shots_total' => 5, 'shots_on' => 2,
                        'passes_total' => 175, 'passes_accuracy' => 88.00, 'tackles' => 7, 'dribbles_success' => 3
                    ]
                ],
                [
                    'api_football_id' => 154,
                    'name' => 'Lionel Messi',
                    'firstname' => 'Lionel',
                    'lastname' => 'Andrés Messi Cuccittini',
                    'birth_date' => '1987-06-24',
                    'nationality' => 'Argentina',
                    'age' => 38,
                    'height' => 170.00,
                    'weight' => 72.00,
                    'photo_url' => 'https://media.api-sports.io/football/players/154.png',
                    'position' => 'Attacker',
                    'number' => '10',
                    'stats' => [
                        'appearances' => 3, 'goals' => 3, 'assists' => 3, 'yellow_cards' => 0, 'red_cards' => 0,
                        'minutes_played' => 270, 'rating' => 8.70, 'shots_total' => 14, 'shots_on' => 8,
                        'passes_total' => 150, 'passes_accuracy' => 86.50, 'tackles' => 1, 'dribbles_success' => 11
                    ]
                ],
                [
                    'api_football_id' => 987,
                    'name' => 'Lautaro Martínez',
                    'firstname' => 'Lautaro',
                    'lastname' => 'Martínez',
                    'birth_date' => '1997-08-22',
                    'nationality' => 'Argentina',
                    'age' => 28,
                    'height' => 174.00,
                    'weight' => 72.00,
                    'photo_url' => 'https://media.api-sports.io/football/players/987.png',
                    'position' => 'Attacker',
                    'number' => '22',
                    'stats' => [
                        'appearances' => 3, 'goals' => 2, 'assists' => 0, 'yellow_cards' => 0, 'red_cards' => 0,
                        'minutes_played' => 210, 'rating' => 7.80, 'shots_total' => 10, 'shots_on' => 6,
                        'passes_total' => 45, 'passes_accuracy' => 77.80, 'tackles' => 2, 'dribbles_success' => 2
                    ]
                ]
            ],
            // FRANÇA (API ID 2)
            2 => [
                [
                    'api_football_id' => 200,
                    'name' => 'Mike Maignan',
                    'firstname' => 'Mike',
                    'lastname' => 'Maignan',
                    'birth_date' => '1995-07-03',
                    'nationality' => 'França',
                    'age' => 30,
                    'height' => 191.00,
                    'weight' => 89.00,
                    'photo_url' => 'https://media.api-sports.io/football/players/200.png',
                    'position' => 'Goalkeeper',
                    'number' => '16',
                    'stats' => [
                        'appearances' => 3, 'goals' => 0, 'assists' => 0, 'yellow_cards' => 0, 'red_cards' => 0,
                        'minutes_played' => 270, 'rating' => 7.35, 'shots_total' => 0, 'shots_on' => 0,
                        'passes_total' => 78, 'passes_accuracy' => 84.60, 'tackles' => 0, 'dribbles_success' => 0
                    ]
                ],
                [
                    'api_football_id' => 321,
                    'name' => 'William Saliba',
                    'firstname' => 'William',
                    'lastname' => 'Saliba',
                    'birth_date' => '2001-03-24',
                    'nationality' => 'França',
                    'age' => 25,
                    'height' => 192.00,
                    'weight' => 92.00,
                    'photo_url' => 'https://media.api-sports.io/football/players/321.png',
                    'position' => 'Defender',
                    'number' => '4',
                    'stats' => [
                        'appearances' => 3, 'goals' => 0, 'assists' => 0, 'yellow_cards' => 0, 'red_cards' => 0,
                        'minutes_played' => 270, 'rating' => 7.25, 'shots_total' => 1, 'shots_on' => 0,
                        'passes_total' => 220, 'passes_accuracy' => 93.50, 'tackles' => 4, 'dribbles_success' => 1
                    ]
                ],
                [
                    'api_football_id' => 642,
                    'name' => 'Aurélien Tchouaméni',
                    'firstname' => 'Aurélien',
                    'lastname' => 'Tchouaméni',
                    'birth_date' => '2000-01-27',
                    'nationality' => 'França',
                    'age' => 26,
                    'height' => 187.00,
                    'weight' => 81.00,
                    'photo_url' => 'https://media.api-sports.io/football/players/642.png',
                    'position' => 'Midfielder',
                    'number' => '8',
                    'stats' => [
                        'appearances' => 3, 'goals' => 0, 'assists' => 0, 'yellow_cards' => 1, 'red_cards' => 0,
                        'minutes_played' => 250, 'rating' => 7.30, 'shots_total' => 4, 'shots_on' => 1,
                        'passes_total' => 190, 'passes_accuracy' => 90.00, 'tackles' => 8, 'dribbles_success' => 2
                    ]
                ],
                [
                    'api_football_id' => 278,
                    'name' => 'Kylian Mbappé',
                    'firstname' => 'Kylian',
                    'lastname' => 'Mbappé Lottin',
                    'birth_date' => '1998-12-20',
                    'nationality' => 'França',
                    'age' => 27,
                    'height' => 178.00,
                    'weight' => 73.00,
                    'photo_url' => 'https://media.api-sports.io/football/players/278.png',
                    'position' => 'Attacker',
                    'number' => '10',
                    'stats' => [
                        'appearances' => 3, 'goals' => 3, 'assists' => 1, 'yellow_cards' => 0, 'red_cards' => 0,
                        'minutes_played' => 265, 'rating' => 8.40, 'shots_total' => 13, 'shots_on' => 8,
                        'passes_total' => 95, 'passes_accuracy' => 82.10, 'tackles' => 1, 'dribbles_success' => 9
                    ]
                ],
                [
                    'api_football_id' => 872,
                    'name' => 'Antoine Griezmann',
                    'firstname' => 'Antoine',
                    'lastname' => 'Griezmann',
                    'birth_date' => '1991-03-21',
                    'nationality' => 'França',
                    'age' => 35,
                    'height' => 176.00,
                    'weight' => 73.00,
                    'photo_url' => 'https://media.api-sports.io/football/players/872.png',
                    'position' => 'Attacker',
                    'number' => '7',
                    'stats' => [
                        'appearances' => 3, 'goals' => 1, 'assists' => 2, 'yellow_cards' => 0, 'red_cards' => 0,
                        'minutes_played' => 240, 'rating' => 7.90, 'shots_total' => 6, 'shots_on' => 3,
                        'passes_total' => 130, 'passes_accuracy' => 84.60, 'tackles' => 3, 'dribbles_success' => 3
                    ]
                ]
            ],
            // ESPANHA (API ID 9)
            9 => [
                [
                    'api_football_id' => 99,
                    'name' => 'Unai Simón',
                    'firstname' => 'Unai',
                    'lastname' => 'Simón Mendibil',
                    'birth_date' => '1997-06-11',
                    'nationality' => 'Espanha',
                    'age' => 28,
                    'height' => 190.00,
                    'weight' => 88.00,
                    'photo_url' => 'https://media.api-sports.io/football/players/99.png',
                    'position' => 'Goalkeeper',
                    'number' => '23',
                    'stats' => [
                        'appearances' => 3, 'goals' => 0, 'assists' => 0, 'yellow_cards' => 0, 'red_cards' => 0,
                        'minutes_played' => 270, 'rating' => 7.20, 'shots_total' => 0, 'shots_on' => 0,
                        'passes_total' => 105, 'passes_accuracy' => 89.50, 'tackles' => 0, 'dribbles_success' => 0
                    ]
                ],
                [
                    'api_football_id' => 101,
                    'name' => 'Robin Le Normand',
                    'firstname' => 'Robin',
                    'lastname' => 'Aime Robert Le Normand',
                    'birth_date' => '1996-11-11',
                    'nationality' => 'Espanha',
                    'age' => 29,
                    'height' => 187.00,
                    'weight' => 80.00,
                    'photo_url' => 'https://media.api-sports.io/football/players/101.png',
                    'position' => 'Defender',
                    'number' => '3',
                    'stats' => [
                        'appearances' => 3, 'goals' => 0, 'assists' => 0, 'yellow_cards' => 1, 'red_cards' => 0,
                        'minutes_played' => 270, 'rating' => 7.10, 'shots_total' => 1, 'shots_on' => 0,
                        'passes_total' => 240, 'passes_accuracy' => 94.20, 'tackles' => 5, 'dribbles_success' => 0
                    ]
                ],
                [
                    'api_football_id' => 102,
                    'name' => 'Rodri',
                    'firstname' => 'Rodrigo',
                    'lastname' => 'Hernández Cascante',
                    'birth_date' => '1996-06-22',
                    'nationality' => 'Espanha',
                    'age' => 29,
                    'height' => 190.00,
                    'weight' => 82.00,
                    'photo_url' => 'https://media.api-sports.io/football/players/102.png',
                    'position' => 'Midfielder',
                    'number' => '16',
                    'stats' => [
                        'appearances' => 3, 'goals' => 1, 'assists' => 1, 'yellow_cards' => 1, 'red_cards' => 0,
                        'minutes_played' => 270, 'rating' => 8.00, 'shots_total' => 6, 'shots_on' => 3,
                        'passes_total' => 280, 'passes_accuracy' => 93.80, 'tackles' => 8, 'dribbles_success' => 2
                    ]
                ],
                [
                    'api_football_id' => 103,
                    'name' => 'Lamine Yamal',
                    'firstname' => 'Lamine Yamal',
                    'lastname' => 'Nasraoui Ebana',
                    'birth_date' => '2007-07-13',
                    'nationality' => 'Espanha',
                    'age' => 18,
                    'height' => 178.00,
                    'weight' => 66.00,
                    'photo_url' => 'https://media.api-sports.io/football/players/103.png',
                    'position' => 'Attacker',
                    'number' => '19',
                    'stats' => [
                        'appearances' => 3, 'goals' => 2, 'assists' => 2, 'yellow_cards' => 0, 'red_cards' => 0,
                        'minutes_played' => 230, 'rating' => 8.25, 'shots_total' => 10, 'shots_on' => 5,
                        'passes_total' => 110, 'passes_accuracy' => 85.00, 'tackles' => 4, 'dribbles_success' => 14
                    ]
                ],
                [
                    'api_football_id' => 104,
                    'name' => 'Nico Williams',
                    'firstname' => 'Nicholas',
                    'lastname' => 'Williams Arthuer',
                    'birth_date' => '2002-07-12',
                    'nationality' => 'Espanha',
                    'age' => 23,
                    'height' => 181.00,
                    'weight' => 74.00,
                    'photo_url' => 'https://media.api-sports.io/football/players/104.png',
                    'position' => 'Attacker',
                    'number' => '17',
                    'stats' => [
                        'appearances' => 2, 'goals' => 1, 'assists' => 1, 'yellow_cards' => 0, 'red_cards' => 0,
                        'minutes_played' => 160, 'rating' => 7.85, 'shots_total' => 5, 'shots_on' => 2,
                        'passes_total' => 50, 'passes_accuracy' => 80.00, 'tackles' => 1, 'dribbles_success' => 8
                    ],
                    'status' => [
                        'type' => 'suspension',
                        'reason' => 'Yellow Card Accumulation',
                        'start_date' => '2026-06-16',
                        'expected_return' => '2026-06-21',
                        'is_active' => true,
                    ]
                ]
            ],
            // ALEMANHA (API ID 25)
            25 => [
                [
                    'api_football_id' => 250,
                    'name' => 'Marc-André ter Stegen',
                    'firstname' => 'Marc-André',
                    'lastname' => 'ter Stegen',
                    'birth_date' => '1992-04-30',
                    'nationality' => 'Alemanha',
                    'age' => 34,
                    'height' => 187.00,
                    'weight' => 85.00,
                    'photo_url' => 'https://media.api-sports.io/football/players/250.png',
                    'position' => 'Goalkeeper',
                    'number' => '1',
                    'stats' => [
                        'appearances' => 3, 'goals' => 0, 'assists' => 0, 'yellow_cards' => 0, 'red_cards' => 0,
                        'minutes_played' => 270, 'rating' => 7.15, 'shots_total' => 0, 'shots_on' => 0,
                        'passes_total' => 110, 'passes_accuracy' => 90.00, 'tackles' => 0, 'dribbles_success' => 0
                    ]
                ],
                [
                    'api_football_id' => 251,
                    'name' => 'Antonio Rüdiger',
                    'firstname' => 'Antonio',
                    'lastname' => 'Rüdiger',
                    'birth_date' => '1993-03-03',
                    'nationality' => 'Alemanha',
                    'age' => 33,
                    'height' => 190.00,
                    'weight' => 85.00,
                    'photo_url' => 'https://media.api-sports.io/football/players/251.png',
                    'position' => 'Defender',
                    'number' => '2',
                    'stats' => [
                        'appearances' => 3, 'goals' => 0, 'assists' => 0, 'yellow_cards' => 2, 'red_cards' => 0,
                        'minutes_played' => 270, 'rating' => 7.20, 'shots_total' => 3, 'shots_on' => 1,
                        'passes_total' => 200, 'passes_accuracy' => 89.50, 'tackles' => 7, 'dribbles_success' => 0
                    ]
                ],
                [
                    'api_football_id' => 252,
                    'name' => 'Florian Wirtz',
                    'firstname' => 'Florian',
                    'lastname' => 'Richard Wirtz',
                    'birth_date' => '2003-05-03',
                    'nationality' => 'Alemanha',
                    'age' => 23,
                    'height' => 176.00,
                    'weight' => 71.00,
                    'photo_url' => 'https://media.api-sports.io/football/players/252.png',
                    'position' => 'Midfielder',
                    'number' => '10',
                    'stats' => [
                        'appearances' => 3, 'goals' => 2, 'assists' => 1, 'yellow_cards' => 0, 'red_cards' => 0,
                        'minutes_played' => 250, 'rating' => 8.15, 'shots_total' => 8, 'shots_on' => 5,
                        'passes_total' => 140, 'passes_accuracy' => 87.10, 'tackles' => 3, 'dribbles_success' => 8
                    ]
                ],
                [
                    'api_football_id' => 253,
                    'name' => 'Jamal Musiala',
                    'firstname' => 'Jamal',
                    'lastname' => 'Musiala',
                    'birth_date' => '2003-02-26',
                    'nationality' => 'Alemanha',
                    'age' => 23,
                    'height' => 184.00,
                    'weight' => 72.00,
                    'photo_url' => 'https://media.api-sports.io/football/players/253.png',
                    'position' => 'Midfielder',
                    'number' => '42',
                    'stats' => [
                        'appearances' => 3, 'goals' => 1, 'assists' => 2, 'yellow_cards' => 0, 'red_cards' => 0,
                        'minutes_played' => 245, 'rating' => 8.20, 'shots_total' => 7, 'shots_on' => 3,
                        'passes_total' => 125, 'passes_accuracy' => 86.40, 'tackles' => 4, 'dribbles_success' => 12
                    ]
                ],
                [
                    'api_football_id' => 254,
                    'name' => 'Kai Havertz',
                    'firstname' => 'Kai',
                    'lastname' => 'Havertz',
                    'birth_date' => '1999-06-11',
                    'nationality' => 'Alemanha',
                    'age' => 27,
                    'height' => 193.00,
                    'weight' => 83.00,
                    'photo_url' => 'https://media.api-sports.io/football/players/254.png',
                    'position' => 'Attacker',
                    'number' => '7',
                    'stats' => [
                        'appearances' => 3, 'goals' => 1, 'assists' => 1, 'yellow_cards' => 0, 'red_cards' => 0,
                        'minutes_played' => 230, 'rating' => 7.50, 'shots_total' => 9, 'shots_on' => 4,
                        'passes_total' => 60, 'passes_accuracy' => 80.00, 'tackles' => 2, 'dribbles_success' => 3
                    ]
                ]
            ],
            // ESTADOS UNIDOS (API ID 2384)
            2384 => [
                [
                    'api_football_id' => 23841,
                    'name' => 'Matt Turner',
                    'firstname' => 'Matt',
                    'lastname' => 'Turner',
                    'birth_date' => '1994-06-24',
                    'nationality' => 'Estados Unidos',
                    'age' => 31,
                    'height' => 191.00,
                    'weight' => 79.00,
                    'photo_url' => 'https://media.api-sports.io/football/players/23841.png',
                    'position' => 'Goalkeeper',
                    'number' => '1',
                    'stats' => [
                        'appearances' => 3, 'goals' => 0, 'assists' => 0, 'yellow_cards' => 0, 'red_cards' => 0,
                        'minutes_played' => 270, 'rating' => 6.95, 'shots_total' => 0, 'shots_on' => 0,
                        'passes_total' => 65, 'passes_accuracy' => 72.30, 'tackles' => 0, 'dribbles_success' => 0
                    ]
                ],
                [
                    'api_football_id' => 23842,
                    'name' => 'Antonee Robinson',
                    'firstname' => 'Antonee',
                    'lastname' => 'Robinson',
                    'birth_date' => '1997-08-08',
                    'nationality' => 'Estados Unidos',
                    'age' => 28,
                    'height' => 183.00,
                    'weight' => 77.00,
                    'photo_url' => 'https://media.api-sports.io/football/players/23842.png',
                    'position' => 'Defender',
                    'number' => '5',
                    'stats' => [
                        'appearances' => 3, 'goals' => 0, 'assists' => 1, 'yellow_cards' => 1, 'red_cards' => 0,
                        'minutes_played' => 270, 'rating' => 7.10, 'shots_total' => 1, 'shots_on' => 0,
                        'passes_total' => 140, 'passes_accuracy' => 81.40, 'tackles' => 8, 'dribbles_success' => 4
                    ]
                ],
                [
                    'api_football_id' => 23843,
                    'name' => 'Weston McKennie',
                    'firstname' => 'Weston',
                    'lastname' => 'McKennie',
                    'birth_date' => '1998-08-28',
                    'nationality' => 'Estados Unidos',
                    'age' => 27,
                    'height' => 185.00,
                    'weight' => 84.00,
                    'photo_url' => 'https://media.api-sports.io/football/players/23843.png',
                    'position' => 'Midfielder',
                    'number' => '8',
                    'stats' => [
                        'appearances' => 3, 'goals' => 0, 'assists' => 0, 'yellow_cards' => 0, 'red_cards' => 0,
                        'minutes_played' => 260, 'rating' => 7.00, 'shots_total' => 4, 'shots_on' => 1,
                        'passes_total' => 120, 'passes_accuracy' => 82.50, 'tackles' => 6, 'dribbles_success' => 2
                    ]
                ],
                [
                    'api_football_id' => 23844,
                    'name' => 'Christian Pulisic',
                    'firstname' => 'Christian',
                    'lastname' => 'Pulisic',
                    'birth_date' => '1998-09-18',
                    'nationality' => 'Estados Unidos',
                    'age' => 27,
                    'height' => 177.00,
                    'weight' => 73.00,
                    'photo_url' => 'https://media.api-sports.io/football/players/23844.png',
                    'position' => 'Attacker',
                    'number' => '10',
                    'stats' => [
                        'appearances' => 3, 'goals' => 2, 'assists' => 1, 'yellow_cards' => 0, 'red_cards' => 0,
                        'minutes_played' => 270, 'rating' => 7.95, 'shots_total' => 8, 'shots_on' => 5,
                        'passes_total' => 90, 'passes_accuracy' => 84.00, 'tackles' => 3, 'dribbles_success' => 7
                    ]
                ],
                [
                    'api_football_id' => 23845,
                    'name' => 'Folarin Balogun',
                    'firstname' => 'Folarin',
                    'lastname' => 'Balogun',
                    'birth_date' => '2001-07-03',
                    'nationality' => 'Estados Unidos',
                    'age' => 24,
                    'height' => 178.00,
                    'weight' => 68.00,
                    'photo_url' => 'https://media.api-sports.io/football/players/23845.png',
                    'position' => 'Attacker',
                    'number' => '9',
                    'stats' => [
                        'appearances' => 3, 'goals' => 1, 'assists' => 0, 'yellow_cards' => 0, 'red_cards' => 0,
                        'minutes_played' => 220, 'rating' => 7.20, 'shots_total' => 7, 'shots_on' => 3,
                        'passes_total' => 35, 'passes_accuracy' => 78.60, 'tackles' => 1, 'dribbles_success' => 2
                    ]
                ]
            ]
        ];

        foreach ($playersData as $teamApiId => $players) {
            // Get the database ID of the team
            $team = DB::table('teams')->where('api_football_id', $teamApiId)->first();

            if (!$team) {
                continue;
            }

            foreach ($players as $player) {
                $stats = $player['stats'];
                $status = $player['status'] ?? null;

                // Remove stats and status from player data array to match DB fields
                unset($player['stats']);
                unset($player['status']);

                $player['team_id'] = $team->id;
                $player['created_at'] = now();
                $player['updated_at'] = now();

                // Insert or update player
                $playerId = DB::table('players')->updateOrInsert(
                    ['api_football_id' => $player['api_football_id']],
                    $player
                );

                // Fetch the player ID (since updateOrInsert doesn't return ID directly on update)
                $dbPlayer = DB::table('players')->where('api_football_id', $player['api_football_id'])->first();

                if ($dbPlayer) {
                    // Seed stats
                    $stats['player_id'] = $dbPlayer->id;
                    $stats['team_id'] = $team->id;
                    $stats['created_at'] = now();
                    $stats['updated_at'] = now();

                    DB::table('player_stats')->updateOrInsert(
                        ['player_id' => $dbPlayer->id],
                        $stats
                    );

                    // Seed status (if any)
                    if ($status) {
                        $status['player_id'] = $dbPlayer->id;
                        $status['team_id'] = $team->id;
                        $status['api_football_id'] = $dbPlayer->api_football_id;
                        $status['created_at'] = now();
                        $status['updated_at'] = now();

                        DB::table('player_statuses')->updateOrInsert(
                            ['player_id' => $dbPlayer->id],
                            $status
                        );
                    }
                }
            }
        }
    }
}
