import React from "react";
import { NavLink, useLocation } from "react-router-dom";

const TabNavigation = ({ items }) => {
  const location = useLocation();

  return (
    <div className="swpl__tab--navigation">
      <h2 className="swpl__tab--wrapper">
        {items.map((item) => (
          <NavLink
            key={item.path}
            to={item.path}
            className={({ isActive }) =>
              isActive
                ? "swpl__tab--nav swpl__tab--nav-active"
                : "swpl__tab--nav"
            }
          >
            <span className={`dashicons ${item.icon}`}></span>
            <span className="swpl-tab-label">{item.label}</span>
          </NavLink>
        ))}
      </h2>
    </div>
  );
};

export default TabNavigation;
