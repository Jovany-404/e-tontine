// Routeur principal de l'application

import {
  BrowserRouter,
  Navigate,
  Route,
  Routes,
  useLocation,
} from "react-router-dom";
import { useAuth } from "../context/AuthContext";

import Layout from "../components/layout/Layout";
import JoinPage from "../pages/JoinPage";
import Login from "../pages/login.tsx";
import Register from "../pages/Register";
import Dashboard from "../pages/Dashboard";
import Tontines from "../pages/Tontines";
import TontineCreate from "../pages/TontineCreate";
import TontineOverview from "../pages/TontineOverview";
import TontineDetail from "../pages/TontineDetail";
import TontineEdit from "../pages/TontineEdit";
import Members from "../pages/Members";
import MemberCreate from "../pages/MemberCreate";
import Contributions from "../pages/Contributions";
import Draws from "../pages/Draws";
import Profile from "../pages/Profile";
import Security from "../pages/Security";

// Route protégée
function ProtectedRoute({ children }: { children: React.ReactElement }) {
  const { isAuthenticated } = useAuth();
  const location = useLocation();

  return isAuthenticated
    ? children
    : <Navigate to="/login" replace state={{ from: location }} />;
}

export default function AppRoutes() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/join/:token" element={<JoinPage />} />
        <Route path="/login" element={<Login />} />
        <Route path="/register" element={<Register />} />

        <Route
          path="/"
          element={
            <ProtectedRoute>
              <Layout />
            </ProtectedRoute>
          }
        >
          <Route index element={<Dashboard />} />
          <Route path="tontines" element={<Tontines />} />
          <Route path="tontines/create" element={<TontineCreate />} />
          <Route path="tontines/:id" element={<TontineOverview />} />
          <Route path="tontines/:id/detail" element={<TontineDetail />} />
          <Route path="tontines/:id/edit" element={<TontineEdit />} />
          <Route
            path="tontines/:tontineId/contributions"
            element={<Contributions />}
          />
          <Route path="tontines/:tontineId/draws" element={<Draws />} />
          <Route path="members" element={<Members />} />
          <Route path="members/create" element={<MemberCreate />} />
          <Route path="profile" element={<Profile />} />
          <Route path="security" element={<Security />} />
        </Route>

        <Route path="*" element={<Navigate to="/" replace />} />
      </Routes>
    </BrowserRouter>
  );
}
