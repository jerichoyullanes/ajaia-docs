<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;

test('owners can import a text file as a document', function () {
    $owner = User::factory()->create();

    $response = $this->actingAs($owner)->post(route('documents.import'), [
        'file' => UploadedFile::fake()->createWithContent(
            'meeting-notes.txt',
            "First paragraph\ncontinued.\n\nSecond paragraph.",
        ),
    ]);

    $document = $owner->documents()->firstOrFail();

    $response->assertRedirect(route('documents.show', $document));
    $response->assertInertiaFlash('toast.message', 'Document imported.');
    expect($document->title)->toBe('meeting-notes.txt');
    expect($document->content)->toBe([
        'type' => 'doc',
        'content' => [
            [
                'type' => 'paragraph',
                'content' => [
                    ['type' => 'text', 'text' => "First paragraph\ncontinued."],
                ],
            ],
            [
                'type' => 'paragraph',
                'content' => [
                    ['type' => 'text', 'text' => 'Second paragraph.'],
                ],
            ],
        ],
    ]);
});

test('owners can import markdown headings paragraphs and lists without inline formatting', function () {
    $owner = User::factory()->create();

    $this->actingAs($owner)->post(route('documents.import'), [
        'file' => UploadedFile::fake()->createWithContent(
            'guide.md',
            "# Guide\n\nParagraph with **literal** text.\n\n- first\n- second\n\n1. ordered one\n2. ordered two\n\n### Details",
        ),
    ])->assertRedirect(route('documents.show', $owner->documents()->firstOrFail()));

    expect($owner->documents()->firstOrFail()->content)->toBe([
        'type' => 'doc',
        'content' => [
            [
                'type' => 'heading',
                'attrs' => ['level' => 1],
                'content' => [['type' => 'text', 'text' => 'Guide']],
            ],
            [
                'type' => 'paragraph',
                'content' => [['type' => 'text', 'text' => 'Paragraph with **literal** text.']],
            ],
            [
                'type' => 'bulletList',
                'content' => [
                    ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'first']]]]],
                    ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'second']]]]],
                ],
            ],
            [
                'type' => 'orderedList',
                'attrs' => ['start' => 1],
                'content' => [
                    ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'ordered one']]]]],
                    ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'ordered two']]]]],
                ],
            ],
            [
                'type' => 'heading',
                'attrs' => ['level' => 3],
                'content' => [['type' => 'text', 'text' => 'Details']],
            ],
        ],
    ]);
});

test('document import rejects a missing file', function () {
    $owner = User::factory()->create();

    $this->actingAs($owner)
        ->post(route('documents.import'))
        ->assertSessionHasErrors('file');

    expect($owner->documents()->count())->toBe(0);
});

test('document import rejects unsupported file extensions', function () {
    $owner = User::factory()->create();

    $this->actingAs($owner)
        ->post(route('documents.import'), [
            'file' => UploadedFile::fake()->createWithContent('report.pdf', 'not a supported document'),
        ])
        ->assertSessionHasErrors('file');

    expect($owner->documents()->count())->toBe(0);
});

test('document import rejects files larger than one megabyte', function () {
    $owner = User::factory()->create();

    $this->actingAs($owner)
        ->post(route('documents.import'), [
            'file' => UploadedFile::fake()->create('large.txt', 1025, 'text/plain'),
        ])
        ->assertSessionHasErrors('file');

    expect($owner->documents()->count())->toBe(0);
});

test('document import rejects filenames too long for the document title', function () {
    $owner = User::factory()->create();

    $this->actingAs($owner)
        ->post(route('documents.import'), [
            'file' => UploadedFile::fake()->createWithContent(
                str_repeat('a', 252).'.txt',
                'valid text',
            ),
        ])
        ->assertSessionHasErrors([
            'file' => 'The filename may not exceed 255 characters.',
        ]);

    expect($owner->documents()->count())->toBe(0);
});

test('document import rejects non UTF-8 text without creating a document', function () {
    $owner = User::factory()->create();

    $this->actingAs($owner)
        ->post(route('documents.import'), [
            'file' => UploadedFile::fake()->createWithContent('invalid.txt', "\xFF"),
        ])
        ->assertSessionHasErrors([
            'file' => 'The uploaded file must contain valid UTF-8 text.',
        ]);

    expect($owner->documents()->count())->toBe(0);
});

test('document import rejects empty files without creating a document', function () {
    $owner = User::factory()->create();

    $this->actingAs($owner)
        ->post(route('documents.import'), [
            'file' => UploadedFile::fake()->createWithContent('empty.txt', " \r\n\t"),
        ])
        ->assertSessionHasErrors([
            'file' => 'The uploaded file must not be empty.',
        ]);

    expect($owner->documents()->count())->toBe(0);
});

test('guests are redirected when opening a document', function () {
    $owner = User::factory()->create();
    $document = $owner->documents()->create([
        'title' => 'Private notes',
        'content' => ['type' => 'doc', 'content' => [['type' => 'paragraph']]],
    ]);

    $this->get(route('documents.show', $document))
        ->assertRedirect(route('login'));
});

