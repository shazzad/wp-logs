import React from "react";
import {
  HashRouter as Router,
  Route,
  Routes,
  Navigate,
} from "react-router-dom";

import { ToastContainer } from "react-toastify";
import TabNavigation from "./components/TabNavigation";

import Logs from "./pages/Logs";
import Requests from "./pages/Requests";
import PluginSettings from "./pages/PluginSettings";

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
            <Route path="/settings" element={<PluginSettings />} />
          </Routes>
        </div>
        <ToastContainer />
      </div>
    </Router>
  );
};

export default App;
