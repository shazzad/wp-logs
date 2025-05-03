// src/components/LogFilters.js
import React from "react";
import { SearchControl, SelectControl } from "@wordpress/components";

const LogFilters = ({
  searchTerm,
  levelFilter,
  sourceFilter,
  levelOptions,
  sourceOptions,
  isLoading,
  onSearchChange,
  onLevelChange,
  onSourceChange,
  onApplyFilters,
  onResetFilters,
}) => {
  return (
    <div className="swpl-admin-filters">
      <div className="swpl-search-wrapper"></div>

      <div className="swpl-filter-controls">
        <SearchControl
          value={searchTerm}
          onChange={onSearchChange}
          label="Search logs"
          placeholder="Search logs..."
          className="swpl-search-control"
          __nextHasNoMarginBottom
        />

        <SelectControl
          // label="Log Level"
          value={levelFilter}
          options={levelOptions}
          onChange={onLevelChange}
          className="swpl-filter-select"
          __nextHasNoMarginBottom
          __next40pxDefaultSize
        />

        <SelectControl
          // label="Log Source"
          value={sourceFilter}
          options={sourceOptions}
          onChange={onSourceChange}
          className="swpl-filter-select"
          __nextHasNoMarginBottom
          __next40pxDefaultSize
        />

        <div className="swpl-filter-buttons">
          <button
            className="button"
            onClick={onApplyFilters}
            disabled={isLoading}
          >
            Apply Filters
          </button>

          <button
            className="button"
            onClick={onResetFilters}
            disabled={isLoading}
          >
            Reset Filters
          </button>
        </div>
      </div>
    </div>
  );
};

export default LogFilters;
