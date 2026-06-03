<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Notification;
use App\Models\SupportTicket;
use App\Models\TicketMessage;
use Illuminate\Http\Request;

/**
 * Управление тикетами со стороны администратора.
 *
 * В отличие от App\Http\Controllers\Api\TicketController (который работает
 * только с тикетами текущего пользователя через $request->user()->supportTickets()),
 * здесь админ видит и отвечает на ВСЕ тикеты.
 */
class AdminTicketController extends Controller
{
    /** GET /api/admin/tickets?status=open */
    private function hasAssignedColumn(): bool
    {
        static $has = null;
        if ($has === null) {
            $has = \Illuminate\Support\Facades\Schema::hasColumn('support_tickets', 'assigned_admin_id');
        }
        return $has;
    }

    public function index(Request $request)
    {
        $q = SupportTicket::query()
            ->with(array_filter([
                'user:id,email',
                $this->hasAssignedColumn() ? 'assignedAdmin:id,email' : null,
            ]))
            ->withCount('messages');

        if ($status = $request->query('status')) {
            $q->where('status', $status);
        }

        return response()->json($q->orderByDesc('updated_at')->paginate(30));
    }

    /** GET /api/admin/tickets/{id} — тикет с сообщениями */
    public function show($id)
    {
        $withs = ['user:id,email,role'];
        if ($this->hasAssignedColumn()) {
            $withs[] = 'assignedAdmin:id,email';
        }
        $ticket = SupportTicket::with($withs)->findOrFail($id);
        $messages = $ticket->messages()
            ->with('user:id,email,role')
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'ticket'   => $ticket,
            'messages' => $messages,
        ]);
    }

    /** POST /api/admin/tickets/{id}/reply */
    public function reply(Request $request, $id)
    {
        $request->validate(['message' => 'required|string']);

        $ticket = SupportTicket::findOrFail($id);

        $msg = TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id'   => $request->user()->id, // админ
            'body'      => $request->message,
        ]);

        // Ответ админа -> статус "answered".
        $ticket->update(['status' => 'answered']);
        $ticket->touch();

        // Уведомляем владельца тикета.
        Notification::create([
            'user_id'    => $ticket->user_id,
            'type'       => 'ticket_reply',
            'data'       => [
                'ticket_id' => $ticket->id,
                'subject'   => $ticket->subject,
            ],
            'created_at' => now(),
        ]);

        AuditLog::record('ticket.replied', 'ticket', $ticket->id);

        return response()->json([
            'message' => 'Ответ отправлен',
            'data'    => $msg->load('user:id,email,role'),
        ], 201);
    }

    /**
     * POST /api/admin/tickets/{id}/assign — админ принимает тикет в работу.
     * Без тела — назначает текущего админа. С {release:true} — снимает назначение.
     */
    public function assign(Request $request, $id)
    {
        if (!$this->hasAssignedColumn()) {
            return response()->json([
                'error' => 'Колонка assigned_admin_id не найдена. Выполните SQL: ALTER TABLE support_tickets ADD COLUMN assigned_admin_id BIGINT UNSIGNED DEFAULT NULL AFTER user_id;',
            ], 500);
        }

        $ticket = SupportTicket::findOrFail($id);

        if ($request->boolean('release')) {
            $ticket->update(['assigned_admin_id' => null]);
            AuditLog::record('ticket.released', 'ticket', $ticket->id);
            return response()->json(['message' => 'Тикет снят с работы']);
        }

        $ticket->update([
            'assigned_admin_id' => $request->user()->id,
            'status'            => $ticket->status === 'closed' ? 'open' : $ticket->status,
        ]);
        AuditLog::record('ticket.assigned', 'ticket', $ticket->id, ['admin_id' => $request->user()->id]);

        return response()->json([
            'message'        => 'Тикет принят в работу',
            'assigned_admin' => $ticket->assignedAdmin()->first(['id', 'email']),
        ]);
    }

    /** PATCH /api/admin/tickets/{id}/status — сменить статус */
    public function setStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:open,answered,closed']);

        $ticket = SupportTicket::findOrFail($id);
        $ticket->update(['status' => $request->status]);

        AuditLog::record('ticket.status', 'ticket', $ticket->id, ['status' => $request->status]);

        return response()->json(['message' => 'Статус обновлён']);
    }
}
