// src/components/RequestTable.js
import React from "react";
import { Spinner } from "@wordpress/components";

const RequestTable = ({
  requests,
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

  // Only show the full-page loading state on initial load when we have no requests yet
  if (isLoading && requests.length === 0) {
    return (
      <div className="swpl__loading">
        <Spinner />
        <p>Loading requests...</p>
      </div>
    );
  }

  return (
    <div className="swpl__table--container">
      {/* Loading overlay that only appears when refreshing data but keeping existing content */}
      {isLoadingNewData && (
        <div className="swpl__table--loading-overlay">
          <div className="swpl__loading-indicator">
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
                checked={
                  requests.length > 0 && selectedLogs.length === requests.length
                }
                disabled={requests.length === 0}
              />
            </td>
            <th>Url</th>
            <th>Method</th>
            <th>Status</th>
            <th className="sortable" onClick={() => onSort("date_created")}>
              Date {getSortIcon("date_created")}
            </th>
            <th className="sortable" onClick={() => onSort("response_size")}>
              Size {getSortIcon("response_size")}
            </th>
            <th className="sortable" onClick={() => onSort("id")}>
              ID {getSortIcon("id")}
            </th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          {requests.length === 0 ? (
            <tr>
              <td colSpan="8">No requests found.</td>
            </tr>
          ) : (
            requests.map((log) => (
              <tr key={log.id}>
                <th className="manage-column column-cb check-column">
                  <input
                    type="checkbox"
                    onChange={() => onToggleLogSelection(log.id)}
                    checked={selectedLogs.includes(log.id)}
                  />
                </th>
                <td>{log.request_url}</td>
                <td>
                  <span
                    className={`request__method request__method--${log.request_method.toLowerCase()}`}
                  >
                    {log.request_method}
                  </span>
                </td>
                <td>{log.response_code}</td>
                <td>{formatDate(log.date_created)}</td>
                <td>{log.response_size}</td>
                <td>{log.id}</td>
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
