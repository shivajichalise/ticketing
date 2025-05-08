<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Models\TicketSale;
use App\Models\User;
use App\Traits\RespondsWithJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

final class TicketController extends Controller
{
    use RespondsWithJson;

    public function index(Request $request)
    {
        $length = $request->query('length', 10);

        $includes = explode(',', $request->query('include', ''));

        $ticketsQuery = Ticket::query();

        if (in_array('category', $includes)) {
            $ticketsQuery->with('category');
        }

        $ticketsQuery->withExists(['sales as paid' => function ($query) use ($request): void {
            $query->where('user_id', $request->jwt_user_id);
        }]);

        $tickets = $ticketsQuery->cursorPaginate($length);

        return TicketResource::collection($tickets)->additional([
            'message' => 'Tickets fetched successfully.',
            'status' => true,
        ]);
    }

    public function show(Request $request, int $ticketId)
    {
        $includes = explode(',', $request->query('include', ''));

        $query = Ticket::query()
            ->where('id', $ticketId)
            ->withExists(['sales as paid' => function ($query) use ($request) {
                $query->where('user_id', $request->jwt_user_id);
            }]);

        if (in_array('category', $includes)) {
            $query->with('category');
        }

        $ticket = $query->firstOrFail();

        return (new TicketResource($ticket))->additional([
            'message' => 'Ticket fetched successfully.',
            'status' => true,
        ]);
    }

    public function buy(Request $request, int $ticketId)
    {
        $user = User::find($request->jwt_user_id);

        try {
            $purchase = DB::transaction(function () use ($ticketId, $user) {
                $ticket = Ticket::lockForUpdate()->find($ticketId);

                if (! $ticket) {
                    throw new NotFoundHttpException('Ticket not found');
                }

                $purchaseCount = TicketSale::where('ticket_id', $ticketId)->count();

                if ($purchaseCount >= $ticket->limit) {
                    throw new ConflictHttpException('Ticket is sold out');
                }

                $salesExists = TicketSale::where('ticket_id', $ticketId)->where('user_id', $user->id)->exists();
                if ($salesExists) {
                    throw new ConflictHttpException('You have already purchased this ticket');
                }

                if (! $this->pay()) {
                    throw new HttpException(402, 'Payment failed');
                }

                return TicketSale::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => $user->id,
                    'amount' => $ticket->price,
                    'payment_status' => 'paid',
                    'sold_at' => now(),
                ]);
            });

            return $this->success(
                ['purchase' => $purchase],
                'Ticket bought successfully.',
                201
            );
        } catch (Throwable $th) {
            return $this->error($th, 'Purchase failed', $th->getCode() ?: 500);
        }
    }

    private function pay(): bool
    {
        // esewa or khalti integration
        return true;
    }
}
