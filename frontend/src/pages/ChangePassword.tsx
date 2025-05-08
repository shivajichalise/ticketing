import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { useAuth } from "@/contexts/AuthContext";

const API_URL = import.meta.env.VITE_API_URL;

export default function ChangePassword() {
    const { accessToken, logout } = useAuth();

    const [oldPassword, setOldPassword] = useState("");
    const [newPassword, setNewPassword] = useState("");
    const [passwordConfirmation, setPasswordConfirmation] = useState("");

    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [success, setSuccess] = useState<string | null>(null);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setError(null);
        setSuccess(null);
        setLoading(true);

        try {
            const res = await fetch(`${API_URL}/change-password`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    Authorization: `Bearer ${accessToken}`,
                },
                body: JSON.stringify({
                    old_password: oldPassword,
                    password: newPassword,
                    password_confirmation: passwordConfirmation,
                }),
            });

            const json = await res.json();

            if (!res.ok) {
                throw new Error(json.message || "Password change failed");
            }

            setSuccess("Password changed successfully. Please log in again.");
            await logout();
        } catch (err) {
            setError((err as Error).message || "Failed to change password");
        } finally {
            setLoading(false);
        }
    };

    return (
        <form
            onSubmit={handleSubmit}
            className="p-4 max-w-md mx-auto space-y-4"
        >
            <h2 className="text-xl font-semibold">Change Password</h2>

            {error && <p className="text-sm text-red-600">{error}</p>}
            {success && <p className="text-sm text-green-600">{success}</p>}

            <Input
                placeholder="Current Password"
                type="password"
                value={oldPassword}
                onChange={(e) => setOldPassword(e.target.value)}
            />
            <Input
                placeholder="New Password"
                type="password"
                value={newPassword}
                onChange={(e) => setNewPassword(e.target.value)}
            />
            <Input
                placeholder="Confirm New Password"
                type="password"
                value={passwordConfirmation}
                onChange={(e) => setPasswordConfirmation(e.target.value)}
            />

            <Button type="submit" className="w-full" disabled={loading}>
                {loading ? "Changing..." : "Change Password"}
            </Button>
        </form>
    );
}
