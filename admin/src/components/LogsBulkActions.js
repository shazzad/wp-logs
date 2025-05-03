// src/components/LogsBulkActions.js
import React from "react";

const LogsBulkActions = ({
  selectedCount,
  isLoading,
  isDeleting,
  hasLogs,
  onDeleteSelected,
  onDeleteAll,
}) => {
  return (
    <div className="swpl-bulk-actions">
      <div className="swpl-bulk-actions-info">
        {selectedCount > 0 && <span>{selectedCount} log(s) selected</span>}
      </div>
      <div className="swpl-bulk-actions-buttons">
        <button
          className="button"
          onClick={onDeleteSelected}
          disabled={isLoading || isDeleting || selectedCount === 0}
        >
          Delete Selected
        </button>
        <button
          className="button button-link-delete"
          onClick={onDeleteAll}
          disabled={isLoading || isDeleting || !hasLogs}
        >
          Delete All Logs
        </button>
      </div>
    </div>
  );
};

export default LogsBulkActions;
