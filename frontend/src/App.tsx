import { Routes, Route } from "react-router-dom";
import Layout from "./components/Layout";
import Home from "./pages/Home";
import Login from "./pages/Login";
import Register from "./pages/Register";
import PrivateRoute from "./components/PrivateRoute";
import GuestRoute from "./components/GuestRoute";
import Ticket from "./pages/Ticket";
import SingleTicket from "./pages/SingleTicket";

function App() {
    return (
        <Routes>
            <Route element={<Layout />}>
                <Route element={<GuestRoute />}>
                    <Route path="/login" element={<Login />} />
                    <Route path="/register" element={<Register />} />
                </Route>

                <Route element={<PrivateRoute />}>
                    <Route path="/" element={<Home />} />
                    <Route path="/tickets" element={<Ticket />} />
                    <Route path="/tickets/:id" element={<SingleTicket />} />
                </Route>
            </Route>
        </Routes>
    );
}

export default App;
