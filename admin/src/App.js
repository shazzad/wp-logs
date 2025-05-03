import React from "react";
import {
  HashRouter as Router,
  Route,
  Routes,
  Navigate,
} from "react-router-dom";
import { ToastContainer } from "react-toastify";
import Logs from "./pages/Logs";

const App = () => {
  return (
    <Router>
      <div className="hr__admin">
        <Routes>
          <Route path="/" element={<Navigate to="/logs" />} />
          <Route path="/logs" element={<Logs />} />
          <Route path="/requests" element={<Logs />} />
        </Routes>
        <ToastContainer />
      </div>
    </Router>
  );
};

export default App;
