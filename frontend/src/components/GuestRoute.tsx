import { useAuth } from "@/contexts/AuthContext";
import { Navigate, Outlet } from "react-router-dom";

export default function GuestRoute() {
    const { user, loading } = useAuth();

    if (loading) return <div>Loading...</div>;

    return user ? <Navigate to="/" replace /> : <Outlet />;
}
