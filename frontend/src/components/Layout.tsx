import { Link, Outlet } from "react-router-dom";
import { useAuth } from "@/contexts/AuthContext";

export default function Layout() {
    const { user, loading, logout } = useAuth();

    if (loading) return <div>Loading...</div>;

    return (
        <div>
            <header className="p-4 bg-gray-100 flex justify-between items-center">
                <div className="font-bold">Ticketing</div>
                <nav className="space-x-4">
                    {user ? (
                        <>
                            <Link to="/" className="hover:underline">
                                Home
                            </Link>
                            <button
                                onClick={logout}
                                className="hover:underline text-sm"
                            >
                                Logout
                            </button>
                        </>
                    ) : (
                        <>
                            <Link to="/login" className="hover:underline">
                                Login
                            </Link>
                            <Link to="/register" className="hover:underline">
                                Register
                            </Link>
                        </>
                    )}
                </nav>
            </header>
            <main className="p-4">
                <Outlet />
            </main>
        </div>
    );
}
