import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { useAuth } from "@/contexts/AuthContext";

export default function Register() {
    const { register } = useAuth();

    const [name, setName] = useState("");
    const [email, setEmail] = useState("");
    const [password, setPassword] = useState("");
    const [passwordConfirmation, setPasswordConfirmation] = useState("");
    const [phone, setPhone] = useState("");

    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setError(null);
        setLoading(true);
        try {
            await register(name, email, password, passwordConfirmation, phone);
        } catch (err) {
            setError((err as Error).message || "Registration failed");
        } finally {
            setLoading(false);
        }
    };

    return (
        <form
            onSubmit={handleSubmit}
            className="p-4 max-w-md mx-auto space-y-4"
        >
            <h2 className="text-xl font-semibold">Register</h2>

            {error && <p className="text-sm text-red-600">{error}</p>}

            <Input
                placeholder="Full Name"
                type="text"
                value={name}
                onChange={(e) => setName(e.target.value)}
            />
            <Input
                placeholder="Email"
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
            />
            <Input
                placeholder="Phone"
                type="tel"
                value={phone}
                onChange={(e) => setPhone(e.target.value)}
            />
            <Input
                placeholder="Password"
                type="password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
            />
            <Input
                placeholder="Password"
                type="password"
                value={passwordConfirmation}
                onChange={(e) => setPasswordConfirmation(e.target.value)}
            />
            <Button type="submit" className="w-full" disabled={loading}>
                {loading ? "Registering..." : "Register"}
            </Button>
        </form>
    );
}
