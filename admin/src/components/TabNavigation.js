import React from "react";
import { NavLink, useLocation } from "react-router-dom";

const TabNavigation = ({ items }) => {
  const location = useLocation();

  return (
    <div className="swpl-tab-navigation">
      <h2 className="nav-tab-wrapper">
        {items.map((item) => (
          <NavLink
            key={item.path}
            to={item.path}
            className={({ isActive }) =>
              isActive ? "nav-tab nav-tab-active" : "nav-tab"
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
