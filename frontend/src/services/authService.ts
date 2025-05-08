const API_URL = import.meta.env.VITE_API_URL;

const jsonHeaders = {
    "Content-Type": "application/json",
    Accept: "application/json",
};

export async function login(email: string, password: string) {
    const res = await fetch(`${API_URL}/login`, {
        method: "POST",
        headers: jsonHeaders,
        body: JSON.stringify({ email, password }),
        credentials: "include",
    });

    if (!res.ok) throw new Error("Login failed");
    const { data } = await res.json();
    return data;
}

export async function register(
    name: string,
    email: string,
    password: string,
    passwordConfirmation: string,
    phone: string
) {
    const res = await fetch(`${API_URL}/register`, {
        method: "POST",
        headers: jsonHeaders,
        body: JSON.stringify({
            name,
            email,
            password,
            password_confirmation: passwordConfirmation,
            phone,
        }),
        credentials: "include",
    });

    if (!res.ok) throw new Error("Registration failed");
}

export async function logout(accessToken: string) {
    await fetch(`${API_URL}/logout`, {
        method: "POST",
        headers: {
            Authorization: `Bearer ${accessToken}`,
            Accept: "application/json",
        },
        credentials: "include",
    });
}

export async function refresh() {
    const res = await fetch(`${API_URL}/refresh`, {
        method: "POST",
        headers: {
            Accept: "application/json",
        },
        credentials: "include",
    });

    if (!res.ok) throw new Error("Token refresh failed");
    const { data } = await res.json();
    return data;
}

export async function me(accessToken: string) {
    const res = await fetch(`${API_URL}/me`, {
        headers: {
            Authorization: `Bearer ${accessToken}`,
            Accept: "application/json",
        },
    });

    if (!res.ok) throw new Error("Failed to fetch user");
    const { data } = await res.json();
    return data;
}
