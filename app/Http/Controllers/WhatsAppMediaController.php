<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class WhatsAppMediaController extends Controller
{
    public function index()
    {
        $mediaMessages = ChatMessage::with(['chatSession.customer', 'chatSession.whatsappAccount'])
            ->whereNotNull('attachment_path')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('Settings/WhatsAppMedia', [
            'mediaMessages' => $mediaMessages
        ]);
    }

    public function destroy(ChatMessage $chatMessage)
    {
        if ($chatMessage->attachment_path) {
            Storage::disk('public')->delete($chatMessage->attachment_path);
            $chatMessage->update(['attachment_path' => null]);
        }

        return redirect()->back()->with('message', 'Media file deleted successfully.');
    }
}
