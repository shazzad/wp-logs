// src/Logs.js
import React, { useState, useEffect } from "react";
import apiFetch from "@wordpress/api-fetch";
import { SearchControl, Spinner, SelectControl } from "@wordpress/components";
import SimplePagination from "../components/SimplePagination";
import LogDetailsModal from "../components/LogDetailsModal";
import "../styles/logs.scss";

const Logs = () => {
  const [logs, setLogs] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);
  const [searchTerm, setSearchTerm] = useState("");
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [totalItems, setTotalItems] = useState(0);
  const [selectedLogId, setSelectedLogId] = useState(null);
  const [levelFilter, setLevelFilter] = useState("");
  const [sourceFilter, setSourceFilter] = useState("");
  const perPage = 10;

  // Get available levels and sources from the global settings
  const availableLevels = window.swplAdminAppSettings?.levels || {};
  const availableSources = window.swplAdminAppSettings?.logSources || {};

  // Prepare level options for the select dropdown
  const levelOptions = [
    { label: "All Levels", value: "" },
    // Convert object to array of options if needed
    ...(Array.isArray(availableLevels)
      ? availableLevels.map((level) => ({
          label: level.charAt(0).toUpperCase() + level.slice(1),
          value: level,
        }))
      : Object.keys(availableLevels).map((level) => ({
          label: level.charAt(0).toUpperCase() + level.slice(1),
          value: level,
        }))),
  ];

  // Prepare source options for the select dropdown
  const sourceOptions = [
    { label: "All Sources", value: "" },
    // Convert object to array of options if needed
    ...(Array.isArray(availableSources)
      ? availableSources.map((source) => ({
          label: source,
          value: source,
        }))
      : Object.keys(availableSources).map((source) => ({
          label: source,
          value: source,
        }))),
  ];

  useEffect(() => {
    // Set up API authentication with the provided credentials
    if (swplAdminAppSettings) {
      apiFetch.use(apiFetch.createNonceMiddleware(swplAdminAppSettings.nonce));
      apiFetch.use(apiFetch.createRootURLMiddleware(swplAdminAppSettings.root));
    }

    // Fetch logs when component mounts, page changes, or filters change
    fetchLogs();
  }, [currentPage, searchTerm, levelFilter, sourceFilter]);

  const fetchLogs = async () => {
    try {
      setIsLoading(true);

      // Build query parameters for pagination, search, and filters
      let queryParams = `?page=${currentPage}&per_page=${perPage}`;

      if (searchTerm.trim()) {
        queryParams += `&search=${encodeURIComponent(searchTerm)}`;
      }

      if (levelFilter) {
        queryParams += `&level=${encodeURIComponent(levelFilter)}`;
      }

      if (sourceFilter) {
        queryParams += `&source=${encodeURIComponent(sourceFilter)}`;
      }

      const response = await apiFetch({
        path: `/wp/v2/logs${queryParams}`,
        // Parse headers to get pagination information
        parse: false,
      });

      // Get response data
      const data = await response.json();

      // Get total pages and total items from headers
      const totalItems = parseInt(response.headers.get("X-WP-Total"), 10) || 0;
      const totalPages =
        parseInt(response.headers.get("X-WP-TotalPages"), 10) || 1;

      // Check if the response has data in the expected format
      if (data && data.data && Array.isArray(data.data)) {
        setLogs(data.data);
        setTotalItems(totalItems);
        setTotalPages(totalPages);
      } else {
        setLogs([]);
        setError("Unexpected response format from API");
      }
    } catch (err) {
      console.error("Error fetching logs:", err);
      setError("Failed to fetch logs. Please try again later.");
    } finally {
      setIsLoading(false);
    }
  };

  const handleSearch = (value) => {
    setSearchTerm(value);
    setCurrentPage(1); // Reset to first page when search changes
  };

  const handleLevelChange = (value) => {
    setLevelFilter(value);
    setCurrentPage(1); // Reset to first page when filter changes
  };

  const handleSourceChange = (value) => {
    setSourceFilter(value);
    setCurrentPage(1); // Reset to first page when filter changes
  };

  const handlePageChange = (page) => {
    if (page >= 1 && page <= totalPages) {
      setCurrentPage(page);
    }
  };

  const resetFilters = () => {
    setSearchTerm("");
    setLevelFilter("");
    setSourceFilter("");
    setCurrentPage(1);
  };

  const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleString();
  };

  const openLogDetails = (logId) => {
    setSelectedLogId(logId);
  };

  const closeLogDetails = () => {
    setSelectedLogId(null);
  };

  return (
    <div className="swpl-admin-page">
      <h1 className="wpl-admin-page-heading">Logs</h1>

      <div className="swpl-admin-filters">
        <div className="swpl-search-wrapper">
          <SearchControl
            value={searchTerm}
            onChange={handleSearch}
            label="Search logs"
            placeholder="Search logs..."
            className="swpl-search-control"
          />
        </div>

        <div className="swpl-filter-controls">
          <SelectControl
            label="Log Level"
            value={levelFilter}
            options={levelOptions}
            onChange={handleLevelChange}
            className="swpl-filter-select"
          />

          <SelectControl
            label="Log Source"
            value={sourceFilter}
            options={sourceOptions}
            onChange={handleSourceChange}
            className="swpl-filter-select"
          />

          <div className="swpl-filter-buttons">
            <button className="button" onClick={fetchLogs} disabled={isLoading}>
              Apply Filters
            </button>

            <button
              className="button"
              onClick={resetFilters}
              disabled={isLoading}
            >
              Reset Filters
            </button>
          </div>
        </div>
      </div>

      {error && (
        <div className="notice notice-error">
          <p>{error}</p>
        </div>
      )}

      {isLoading ? (
        <div className="swpl-loading">
          <Spinner />
          <p>Loading logs...</p>
        </div>
      ) : (
        <>
          <table className="wp-list-table widefat striped">
            <thead>
              <tr>
                <th>ID</th>
                <th>Date</th>
                <th>Level</th>
                <th>Source</th>
                <th>Message</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              {logs.length === 0 ? (
                <tr>
                  <td colSpan="6">No logs found.</td>
                </tr>
              ) : (
                logs.map((log) => (
                  <tr key={log.id}>
                    <td>{log.id}</td>
                    <td>{formatDate(log.date)}</td>
                    <td>
                      <span className={`log-level log-level-${log.level}`}>
                        {log.level}
                      </span>
                    </td>
                    <td>{log.source}</td>
                    <td>{log.message}</td>
                    <td>
                      <button
                        className="button button-small"
                        onClick={() => openLogDetails(log.id)}
                      >
                        View Details
                      </button>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>

          {totalPages > 1 && (
            <div className="swpl-pagination">
              <div className="swpl-pagination-info">
                Showing {logs.length} of {totalItems} logs
              </div>
              <SimplePagination
                currentPage={currentPage}
                totalPages={totalPages}
                onPageChange={handlePageChange}
              />
            </div>
          )}
        </>
      )}

      {selectedLogId && (
        <LogDetailsModal logId={selectedLogId} onClose={closeLogDetails} />
      )}
    </div>
  );
};

export default Logs;
