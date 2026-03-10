<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMessageRequest;
use App\Models\User;
use App\Services\MessageService;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    public function __construct(
        private MessageService $messageService
    ) {}

    /**
     * Display the dashboard with list of users and messenger.
     */
    public function index()
    {
        $users = $this->messageService->getUsersWithUnreadCounts(Auth::id());

        return view('dashboard', [
            'users' => $users,
        ]);
    }

    /**
     * Store a new message and broadcast it.
     */
    public function store(StoreMessageRequest $request)
    {
        $validated = $request->validated();

        try {
            $message = $this->messageService->sendMessage(
                Auth::id(),
                (int) $validated['recipient_id'],
                $validated['body']
            );
        } catch (\InvalidArgumentException $e) {
            abort(422, $e->getMessage());
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => $this->messageService->formatMessageForJson($message),
            ], 201);
        }

        return back();
    }

    /**
     * Get messages between the authenticated user and the given user.
     * Marks messages from the other user as read and returns updated unread counts.
     */
    public function messages(User $user)
    {
        try {
            $result = $this->messageService->getConversationWithUnreadCounts(Auth::id(), $user->id);
        } catch (\InvalidArgumentException $e) {
            abort(422, $e->getMessage());
        }

        return response()->json([
            'messages' => $result['messages']->map(fn ($m) => $this->messageService->formatMessageItem($m)),
            'unread_counts' => $result['unread_counts'],
        ]);
    }
}
