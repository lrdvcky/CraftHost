<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SupportTicket;
use App\Models\TicketMessage;
use App\Models\Notification;
use App\Models\User;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(
            $request->user()->supportTickets()->orderBy('updated_at', 'desc')->get()
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        $ticket = SupportTicket::create([
            'user_id' => $request->user()->id,
            'subject' => $request->subject,
        ]);

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id'   => $request->user()->id,
            'body'      => $request->message,
        ]);

        // Уведомление всем админам о новом тикете
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'title'   => 'Новый тикет #' . $ticket->id,
                'message' => $request->user()->email . ': ' . $request->subject,
                'type'    => 'info',
            ]);
        }

        return response()->json($ticket);
    }

    // Polling-чат (Vue опрашивает каждые 5 сек)
    public function messages(Request $request, $id)
    {
        $ticket = $request->user()->supportTickets()->findOrFail($id);
        return response()->json($ticket->messages()->with('user:id,email,role')->get());
    }

    public function reply(Request $request, $id)
    {
        $request->validate(['message' => 'required|string']);
        $ticket = $request->user()->supportTickets()->findOrFail($id);

        if ($ticket->status === 'closed') {
            return response()->json(['error' => 'Тикет закрыт'], 422);
        }

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id'   => $request->user()->id,
            'body'      => $request->message,
        ]);

        // Ответ клиента -> тикет снова требует внимания админа.
        $ticket->update(['status' => 'open']);
        $ticket->touch();

        // Уведомление админам
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'title'   => 'Ответ в тикете #' . $ticket->id,
                'message' => $request->user()->email . ' написал новое сообщение.',
                'type'    => 'info',
            ]);
        }

        return response()->json(['message' => 'Ответ добавлен']);
    }

    /**
     * PATCH /api/tickets/{id}/close
     * Метод был объявлен в routes/api.php, но не реализован — добавлен здесь.
     */
    public function close(Request $request, $id)
    {
        $ticket = $request->user()->supportTickets()->findOrFail($id);
        $ticket->update(['status' => 'closed']);
        return response()->json(['message' => 'Тикет закрыт']);
    }
}