test('owners can create a blank document and open it', function () {
    $owner = User::factory()->create();

    $response = $this->actingAs($owner)->post(route('documents.store'));

    $document = $owner->documents()->firstOrFail();

    $response->assertRedirect(route('documents.show', $document));
    expect($document->title)->toBe('Untitled document');
    expect($document->content)->toBe([
        'type' => 'doc',
        'content' => [
            ['type' => 'paragraph'],
        ],
    ]);

    $this->get(route('documents.show', $document))
        ->assertInertia(fn (Assert $page) => $page
            ->component('documents/edit')
            ->where('document.id', $document->id)
            ->where('document.title', 'Untitled document')
            ->where('can.update', true)
            ->where('can.share', true)
            ->where('can.delete', true),
        );
});

test('owners can update a document and receive a success flash', function () {
    $owner = User::factory()->create();
    $document = $owner->documents()->create([
        'title' => 'Original title',
        'content' => ['type' => 'doc', 'content' => [['type' => 'paragraph']]],
    ]);
    $content = [
        'type' => 'doc',
        'content' => [
            ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Saved']]],
        ],
    ];

    $response = $this->actingAs($owner)->put(route('documents.update', $document), [
        'title' => 'Renamed document',
        'content' => $content,
    ]);

    $response->assertRedirect();
    $response->assertInertiaFlash('toast.message', 'Document saved.');
    expect($document->refresh()->title)->toBe('Renamed document');
    expect($document->content)->toBe($content);
});

test('owners can delete documents and return to the dashboard', function () {
    $owner = User::factory()->create();
    $document = $owner->documents()->create([
        'title' => 'To delete',
        'content' => ['type' => 'doc', 'content' => [['type' => 'paragraph']]],
    ]);

    $response = $this->actingAs($owner)->delete(route('documents.destroy', $document));

    $response->assertRedirect(route('dashboard'));
    $this->assertModelMissing($document);
});

test('edit collaborators can view and update but cannot delete', function () {
    $owner = User::factory()->create();
    $editor = User::factory()->create();
    $document = $owner->documents()->create([
        'title' => 'Shared document',
        'content' => ['type' => 'doc', 'content' => [['type' => 'paragraph']]],
    ]);
    $document->sharedWith()->attach($editor, ['permission' => 'edit']);

    $this->actingAs($editor)
        ->get(route('documents.show', $document))
        ->assertInertia(fn (Assert $page) => $page
            ->where('can.update', true)
            ->where('can.share', false)
            ->where('can.delete', false),
        );

    $this->put(route('documents.update', $document), [
        'title' => 'Editor update',
        'content' => ['type' => 'doc', 'content' => [['type' => 'paragraph']]],
    ])->assertRedirect();

    $this->delete(route('documents.destroy', $document))->assertForbidden();
    expect($document->refresh()->title)->toBe('Editor update');
});

test('view collaborators can view but cannot update or delete', function () {
    $owner = User::factory()->create();
    $viewer = User::factory()->create();
    $document = $owner->documents()->create([
        'title' => 'Read-only document',
        'content' => ['type' => 'doc', 'content' => [['type' => 'paragraph']]],
    ]);
    $document->sharedWith()->attach($viewer, ['permission' => 'view']);

    $this->actingAs($viewer)
        ->get(route('documents.show', $document))
        ->assertInertia(fn (Assert $page) => $page
            ->where('can.update', false)
            ->where('can.share', false)
            ->where('can.delete', false),
        );

    $this->put(route('documents.update', $document), [
        'title' => 'Unauthorized edit',
        'content' => ['type' => 'doc', 'content' => [['type' => 'paragraph']]],
    ])->assertForbidden();

    $this->delete(route('documents.destroy', $document))->assertForbidden();
    expect($document->refresh()->title)->toBe('Read-only document');
});

test('strangers cannot view, update, or delete a document', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $document = $owner->documents()->create([
        'title' => 'Private document',
        'content' => ['type' => 'doc', 'content' => [['type' => 'paragraph']]],
    ]);

    $this->actingAs($stranger)
        ->get(route('documents.show', $document))
        ->assertForbidden()
        ->assertInertia(fn (Assert $page) => $page
            ->component('errors/403'),
        );

    $this->put(route('documents.update', $document), [
        'title' => 'Unauthorized edit',
        'content' => ['type' => 'doc', 'content' => [['type' => 'paragraph']]],
    ])->assertForbidden();

    $this->delete(route('documents.destroy', $document))->assertForbidden();
    expect($document->refresh()->title)->toBe('Private document');
});

