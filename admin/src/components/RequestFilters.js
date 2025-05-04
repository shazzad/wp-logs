// src/components/RequestFilters.js
import React, { useState, useEffect } from "react";
import { SearchControl, SelectControl } from "@wordpress/components";

const RequestFilters = ({
  searchTerm,
  methodFilter,
  hostnameFilter,
  methodOptions,
  hostnameOptions,
  isLoading,
  onSearchChange,
  onMethodChange,
  onSourceChange,
  onApplyFilters,
  onResetFilters,
}) => {
  // Add state to track the input value locally before sending it to parent
  const [localSearchTerm, setLocalSearchTerm] = useState(searchTerm);

  // Update local state when parent state changes (important for reset functionality)
  useEffect(() => {
    setLocalSearchTerm(searchTerm);
  }, [searchTerm]);

  // Set up debounce effect
  useEffect(() => {
    // Create a timer that will update the actual search after 2 seconds
    const timer = setTimeout(() => {
      // Only trigger the parent's onSearchChange if the value actually changed
      if (localSearchTerm !== searchTerm) {
        onSearchChange(localSearchTerm);
      }
    }, 2000); // 2 second delay

    // Clear the timeout if component unmounts or localSearchTerm changes before timeout completes
    return () => clearTimeout(timer);
  }, [localSearchTerm, onSearchChange, searchTerm]);

  // Handle the local change immediately, but delay passing it to parent
  const handleSearchInputChange = (value) => {
    setLocalSearchTerm(value);
  };

  return (
    <div className="swpl__filter__controls">
      <SearchControl
        value={localSearchTerm}
        onChange={handleSearchInputChange} // Use the local handler
        label="Search logs"
        placeholder="Search logs..."
        className="swpl__filter--search"
        __nextHasNoMarginBottom
      />
      <SelectControl
        // label="Log Level"
        value={methodFilter}
        options={methodOptions}
        onChange={onMethodChange}
        className="swpl__filter--select"
        __nextHasNoMarginBottom
        __next40pxDefaultSize
      />
      <SelectControl
        // label="Log Source"
        value={hostnameFilter}
        options={hostnameOptions}
        onChange={onSourceChange}
        className="swpl__filter--select"
        __nextHasNoMarginBottom
        __next40pxDefaultSize
      />
      <button className="button" onClick={onApplyFilters} disabled={isLoading}>
        Apply Filters
      </button>
      <button className="button" onClick={onResetFilters} disabled={isLoading}>
        Reset Filters
      </button>
    </div>
  );
};

export default RequestFilters;
