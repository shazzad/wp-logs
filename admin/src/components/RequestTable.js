// src/components/RequestTable.js
import React from "react";
import { Spinner } from "@wordpress/components";

const RequestTable = ({
  logs,
  isLoading,
  isLoadingNewData,
  selectedLogs,
  sortField,
  sortOrder,
  onToggleSelectAll,
  onToggleLogSelection,
  onSort,
  onViewDetails,
}) => {
  const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleString();
  };

  const getSortIcon = (field) => {
    if (field !== sortField) {
      return <span className="sort-icon sort-none">⇵</span>;
    }
    return sortOrder === "asc" ? (
      <span className="sort-icon sort-asc">↑</span>
    ) : (
      <span className="sort-icon sort-desc">↓</span>
    );
  };

  // Only show the full-page loading state on initial load when we have no logs yet
  if (isLoading && logs.length === 0) {
    return (
      <div className="swpl-loading">
        <Spinner />
        <p>Loading logs...</p>
      </div>
    );
  }

  return (
    <div className="swpl-table-container">
      {/* Loading overlay that only appears when refreshing data but keeping existing content */}
      {isLoadingNewData && (
        <div className="swpl-table-loading-overlay">
          <div className="swpl-loading-indicator">
            <Spinner />
            <p>Updating...</p>
          </div>
        </div>
      )}

      <table className="wp-list-table widefat striped">
        <thead>
          <tr>
            <td className="manage-column column-cb check-column">
              <input
                type="checkbox"
                onChange={onToggleSelectAll}
                checked={logs.length > 0 && selectedLogs.length === logs.length}
                disabled={logs.length === 0}
              />
            </td>
            <th className="sortable" onClick={() => onSort("id")}>
              ID {getSortIcon("id")}
            </th>
            <th className="sortable" onClick={() => onSort("date")}>
              Date {getSortIcon("date")}
            </th>
            <th>Url</th>
            <th>Method</th>
            <th>Status</th>
            <th>Size</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          {logs.length === 0 ? (
            <tr>
              <td colSpan="7">No logs found.</td>
            </tr>
          ) : (
            logs.map((log) => (
              <tr key={log.id}>
                <td className="manage-column column-cb check-column">
                  <input
                    type="checkbox"
                    onChange={() => onToggleLogSelection(log.id)}
                    checked={selectedLogs.includes(log.id)}
                  />
                </td>
                <td>{log.id}</td>
                <td>{formatDate(log.date)}</td>
                <td>{log.request_url}</td>
                <td>{log.request_method}</td>
                <td>{log.response_code}</td>
                <td>{log.response_size}</td>
                <td>
                  <button
                    className="button button-small"
                    onClick={() => onViewDetails(log.id)}
                  >
                    View Details
                  </button>
                </td>
              </tr>
            ))
          )}
        </tbody>
      </table>
    </div>
  );
};

export default RequestTable;
