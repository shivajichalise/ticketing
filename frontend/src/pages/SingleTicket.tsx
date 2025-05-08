import { useEffect, useState } from "react";
import { useParams } from "react-router-dom";
import { Card, CardContent } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import { useAuth } from "@/contexts/AuthContext";

type Category = {
    id: number;
    name: string;
};

type Ticket = {
    id: number;
    name: string;
    price: string;
    limit: number;
    category?: Category;
};

const API_URL = import.meta.env.VITE_API_URL;

export default function SingleTicket() {
    const { id } = useParams();
    const { accessToken } = useAuth();
    const [ticket, setTicket] = useState<Ticket | null>(null);
    const [breadcrumb, setBreadcrumb] = useState<string | null>(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const fetchTicket = async () => {
        setLoading(true);
        setError(null);

        try {
            const res = await fetch(
                `${API_URL}/tickets/${id}?include=category`,
                {
                    headers: {
                        Authorization: `Bearer ${accessToken}`,
                        Accept: "application/json",
                    },
                }
            );

            const json = await res.json();
            setTicket(json.data);

            if (json.data?.category?.id) {
                fetchBreadcrumb(json.data.category.id);
            }
        } catch {
            setError("Failed to load ticket.");
        } finally {
            setLoading(false);
        }
    };

    const fetchBreadcrumb = async (categoryId: number) => {
        try {
            const res = await fetch(
                `${API_URL}/categories/${categoryId}/breadcrumb`,
                {
                    headers: {
                        Authorization: `Bearer ${accessToken}`,
                        Accept: "application/json",
                    },
                }
            );

            const json = await res.json();
            console.log("bc", json);
            setBreadcrumb(json.data?.breadcrumb || null);
        } catch {
            setBreadcrumb(null);
        }
    };

    function formatBreadcrumb(breadcrumb: string): string {
        const parts = breadcrumb.split(" > ").filter(Boolean);
        const total = parts.length;

        if (total <= 7) return parts.join(" > ");
        return [...parts.slice(0, 3), "...", ...parts.slice(-3)].join(" > ");
    }

    useEffect(() => {
        if (id) fetchTicket();
    }, [id]);

    if (loading) {
        return (
            <div className="p-6">
                <Skeleton className="h-10 w-40 mb-4" />
                <Skeleton className="h-24 w-full rounded-xl" />
            </div>
        );
    }

    if (error || !ticket) {
        return (
            <div className="p-6 text-red-500">
                {error || "Ticket not found."}
            </div>
        );
    }

    return (
        <div className="p-6 space-y-4">
            <div className="flex justify-between items-start">
                <h2 className="text-lg font-semibold">Categories</h2>
                {breadcrumb ? (
                    <p className="text-sm text-muted-foreground text-right max-w-4xl truncate">
                        {formatBreadcrumb(breadcrumb)}
                    </p>
                ) : (
                    <p className="text-sm text-muted-foreground text-right">
                        Loading breadcrumb...
                    </p>
                )}
            </div>

            <Card>
                <CardContent className="p-6 space-y-2">
                    <h1 className="text-2xl font-bold">{ticket.name}</h1>
                    <p className="text-sm text-muted-foreground">
                        Price: ${ticket.price}
                    </p>
                    <p className="text-sm">Description will go here</p>
                </CardContent>
            </Card>
        </div>
    );
}
