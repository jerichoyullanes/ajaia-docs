<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $owner = User::updateOrCreate(
            ['email' => 'owner@example.com'],
            [
                'name' => 'Owner',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );
        $reviewer = User::updateOrCreate(
            ['email' => 'reviewer@example.com'],
            [
                'name' => 'Reviewer',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );

        $welcomeDocument = $owner->documents()->updateOrCreate(
            ['title' => 'Welcome to the Editor'],
            [
                'content' => [
                    'type' => 'doc',
                    'content' => [
                        [
                            'type' => 'heading',
                            'attrs' => ['level' => 1],
                            'content' => [
                                ['type' => 'text', 'text' => 'Welcome to the Editor'],
                            ],
                        ],
                        [
                            'type' => 'paragraph',
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => 'Bold text',
                                    'marks' => [['type' => 'bold']],
                                ],
                                ['type' => 'text', 'text' => ', '],
                                [
                                    'type' => 'text',
                                    'text' => 'italic text',
                                    'marks' => [['type' => 'italic']],
                                ],
                                ['type' => 'text', 'text' => ', and '],
                                [
                                    'type' => 'text',
                                    'text' => 'underlined text',
                                    'marks' => [['type' => 'underline']],
                                ],
                                ['type' => 'text', 'text' => '.'],
                            ],
                        ],
                        [
                            'type' => 'bulletList',
                            'content' => [
                                [
                                    'type' => 'listItem',
                                    'content' => [
                                        [
                                            'type' => 'paragraph',
                                            'content' => [
                                                ['type' => 'text', 'text' => 'Bullet list item'],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'type' => 'orderedList',
                            'attrs' => ['start' => 1],
                            'content' => [
                                [
                                    'type' => 'listItem',
                                    'content' => [
                                        [
                                            'type' => 'paragraph',
                                            'content' => [
                                                ['type' => 'text', 'text' => 'Ordered list item'],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        );
        $privateDocument = $owner->documents()->updateOrCreate(
            ['title' => 'Private Notes'],
            ['content' => null],
        );
        $reviewerDocument = $reviewer->documents()->updateOrCreate(
            ['title' => 'Reviewer Notes'],
            ['content' => null],
        );

        $welcomeDocument->sharedWith()->syncWithoutDetaching([
            $reviewer->getKey() => ['permission' => 'edit'],
        ]);
        $privateDocument->sharedWith()->detach();
        $reviewerDocument->sharedWith()->syncWithoutDetaching([
            $owner->getKey() => ['permission' => 'view'],
        ]);
    }
}