test('owners can share with users and update existing permissions', function () {
    $owner = User::factory()->create();
    $collaborator = User::factory()->create();
    $document = $owner->documents()->create([
        'title' => 'Shared document',
        'content' => ['type' => 'doc', 'content' => [['type' => 'paragraph']]],
    ]);

    $this->actingAs($owner)
        ->post(route('documents.shares.store', $document), [
            'email' => $collaborator->email,
            'permission' => 'view',
        ])
        ->assertRedirect()
        ->assertInertiaFlash('toast.message', 'Document shared.');

    $this->post(route('documents.shares.store', $document), [
        'email' => $collaborator->email,
        'permission' => 'edit',
    ])->assertRedirect();

    $this->assertDatabaseCount('document_shares', 1);
    $this->assertDatabaseHas('document_shares', [
        'document_id' => $document->id,
        'user_id' => $collaborator->id,
        'permission' => 'edit',
    ]);

    $this->get(route('documents.show', $document))
        ->assertInertia(fn (Assert $page) => $page
            ->has('shares', 1)
            ->where('shares.0.id', $collaborator->id)
            ->where('shares.0.permission', 'edit'),
        );
});

test('owners can remove a collaborator share', function () {
    $owner = User::factory()->create();
    $collaborator = User::factory()->create();
    $document = $owner->documents()->create([
        'title' => 'Shared document',
        'content' => ['type' => 'doc', 'content' => [['type' => 'paragraph']]],
    ]);
    $document->sharedWith()->attach($collaborator, ['permission' => 'view']);

    $this->actingAs($owner)
        ->delete(route('documents.shares.destroy', [$document, $collaborator]))
        ->assertRedirect()
        ->assertInertiaFlash('toast.message', 'Document unshared.');

    $this->assertDatabaseCount('document_shares', 0);
});

test('shared collaborators cannot manage document shares', function () {
    $owner = User::factory()->create();
    $editor = User::factory()->create();
    $invitee = User::factory()->create();
    $document = $owner->documents()->create([
        'title' => 'Shared document',
        'content' => ['type' => 'doc', 'content' => [['type' => 'paragraph']]],
    ]);
    $document->sharedWith()->attach($editor, ['permission' => 'edit']);

    $this->actingAs($editor)
        ->post(route('documents.shares.store', $document), [
            'email' => $invitee->email,
            'permission' => 'view',
        ])
        ->assertForbidden();

    $this->delete(route('documents.shares.destroy', [$document, $editor]))
        ->assertForbidden();

    $this->assertDatabaseCount('document_shares', 1);
});

test('owners cannot share documents with themselves', function () {
    $owner = User::factory()->create();
    $document = $owner->documents()->create([
        'title' => 'Private document',
        'content' => ['type' => 'doc', 'content' => [['type' => 'paragraph']]],
    ]);

    $this->actingAs($owner)
        ->post(route('documents.shares.store', $document), [
            'email' => mb_strtoupper($owner->email),
            'permission' => 'edit',
        ])
        ->assertSessionHasErrors([
            'email' => 'You cannot share a document with its owner.',
        ]);

    $this->assertDatabaseCount('document_shares', 0);
});

test('owners can only share with existing users using a valid permission', function () {
    $owner = User::factory()->create();
    $collaborator = User::factory()->create();
    $document = $owner->documents()->create([
        'title' => 'Private document',
        'content' => ['type' => 'doc', 'content' => [['type' => 'paragraph']]],
    ]);

    $this->actingAs($owner)
        ->post(route('documents.shares.store', $document), [
            'email' => 'missing@example.com',
            'permission' => 'edit',
        ])
        ->assertSessionHasErrors('email');

    $this->post(route('documents.shares.store', $document), [
        'email' => $collaborator->email,
        'permission' => 'admin',
    ])->assertSessionHasErrors('permission');

    $this->assertDatabaseCount('document_shares', 0);
});

test('view collaborators cannot see share management information', function () {
    $owner = User::factory()->create();
    $viewer = User::factory()->create();
    $collaborator = User::factory()->create();
    $document = $owner->documents()->create([
        'title' => 'Shared document',
        'content' => ['type' => 'doc', 'content' => [['type' => 'paragraph']]],
    ]);
    $document->sharedWith()->attach([
        $viewer->id => ['permission' => 'view'],
        $collaborator->id => ['permission' => 'edit'],
    ]);

    $this->actingAs($viewer)
        ->get(route('documents.show', $document))
        ->assertInertia(fn (Assert $page) => $page
            ->has('shares', 0)
            ->where('can.share', false)
            ->where('can.update', false),
        );
});

test('document updates reject missing and invalid fields', function (array $payload, array $errors) {
    $owner = User::factory()->create();
    $document = $owner->documents()->create([
        'title' => 'Original title',
        'content' => ['type' => 'doc', 'content' => [['type' => 'paragraph']]],
    ]);

    $this->actingAs($owner)
        ->put(route('documents.update', $document), $payload)
        ->assertSessionHasErrors($errors);

    expect($document->refresh()->title)->toBe('Original title');
})->with([
    'required title and content' => [[], ['title', 'content']],
    'string title' => [['title' => ['invalid'], 'content' => []], ['title']],
    'title maximum length' => [['title' => str_repeat('a', 256), 'content' => []], ['title']],
    'array content' => [['title' => 'Valid title', 'content' => 'invalid'], ['content']],
]);
