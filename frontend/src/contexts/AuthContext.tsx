import { createContext, useContext, useEffect, useMemo, useState } from "react";
import { useNavigate } from "react-router-dom";
import * as authService from "@/services/authService";

interface User {
    id: string;
    name: string;
    email: string;
    phone: string;
}

interface AuthContextType {
    user: User | null;
    accessToken: string | null;
    isAuthenticated: boolean;
    login: (email: string, password: string) => Promise<void>;
    register: (
        name: string,
        email: string,
        password: string,
        passwordConfirmation: string,
        phone: string
    ) => Promise<void>;
    logout: () => Promise<void>;
    loading: boolean;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export const useAuth = () => {
    const ctx = useContext(AuthContext);
    if (!ctx) throw new Error("useAuth must be used inside AuthProvider");
    return ctx;
};

interface AuthProviderProps {
    children: React.ReactNode;
}

export const AuthProvider = ({ children }: AuthProviderProps) => {
    const [user, setUser] = useState<User | null>(null);
    const [accessToken, setAccessToken] = useState<string | null>(() =>
        localStorage.getItem("accessToken")
    );
    const [loading, setLoading] = useState(true);
    const navigate = useNavigate();

    const storeToken = (token: string) => {
        localStorage.setItem("accessToken", token);
        setAccessToken(token);
    };

    const clearAuth = () => {
        localStorage.removeItem("accessToken");
        setAccessToken(null);
        setUser(null);
    };

    const fetchUser = async (token: string) => {
        try {
            const data = await authService.me(token);
            setUser(data);
        } catch (err) {
            clearAuth();
            throw err;
        }
    };

    const login = async (email: string, password: string) => {
        const { access_token, user } = await authService.login(email, password);
        storeToken(access_token);
        setUser(user);
        navigate("/");
    };

    const register = async (
        name: string,
        email: string,
        password: string,
        passwordConfirmation: string,
        phone: string
    ) => {
        await authService.register(
            name,
            email,
            password,
            passwordConfirmation,
            phone
        );
        navigate("/login");
    };

    const logout = async () => {
        if (accessToken) {
            try {
                await authService.logout(accessToken);
            } catch {}
        }

        clearAuth();
        navigate("/login");
    };

    const refreshToken = async () => {
        const { access_token } = await authService.refresh();
        storeToken(access_token);
        await fetchUser(access_token);
    };

    useEffect(() => {
        const init = async () => {
            try {
                const token = localStorage.getItem("accessToken");

                if (token) {
                    await fetchUser(token);
                } else {
                    await refreshToken();
                }
            } catch {
                clearAuth();
            } finally {
                setLoading(false);
            }
        };
        init();
    }, []);

    const value = useMemo(
        () => ({
            user,
            accessToken,
            isAuthenticated: !!user,
            login,
            register,
            logout,
            loading,
        }),
        [user, accessToken, loading]
    );

    return (
        <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
    );
};
