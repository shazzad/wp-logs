import App from "./App";
import { render, createRoot } from "@wordpress/element";

import "react-toastify/dist/ReactToastify.css";
import "./styles/main.scss";

// Render the App component into the DOM
const root = createRoot(document.getElementById("homerunner-react-app"));
root.render(<App />);
