<?php

namespace App\Http\Controllers;

use App\Events\ChatMessageSent;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function send(Request $request)
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:500'],
        ]);

        broadcast(new ChatMessageSent(
            $data['message']
        ));

        return response()->json([
            'success' => true,
        ]);
    }
}
