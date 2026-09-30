<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreShareRequest;
use App\Models\Document;
use App\Models\DocumentShare;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class DocumentShareController extends Controller
{
    use AuthorizesRequests;

    /**
     * Share the document with the specified user.
     */
    public function store(StoreShareRequest $request, Document $document): RedirectResponse
    {
        $this->authorize('share', $document);

        $user = User::query()
            ->where('email', $request->validated('email'))
            ->firstOrFail();

        DocumentShare::updateOrCreate(
            [
                'document_id' => $document->id,
                'user_id' => $user->id,
            ],
            [
                'permission' => $request->validated('permission'),
            ],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Document shared.')]);

        return back();
    }

    /**
     * Remove the specified user's access to the document.
     */
    public function destroy(Document $document, User $user): RedirectResponse
    {
        $this->authorize('share', $document);

        $share = DocumentShare::query()
            ->where('document_id', $document->id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $share->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Document unshared.')]);

        return back();
    }
}
