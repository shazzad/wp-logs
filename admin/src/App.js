import React from "react";
import {
  HashRouter as Router,
  Route,
  Routes,
  Navigate,
  NavLink,
  useLocation,
} from "react-router-dom";
import { ToastContainer } from "react-toastify";

import Logs from "./pages/Logs";
import Requests from "./pages/Requests";
import TabNavigation from "./components/TabNavigation";

const App = () => {
  // Define the navigation items
  const navItems = [
    { path: "/logs", label: "Logs", icon: "dashicons-list-view" },
    { path: "/requests", label: "Requests", icon: "dashicons-admin-network" },
    // Add more navigation items as needed
    { path: "/settings", label: "Settings", icon: "dashicons-admin-settings" },
  ];

  return (
    <Router>
      <div className="swpl-app">
        <h1>WP Logs</h1>
        <TabNavigation items={navItems} />

        <div className="swpl-content-wrapper">
          <Routes>
            <Route path="/" element={<Navigate to="/logs" />} />
            <Route path="/logs" element={<Logs />} />
            <Route path="/requests" element={<Requests />} />{" "}
            {/* Replace with actual Requests component when available */}
            <Route
              path="/settings"
              element={
                <div className="swpl-page-content">
                  <h2>Settings</h2>
                  <p>Settings page content will go here.</p>
                </div>
              }
            />
          </Routes>
        </div>
        <ToastContainer />
      </div>
    </Router>
  );
};

export default App;
