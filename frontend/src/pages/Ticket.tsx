import { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";
import { useAuth } from "@/contexts/AuthContext";

type Ticket = {
    id: number;
    category_id: number;
    name: string;
    price: string;
    limit: number;
    paid: boolean;
    created_at: string;
    updated_at: string;
};

type TicketResponse = {
    data: Ticket[];
    meta: {
        next_cursor: string | null;
        prev_cursor: string | null;
    };
};

const API_URL = import.meta.env.VITE_API_URL;

export default function TicketPage() {
    const { accessToken } = useAuth();
    const navigate = useNavigate();

    const [tickets, setTickets] = useState<Ticket[]>([]);
    const [loading, setLoading] = useState(false);
    const [nextCursor, setNextCursor] = useState<string | null>(null);
    const [prevCursor, setPrevCursor] = useState<string | null>(null);
    const [error, setError] = useState<string | null>(null);

    const fetchTickets = async (cursor: string | null = null) => {
        setLoading(true);
        setError(null);

        try {
            const res = await fetch(
                `${API_URL}/tickets${cursor ? `?cursor=${cursor}` : ""}`,
                {
                    headers: {
                        Authorization: `Bearer ${accessToken}`,
                        Accept: "application/json",
                    },
                }
            );

            const json: TicketResponse = await res.json();

            setTickets(json.data);
            setNextCursor(json.meta.next_cursor);
            setPrevCursor(json.meta.prev_cursor);
        } catch (err: any) {
            setError("Failed to load tickets.");
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchTickets();
    }, [accessToken]);

    return (
        <div className="p-6">
            <h1 className="text-2xl font-bold mb-6">Tickets</h1>

            {error && <p className="text-red-500 mb-4">{error}</p>}

            {loading ? (
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    {Array.from({ length: 8 }).map((_, i) => (
                        <Skeleton key={i} className="h-32 rounded-xl" />
                    ))}
                </div>
            ) : (
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    {tickets.map((ticket) => (
                        <Card key={ticket.id}>
                            <CardContent className="p-4 space-y-2">
                                <h2 className="text-lg font-semibold">
                                    {ticket.name}
                                </h2>
                                <p className="text-sm text-muted-foreground">
                                    Price: ${ticket.price}
                                </p>
                                <p className="text-sm">Limit: {ticket.limit}</p>
                                <Button
                                    onClick={() =>
                                        navigate(`/tickets/${ticket.id}`)
                                    }
                                >
                                    View
                                </Button>
                                {ticket.paid && (
                                    <Button
                                        className="ml-2"
                                        disabled
                                        variant="outline"
                                    >
                                        Purchased
                                    </Button>
                                )}
                            </CardContent>
                        </Card>
                    ))}
                </div>
            )}

            <div className="flex justify-center gap-4 mt-8">
                <Button
                    onClick={() => fetchTickets(prevCursor)}
                    disabled={!prevCursor}
                    variant="outline"
                >
                    Previous
                </Button>
                <Button
                    onClick={() => fetchTickets(nextCursor)}
                    disabled={!nextCursor}
                >
                    Next
                </Button>
            </div>
        </div>
    );
}
