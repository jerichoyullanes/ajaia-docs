<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\TeamInvitation;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DocumentController extends Controller
{
    /**
     * Display the user's owned and shared documents.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $owned = $user->documents()
            ->select(['id', 'owner_id', 'title', 'updated_at'])
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (Document $document) => [
                'id' => $document->id,
                'title' => $document->title,
                'updatedAt' => $document->updated_at?->toISOString(),
                'can' => [
                    'update' => true,
                    'share' => true,
                    'delete' => true,
                ],
            ]);

        $shared = $user->sharedDocuments()
            ->select(['documents.id', 'documents.owner_id', 'documents.title', 'documents.updated_at'])
            ->with('owner:id,name')
            ->orderByDesc('documents.updated_at')
            ->get()
            ->map(function (Document $document) {
                /** @var Pivot $pivot */
                $pivot = $document->getRelation('pivot');
                $permission = $pivot->getAttribute('permission');

                return [
                    'id' => $document->id,
                    'title' => $document->title,
                    'updatedAt' => $document->updated_at?->toISOString(),
                    'ownerName' => $document->owner->name,
                    'permission' => $permission,
                    'can' => [
                        'update' => $permission === 'edit',
                        'share' => false,
                        'delete' => false,
                    ],
                ];
            });

        $email = strtolower($user->email);

        $pendingInvitations = TeamInvitation::query()
            ->with(['inviter', 'team'])
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereNull('accepted_at')
            ->where(fn ($query) => $query
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>=', now()))
            ->latest()
            ->get()
            ->map(fn (TeamInvitation $invitation) => [
                'code' => $invitation->code,
                'inviterName' => $invitation->inviter->name,
                'team' => [
                    'name' => $invitation->team->name,
                    'slug' => $invitation->team->slug,
                ],
            ]);

        return Inertia::render('dashboard', [
            'owned' => $owned,
            'shared' => $shared,
            'pendingInvitations' => $pendingInvitations,
        ]);
    }
}
